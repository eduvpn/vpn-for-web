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
require_once sprintf('%s/vendor/autoload.php', dirname(__DIR__));

use fkooman\OAuth\Client\Http\CurlHttpClient;
use SURFnet\VPN\ApiClient\Config;
use SURFnet\VPN\ApiClient\LogoFetcher;
use SURFnet\VPN\ApiClient\ProviderListFetcher;
use SURFnet\VPN\ApiClient\TwigTpl;

try {
    $config = new Config(require sprintf('%s/config/config.php', dirname(__DIR__)));

    $providerListUri = $config->get('providerListUri');
    $providerListPublicKey = $config->get('providerListPublicKey');

    $providerListFetcher = new ProviderListFetcher(sprintf('%s/data/provider_list.json', dirname(__DIR__)));
    $discoveryData = $providerListFetcher->update(new CurlHttpClient(), $providerListUri, $providerListPublicKey);

    $logoDir = sprintf('%s/data/logo', dirname(__DIR__));
    $logoFetcher = new LogoFetcher($logoDir, new CurlHttpClient());
    $hostNameList = [];
    foreach ($discoveryData['instances'] as $instance) {
        if (false === $hostName = parse_url($instance['base_uri'], PHP_URL_HOST)) {
            throw new RuntimeException('unable to extract hostname');
        }
        $logoFetcher->get($hostName, $instance['logo_uri']);
        $hostNameList[] = ['hostName' => $hostName, 'encodedHostName' => preg_replace('/\./', '\.', $hostName)];
    }

    // generate CSS
    // Templates
    $templateDirs = [
        sprintf('%s/views', dirname(__DIR__)),
        sprintf('%s/config/views', dirname(__DIR__)),
    ];

    $tpl = new TwigTpl($templateDirs, null);
    $logoCssFile = sprintf('%s/logo.css', $logoDir);
    if (false === @file_put_contents($logoCssFile, $tpl->render('logo-css', ['hostNameList' => $hostNameList]))) {
        throw new RuntimeException(sprintf('unable to write "%s"', $logoCssFile));
    }
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
