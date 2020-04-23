<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2020, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Web;

interface TplInterface
{
    /**
     * @param string              $templateName
     * @param array<string,mixed> $templateVariables
     *
     * @return string
     */
    public function render($templateName, array $templateVariables);

    /**
     * @param array<string,mixed> $templateVariables
     *
     * @return void
     */
    public function addDefault(array $templateVariables);
}
