<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2020, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Web\Http;

class Response
{
    /** @var int */
    private $statusCode;

    /** @var array */
    private $headers;

    /** @var string */
    private $body;

    /**
     * @param int    $statusCode
     * @param string $body
     */
    public function __construct($statusCode = 200, array $headers = [], $body = '')
    {
        $this->statusCode = (int) $statusCode;
        $this->headers = $headers;
        $this->body = (string) $body;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * @return void
     */
    public function send()
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $k => $v) {
            header(sprintf('%s: %s', $k, $v));
        }
        echo $this->body;
    }
}
