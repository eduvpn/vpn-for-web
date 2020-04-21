#!/usr/bin/env php
<?php
/**
 *  Copyright (C) 2016 SURFnet.
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
require_once dirname(__DIR__).'/vendor/autoload.php';
$baseDir = dirname(__DIR__);

use fkooman\OAuth\Client\Http\CurlHttpClient;
use SURFnet\VPN\Web\Config;
use SURFnet\VPN\Web\ProviderListFetcher;

try {
    $config = new Config(require sprintf('%s/config/config.php', $baseDir));
    $discoveryUrlList = $config->get('Discovery')->keys();
    foreach ($discoveryUrlList as $discoveryUrl) {
        $publicKey = $config->get('Discovery')->get($discoveryUrl)->get('publicKey');
        $encodedDiscoveryUrl = preg_replace('/[^A-Za-z.]/', '_', $discoveryUrl);
        $providerListFetcher = new ProviderListFetcher(sprintf('%s/data/%s', $baseDir, $encodedDiscoveryUrl));
        $providerListFetcher->update(new CurlHttpClient(), $discoveryUrl, $publicKey);
    }
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
