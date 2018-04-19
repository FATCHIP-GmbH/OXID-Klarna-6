<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
//namespace OxidEsales\TestingLibrary\helpers;

/**
 * Helper class for \OxidEsales\Eshop\Core\Utils
 * @codeCoverageIgnore
 */
class oxUtilsHelper extends \OxidEsales\Eshop\Core\Utils
{
    /** @var null Redirect url. */
    public static $sRedirectUrl = null;

    /** @var null Header Code. */
    public static $iCode = null;

    /** @var bool Should SEO engine be active during testing. */
    public static $sSeoIsActive = false;

    /** @var bool Should shop act as a search engine during testing. */
    public static $blIsSearchEngine = false;

    /** @var string Usually ajax response */
    public static $response;

    /**
     * Rewrites parent::redirect method.
     *
     * @param string $sUrl
     * @param bool $blAddRedirectParam
     * @param int $iHeaderCode
     *
     * @throws Exception
     */
    public function redirect($sUrl, $blAddRedirectParam = true, $iHeaderCode = 301)
    {
        self::$sRedirectUrl = $sUrl;
        self::$iCode = $iHeaderCode;
    }

    /**
     * Rewrites parent::seoIsActive method.
     *
     * @param bool $blReset
     * @param null $sShopId
     * @param null $iActLang
     *
     * @return bool
     */
    public function seoIsActive($blReset = false, $sShopId = null, $iActLang = null)
    {
        return self::$sSeoIsActive;
    }

    /**
     * Rewrites parent::isSearchEngine method.
     *
     * @param bool $blReset
     * @param null $sShopId
     * @param null $iActLang
     * @return bool
     */
    public function isSearchEngine($blReset = false, $sShopId = null, $iActLang = null)
    {
        return self::$blIsSearchEngine;
    }

    /**
     * @param string $sMsg
     * @return string
     */
    public function showMessageAndExit($sMsg = '')
    {
        return self::$response = $sMsg;
    }
}
