<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2020, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
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

    /**
     * @param string $dataDir
     */
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
                        return $this->showHome();

                    case '/getProfileList':
                        return $this->getProfileList($request);
                    case '/callback':
                        // handle OAuth server callback
                        return $this->handleCallback($request);
                    default:
                        return new Response(404, [], '[404] Not Found');
                }
                break;
            case 'POST':
                switch ($request->getPathInfo()) {
                    case '/addInstitute':
                        $baseUri = $_POST['baseUri'];
                        $sessionData = $this->getSessionData();
                        $sessionData[$baseUri] = [];
                        $this->writeSessionData($sessionData);

                        return new Response(302, ['Location' => $request->getRootUri()]);

//                    case '/addServer':
//                        // user tries to add a server

//                    case '/':
//                        //
//                        // fetch an OpenVPN client configuration
//                        $providerId = $request->getPostParameter('provider_id');
//                        $_SESSION['activeDiscoveryUrl'] = $request->getPostParameter('disco_url');

//                        return $this->getDownloadPage($request, $providerId);

//                    case '/home':
//                        unset($_SESSION['activeDiscoveryUrl']);

//                        return new Response(302, ['Location' => $request->getRootUri()]);

//                    case '/download':
//                        $action = $request->getPostParameter('action');

//                        if ('back' === $action) {
//                            return new Response(
//                                302,
//                                [
//                                    'Location' => $request->getRootUri(),
//                                ]
//                            );
//                        }

//                        $providerId = $request->getPostParameter('provider_id');
//                        $profileId = $request->getPostParameter('profile_id');

//                        return $this->getConfig($request, $providerId, $profileId);
//                    case '/setDiscoveryUrl':
//                        return $this->setDiscoveryUrl($request);
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
    private function showHome()
    {
        $instituteList = $this->getAvailableServerList($this->dataDir.'/institute_access.json');
        $sessionData = $this->getSessionData();
        $myInstituteList = [];

        foreach (array_keys($sessionData) as $baseUri) {
            foreach ($instituteList as $instituteEntry) {
                if ($baseUri === $instituteEntry['base_uri']) {
                    $myInstituteList[] = $instituteEntry;
                    // TODO: remove the instituteEntry from the instituteList to not allow adding the same one twice
                }
            }
        }

        return new Response(
            200,
            [],
            $this->tpl->render(
                'home',
                [
                    'myInstituteList' => $myInstituteList,
                    'instituteList' => $instituteList,
                ]
            )
        );
    }

    private function getProfileList(Request $request)
    {
        $baseUri = $request->getQueryParameter('baseUri');
        $providerInfo = $this->getProviderInfo($baseUri);
        $provider = new Provider(
            $this->config->get('OAuth')->get('clientId'),
            $this->config->get('OAuth')->get('clientSecret'),
            $providerInfo['authorization_endpoint'],
            $providerInfo['token_endpoint']
        );
        $apiBaseUri = $providerInfo['api_base_uri'];

        $_SESSION['VpnForWebBaseUri'] = $baseUri;

        $response = $this->oauthClient->get(
            $provider,
            $baseUri, // use 'baseUri' as user as we don't have 'local' user
            $this->config->get('OAuth')->get('requestScope'),
            sprintf('%s/profile_list', $apiBaseUri)
        );
        if (false === $response) {
            // no valid OAuth token available...
            $authorizeUri = $this->oauthClient->getAuthorizeUri(
                $provider,
                $baseUri, // use 'baseUri' as user as we don't have 'local' user
                $this->config->get('OAuth')->get('requestScope'),
                sprintf('%scallback', $request->getRootUri())
            );

            return new Response(302, ['Location' => $authorizeUri]);
        }

        $profileList = $response->json()['profile_list']['data'];

        $instituteList = $this->getAvailableServerList($this->dataDir.'/institute_access.json');
        $serverInfo = null;
        foreach ($instituteList as $instituteEntry) {
            if ($baseUri === $instituteEntry['base_uri']) {
                $serverInfo = $instituteEntry;
            }
        }

        return new Response(
            200,
            [],
            $this->tpl->render(
                'profile_list',
                [
                    'profileList' => $profileList,
                    'serverInfo' => $serverInfo,
                ]
            )
        );
    }

    /**
     * @param string $baseUri
     *
     * @return array
     */
    private function getProviderInfo($baseUri)
    {
        $infoUrl = sprintf('%s/info.json', $baseUri);
        $infoResponse = $this->httpClient->send(HttpRequest::get($infoUrl));
        if (!$infoResponse->isOkay()) {
            throw new RuntimeException(sprintf('unable to fetch "%s"', $infoUrl));
        }

        return $infoResponse->json()['api']['http://eduvpn.org/api#2'];
    }

    /**
     * @param string $discoveryFile
     *
     * @return array
     */
    private function getAvailableServerList($discoveryFile)
    {
        $discoveryData = json_decode(file_get_contents($discoveryFile), true);

        return $discoveryData['instances'];
    }

    private function getSessionData()
    {
        if (!\array_key_exists('VpnForWeb', $_SESSION)) {
            return [];
        }

        return json_decode($_SESSION['VpnForWeb'], true);
    }

    private function writeSessionData(array $sessionData)
    {
        $_SESSION['VpnForWeb'] = json_encode($sessionData);
    }

    /**
     * @return Http\Response
     */
    private function handleCallback(Request $request)
    {
        $baseUri = $_SESSION['VpnForWebBaseUri'];
        $providerInfo = $this->getProviderInfo($baseUri);
        $provider = new Provider(
            $this->config->get('OAuth')->get('clientId'),
            $this->config->get('OAuth')->get('clientSecret'),
            $providerInfo['authorization_endpoint'],
            $providerInfo['token_endpoint']
        );

        $this->oauthClient->handleCallback(
            $provider,
            $baseUri, // misuse baseUri as user_id
            $request->getQueryParameters()
        );

        // redirect back
        return new Response(
            302,
            [
                'Location' => $request->getRootUri().'getProfileList?baseUri='.$baseUri,
            ]
        );
    }

//    /**
//     * @param string $baseUri
//     *
//     * @return array
//     */
//    private function getProviderInfo($baseUri)
//    {
//        $infoUrl = sprintf('%s/info.json', $baseUri);
//        $infoResponse = $this->httpClient->send(HttpRequest::get($infoUrl));
//        if (!$infoResponse->isOkay()) {
//            throw new RuntimeException(sprintf('unable to fetch "%s"', $infoUrl));
//        }

//        return $infoResponse->json()['api']['http://eduvpn.org/api#2'];
//    }

//    /**
//     * @param string $providerId
//     *
//     * @return string
//     */
//    private function getTokenProviderId($providerId)
//    {
//        $activeDiscoveryUrl = $_SESSION['activeDiscoveryUrl'];
//        switch ($this->getAuthorizationType($activeDiscoveryUrl)) {
//            case 'local':
//                $_SESSION['tokenProviderId'] = $providerId;
//                break;
//            case 'distributed':
//                if (!\array_key_exists('tokenProviderId', $_SESSION)) {
//                    $_SESSION['tokenProviderId'] = $providerId;
//                }
//                break;
//            default:
//                throw new RuntimeException(sprintf('authorization_type "%s" not supported', $this->getAuthorizationType($activeDiscoveryUrl)));
//        }

//        return $_SESSION['tokenProviderId'];
//    }

//    /**
//     * @return \fkooman\OAuth\Client\Provider
//     */
//    private function getProvider(array $tokenProviderInfo)
//    {
//        // load OAuth provider with this information
//        return new Provider(
//            $this->config->get('OAuth')->get('clientId'),
//            $this->config->get('OAuth')->get('clientSecret'),
//            $tokenProviderInfo['authorization_endpoint'],
//            $tokenProviderInfo['token_endpoint']
//        );
//    }

//    /**
//     * Get an OpenVPN client configuration for a provider.
//     *
//     * @param Http\Request $request
//     * @param string       $providerId
//     * @param string       $profileId
//     *
//     * @return Http\Response
//     */
//    private function getConfig(Request $request, $providerId, $profileId)
//    {
//        $tokenProviderId = $this->getTokenProviderId($providerId);

//        // get OAuth information for chosen tokenProvider
//        $tokenProviderInfo = $this->getProviderInfo($tokenProviderId);
//        $p = $this->getProvider($tokenProviderInfo);
//        $providerInfo = $this->getProviderInfo($providerId);
//        $apiBaseUri = $providerInfo['api_base_uri'];

//        $response = $this->oauthClient->post(
//            $p,
//            'web_user',
//            $this->config->get('OAuth')->get('requestScope'),
//            sprintf('%s/create_config', $apiBaseUri),
//            [
//                'display_name' => 'VPN for Web',
//                'profile_id' => $profileId,
//            ]
//        );
//        if (false === $response) {
//            // no valid OAuth token available...
//            $authorizeUri = $this->oauthClient->getAuthorizeUri(
//                $p,
//                'web_user',
//                $this->config->get('OAuth')->get('requestScope'),
//                sprintf('%scallback', $request->getRootUri())
//            );

//            return new Response(302, ['Location' => $authorizeUri]);
//        }

//        if (null === $providerHostName = parse_url($providerId, PHP_URL_HOST)) {
//            throw new RuntimeException('unable to extract hostname from providerId');
//        }

//        return new Response(
//            200,
//            [
//                'Content-Type' => 'application/x-openvpn-profile',
//                'Content-Disposition' => sprintf('attachment; filename="VPN for Web (%s).ovpn"', $providerHostName),
//            ],
//            $response->getBody()
//        );
//    }

//    /**
//     * @param string $providerId
//     *
//     * @return Http\Response
//     */
//    private function getDownloadPage(Request $request, $providerId)
//    {
//        $tokenProviderId = $this->getTokenProviderId($providerId);
//        $tokenProviderInfo = $this->getProviderInfo($tokenProviderId);
//        $p = $this->getProvider($tokenProviderInfo);
//        $providerInfo = $this->getProviderInfo($providerId);
//        $apiBaseUri = $providerInfo['api_base_uri'];

//        // get OAuth information for chosen tokenProvider
//        $tokenProviderInfo = $this->getProviderInfo($tokenProviderId);
//        $p = $this->getProvider($tokenProviderInfo);

//        $response = $this->oauthClient->get(
//            $p,
//            'web_user',
//            $this->config->get('OAuth')->get('requestScope'),
//            sprintf('%s/user_info', $apiBaseUri)
//        );
//        if (false === $response) {
//            // no valid OAuth token available...
//            $authorizeUri = $this->oauthClient->getAuthorizeUri(
//                $p,
//                'web_user',
//                $this->config->get('OAuth')->get('requestScope'),
//                sprintf('%scallback', $request->getRootUri())
//            );

//            return new Response(302, ['Location' => $authorizeUri]);
//        }

//        $userInfo = $response->json()['user_info']['data'];

////        if (!$this->oauthClient->hasAccessToken($this->config->get('OAuth')->get('requestScope'))) {
////            // no oauth token
////            $authorizeUri = $this->oauthClient->getAuthorizeUri(
////                $this->config->get('OAuth')->get('requestScope'),
////                sprintf('%scallback', $request->getRootUri())
////            );

////            return new Response(302, ['Location' => $authorizeUri]);
////        }

//        $discoveryData = $this->getDiscoveryData($_SESSION['activeDiscoveryUrl']);
//        $displayName = null;
//        foreach ($discoveryData as $provider) {
//            if ($provider['base_uri'] === $providerId) {
//                $displayName = $provider['display_name'];
//            }
//        }

//        return new Response(
//            200,
//            [],
//            $this->tpl->render(
//                'download',
//                [
//                    'profileList' => $this->getProfileList($request, $providerId),
//                    'providerId' => $providerId,
//                    'displayName' => $displayName,
//                    'userInfo' => $userInfo,
//                ]
//            )
//        );
//    }

//    /**
//     * @param string $providerId
//     *
//     * @return array|Http\Response
//     */
//    private function getProfileList(Request $request, $providerId)
//    {
//        $tokenProviderId = $this->getTokenProviderId($providerId);

//        // get OAuth information for chosen tokenProvider
//        $tokenProviderInfo = $this->getProviderInfo($tokenProviderId);
//        $p = $this->getProvider($tokenProviderInfo);
//        $providerInfo = $this->getProviderInfo($providerId);
//        $apiBaseUri = $providerInfo['api_base_uri'];

//        $response = $this->oauthClient->get(
//            $p,
//            'web_user',
//            $this->config->get('OAuth')->get('requestScope'),
//            sprintf('%s/profile_list', $apiBaseUri)
//        );
//        if (false === $response) {
//            // no valid OAuth token available...
//            $authorizeUri = $this->oauthClient->getAuthorizeUri(
//                $p,
//                'web_user',
//                $this->config->get('OAuth')->get('requestScope'),
//                sprintf('%scallback', $request->getRootUri())
//            );

//            return new Response(302, ['Location' => $authorizeUri]);
//        }

//        return $response->json()['profile_list']['data'];
//    }
}
