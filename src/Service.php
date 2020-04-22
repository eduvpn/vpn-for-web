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
                    case '/chooseServer':
                        return $this->showChooseServer();
                    case '/chooseIdP':
                        return $this->showChooseIdp();
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
                    case '/addServer':
                        $baseUri = $_POST['baseUri'];
                        $sessionData = $this->getSessionData();
                        $sessionData[$baseUri] = [];
                        $this->writeSessionData($sessionData);

                        return new Response(
                            302,
                            [
                                'Location' => $request->getRootUri().'getProfileList?baseUri='.$baseUri,
                            ]
                        );
                    case '/clearList':
                        $_SESSION = [];

                        return new Response(302, ['Location' => $request->getRootUri()]);
                    default:
                        return new Response(404, [], '[404] Not Found');
                }
                break;
            default:
                return new Response(405, ['Allow' => 'GET,HEAD'], '[405] Method Not Allowed');
        }
    }

    /**
     * @return bool
     */
    public function hasSecureInternetToken()
    {
        return false;
    }

    /**
     * @return Http\Response
     */
    private function showHome()
    {
        $instituteList = $this->getAvailableServerList();
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
                    'instituteList' => $this->getAvailableServerList(),
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
     * @param mixed $baseUri
     *
     * @return bool
     */
    private function isSecureInternetServer($baseUri)
    {
        $serverList = $this->getAvailableServerList();
        foreach ($serverList as $serverEntry) {
            if ($baseUri === $serverEntry['base_uri']) {
                return 'secure_internet' === $serverEntry['type'];
            }
        }

        return false;
    }

    private function getProfileList(Request $request)
    {
        $baseUri = $request->getQueryParameter('baseUri');
        if ($this->isSecureInternetServer($baseUri)) {
            // do we already have a token for any secure internet server?
            if (!$this->hasSecureInternetToken()) {
                // show IdP list
                return new Response(302, ['Location' => $request->getRootUri().'chooseIdP?baseUri='.$baseUri]);
            }
            // set provider to "home" provider
            // set apiBaseUri to other secure internet server
        }

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

        $instituteList = $this->getAvailableServerList();
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
     * @return array
     */
    private function getAvailableServerList()
    {
        $serverList = [];
        $fileList = [
            $this->dataDir.'/institute_access.json',
            $this->dataDir.'/secure_internet.json',
            $this->dataDir.'/alien.json',
        ];
        foreach ($fileList as $discoveryFile) {
            $discoveryData = json_decode(file_get_contents($discoveryFile), true);
            foreach ($discoveryData['instances'] as $serverEntry) {
                $serverEntry['type'] = basename($discoveryFile, '.json');
                $serverList[] = $serverEntry;
            }
        }

        return $serverList;
    }

    /**
     * @return array
     */
    private function getIdpList()
    {
        return json_decode(file_get_contents($this->dataDir.'/organization_list.json'), true)['organization_list'];
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
}
