<?php
/**
 *  Copyright (C) 2017 SURFnet.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SURFnet\VPN\Web;

use fkooman\OAuth\Client\Http\HttpClientInterface;
use fkooman\OAuth\Client\Http\Request as HttpRequest;
use fkooman\OAuth\Client\OAuthClient;
use fkooman\OAuth\Client\Provider;
use RuntimeException;
use SURFnet\VPN\Web\Http\Request;
use SURFnet\VPN\Web\Http\Response;

class Service
{
    /** @var Config */
    private $config;

    /** @var TplInterface */
    private $tpl;

    /** @var \fkooman\OAuth\Client\Http\HttpClientInterface */
    private $httpClient;

    /** @var \fkooman\OAuth\Client\OAuthClient */
    private $oauthClient;

    /** @var string */
    private $dataDir;

    public function __construct(Config $config, TplInterface $tpl, OAuthClient $oauthClient, HttpClientInterface $httpClient, $dataDir)
    {
        $this->config = $config;
        $this->tpl = $tpl;
        $this->httpClient = $httpClient;
        $this->oauthClient = $oauthClient;
        $this->dataDir = $dataDir;
    }

    /**
     * @param Http\Request $request
     *
     * @return Http\Response
     */
    public function run(Request $request)
    {
        switch ($request->getMethod()) {
            case 'HEAD':
            case 'GET':
                switch ($request->getPathInfo()) {
                    case '/':
                        return $this->showDiscovery();
                    case '/callback':
                        // handle OAuth server callback
                        return $this->handleCallback($request);
                    default:
                        return new Response(404, [], '[404] Not Found');
                }
                break;
            case 'POST':
                switch ($request->getPathInfo()) {
                    case '/':
                        // fetch an OpenVPN client configuration
                        $providerId = $request->getPostParameter('provider_id');

                        return $this->getDownloadPage($request, $providerId);

                    case '/home':
                        unset($_SESSION['activeDiscoveryUrl']);

                        return new Response(302, ['Location' => $request->getRootUri()]);

                    case '/download':
                        $action = $request->getPostParameter('action');

                        if ('back' === $action) {
                            return new Response(
                                302,
                                [
                                    'Location' => $request->getRootUri(),
                                ]
                            );
                        }

                        $providerId = $request->getPostParameter('provider_id');
                        $profileId = $request->getPostParameter('profile_id');

                        return $this->getConfig($request, $providerId, $profileId);
                    case '/setDiscoveryUrl':
                        return $this->setDiscoveryUrl($request);
                    default:
                        return new Response(404, [], '[404] Not Found');
                }
                break;
            default:
                return new Response(405, ['Allow' => 'GET,HEAD'], '[405] Method Not Allowed');
        }
    }

    /**
     * @return Http\Response
     */
    private function showDiscovery()
    {
        $discoChooser = [];
        $discoveryUrlList = $this->config->get('Discovery')->keys();
        foreach ($discoveryUrlList as $discoveryUrl) {
            $discoChooser[] = ['discoveryUrl' => $discoveryUrl, 'displayName' => $this->config->get('Discovery')->get($discoveryUrl)->get('displayName')];
        }

        // check if we already chose a discoveryUrl, if not default to the
        // first one in the configuration
        if (!array_key_exists('activeDiscoveryUrl', $_SESSION)) {
            return new Response(
                200,
                [],
                $this->tpl->render(
                    'home',
                    [
                        'discoChooser' => $discoChooser,
                    ]
                )
            );
        }
        $activeDiscoveryUrl = $_SESSION['activeDiscoveryUrl'];

        $discoveryData = $this->getDiscoveryData($activeDiscoveryUrl);

        return new Response(
            200,
            [],
            $this->tpl->render(
                'discovery',
                [
                    'discoChooser' => $discoChooser,
                    'activeDiscoveryUrl' => $activeDiscoveryUrl,
                    'encodedDiscoveryUrl' => self::encodeStr($activeDiscoveryUrl),
                    'providerList' => $discoveryData,
                ]
            )
        );
    }

    private function getDiscoveryData($discoveryUrl)
    {
        $discoveryData = json_decode(file_get_contents(sprintf('%s/%s', $this->dataDir, self::encodeStr($discoveryUrl))), true);

        foreach ($discoveryData['instances'] as $k => $v) {
            $discoveryData['instances'][$k]['hostName'] = parse_url($v['base_uri'], PHP_URL_HOST);
        }

        return $discoveryData['instances'];
    }

    private function getAuthorizationType($discoveryUrl)
    {
        $discoveryData = json_decode(file_get_contents(sprintf('%s/%s', $this->dataDir, self::encodeStr($discoveryUrl))), true);

        return $discoveryData['authorization_type'];
    }

    private static function encodeStr($str)
    {
        return preg_replace('/[^A-Za-z.]/', '_', $str);
    }

    private function handleCallback(Request $request)
    {
        // this was our chosen "home" organization
        $tokenProviderId = $_SESSION['tokenProviderId'];

        // get OAuth information for chosen tokenProvider
        $tokenProviderInfo = $this->getProviderInfo($tokenProviderId);

        // load OAuth provider with this information
        $this->oauthClient->setProvider(
            new Provider(
                $this->config->get('OAuth')->get('clientId'),
                null,
                $tokenProviderInfo['authorization_endpoint'],
                $tokenProviderInfo['token_endpoint']
            )
        );

        $this->oauthClient->handleCallback(
            $request->getQueryParameter('code'),
            $request->getQueryParameter('state')
        );

        $activeDiscoveryUrl = $_SESSION['activeDiscoveryUrl'];
        if ('local' === $this->getAuthorizationType($activeDiscoveryUrl)) {
            // download config
            return $this->getDownloadPage($request, $tokenProviderId);
        }

        // redirect back
        return new Response(
            302,
            [
                'Location' => $request->getRootUri(),
            ]
        );
    }

    private function getProviderInfo($providerId)
    {
        $providerInfoUrl = sprintf('%s/info.json', $providerId);
        $providerInfoResponse = $this->httpClient->send(HttpRequest::get($providerInfoUrl));
        if (!$providerInfoResponse->isOkay()) {
            throw new RuntimeException(sprintf('unable to fetch "%s"', $providerInfoUrl));
        }

        // XXX check response format!
        return $providerInfoResponse->json()['api']['http://eduvpn.org/api#2'];
    }

    private function getTokenProviderId($providerId)
    {
        $activeDiscoveryUrl = $_SESSION['activeDiscoveryUrl'];
        switch ($this->getAuthorizationType($activeDiscoveryUrl)) {
            case 'local':
                $_SESSION['tokenProviderId'] = $providerId;
                break;
            case 'distributed':
                if (!array_key_exists('tokenProviderId', $_SESSION)) {
                    $_SESSION['tokenProviderId'] = $providerId;
                }
                break;
            default:
                throw new RuntimeException(sprintf('authorization_type "%s" not supported', $this->getAuthorizationType($activeDiscoveryUrl)));
        }

        return $_SESSION['tokenProviderId'];
    }

    private function setProvider(array $tokenProviderInfo)
    {
        // load OAuth provider with this information
        $this->oauthClient->setProvider(
            new Provider(
                $this->config->get('OAuth')->get('clientId'),
                null,
                $tokenProviderInfo['authorization_endpoint'],
                $tokenProviderInfo['token_endpoint']
            )
        );
    }

    /**
     * Get an OpenVPN client configuration for a provider.
     *
     * @param Http\Request $request
     * @param string       $providerId
     */
    private function getConfig(Request $request, $providerId, $profileId)
    {
        $tokenProviderId = $this->getTokenProviderId($providerId);

        // get OAuth information for chosen tokenProvider
        $tokenProviderInfo = $this->getProviderInfo($tokenProviderId);
        $this->setProvider($tokenProviderInfo);
        $providerInfo = $this->getProviderInfo($providerId);
        $apiBaseUri = $providerInfo['api_base_uri'];

        $response = $this->oauthClient->post(
            $this->config->get('OAuth')->get('requestScope'),
            sprintf('%s/create_config', $apiBaseUri),
            [
                'display_name' => 'VPN for Web',
                'profile_id' => $profileId,
            ]
        );
        if (false === $response) {
            // no valid OAuth token available...
            $authorizeUri = $this->oauthClient->getAuthorizeUri(
                $this->config->get('OAuth')->get('requestScope'),
                sprintf('%scallback', $request->getRootUri())
            );

            return new Response(302, ['Location' => $authorizeUri]);
        }

        if (false === $providerHostName = parse_url($providerId, PHP_URL_HOST)) {
            throw new RuntimeException('unable to extract hostname from providerId');
        }

        return new Response(
            200,
            [
                'Content-Type' => 'application/x-openvpn-profile',
                'Content-Disposition' => sprintf('attachment; filename="VPN for Web (%s).ovpn"', $providerHostName),
            ],
            $response->getBody()
        );
    }

    private function getDownloadPage(Request $request, $providerId)
    {
        $tokenProviderId = $this->getTokenProviderId($providerId);

        // get OAuth information for chosen tokenProvider
        $tokenProviderInfo = $this->getProviderInfo($tokenProviderId);
        $this->setProvider($tokenProviderInfo);
        if (!$this->oauthClient->hasAccessToken($this->config->get('OAuth')->get('requestScope'))) {
            // no oauth token
            $authorizeUri = $this->oauthClient->getAuthorizeUri(
                $this->config->get('OAuth')->get('requestScope'),
                sprintf('%scallback', $request->getRootUri())
            );

            return new Response(302, ['Location' => $authorizeUri]);
        }

        $discoveryData = $this->getDiscoveryData($_SESSION['activeDiscoveryUrl']);
        $displayName = null;
        foreach ($discoveryData as $provider) {
            if ($provider['base_uri'] === $providerId) {
                $displayName = $provider['display_name'];
            }
        }

        return new Response(
            200,
            [],
            $this->tpl->render(
                'download',
                [
                    'profileList' => $this->getProfileList($request, $providerId),
                    'providerId' => $providerId,
                    'displayName' => $displayName,
                ]
            )
        );
    }

    private function getProfileList(Request $request, $providerId)
    {
        $tokenProviderId = $this->getTokenProviderId($providerId);

        // get OAuth information for chosen tokenProvider
        $tokenProviderInfo = $this->getProviderInfo($tokenProviderId);
        $this->setProvider($tokenProviderInfo);
        $providerInfo = $this->getProviderInfo($providerId);
        $apiBaseUri = $providerInfo['api_base_uri'];

        $response = $this->oauthClient->get(
            $this->config->get('OAuth')->get('requestScope'),
            sprintf('%s/profile_list', $apiBaseUri)
        );
        if (false === $response) {
            // no valid OAuth token available...
            $authorizeUri = $this->oauthClient->getAuthorizeUri(
                $this->config->get('OAuth')->get('requestScope'),
                sprintf('%scallback', $request->getRootUri())
            );

            return new Response(302, ['Location' => $authorizeUri]);
        }

        return $response->json()['profile_list']['data'];
    }

    private function setDiscoveryUrl(Request $request)
    {
        $_SESSION['activeDiscoveryUrl'] = $request->getPostParameter('discoveryUrl');
        // forget about our token provider, may not be relevant!
        unset($_SESSION['tokenProviderId']);

        return new Response(302, ['Location' => $request->getRootUri()]);
    }
}
