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
use fkooman\OAuth\Client\SessionTokenStorage;
use SURFnet\VPN\Web\Config;
use SURFnet\VPN\Web\Http\Request;
use SURFnet\VPN\Web\Service;
use SURFnet\VPN\Web\TwigTpl;

// XXX move this into a separate class!
if ('' === session_id()) {
    session_start();
}

try {
    $config = new Config(require sprintf('%s/config/config.php', dirname(__DIR__)));
    $dataDir = sprintf('%s/data', dirname(__DIR__));

    // Templates
    $templateDirs = [
        sprintf('%s/views', dirname(__DIR__)),
        sprintf('%s/config/views', dirname(__DIR__)),
    ];

    $tpl = new TwigTpl(
        $templateDirs,
        $config->get('TemplateCache') ? sprintf('%s/tpl', $dataDir) : null
    );

    $httpClient = new CurlHttpClient();

    // OAuth client
    $oauthClient = new OAuthClient(
        new SessionTokenStorage(),
        $httpClient
    );
    // we store tokens in session, so no need to bind it to a user
    $oauthClient->setUserId('N/A');

    $service = new Service(
        $config,
        $tpl,
        $oauthClient,
        $httpClient,
        $dataDir
    );
    $response = $service->run(
        new Request($_SERVER, $_GET, $_POST)
    );
    $response->send();
} catch (Exception $e) {
    echo sprintf('ERROR (%s): %s', get_class($e), $e->getMessage());
    exit(1);
}
