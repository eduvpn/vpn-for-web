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

use SURFnet\VPN\ApiClient\Config;

class Session extends Config
{
    public function __construct($serverName, $requestRoot, $secureOnly)
    {
        session_set_cookie_params(0, $requestRoot, $serverName, $secureOnly, true);
        session_start();

        if (!isset($_SESSION['canary'])) {
            session_regenerate_id(true);
            $_SESSION['canary'] = time();
        }
        // Regenerate session ID every five minutes:
        if ($_SESSION['canary'] < time() - 300) {
            session_regenerate_id(true);
            $_SESSION['canary'] = time();
        }

        parent::__construct($_SESSION);
    }

    public function __set($key, $value)
    {
        $_SESSION[$key] = $value;
    }
}
