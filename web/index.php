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

use SURFnet\VPN\ApiClient\Config;
use SURFnet\VPN\ApiClient\Http\Request;
use SURFnet\VPN\ApiClient\Http\Session;
use SURFnet\VPN\ApiClient\HttpClient\CurlHttpClient;
use SURFnet\VPN\ApiClient\Service;
use SURFnet\VPN\ApiClient\TwigTpl;

try {
    $config = new Config(require sprintf('%s/config/config.php', dirname(__DIR__)));

    // Template
    $templateDirs = [
        sprintf('%s/views', dirname(__DIR__)),
        sprintf('%s/config/views', dirname(__DIR__)),
    ];
    $tpl = new TwigTpl($templateDirs);

    // OAuth
    $oauthProvider = new \fkooman\OAuth\Client\Provider(
        $config->clientConfig->client_id,
        $config->clientConfig->client_secret,
        $config->clientConfig->authorize_endpoint,
        $config->clientConfig->token_endpoint
    );

    $oauthClient = new \fkooman\OAuth\Client\OAuth2Client(
        $oauthProvider,
        new \fkooman\OAuth\Client\CurlHttpClient()
    );

    $service = new Service(
        $config,
        new CurlHttpClient(),
        $tpl,
        new Session(),
        $oauthClient,
        new DateTime()
    );

    $request = new Request($_SERVER, $_GET, $_POST);
    $response = $service->run($request);
    $response->send();
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
