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

namespace SURFnet\VPN\ApiClient\HttpClient;

use RuntimeException;

class CurlHttpClient implements HttpClientInterface
{
    /** @var resource */
    private $curlChannel;

    public function __construct()
    {
        if (false === $this->curlChannel = curl_init()) {
            throw new RuntimeException('unable to create cURL channel');
        }
    }

    public function __destruct()
    {
        curl_close($this->curlChannel);
    }

    public function get($requestUri, array $options = [])
    {
        return $this->exec(
            [
                CURLOPT_URL => $requestUri,
            ],
            $options
        );
    }

    public function post($requestUri, array $postData = [], array $options = [])
    {
        return $this->exec(
            [
                CURLOPT_URL => $requestUri,
                CURLOPT_POSTFIELDS => http_build_query($postData),
            ],
            $options
        );
    }

    private function exec(array $curlOptions, array $options)
    {
        // reset all cURL options
        $this->curlReset();

        $decodeJson = array_key_exists('json', $options) ? $options['json'] : true;
        $bearerToken = array_key_exists('bearer', $options) ? $options['bearer'] : false;

        $defaultCurlOptions = [
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ];

        if ($bearerToken) {
            $defaultCurlOptions += [
                CURLOPT_HTTPHEADER => [sprintf('Authorization: Bearer %s', $bearerToken)],
            ];
        }

        if (false === curl_setopt_array($this->curlChannel, $curlOptions + $defaultCurlOptions)) {
            throw new RuntimeException('unable to set cURL options');
        }

        if (false === $responseData = curl_exec($this->curlChannel)) {
            $curlError = curl_error($this->curlChannel);
            throw new RuntimeException(sprintf('failure performing the HTTP request: "%s"', $curlError));
        }

        return [
            curl_getinfo($this->curlChannel, CURLINFO_HTTP_CODE),
            $decodeJson ? json_decode($responseData, true) : $responseData,
        ];
    }

    private function curlReset()
    {
        // requires PHP >= 5.5 for curl_reset
        if (function_exists('curl_reset')) {
            curl_reset($this->curlChannel);

            return;
        }

        // reset the request method to GET, that is enough to allow for
        // multiple requests using the same cURL channel
        if (false === curl_setopt($this->curlChannel, CURLOPT_HTTPGET, true)) {
            throw new RuntimeException('unable to set cURL options');
        }
    }
}
