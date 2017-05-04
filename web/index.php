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
use SURFnet\VPN\ApiClient\Http\Session;
use SURFnet\VPN\ApiClient\Service;
use SURFnet\VPN\ApiClient\TwigTpl;

try {
    if ('' === session_id()) {
        session_start();
    }

    $config = new Config(require sprintf('%s/config/config.php', dirname(__DIR__)));

    // Templates
    $templateDirs = [
        sprintf('%s/views', dirname(__DIR__)),
        sprintf('%s/config/views', dirname(__DIR__)),
    ];

    $dataDir = sprintf('%s/data', dirname(__DIR__));
    $templateCache = null;
    if ($config->get('enableTemplateCache')) {
        $templateCache = sprintf('%s/tpl', $dataDir);
    }
    $tpl = new TwigTpl($templateDirs, $templateCache);

    // OAuth
    $oauthProvider = new Provider(
        $config->get('clientConfig')->get('client_id'),
        $config->get('clientConfig')->get('client_secret'),
        $config->get('clientConfig')->get('authorize_endpoint'),
        $config->get('clientConfig')->get('token_endpoint')
    );

    $oauthClient = new OAuthClient(
        $oauthProvider,
        new SessionTokenStorage(),
        new CurlHttpClient(['httpsOnly' => false])
    );
    $oauthClient->setUserId('N/A');

    $session = new Session();
    $request = new Request($_SERVER, $_GET, $_POST);
    $service = new Service(
        $tpl,
        $session,
        $oauthClient
    );
    $response = $service->run($request);
    $response->send();
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
