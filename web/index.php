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
use LC\Web\PhpSession;
use LC\Web\Service;
use LC\Web\Tpl;

session_start();

$tpl = new Tpl(
    [
        $baseDir.'/views',
        $baseDir.'/config/views',
    ]
);

try {
    $request = new Request($_SERVER, $_GET, $_POST);
    $config = new Config(require $baseDir.'/config/config.php');
    $dataDir = $baseDir.'/data';
    $tpl->addDefault(
        [
            'rootUri' => $request->getRootUri(),
        ]
    );

    $httpClient = new CurlHttpClient();
    // OAuth client
    $oauthClient = new OAuthClient(
        new SessionTokenStorage(),
        $httpClient
    );

    $service = new Service(
        new PhpSession(),
        $config,
        $tpl,
        $oauthClient,
        $httpClient,
        $dataDir
    );
    $service->run($request)->send();
} catch (Exception $e) {
    $tpl->render('error', ['errorCode' => 500, 'errorMessage' => $e->getMessage()]);
}
