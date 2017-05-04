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

use SURFnet\VPN\ApiClient\Http\Exception\SessionException;

class Session
{
    public function __construct()
    {
        if ('' === session_id()) {
            session_start();
        }
    }

    public function get($key)
    {
        if (!array_key_exists($key, $_SESSION)) {
            throw new SessionException(sprintf('key "%s" not found in session', $key));
        }

        return $_SESSION[$key];
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function del($key)
    {
        if (!array_key_exists($key, $_SESSION)) {
            throw new SessionException(sprintf('key "%s" not found in session', $key));
        }
        unset($_SESSION[$key]);
    }
}
