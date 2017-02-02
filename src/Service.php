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

use DateTime;
use fkooman\OAuth\Client\OAuth2Client;
use RuntimeException;
use SURFnet\VPN\ApiClient\Http\Request;
use SURFnet\VPN\ApiClient\Http\Response;
use SURFnet\VPN\ApiClient\Http\Session;
use SURFnet\VPN\ApiClient\HttpClient\HttpClientInterface;

class Service
{
    /** @var Config */
    private $config;

    /** @var \SURFnet\VPN\ApiClient\HttpClient\HttpClientInterface */
    private $httpClient;

    /** @var TplInterface */
    private $tpl;

    private $session;

    private $oauthClient;

    private $dateTime;

    public function __construct(Config $config, HttpClientInterface $httpClient, TplInterface $tpl, Session $session, OAuth2Client $oauthClient, DateTime $dateTime)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->tpl = $tpl;
        $this->session = $session;
        $this->oauthClient = $oauthClient;
        $this->dateTime = $dateTime;
    }

    public function run(Request $request)
    {
        // only support GET requests
        if ('GET' !== $request->getMethod()) {
            return new Response(405, ['Allow' => 'GET']);
        }

        // check if access_token is available
        if (!isset($this->session->access_token)) {
            // no access_token available
            return $this->getAccessToken($request);
        }

        // check if access_token is still valid
        if (0 >= $this->session->access_token->getExpiresIn()) {
            unset($this->session->access_token);

            return $this->getAccessToken($request);
        }

        // get the instance list
        list($responseCode, $instanceData) = $this->httpClient->get($this->config->instanceList);    // XXX rename config field to uri
        if (200 !== $responseCode) {
            throw new RuntimeException('unable to fetch instanceList'); // XXX rename
        }

        // is an instance_id and profile_id specified?
        $instanceId = $request->getQueryParameter('instance_id');
        $profileId = $request->getQueryParameter('profile_id');

        if (!is_null($instanceId) && !is_null($profileId)) {
            // XXX it *DOES* have sideeffects, so this is NOT nice, maybe we
            // need to use a POST for this?
            return $this->showConfig($instanceData, $instanceId, $profileId);
        }

        if (!is_null($instanceId)) {
            return $this->showProfiles($instanceData, $instanceId);
        }

        // show the instances
        return $this->showInstances($instanceData);
    }

    private function getAccessToken(Request $request)
    {
        $authorizationRequestUri = $this->oauthClient->getAuthorizationRequestUri(
            'config',
            $request->getAuthority().'/callback.php'  // XXX consider the root the app is running in!
        );
        $this->session->oauth2_session = $authorizationRequestUri;

        return new Response(302, ['Location' => $authorizationRequestUri]);
    }

    private function showInstances(array $instanceData)
    {
        return new Response(200, [], $this->tpl->render('instances', $instanceData));
    }

    private function showProfiles(array $instanceData, $instanceId)
    {
        $info = $this->getInfo($instanceData, $instanceId);
        $profileListUri = $info->profile_list;

        $profileList = $this->getProfiles($profileListUri, $this->session->access_token->getToken());

//        $systemMessagesUri = $infoData['api']['http://eduvpn.org/api#2']['system_messages'];
//        $motdMessages = $httpClient->get($systemMessagesUri, ['message_type' => 'motd']);
//        $motdMessage = $motdMessages[1]['system_messages']['data'][0]['message'];

        return new Response(
            200,
            [],
            $this->tpl->render(
                'profiles',
                [
                    'motd' => 'XXX',
                    'instance_id' => $instanceId,
                    'profiles' => $profileList,
                ]
            )
        );
    }

    private function showConfig(array $instanceData, $instanceId, $profileId)
    {
        // instanceId exists?
        // profileId exits?
        return new Response(400, [], 'not yet implemented');
    }

    private function getInfoUri(array $instanceData, $instanceId)
    {
        foreach ($instanceData['instances'] as $instanceConfig) {
            if ($instanceConfig['base_uri'] === $instanceId) {
                return sprintf('%sinfo.json', $instanceConfig['base_uri']);
            }
        }

        return false;
    }

    private function getInfo(array $instanceData, $instanceId)
    {
        $infoUri = $this->getInfoUri($instanceData, $instanceId);
        if (false === $infoUri) {
            throw new RuntimeException(sprintf('instance "%s" does not exist', $instanceId));
        }

        list($responseCode, $infoData) = $this->httpClient->get($infoUri);
        if (200 !== $responseCode) {
            throw new RuntimeException(sprintf('unable to fetch "%s"', $infoUri));
        }

        if (!array_key_exists('api', $infoData)) {
            throw new RuntimeException(sprintf('missing "api" key in data from "%s"', $infoUri));
        }

        if (!array_key_exists('http://eduvpn.org/api#2', $infoData['api'])) {
            throw new RuntimeException(sprintf('missing "http://eduvpn.org/api#2" key in data from "%s"', $infoUri));
        }

        return new Config($infoData['api']['http://eduvpn.org/api#2']);
    }

    private function getProfiles($profileListUri, $bearerToken)
    {
        list($responseCode, $responseData) = $this->httpClient->get($profileListUri, ['bearer' => $bearerToken]);

//        $responseData = $this->httpClient->get($profileListUri, ['bearer' => $bearerToken]);
        if (!array_key_exists('profile_list', $responseData)) {
            throw new RuntimeException(sprintf('missing "profile_list" key in data from "%s"', $profileListUri));
        }
        if (!array_key_exists('data', $responseData['profile_list'])) {
            throw new RuntimeException(sprintf('missing "profile_list/data" key in data from "%s"', $profileListUri));
        }

        return $responseData['profile_list']['data'];
    }
}
