<?php
/**
 * Copyright 2017 François Kooman <fkooman@tuxed.net>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace SURFnet\VPN\ApiClient;

use fkooman\OAuth\Client\Http\HttpClientInterface;
use fkooman\OAuth\Client\Http\Request;
use Imagick;
use ImagickException;
use RuntimeException;
use SURFnet\VPN\ApiClient\Exception\LogoException;

class LogoFetcher
{
    /** @var string */
    private $logoDir;

    /** @var \fkooman\OAuth\Client\Http\HttpClientInterface */
    private $httpClient;

    public function __construct($logoDir, HttpClientInterface $httpClient)
    {
        if (!@file_exists($logoDir)) {
            if (false === @mkdir($logoDir, 0711, true)) {
                throw new RuntimeException(sprintf('unable to create folder "%s"', $logoDir));
            }
        }
        $this->logoDir = $logoDir;
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $logoUrl
     */
    public function get($fileName, $logoUrl)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'logo');
        $logoData = $this->obtainLogo($logoUrl);

        if (false === @file_put_contents($tmpFile, $logoData)) {
            throw new RuntimeException(sprintf('unable to write to "%s"', $tmpFile));
        }

        $optimizedLogoData = self::optimize($tmpFile);
        @unlink($tmpFile);
        $optimizedFileName = sprintf('%s/%s.png', $this->logoDir, $fileName);
        if (false === @file_put_contents($optimizedFileName, $optimizedLogoData)) {
            throw new RuntimeException(sprintf('unable to write to "%s"', $optimizedFileName));
        }
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private static function optimize($fileName)
    {
        try {
            $imagick = new Imagick($fileName);
            $imagick->setimagebackgroundcolor('transparent');
            $imagick->thumbnailimage(64, 48, true, true);
            $imagick->setimageformat('png');
            $optimizedData = $imagick->getimageblob();
            $imagick->destroy();

            return $optimizedData;
        } catch (ImagickException $e) {
            throw new LogoException(sprintf('unable to convert logo (%s)', $e->getMessage()));
        }
    }

    /**
     * @return string
     */
    private function obtainLogo($logoUrl)
    {
        // logoUrl MUST be a valid URL now
        if (false === filter_var($logoUrl, FILTER_VALIDATE_URL)) {
            throw new LogoException(sprintf('"%s" is an invalid URI', $logoUrl));
        }

        // try to get the logo and content-type
        try {
            $clientResponse = $this->httpClient->send(Request::get($logoUrl));
            if (!$clientResponse->isOkay()) {
                throw new LogoException(sprintf('got a HTTP %d response from HTTP request to "%s" ', $clientResponse->getStatusCode(), $logoUrl));
            }

            return $clientResponse->getBody();
        } catch (RuntimeException $e) {
            throw new LogoException(sprintf('unable to retrieve logo: "%s"', $e->getMessage()));
        }
    }
}
