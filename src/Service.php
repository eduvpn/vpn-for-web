<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2020, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Web;

use fkooman\OAuth\Client\Http\HttpClientInterface;
use fkooman\OAuth\Client\Http\Request as HttpRequest;
use fkooman\OAuth\Client\OAuthClient;
use fkooman\OAuth\Client\Provider;
use LC\Web\Http\Request;
use LC\Web\Http\Response;
use RuntimeException;

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
                        return $this->showHome($request);
                    case '/settings':
                        return new Response(
                            200,
                            [],
                            $this->tpl->render(
                                'settings',
                                [
                                    'forceTcp' => isset($_SESSION['forceTcp']) ? $_SESSION['forceTcp'] : false,
                                ]
                            )
                        );
                    case '/chooseServer':
                        return $this->showChooseServer();
                    case '/switchLocation':
                        return $this->showSwitchLocation();
                    case '/chooseIdP':
                        return $this->showChooseIdp($request);
                    case '/getProfileList':
                        return $this->getProfileList($request);
                    case '/callback':
                        // handle OAuth server callback
                        return $this->handleCallback($request);
                    default:
                        return new Response(404, [], '[404] Not Found');
                }
                // no break
            case 'POST':
                switch ($request->getPathInfo()) {
                    case '/addServer':
                        $baseUri = $_POST['baseUri'];

                        return new Response(
                            302,
                            [
                                'Location' => $request->getRootUri().'getProfileList?baseUri='.$baseUri,
                            ]
                        );

                    case '/selectIdP':
                        $baseUri = $_POST['baseUri'];
                        $orgId = $_POST['orgId'];

                        return new Response(
                            302,
                            [
                                'Location' => $request->getRootUri().'getProfileList?orgId='.$orgId,
                            ]
                        );

                    case '/saveSettings':
                        $_SESSION['forceTcp'] = 'on' === $request->getPostParameter('forceTcp');

                        return new Response(302, ['Location' => $request->getRootUri()]);

                    case '/resetAppData':
                        $_SESSION = [];

                        return new Response(302, ['Location' => $request->getRootUri()]);
                    default:
                        return new Response(404, [], '[404] Not Found');
                }
                // no break
            default:
                return new Response(405, ['Allow' => 'GET,HEAD'], '[405] Method Not Allowed');
        }
    }

    /**
     * @return Http\Response
     */
    private function showHome(Request $request)
    {
        $mySessionInstituteAccessList = isset($_SESSION['institute_access']) ? $_SESSION['institute_access'] : [];
        $secureInternetBaseUri = isset($_SESSION['secure_internet']) ? $_SESSION['secure_internet'] : null;

        $instituteList = $this->getInstituteAccessServerList();
        $myInstituteServerInfo = [];

        foreach ($mySessionInstituteAccessList as $baseUri) {
            foreach ($instituteList as $instituteEntry) {
                if ($baseUri === $instituteEntry['base_uri']) {
                    $myInstituteServerInfo[] = $instituteEntry;
                }
            }
        }

        return new Response(
            200,
            [],
            $this->tpl->render(
                'home',
                [
                    'myInstituteServerInfo' => $myInstituteServerInfo,
                    'secureInternetServerInfo' => null !== $secureInternetBaseUri ? $this->getServerInfo($secureInternetBaseUri) : null,
                ]
            )
        );
    }

    /**
     * @return Http\Response
     */
    private function showChooseServer()
    {
        return new Response(
            200,
            [],
            $this->tpl->render(
                'choose_server',
                [
                    'instituteList' => $this->getInstituteAccessServerList(),
                ]
            )
        );
    }

    /**
     * @return Http\Response
     */
    private function showSwitchLocation()
    {
        return new Response(
            200,
            [],
            $this->tpl->render(
                'switch_location',
                [
                    'secureInternetServerList' => $this->getSecureInternetServerList(),
                ]
            )
        );
    }

    /**
     * @return Http\Response
     */
    private function showChooseIdp(Request $request)
    {
        return new Response(
            200,
            [],
            $this->tpl->render(
                'choose_idp',
                [
                    'idpList' => $this->getIdpList(),
                ]
            )
        );
    }

//    /**
//     * @param mixed $baseUri
//     *
//     * @return bool
//     */
//    private function isSecureInternetServer($baseUri)
//    {
//        $serverList = $this->getAvailableServerList();
//        foreach ($serverList as $serverEntry) {
//            if ($baseUri === $serverEntry['base_uri']) {
//                return 'secure_internet' === $serverEntry['type'];
//            }
//        }

//        return false;
//    }

//    /**
//     * @return string|null
//     */
//    private function hasSecureInternetToken()
//    {
//        $serverList = $this->getAvailableServerList();
//        foreach ($serverList as $serverEntry) {
//            if ('secure_internet' !== $serverEntry['type']) {
//                continue;
//            }

