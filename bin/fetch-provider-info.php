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
use SURFnet\VPN\Web\LogoFetcher;
use SURFnet\VPN\Web\ProviderListFetcher;
use SURFnet\VPN\Web\TwigTpl;

$preferredLanguage = 'en-US';

try {
    $config = new Config(require sprintf('%s/config/config.php', $baseDir));

    $discoveryUrlList = $config->get('Discovery')->keys();
    foreach ($discoveryUrlList as $discoveryUrl) {
        $publicKey = $config->get('Discovery')->get($discoveryUrl)->get('publicKey');
        $encodedDiscoveryUrl = preg_replace('/[^A-Za-z.]/', '_', $discoveryUrl); // XXX code duplication
        $providerListFetcher = new ProviderListFetcher(sprintf('%s/data/%s', $baseDir, $encodedDiscoveryUrl));
        $discoveryData = $providerListFetcher->update(new CurlHttpClient(), $discoveryUrl, $publicKey);

        $logoDir = sprintf('%s/data/logo', $baseDir);
        $logoFetcher = new LogoFetcher($logoDir, new CurlHttpClient());
        $hostNameList = [];
        foreach ($discoveryData['instances'] as $instance) {
            if (null === $hostName = parse_url($instance['base_uri'], PHP_URL_HOST)) {
                throw new RuntimeException('unable to extract hostname');
            }

            $logoInfo = $instance['logo'];
            if (is_string($logoInfo)) {
                $logoUri = $logoInfo;
            } else {
                if ($logoInfo->has($preferredLanguage)) {
                    $logoUri = $logoInfo->get($preferredLanguage);
                } else {
                    $logoUri = $logoInfo->get($logoInfo->keys()[0]);
                }
            }

            $logoFetcher->get($hostName, $logoUri);
            $hostNameList[] = ['hostName' => $hostName, 'encodedHostName' => preg_replace('/\./', '\.', $hostName)];
        }

        // generate CSS
        // Templates
        $templateDirs = [
            sprintf('%s/views', $baseDir),
            sprintf('%s/config/views', $baseDir),
        ];

        $tpl = new TwigTpl($templateDirs, null);
        $logoCssFile = sprintf('%s/%s.css', $logoDir, $encodedDiscoveryUrl);    // XXX strip json from encodedDiscoveryUrl
        if (false === @file_put_contents($logoCssFile, $tpl->render('logo-css', ['hostNameList' => $hostNameList]))) {
            throw new RuntimeException(sprintf('unable to write "%s"', $logoCssFile));
        }
    }
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
