<?php
/**
 * Copyright 2018 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace TopConcepts\Klarna\Model;


use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopVersion;

/**
 * Class Klarna_oxArticle extends oxArticle class
 */
class KlarnaArticle extends KlarnaArticle_parent
{
    /**
     * Array of Klarna_PClass objects
     * @var array
     */
    protected $_aPClassList = null;

    /**
     * Show monthly cost?
     * @var bool
     */
    protected $_blShowMonthlyCost = null;

    /**
     * Check if article stock is good for expire check
     *
     * @return bool
     */
    public function isGoodStockForExpireCheck()
    {
        return (
            $this->getFieldData('oxstock') == 0
            && ($this->getFieldData('oxstockflag') == 1 || $this->getFieldData('oxstockflag') == 4)
        );
    }


    /**
     * Returning stock items by article number
     *
     * @param $sArtNum
     * @return object Article
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function klarna_loadByArtNum($sArtNum)
    {
        $sArticleTable = $this->getViewName();
        if (strlen($sArtNum) === 64) {
            $sArtNum   .= '%';
            $sSQL      = "SELECT art.oxid FROM {$sArticleTable} art WHERE art.OXACTIVE=1 AND art.OXARTNUM LIKE \"{$sArtNum}\"";
            $articleId = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getOne($sSQL);
        } else {
            if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization')) {
                $sSQL = "SELECT oxartid 
                            FROM tcklarna_anon_lookup 
                            JOIN {$sArticleTable} art
                            ON art.OXID=oxartid
                            WHERE art.OXACTIVE=1 AND tcklarna_artnum = ?";
            } else {
                $sSQL = "SELECT art.oxid FROM {$sArticleTable} art WHERE art.OXACTIVE=1 AND art.OXARTNUM = ?";
            }
            $articleId = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getOne($sSQL, array($sArtNum));
        }

        return $this->load($articleId);
    }


    /**
     * Return anonymized or regular product title
     *
     * @param null $counter
     * @param null $iOrderLang
     * @return mixed
     */
    public function tcklarna_getOrderArticleName($counter = null, $iOrderLang = null)
    {

        if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization')) {
            if ($iOrderLang) {
                $lang = strtoupper(Registry::getLang()->getLanguageAbbr($iOrderLang));
            } else {
                $lang = strtoupper(Registry::getLang()->getLanguageAbbr());
            }

            $name = KlarnaUtils::getShopConfVar('sKlarnaAnonymizedProductTitle_' . $lang);

            return html_entity_decode("$name $counter", ENT_QUOTES);
        }

        $name = $this->getFieldData('oxtitle');

        if (!$name && $parent = $this->getParentArticle()) {
            if ($iOrderLang) {
                $this->loadInLang($iOrderLang, $parent->getId());
            } else {
                $this->load($parent->getId());
            }
            $name = $this->getFieldData('oxtitle');
        }

        return html_entity_decode($name, ENT_QUOTES) ?: '(no title)';
    }

    /**
     * @return array
     */
    public function tcklarna_getArticleUrl()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaSendProductUrls') === true &&
            KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {

            $link = $this->getLink(null, true);

            $link = preg_replace('/\?.+/', '', $link);

            return $link ?: null;
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function tcklarna_getArticleImageUrl()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaSendImageUrls') === true &&
            KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {

            $link = $this->getPictureUrl();
        }

        return $link ?: null;
    }

    /**
     * @return null
     */
    public function tcklarna_getArticleEAN()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {
            $ean = $this->getFieldData('oxean');
        }

        return $ean ?: null;
    }

    /**
     * @return null
     */
    public function tcklarna_getArticleMPN()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {
            $mpn = $this->getFieldData('oxmpn');
        }

        return $mpn ?: null;
    }

    /**
     * @return string
     */
    public function tcklarna_getArticleCategoryPath()
    {
        $sCategories = null;
        if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {
            $oCat = $this->getCategory();

            if ($oCat) {
                $aCategories = KlarnaUtils::getSubCategoriesArray($oCat);
                $sCategories = html_entity_decode(implode(' > ', array_reverse($aCategories)), ENT_QUOTES);
            }

        }

        return $sCategories ?: null;
    }

    /**
     * @return string|null
     */
    public function tcklarna_getArticleManufacturer()
    {
        if (KlarnaUtils::getShopConfVar('blKlarnaEnableAnonymization') === false) {
            if (!$oManufacturer = $this->getManufacturer())
                return null;
        }

        return html_entity_decode($oManufacturer->getTitle(), ENT_QUOTES) ?: null;
    }

}
