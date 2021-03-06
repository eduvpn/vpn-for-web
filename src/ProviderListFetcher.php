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
    private $dataDir;

    /**
     * @param string $dataDir
     */
    public function __construct($dataDir)
    {
        $this->dataDir = $dataDir;
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

        $localFile = $this->dataDir.'/'.basename($discoveryUrl);

        // check if we already have a file from a previous run
        $seq = 0;
        if (false !== $fileContent = @file_get_contents($localFile)) {
            // extract the "seq" field to see if we got a newer version
            $jsonData = self::jsonDecode($fileContent);
//            $seq = (int) $jsonData['seq'];
        }

        $discoveryData = $discoveryResponse->json();
        // XXX
//        if ($discoveryData['g'] < $seq) {
//            throw new RuntimeException('rollback, this is really unexpected!');
//        }

        // all fine, write file
        if (false === @file_put_contents($localFile, $discoveryBody)) {
            throw new RuntimeException(sprintf('unable to write file "%s"', $localFile));
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
