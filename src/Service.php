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
use fkooman\OAuth\Client\SessionTokenStorage;
use ParagonIE\ConstantTime\Base64;
use RuntimeException;
use SURFnet\VPN\ApiClient\Http\Exception\TokenException;
use SURFnet\VPN\ApiClient\Http\Request;
use SURFnet\VPN\ApiClient\Http\Response;

class Service
{
    /** @var TplInterface */
    private $tpl;

    /** @var \fkooman\OAuth\Client\Http\HttpClientInterface */
    private $httpClient;

    /** @var array */
    private $publicKeys;

    /** @var array */
    private $clientConfig;

    /** @var \fkooman\OAuth\Client\OAuthClient|null */
    private $oauthClient = null;

    public function __construct(TplInterface $tpl, HttpClientInterface $httpClient, array $clientConfig, array $publicKeys)
    {
        if ('' === session_id()) {
            session_start();
        }

        $this->tpl = $tpl;
        $this->httpClient = $httpClient;
        $this->publicKeys = $publicKeys;
        $this->clientConfig = $clientConfig;
    }

    public function run(Request $request)
    {
        $requestScope = 'config';
        $callbackUri = sprintf('%sindex.php?callback=yes', $request->getRootUri());
        $instanceId = null;

        try {
            switch ($request->getMethod()) {
                case 'HEAD':
                case 'GET':
                    if ('yes' === $request->getQueryParameter('callback')) {
                        $instanceId = $_SESSION['instance_id'];
                        $apiInfo = $this->apiDisco($instanceId);
                        $this->oauthClient->handleCallback(
                            $request->getQueryParameter('code'),
                            $request->getQueryParameter('state')
                        );

                        if (array_key_exists('federation_attempt', $_SESSION)) {
                            unset($_SESSION['federation_attempt']);
                            $_SESSION['federation_provider'] = $instanceId;
                        }

                        return new Response(
                            302,
                            [
                                'Location' => sprintf('%sindex.php?instance_id=%s', $request->getRootUri(), $instanceId),
                            ]
                        );
                    }

                    if (null === $instanceId = $request->getQueryParameter('instance_id')) {
                        // no instance specified
                        return $this->getInstanceList();
                    }

                    if (null === $profileId = $request->getQueryParameter('profile_id')) {
                        // no profile specified
                        if ('yes' === $request->getQueryParameter('federation')) {
                            if (!array_key_exists('federation_attempt', $_SESSION)) {
                                $_SESSION['federation_attempt'] = true;
                            }
                        }

                        return $this->getProfileList($requestScope, $instanceId);
                    }

                    // instance & profile specified, show all API call outputs
                    return $this->getConfig($requestScope, $instanceId, $profileId);
                case 'POST':
                    if (null === $instanceId = $request->getQueryParameter('instance_id')) {
                        // no instance specified, we cannot generate a keypair
                        return new Response(
                            400,
                            [],
                            'no instance_id specified'
                        );
                    }

                    if (null === $displayName = $request->getPostParameter('display_name')) {
                        return new Response(
                            400,
                            [],
                            'no display_name specified'
                        );
                    }

                    return $this->getKeypair($requestScope, $instanceId, $displayName);
                default:
                    return new Response(405, ['Allow' => 'GET,POST']);
            }
        } catch (TokenException $e) {
            // no valid OAuth token available...
            $authorizeUri = $this->oauthClient->getAuthorizeUri(
                $requestScope,
                $callbackUri
            );

            return new Response(302, ['Location' => $authorizeUri]);
        }
    }

    private function getInstanceList()
    {
        // we get both the "secure internet" and "secure access" lists
        $secureInternetList = $this->getAndVerifyList('https://static.eduvpn.nl/federation.json');
        $secureAccessList = $this->getAndVerifyList('https://static.eduvpn.nl/instances.json');

//        echo '<pre>';
//        var_dump($_SESSION);
//        echo '</pre>';

        return new Response(
            200,
            [],
            $this->tpl->render(
                'instances',
                [
                    'federationProvider' => array_key_exists('federation_provider', $_SESSION) ? $_SESSION['federation_provider'] : false,
                    'secureInternet' => $secureInternetList,
                    'secureAccess' => $secureAccessList,
                ]
            )
        );
    }

    private function getAndVerifyList($instanceListUrl)
    {
        $response = $this->httpClient->send(HttpRequest::get($instanceListUrl));
        if (!$response->isOkay()) {
            throw new RuntimeException(sprintf('unable to fetch "%s"', $instanceListUrl));
        }

        $instancesSignatureUrl = sprintf('%s.sig', $instanceListUrl);
        $signatureResponse = $this->httpClient->send(HttpRequest::get($instancesSignatureUrl));
        if (!$signatureResponse->isOkay()) {
            throw new RuntimeException(sprintf('unable to fetch "%s"', $instancesSignatureUrl));
        }

        $this->verifySignature($response->getBody(), $signatureResponse->getBody());

        return $response->json();
    }

