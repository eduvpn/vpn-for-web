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

class Service
{
    /** @var Config */
    private $config;

    /** @var TplInterface */
    private $tpl;

    /** @var \fkooman\OAuth\Client\OAuthClient */
    private $oauthClient;

    public function __construct(Config $config, TplInterface $tpl, OAuthClient $oauthClient)
    {
        $this->config = $config;
        $this->tpl = $tpl;
        $this->oauthClient = $oauthClient;
    }

    public function run(Request $request)
    {
        // only support GET requests
        if ('GET' !== $request->getMethod()) {
            return new Response(405, ['Allow' => 'GET']);
        }

        if (false === $oauthResponse = $this->oauthClient->get('config', 'https://labrat.eduvpn.nl/portal/api.php/user_info')) {
            return $this->getAccessToken($request);
        }

        return new Response(200, ['Content-Type' => 'application/json'], $oauthResponse->getBody());

//        return $response;

//        echo $response->getBody();
    }

    private function getAccessToken(Request $request)
    {
        $authorizationRequestUri = $this->oauthClient->getAuthorizeUri(
            'config',
            sprintf('%scallback.php', $request->getRootUri())
        );
        $_SESSION['_oauth2_session'] = $authorizationRequestUri;

        return new Response(302, ['Location' => $authorizationRequestUri]);
    }
}
