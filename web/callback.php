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
require_once sprintf('%s/vendor/autoload.php', dirname(__DIR__));

use fkooman\OAuth\Client\Http\CurlHttpClient;
use fkooman\OAuth\Client\OAuthClient;
use fkooman\OAuth\Client\Provider;
use fkooman\OAuth\Client\SessionTokenStorage;
use SURFnet\VPN\ApiClient\Config;
use SURFnet\VPN\ApiClient\Http\Request;
use SURFnet\VPN\ApiClient\Http\Response;

try {
    if ('' === session_id()) {
        session_start();
    }

    $config = new Config(require sprintf('%s/config/config.php', dirname(__DIR__)));

    $request = new Request($_SERVER, $_GET, $_POST);

    $oauthProvider = new Provider(
        $config->clientConfig->client_id,
        $config->clientConfig->client_secret,
        $config->clientConfig->authorize_endpoint,
        $config->clientConfig->token_endpoint
    );

    $oauthClient = new OAuthClient(
        $oauthProvider,
        new SessionTokenStorage(),
        new CurlHttpClient(['httpsOnly' => false])
    );
    $oauthClient->setUserId('N/A');

    $oauthClient->handleCallback(
        $_SESSION['_oauth2_session'], // URI from session
        $request->getQueryParameter('code'),
        $request->getQueryParameter('state')
    );
    unset($_SESSION['_oauth2_session']);

    $response = new Response(
        302,
        [
            'Location' => sprintf('%sindex.php', $request->getRootUri()),
        ]
    );
    $response->send();
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
