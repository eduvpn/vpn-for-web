<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2020, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Web\Http;

class RedirectResponse extends Response
{
    /**
     * @param string $locationUrl
     * @param int    $statusCode
     */
    public function __construct($locationUrl, $statusCode = 302)
    {
        parent::__construct($statusCode, ['Location' => $locationUrl]);
    }
}
