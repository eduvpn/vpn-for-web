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

namespace SURFnet\VPN\ApiClient\Http;

class Request
{
    /** @var array */
    private $serverData;

    /** @var array */
    private $getData;

    /** @var array */
    private $postData;

    /**
     * @param array $serverData
     * @param array $getData
     * @param array $postData
     */
    public function __construct(array $serverData, array $getData, array $postData)
    {
        $this->serverData = $serverData;
        $this->getData = $getData;
        $this->postData = $postData;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->serverData['REQUEST_METHOD'];
    }

    public function getServerName()
    {
        return $this->serverData['SERVER_NAME'];
    }

    /**
     * @return array
     */
    public function getQueryParameters()
    {
        return $this->getData;
    }

    public function getQueryParameter($key)
    {
        return array_key_exists($key, $this->getData) ? $this->getData[$key] : null;
    }

    /**
     * @return array
     */
    public function getPostParameters()
    {
        return $this->postData;
    }

    /**
     * @return string|null
     */
    public function getHeader($key)
    {
        return array_key_exists($key, $this->serverData) ? $this->serverData[$key] : null;
    }

    /**
     * @return string
     */
    public function getAuthority()
    {
        $requestScheme = array_key_exists('REQUEST_SCHEME', $this->serverData) ? $this->serverData['REQUEST_SCHEME'] : 'http';
        $serverName = $this->serverData['SERVER_NAME'];
        $serverPort = (int) $this->serverData['SERVER_PORT'];
        if (('https' === $requestScheme && 443 !== $serverPort) || ('http' === $requestScheme && 80 !== $serverPort)) {
            return sprintf('%s://%s:%d', $requestScheme, $serverName, $serverPort);
        }

        return sprintf('%s://%s', $requestScheme, $serverName);
    }

    /**
     * @return string
     */
    public function getRoot()
    {
        $rootDir = dirname($this->serverData['SCRIPT_NAME']);
        if ('/' !== $rootDir) {
            return sprintf('%s/', $rootDir);
        }

        return $rootDir;
    }

    /**
     * @return string
     */
    public function getRootUri()
    {
        return sprintf('%s%s', $this->getAuthority(), $this->getRoot());
    }
}
