<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2020, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Web;

use fkooman\OAuth\Client\Http\HttpClientInterface;
use fkooman\OAuth\Client\Http\Request;
use RuntimeException;

class ProviderListFetcher
{
    /** @var string */
    private $filePath;

    /**
     * @param string $filePath
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @param string $discoveryUrl
     *
     * @return array
     */
    public function update(HttpClientInterface $httpClient, $discoveryUrl)
    {
        $discoveryResponse = $this->httpGet($httpClient, $discoveryUrl);
        $discoveryBody = $discoveryResponse->getBody();

        // check if we already have a file from a previous run
        $seq = 0;
        if (false !== $fileContent = @file_get_contents($this->filePath)) {
            // extract the "seq" field to see if we got a newer version
            $jsonData = self::jsonDecode($fileContent);
            $seq = (int) $jsonData['seq'];
        }

        $discoveryData = $discoveryResponse->json();
        if ($discoveryData['seq'] < $seq) {
            throw new RuntimeException('rollback, this is really unexpected!');
        }

        // all fine, write file
        if (false === @file_put_contents($this->filePath, $discoveryBody)) {
            throw new RuntimeException(sprintf('unable to write file "%s"', $this->filePath));
        }

        return $discoveryData;
    }

    /**
     * @param string $requestUrl
     *
     * @return \fkooman\OAuth\Client\Http\Response
     */
    private function httpGet(HttpClientInterface $httpClient, $requestUrl)
    {
        $httpResponse = $httpClient->send(Request::get($requestUrl));
        if (!$httpResponse->isOkay()) {
            throw new RuntimeException(sprintf('unable to fetch "%s"', $requestUrl));
        }

        return $httpResponse;
    }

    /**
     * @param string $jsonText
     *
     * @return array
     */
    private static function jsonDecode($jsonText)
    {
        if (null === $jsonData = json_decode($jsonText, true)) {
            throw new RuntimeException('unable to decode JSON');
        }

        return $jsonData;
    }
}
