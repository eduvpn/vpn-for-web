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

namespace SURFnet\VPN\ApiClient;

use fkooman\OAuth\Client\Http\HttpClientInterface;
use fkooman\OAuth\Client\Http\Request as HttpRequest;
use fkooman\OAuth\Client\OAuthClient;
use fkooman\OAuth\Client\Provider;
use RuntimeException;
use SURFnet\VPN\ApiClient\Http\Exception\TokenException;
use SURFnet\VPN\ApiClient\Http\Request;
use SURFnet\VPN\ApiClient\Http\Response;

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

        if ('' === session_id()) {
            session_start();
        }
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
                        // show list of providers
                        return $this->showProviderList();
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

                        return $this->getConfig($request, $providerId);
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
    private function showProviderList()
    {
        $providerList = json_decode(file_get_contents(sprintf('%s/provider_list.json', $this->dataDir)), true);
        // XXX cleanup this crap!
        foreach ($providerList['instances'] as $k => $v) {
            $providerList['instances'][$k]['hostName'] = parse_url($v['base_uri'], PHP_URL_HOST);
        }

        return new Response(
            200,
            [],
            $this->tpl->render(
                'providerList',
                [
                    'tokenProviderId' => array_key_exists('tokenProviderId', $_SESSION) ? $_SESSION['tokenProviderId'] : false,
                    'providerList' => $providerList['instances'],
                ]
            )
        );
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

    /**
     * Get an OpenVPN client configuration for a provider.
     *
     * @param Http\Request $request
     * @param string       $providerId
     */
    private function getConfig(Request $request, $providerId)
    {
        try {
            if (!array_key_exists('tokenProviderId', $_SESSION)) {
                $_SESSION['tokenProviderId'] = $providerId;
            }
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

            $providerInfo = $this->getProviderInfo($providerId);
            $apiBaseUri = $providerInfo['api_base_uri'];

            $response = $this->post(
                sprintf('%s/create_config', $apiBaseUri),
                [
                    'display_name' => 'eduVPN for Web',
                    'profile_id' => 'internet',
                ]
            );

            if (false === $providerHostName = parse_url($providerId, PHP_URL_HOST)) {
                throw new RuntimeException('unable to extract hostname from providerId');
            }

            return new Response(
                200,
                [
                    'Content-Type' => 'application/x-openvpn-profile',
                    'Content-Disposition' => sprintf('attachment; filename="eduVPN for Android (%s).ovpn"', $providerHostName),
                ],
                $response->getBody()
            );
        } catch (TokenException $e) {
            // no valid OAuth token available...
            $authorizeUri = $this->oauthClient->getAuthorizeUri(
                $this->config->get('OAuth')->get('requestScope'),
                sprintf('%scallback', $request->getRootUri())
            );

            return new Response(302, ['Location' => $authorizeUri]);
        }
    }

    private function post($requestUri, array $postBody)
    {
        if (false === $response = $this->oauthClient->post($this->config->get('OAuth')->get('requestScope'), $requestUri, $postBody)) {
            throw new TokenException('no token available');
        }

        return $response;
    }
}