//            // do we have a token for this one?
//            if (\array_key_exists('_oauth2_token_'.$serverEntry['base_uri'], $_SESSION)) {
    ////                echo "Home Provider: " . $serverEntry['base_uri'];

//                return $serverEntry['base_uri'];
//            }
//        }

//        return null;
//    }

    /**
     * @param string $orgId
     *
     * @return string|null
     */
    private function getBaseUriFromOrgId($orgId)
    {
        $idpList = $this->getIdpList();
        foreach ($idpList as $idpEntry) {
            if ($orgId === $idpEntry['org_id']) {
                // found it
                $serverList = json_decode(file_get_contents($this->dataDir.'/'.$idpEntry['server_list']), true);
                foreach ($serverList['server_list'] as $serverEntry) {
                    if (\array_key_exists('peer_list', $serverEntry)) {
                        return $serverEntry['base_url'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return \LC\Web\Http\Response
     */
    private function getProfileList(Request $request)
    {
        if (null === $baseUri = $request->getQueryParameter('baseUri')) {
            // if we do NOT get a baseUri, we MUST have an orgId that we then
            // use to figure out *which* baseUri we need to connect to
            if (null === $orgId = $request->getQueryParameter('orgId')) {
                return new Response(400, [], 'baseUri and orgId query parameter missing');
            }
            if (null === $baseUri = $this->getBaseUriFromOrgId($orgId)) {
                return new Response(400, [], 'Bummer! orgId does not have a "Secure Internet" server available');
            }
        }

        // XXX if baseUri points to a secure internet server we have to
        // update the authz_endpoint and token_endpoint with our "home" server

        $providerInfo = $this->getProviderInfo($baseUri);
        $provider = new Provider(
            $this->config->get('OAuth')->get('clientId'),
            $this->config->get('OAuth')->get('clientSecret'),
            $providerInfo['authorization_endpoint'],
            $providerInfo['token_endpoint']
        );
        $apiBaseUri = $providerInfo['api_base_uri'];
        $response = $this->oauthClient->get(
            $provider,
            $baseUri, // use baseUri as "user"
            $this->config->get('OAuth')->get('requestScope'),
            sprintf('%s/profile_list', $apiBaseUri)
        );

        if (false === $response) {
            $_SESSION['_base_uri'] = $baseUri;
            // no valid OAuth token available...
            $authorizeUri = $this->oauthClient->getAuthorizeUri(
                $provider,
                $baseUri, // use baseUri as "user"
                $this->config->get('OAuth')->get('requestScope'),
                sprintf('%scallback', $request->getRootUri())
            );

            return new Response(302, ['Location' => $authorizeUri]);
        }

        $profileList = $response->json()['profile_list']['data'];
        $serverInfo = $this->getServerInfo($baseUri);

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
     * @param string $baseUri
     *
     * @return array|null
     */
    private function getServerInfo($baseUri)
    {
        $fileList = [
            'institute_access' => $this->dataDir.'/institute_access.json',
            'secure_internet' => $this->dataDir.'/secure_internet.json',
        ];
        foreach ($fileList as $type => $discoveryFile) {
            $discoveryData = json_decode(file_get_contents($discoveryFile), true);
            foreach ($discoveryData['instances'] as $serverEntry) {
                if ($baseUri === $serverEntry['base_uri']) {
                    $serverEntry['type'] = $type;

                    return $serverEntry;
                }
            }
        }

        return null;
    }

    /**
     * @return array
     */
    private function getInstituteAccessServerList()
    {
        return json_decode(file_get_contents($this->dataDir.'/institute_access.json'), true)['instances'];
    }

    /**
     * @return array
     */
    private function getSecureInternetServerList()
    {
        return json_decode(file_get_contents($this->dataDir.'/secure_internet.json'), true)['instances'];
    }

    /**
     * @return array
     */
    private function getIdpList()
    {
        return json_decode(file_get_contents($this->dataDir.'/organization_list.json'), true)['organization_list'];
    }

    /**
     * @return Http\Response
     */
    private function handleCallback(Request $request)
    {
        $baseUri = $_SESSION['_base_uri'];
        unset($_SESSION['_base_uri']);

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

        // add baseUri to server list
        $serverInfo = $this->getServerInfo($baseUri);
        if ('secure_internet' === $serverInfo['type']) {
            $_SESSION['secure_internet'] = $baseUri;
        } else {
            if (!\array_key_exists('institute_access', $_SESSION)) {
                $_SESSION['institute_access'] = [];
            }
            $_SESSION['institute_access'][] = $baseUri;
        }

        // redirect back
        return new Response(
            302,
            [
                'Location' => $request->getRootUri().'getProfileList?baseUri='.$baseUri,
            ]
        );
    }
}
