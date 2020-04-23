<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2020, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

require_once dirname(__DIR__).'/vendor/autoload.php';
$baseDir = dirname(__DIR__);

use fkooman\OAuth\Client\Http\CurlHttpClient;
use fkooman\OAuth\Client\OAuthClient;
use fkooman\OAuth\Client\SessionTokenStorage;
use LC\Web\Config;
use LC\Web\Http\Request;
use LC\Web\Service;
use LC\Web\Tpl;

session_start();

try {
    $config = new Config(require sprintf('%s/config/config.php', $baseDir));
    $dataDir = sprintf('%s/data', $baseDir);
    $tpl = new Tpl(
        [
            sprintf('%s/views', $baseDir),
            sprintf('%s/config/views', $baseDir),
        ]
    );

    $httpClient = new CurlHttpClient();
    // OAuth client
    $oauthClient = new OAuthClient(
        new SessionTokenStorage(),
        $httpClient
    );

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
    echo sprintf('ERROR (%s): %s %s', get_class($e), $e->getMessage(), $e->getTraceAsString());
    exit(1);
}
