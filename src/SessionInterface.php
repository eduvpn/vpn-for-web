<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2020, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Web;

interface SessionInterface
{
    /**
     * @return void
     */
    public function destroy();

    /**
     * @return bool
     */
    public function getForceTcp();

    /**
     * @param string $baseUri
     *
     * @return void
     */
    public function setSecureInternetBaseUri($baseUri);

    /**
     * @param bool $forceTcp
     *
     * @return void
     */
    public function setForceTcp($forceTcp);

    /**
     * @return array<string>
     */
    public function getMyInstituteAccessBaseUriList();

    /**
     * @return array<string>
     */
    public function getMyAlienBaseUriList();

    /**
     * @return string|null
     */
    public function getSecureInternetBaseUri();

    /**
     * @return string|null
     */
    public function getSecureInternetHomeBaseUri();

    /**
     * @param string $baseUri
     *
     * @return void
     */
    public function setSecureInternetHomeBaseUri($baseUri);

    /**
     * @param string $baseUri
     *
     * @return void
     */
    public function setBaseUri($baseUri);

    /**
     * @return string|null
     */
    public function getBaseUri();

    /**
     * @return void
     */
    public function removeBaseUri();

    /**
     * @param string $baseUri
     *
     * @return void
     */
    public function addInstituteAccessBaseUri($baseUri);

    /**
     * @param string $baseUri
     *
     * @return void
     */
    public function addAlienBaseUri($baseUri);

    /**
     * @return string
     */
    public function getFlowId();

    /**
     * @param string $flowId
     *
     * @return void
     */
    public function setFlowId($flowId);
}
