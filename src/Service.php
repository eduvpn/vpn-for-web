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

use fkooman\OAuth\Client\OAuthClient;
use SURFnet\VPN\ApiClient\Http\Exception\TokenException;
use SURFnet\VPN\ApiClient\Http\Request;
use SURFnet\VPN\ApiClient\Http\Response;
use SURFnet\VPN\ApiClient\Http\Session;

class Service
{
    /** @var TplInterface */
    private $tpl;

    /** @var Http\Session */
    private $session;

    /** @var \fkooman\OAuth\Client\OAuthClient */
    private $oauthClient;

    /** @var string */
    private $callbackUri;

    public function __construct(TplInterface $tpl, Session $session, OAuthClient $oauthClient)
    {
        $this->tpl = $tpl;
        $this->session = $session;
        $this->oauthClient = $oauthClient;
    }

    public function run(Request $request)
    {
        $requestScope = 'config';
        $callbackUri = sprintf('%scallback.php', $request->getRootUri());

        // only support GET requests
        if ('GET' !== $request->getMethod()) {
            return new Response(405, ['Allow' => 'GET']);
        }

        try {
            if (null === $instanceId = $request->getQueryParameter('instance_id')) {
                // no instance specified
                return $this->getInstanceList();
            }

            if (null === $profileId = $request->getQueryParameter('profile_id')) {
                // no profile specified
                return $this->getProfileList($requestScope, $instanceId);
            }

            // instance & profile specified, show all API call outputs
            return new Response(
                200,
                [],
                'Not Yet Implemented!'
            );
        } catch (TokenException $e) {
            // no valid OAuth token available...
            $authorizeUri = $this->oauthClient->getAuthorizeUri(
                $requestScope,
                $callbackUri
            );
            $this->session->set('_oauth2_session', $authorizeUri);

            return new Response(302, ['Location' => $authorizeUri]);
        }
    }

    private function getInstanceList()
    {
        $response = $this->oauthClient->get(null, 'https://static.eduvpn.nl/instances.json');
        if (!$response->isOkay()) {
            return new Response(500, [], 'unable to fetch instance list');
        }

        return new Response(
            200,
            [],
            $this->tpl->render('instances', $response->json())
        );
    }

    private function getProfileList($requestScope, $instanceId)
    {
        // instance specified, get user_info
        $userInfo = $this->get($requestScope, 'https://labrat.eduvpn.nl/portal/api.php/user_info')->json();
        $profileList = $this->get($requestScope, 'https://labrat.eduvpn.nl/portal/api.php/profile_list')->json();
        $systemMessages = $this->get($requestScope, 'https://labrat.eduvpn.nl/portal/api.php/system_messages')->json();
        $userMessages = $this->get($requestScope, 'https://labrat.eduvpn.nl/portal/api.php/user_messages')->json();

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

    private function get($requestScope, $requestUri)
    {
        if (false === $response = $this->oauthClient->get($requestScope, $requestUri)) {
            throw new TokenException('no token available');
        }

        return $response;
    }
}
