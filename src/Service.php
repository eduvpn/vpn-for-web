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
use LC\Web\Http\Exception\HttpException;
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
        try {
            switch ($request->getMethod()) {
                case 'HEAD':
                case 'GET':
                    switch ($request->getPathInfo()) {
                        case '/':
                            return $this->showHome();
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
                            return $this->showChooseIdp();
                        case '/getProfileList':
                            $baseUri = self::validateBaseUri($request->getQueryParameter('baseUri'));
                            $orgId = self::validateOrgId($request->getQueryParameter('orgId'));

                            return $this->getProfileList($baseUri, $orgId, $request->getRootUri());
                        case '/callback':
                            // handle OAuth server callback
                            return $this->handleCallback($request);
                        default:
                            throw new HttpException('Not Found', 404);
                    }
                    // no break
                case 'POST':
                    switch ($request->getPathInfo()) {
                        case '/addServer':
                            $baseUri = self::validateBaseUri($request->getPostParameter('baseUri'));

                            return new Response(
                                302,
                                [
                                    'Location' => $request->getRootUri().'getProfileList?baseUri='.$baseUri,
                                ]
                            );

                        case '/addOtherServer':
                            $baseUri = self::validateBaseUri('https://'.$request->getPostParameter('serverName').'/');

                            return new Response(
                                302,
                                [
                                    'Location' => $request->getRootUri().'getProfileList?baseUri='.$baseUri,
                                ]
                            );

                        case '/selectIdP':
                            $orgId = self::validateOrgId($request->getPostParameter('orgId'));

                            return new Response(
                                302,
                                [
                                    'Location' => $request->getRootUri().'getProfileList?orgId='.$orgId,
                                ]
                            );

                        case '/switchLocation':
                            $baseUri = self::validateBaseUri($request->getPostParameter('baseUri'));
                            $_SESSION['secure_internet'] = $baseUri;

                            return new Response(
                                302,
                                [
                                    'Location' => $request->getRootUri().'getProfileList?baseUri='.$baseUri,
                                ]
                            );

                        case '/saveSettings':
                            $_SESSION['forceTcp'] = 'on' === $request->getPostParameter('forceTcp');

                            return new Response(302, ['Location' => $request->getRootUri()]);

                        case '/resetAppData':
                            $_SESSION = [];

                            return new Response(302, ['Location' => $request->getRootUri()]);
                        default:
                            throw new HttpException('Not Found', 404);
                    }
                    // no break
                default:
                    throw new HttpException('Method Not Allowed', 405, ['Allow' => 'GET,HEAD,POST']);
            }
        } catch (HttpException $e) {
            return new Response(
                $e->getCode(),
                $e->getResponseHeaders(),
                $this->tpl->render(
                    'error',
                    [
                        'errorCode' => $e->getCode(),
                        'errorMessage' => $e->getMessage(),
                    ]
                )
            );
        }
    }

    /**
     * @return Http\Response
     */
    private function showHome()
    {
        $myInstituteAccessBaseUriList = isset($_SESSION['institute_access']) ? $_SESSION['institute_access'] : [];
        $myInstituteAccessServerList = [];
        foreach ($myInstituteAccessBaseUriList as $baseUri) {
            $myInstituteAccessServerList[] = $this->getServerInfo($baseUri);
        }

        $myAlienBaseUriList = isset($_SESSION['alien']) ? $_SESSION['alien'] : [];
        $myAlienServerList = [];
        foreach ($myAlienBaseUriList as $baseUri) {
            $myAlienServerList[] = $this->getServerInfo($baseUri);
        }

        $secureInternetBaseUri = isset($_SESSION['secure_internet']) ? $_SESSION['secure_internet'] : null;
        $secureInternetServerInfo = null !== $secureInternetBaseUri ? $this->getServerInfo($secureInternetBaseUri) : null;

        return new Response(
            200,
            [],
            $this->tpl->render(
                'home',
                [
                    'myInstituteAccessServerList' => $myInstituteAccessServerList,
                    'myAlienServerList' => $myAlienServerList,
                    'secureInternetServerInfo' => $secureInternetServerInfo,
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
    private function showChooseIdp()
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
     * @param string|null $baseUri
     * @param string|null $orgId
     * @param string      $rootUri
     *
     * @return Http\Response
     */
    private function getProfileList($baseUri, $orgId, $rootUri)
    {
        if (null === $baseUri) {
            // if we do NOT get a baseUri, we MUST have an orgId that we then
            // use to figure out *which* baseUri we need to connect to
            if (null === $orgId) {
                throw new HttpException('baseUri and orgId query parameter missing', 400);
            }
            if (null === $baseUri = $this->getBaseUriFromOrgId($orgId)) {
                throw new HttpException('Bummer! orgId does not have a "Secure Internet" server available', 400);
            }
        }
        $userId = $baseUri; // use baseUri as user_id

        $providerInfo = $this->getProviderInfo($baseUri);
        $serverInfo = $this->getServerInfo($baseUri);
        // are we trying to connect to a secure internet server?
        if ('secure_internet' === $this->getServerInfo($baseUri)['type']) {
            if (isset($_SESSION['secure_internet_home'])) {
                // we already have a home server!
                $secureInternetHomeBaseUri = $_SESSION['secure_internet_home'];
                $secureInternetProviderInfo = $this->getProviderInfo($secureInternetHomeBaseUri);
                // override the OAuth stuff to point to the home server
                $providerInfo['authorization_endpoint'] = $secureInternetProviderInfo['authorization_endpoint'];
                $providerInfo['token_endpoint'] = $secureInternetProviderInfo['token_endpoint'];
                $userId = $secureInternetHomeBaseUri;
            }
        }

        $provider = new Provider(
            $this->config->get('OAuth')->get('clientId'),
            null,
            $providerInfo['authorization_endpoint'],
            $providerInfo['token_endpoint']
        );
        $apiBaseUri = $providerInfo['api_base_uri'];

        // get profile list
        $response = $this->oauthClient->get(
            $provider,
            $userId,
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
                sprintf('%scallback', $rootUri)
            );

            return new Response(302, ['Location' => $authorizeUri]);
        }
        $profileList = $response->json()['profile_list']['data'];

        // get MOTD (XXX lots of code duplication...)
        $response = $this->oauthClient->get(
            $provider,
            $userId,
            $this->config->get('OAuth')->get('requestScope'),
            sprintf('%s/system_messages', $apiBaseUri)
        );

        if (false === $response) {
            $_SESSION['_base_uri'] = $baseUri;
            // no valid OAuth token available...
            $authorizeUri = $this->oauthClient->getAuthorizeUri(
                $provider,
                $baseUri, // use baseUri as "user"
                $this->config->get('OAuth')->get('requestScope'),
                sprintf('%scallback', $rootUri)
            );

            return new Response(302, ['Location' => $authorizeUri]);
        }

        $systemMessages = $response->json()['system_messages']['data'];

        return new Response(
            200,
            [],
            $this->tpl->render(
                'profile_list',
                [
                    'profileList' => $profileList,
                    'systemMessages' => $systemMessages,
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
     * @return array
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

        return [
            'base_uri' => $baseUri,
            'display_name' => $baseUri,
            'type' => 'alien',
        ];
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
            null,
            $providerInfo['authorization_endpoint'],
            $providerInfo['token_endpoint']
        );

        $this->oauthClient->handleCallback(
            $provider,
            $baseUri, // misuse baseUri as user_id
            $request->getQueryParameters()
        );

        // add baseUri to server list
        // XXX make sure to never add the same server twice! this will happen
        // after app is revoked for example...
        $serverInfo = $this->getServerInfo($baseUri);
        if ('secure_internet' === $serverInfo['type']) {
            if (!isset($_SESSION['secure_internet_home'])) {
                $_SESSION['secure_internet_home'] = $baseUri;
            }
            $_SESSION['secure_internet'] = $baseUri;
        } elseif ('institute_access' === $serverInfo['type']) {
            if (!\array_key_exists('institute_access', $_SESSION)) {
                $_SESSION['institute_access'] = [];
            }
            $_SESSION['institute_access'][] = $baseUri;
        } else {
            // alien
            if (!\array_key_exists('alien', $_SESSION)) {
                $_SESSION['alien'] = [];
            }
            $_SESSION['alien'][] = $baseUri;
        }

        // redirect back
        return new Response(
            302,
            [
                'Location' => $request->getRootUri().'getProfileList?baseUri='.$baseUri,
            ]
        );
    }

    /**
     * @param string|null $baseUri
     *
     * @return string
     */
    private static function validateBaseUri($baseUri)
    {
        if (null === $baseUri) {
            return null;
        }

        if (!\is_string($baseUri)) {
            throw new HttpException(sprintf('invalid baseUri "%s"', $baseUri), 400);
        }
        if (1 !== preg_match('/^https:\/\/[a-zA-Z0-9-.]+\/$/', $baseUri)) {
            throw new HttpException(sprintf('invalid baseUri "%s"', $baseUri), 400);
        }

        return $baseUri;
    }

    /**
     * @param string|null $orgId
     *
     * @return string|null
     */
    private static function validateOrgId($orgId)
    {
        // XXX implement orgId validation!
        return $orgId;
    }
}
