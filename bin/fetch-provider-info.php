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
use LC\Web\Config;
use LC\Web\ProviderListFetcher;

try {
    $config = new Config(require sprintf('%s/config/config.php', $baseDir));
    $discoveryUrlList = $config->get('Discovery')->keys();
    foreach ($discoveryUrlList as $discoveryUrl) {
        $encodedDiscoveryUrl = preg_replace('/[^A-Za-z.]/', '_', $discoveryUrl);
        $providerListFetcher = new ProviderListFetcher(sprintf('%s/data/%s', $baseDir, $encodedDiscoveryUrl));
        $providerListFetcher->update(new CurlHttpClient(), $discoveryUrl);
    }
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
