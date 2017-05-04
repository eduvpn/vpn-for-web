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

    public function __construct(TplInterface $tpl, Session $session, OAuthClient $oauthClient)
    {
        $this->tpl = $tpl;
        $this->session = $session;
        $this->oauthClient = $oauthClient;
    }

    public function run(Request $request)
    {
        // only support GET requests
        if ('GET' !== $request->getMethod()) {
            return new Response(405, ['Allow' => 'GET']);
        }

        return $this->getInstanceList();
//        if (false === $oauthResponse = $this->oauthClient->get('config', 'https://labrat.eduvpn.nl/portal/api.php/user_info')) {
//            return $this->getAccessToken($request);
//        }

//        return new Response(200, ['Content-Type' => 'application/json'], $oauthResponse->getBody());
    }

    private function getInstanceList()
    {
        $response = $this->oauthClient->get(null, 'https://static.eduvpn.nl/instances.json');
        if (!$response->isOkay()) {
            return new Response(500, [], 'unable to fetch instance list');
        }

//        return new Response(200, ['Content-Type' => 'application/json'], $response->getBody());
        return new Response(
            200,
            [],
            $this->tpl->render('instances', $response->json())
        );
    }

    private function getAccessToken(Request $request)
    {
        $authorizationRequestUri = $this->oauthClient->getAuthorizeUri(
            'config',
            sprintf('%scallback.php', $request->getRootUri())
        );
        $this->session->set('_oauth2_session', $authorizationRequestUri);

        return new Response(302, ['Location' => $authorizationRequestUri]);
    }
}