    private function apiInfo($instanceId)
    {
        $infoUrl = sprintf('%s/info.json', $instanceId);
        $response = $this->httpClient->send(HttpRequest::get($infoUrl));
        if (!$response->isOkay()) {
            throw new RuntimeException(sprintf('unable to fetch "%s"', $infoUrl));
        }

        return $response->json()['api']['http://eduvpn.org/api#2'];
    }

    /**
     * @return Response|array
     */
    private function apiDisco($instanceId)
    {
        $apiInfo = $this->apiInfo($instanceId);

        // every endpoint has their own OAuth server, so we need to connect
        // to that one!
        $sessionTokenStorage = new SessionTokenStorage();
        $this->oauthClient = new OAuthClient(
            $sessionTokenStorage,
            $this->httpClient
        );

        if (array_key_exists('federation_provider', $_SESSION)) {
            // we need to set the federation provider client info!
            $federationProviderInfo = $this->apiInfo($_SESSION['federation_provider']);
            $provider = new Provider(
                $this->clientConfig['client_id'],
                $this->clientConfig['client_secret'],
                $federationProviderInfo['authorization_endpoint'],
                $federationProviderInfo['token_endpoint']
            );
        } else {
            $provider = new Provider(
                $this->clientConfig['client_id'],
                $this->clientConfig['client_secret'],
                $apiInfo['authorization_endpoint'],
                $apiInfo['token_endpoint']
            );
        }

        $this->oauthClient->setProvider($provider);
        $this->oauthClient->setUserId('session_user');

        return $apiInfo;
    }

    private function getKeypair($requestScope, $instanceId, $displayName)
    {
        $apiInfo = $this->apiDisco($instanceId);
        $apiBaseUri = $apiInfo['api_base_uri'];

        $createKeypair = $this->post(
            $requestScope,
            sprintf('%s/create_keypair', $apiInfo['api_base_uri']),
            [
                'display_name' => $displayName,
            ]
        )->json();

        return new Response(
            200,
            [],
            $this->tpl->render(
                'keypair',
                array_merge(
                    ['instance_id' => $instanceId],
                    ['create_keypair' => $createKeypair['create_keypair']['data']]
                )
            )
        );
    }

    private function getProfileList($requestScope, $instanceId)
    {
        $apiInfo = $this->apiDisco($instanceId);
        $apiBaseUri = $apiInfo['api_base_uri'];

        $_SESSION['instance_id'] = $instanceId;

//        echo '<pre>';

//        var_dump($this->oauthClient);
//        var_dump($_SESSION);
//        echo '</pre>';
//        die();

        // instance specified, get user_info
        $userInfo = $this->get($requestScope, sprintf('%s/user_info', $apiBaseUri))->json();
        $profileList = $this->get($requestScope, sprintf('%s/profile_list', $apiBaseUri))->json();
        $systemMessages = $this->get($requestScope, sprintf('%s/system_messages', $apiBaseUri))->json();
        $userMessages = $this->get($requestScope, sprintf('%s/user_messages', $apiBaseUri))->json();

        return new Response(
            200,
            [],
            $this->tpl->render(
                'profiles',
                array_merge(
                    ['instance_id' => $instanceId],
                    ['user_info' => $userInfo['user_info']['data']],
                    ['profile_list' => $profileList['profile_list']['data']],
                    ['system_messages' => $systemMessages['system_messages']['data']],
                    ['user_messages' => $userMessages['user_messages']['data']]
                )
            )
        );
    }

    private function getConfig($requestScope, $instanceId, $profileId)
    {
        $apiInfo = $this->apiDisco($instanceId);
        $apiBaseUri = $apiInfo['api_base_uri'];

        $profileConfig = $this->get(
            $requestScope,
            sprintf('%s/profile_config?profile_id=%s', $apiBaseUri, $profileId)
        )->getBody();

        return new Response(
            200,
            [],
            $this->tpl->render(
                'config',
                array_merge(
                    ['instance_id' => $instanceId],
                    ['profile_id' => $profileId],
                    ['profile_config' => $profileConfig]
                )
            )
        );
    }

    private function get($requestScope, $requestUri)
    {
        if (false === $response = $this->oauthClient->get($requestScope, $requestUri)) {
            throw new TokenException('no token available');
        }

        return $response;
    }

    private function post($requestScope, $requestUri, array $postBody)
    {
        if (false === $response = $this->oauthClient->post($requestScope, $requestUri, $postBody)) {
            throw new TokenException('no token available');
        }

        return $response;
    }

    private function verifySignature($jsonText, $instanceSignature)
    {
        $rawSignature = Base64::decode($instanceSignature);
        foreach ($this->publicKeys as $encodedPublicKey) {
            $publicKey = Base64::decode($encodedPublicKey);
            if (\Sodium\crypto_sign_verify_detached($rawSignature, $jsonText, $publicKey)) {
                return;
            }
        }

        throw new RuntimeException('unable to verify discovery file signature');
    }
}
