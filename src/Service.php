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
    /** @var Config */
    private $config;

    /** @var TplInterface */
    private $tpl;

    /** @var \fkooman\OAuth\Client\Http\HttpClientInterface */
    private $httpClient;

    /** @var \fkooman\OAuth\Client\OAuthClient|null */
    private $oauthClient = null;

    public function __construct(Config $config, TplInterface $tpl, HttpClientInterface $httpClient)
    {
        $this->config = $config;
        $this->tpl = $tpl;
        $this->httpClient = $httpClient;

        if ('' === session_id()) {
            session_start();
        }
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

                        $_SESSION['tokenProvider'] = $instanceId;

                        return new Response(
                            302,
                            [
                                'Location' => $request->getRootUri(),
                            ]
                        );
                    }

                    if (null === $instanceId = $request->getQueryParameter('instance_id')) {
                        // no instance specified
                        return $this->showInstanceList();
                    }

                    return $this->getConfig($instanceId);
                default:
                    return new Response(405, ['Allow' => 'GET,HEAD']);
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

    private function showInstanceList()
    {
        $instanceList = $this->getInstanceList($this->config->get('instanceListUri'));

        return new Response(
            200,
            [],
            $this->tpl->render(
                'instanceList',
                [
                    'tokenProvider' => array_key_exists('tokenProvider', $_SESSION) ? $_SESSION['tokenProvider'] : false,
                    'instanceList' => $instanceList['instances'],
                ]
            )
        );
    }

    private function getInstanceList($instanceListUrl)
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

        if (array_key_exists('tokenProvider', $_SESSION)) {
            // we need to set the federation provider client info!
            $tokenProviderInfo = $this->apiInfo($_SESSION['tokenProvider']);
            $provider = new Provider(
                $this->config->get('clientId'),
                $this->config->get('clientSecret'),
                $tokenProviderInfo['authorization_endpoint'],
                $tokenProviderInfo['token_endpoint']
            );
        } else {
            $provider = new Provider(
                $this->config->get('clientId'),
                $this->config->get('clientSecret'),
                $apiInfo['authorization_endpoint'],
                $apiInfo['token_endpoint']
            );
        }

        $this->oauthClient->setProvider($provider);
        $this->oauthClient->setUserId('N/A');

        return $apiInfo;
    }

    private function getConfig($instanceId)
    {
        $_SESSION['instance_id'] = $instanceId;

        $apiInfo = $this->apiDisco($instanceId);
        $apiBaseUri = $apiInfo['api_base_uri'];

        $response = $this->post(
            'config',
            sprintf('%s/create_config', $apiInfo['api_base_uri']),
            [
                'display_name' => 'eduVPN for Web',
                'profile_id' => 'internet',
            ]
        );

        return new Response(
            200,
            [
                'Content-Type' => 'application/x-openvpn-profile',
                'Content-Disposition' => 'attachment; filename="eduVPN for Web.ovpn"',
            ],
            $response->getBody()
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
        $publicKey = Base64::decode($this->config->get('instanceListPublicKey'));
        if (!\Sodium\crypto_sign_verify_detached($rawSignature, $jsonText, $publicKey)) {
            throw new RuntimeException('unable to verify discovery file signature');
        }
    }
}
