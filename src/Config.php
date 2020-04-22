<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2020, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Web;

use SURFnet\VPN\Web\Exception\ConfigException;

class Config
{
    /** @var array */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return \array_key_exists($key, $this->data);
    }

    /**
     * @return array
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (!\array_key_exists($key, $this->data)) {
            // consumers MUST check first if a field is available before
            // requesting it
            throw new ConfigException(sprintf('missing field "%s" in configuration', $key));
        }

        if (\is_array($this->data[$key])) {
            // if all we get is a "flat" array with sequential numeric keys
            // return the array instead of an object
            $k = array_keys($this->data[$key]);
            if ($k === range(0, \count($k) - 1)) {
                return $this->data[$key];
            }

            return new self($this->data[$key]);
        }

        return $this->data[$key];
    }
}
