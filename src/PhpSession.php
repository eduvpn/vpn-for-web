<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2020, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Web;

class PhpSession implements SessionInterface
{
    /**
     * @return bool
     */
    public function getForceTcp()
    {
        return isset($_SESSION['forceTcp']) ? $_SESSION['forceTcp'] : false;
    }

    /**
     * @param string $baseUri
     *
     * @return void
     */
    public function setSecureInternetBaseUri($baseUri)
    {
        $_SESSION['secure_internet'] = $baseUri;
    }

    /**
     * @param bool $forceTcp
     *
     * @return void
     */
    public function setForceTcp($forceTcp)
    {
        $_SESSION['forceTcp'] = $forceTcp;
    }

    /**
     * @return void
     */
    public function destroy()
    {
        session_destroy();
    }

    /**
     * @return array<string>
     */
    public function getMyInstituteAccessBaseUriList()
    {
        return isset($_SESSION['institute_access']) ? $_SESSION['institute_access'] : [];
    }

    /**
     * @return array<string>
     */
    public function getMyAlienBaseUriList()
    {
        return isset($_SESSION['alien']) ? $_SESSION['alien'] : [];
    }

    /**
     * @return string|null
     */
    public function getSecureInternetBaseUri()
    {
        return isset($_SESSION['secure_internet']) ? $_SESSION['secure_internet'] : null;
    }

    /**
     * @return string|null
     */
    public function getSecureInternetHomeBaseUri()
    {
        return isset($_SESSION['secure_internet_home']) ? $_SESSION['secure_internet_home'] : null;
    }

    /**
     * @param string $baseUri
     *
     * @return void
     */
    public function setSecureInternetHomeBaseUri($baseUri)
    {
        $_SESSION['secure_internet_home'] = $baseUri;
    }

    /**
     * @param string $baseUri
     *
     * @return void
     */
    public function setBaseUri($baseUri)
    {
        $_SESSION['_base_uri'] = $baseUri;
    }

    /**
     * @return string|null
     */
    public function getBaseUri()
    {
        return isset($_SESSION['_base_uri']) ? $_SESSION['_base_uri'] : null;
    }

    /**
     * @return void
     */
    public function removeBaseUri()
    {
        unset($_SESSION['_base_uri']);
    }

    /**
     * @param string $baseUri
     *
     * @return void
     */
    public function addInstituteAccessBaseUri($baseUri)
    {
        if (!\array_key_exists('institute_access', $_SESSION)) {
            $_SESSION['institute_access'] = [];
        }
        $_SESSION['institute_access'][] = $baseUri;
    }

    /**
     * @param string $baseUri
     *
     * @return void
     */
    public function addAlienBaseUri($baseUri)
    {
        if (!\array_key_exists('alien', $_SESSION)) {
            $_SESSION['alien'] = [];
        }
        $_SESSION['alien'][] = $baseUri;
    }

    /**
     * @param string $flowId
     *
     * @return void
     */
    public function setFlowId($flowId)
    {
        $_SESSION['flowId'] = $flowId;
    }

    /**
     * @return string
     */
    public function getFlowId()
    {
        return isset($_SESSION['flowId']) ? $_SESSION['flowId'] : 'modern_two_buttons';
    }
}
