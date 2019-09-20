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

namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\UtilsObject;
use TopConcepts\Klarna\Model\KlarnaCountryList;
use TopConcepts\Klarna\Model\KlarnaUser;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\CountryList;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\SystemComponentException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * Class KlarnaUtils
 * @package TopConcepts\Klarna\Core
 */
class KlarnaUtils
{
    /**
     * @param null $email
     * @return User
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public static function getFakeUser($email = null)
    {
        /** @var User | KlarnaUser $oUser */
        $oUser = oxNew(User::class);
        $oUser->loadByEmail($email);

        $sCountryISO = Registry::getSession()->getVariable('sCountryISO');
        if ($sCountryISO) {
            $oCountry   = oxNew(Country::class);
            $sCountryId = $oCountry->getIdByCode($sCountryISO);
            $oCountry->load($sCountryId);
            $oUser->oxuser__oxcountryid = new Field($sCountryId);
            $oUser->oxuser__oxcountry   = new Field($oCountry->oxcountry__oxtitle->value);
        }
        Registry::getConfig()->setUser($oUser);

        if ($email) {
            Registry::getSession()->setVariable('klarna_checkout_user_email', $email);
        }

        return $oUser;
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function getShopConfVar($name)
    {
        $config = Registry::getConfig();
        $shopId = $config->getShopId();

        return $config->getShopConfVar($name, $shopId, 'module:tcklarna');
    }

    /**
     * @param $sCountryId
     * @return mixed
     */
    public static function getCountryISO($sCountryId)
    {
        /** @var Country $oCountry */
        $oCountry = oxNew(Country::class);
        $oCountry->load($sCountryId);

        return $oCountry->getFieldData('oxisoalpha2');
    }

    /**
     * @return mixed
     */
    public static function getKlarnaModuleMode()
    {
        return self::getShopConfVar('sKlarnaActiveMode');
    }

    /**
     * @return bool
     */
    public static function isKlarnaPaymentsEnabled()
    {
        return self::getKlarnaModuleMode() === KlarnaConsts::MODULE_MODE_KP;
    }

    /**
     *
     */
    public static function isKlarnaCheckoutEnabled()
    {
        /** @var Payment $oPayment */
        $oPayment = oxNew(Payment::class);
        $oPayment->load('klarna_checkout');
        $klarnaActiveInOxid = $oPayment->oxpayments__oxactive->value == 1;
        $ssl                = Registry::getConfig()->getConfigParam('sSSLShopURL');

        return KlarnaUtils::getKlarnaModuleMode() === KlarnaConsts::MODULE_MODE_KCO && $klarnaActiveInOxid && isset($ssl);
    }

    /**
     * @param null $iLang
     * @return CountryList
     */
    public static function getActiveShopCountries($iLang = null)
    {
        /** @var CountryList $oCountryList */
        $oCountryList = oxNew(CountryList::class);
        $oCountryList->loadActiveCountries($iLang);

        return $oCountryList;
    }

    /**
     * @param null $sCountryISO
     * @return array|mixed
     */
    public static function getAPICredentials($sCountryISO = null)
    {
        if (!$sCountryISO) {
            $sCountryISO = Registry::getSession()->getVariable('sCountryISO');
        }

        if (!$aCredentials = KlarnaUtils::getShopConfVar('aKlarnaCreds_' . $sCountryISO)) {
            $aCredentials = array(
                'mid'      => KlarnaUtils::getShopConfVar('sKlarnaMerchantId'),
                'password' => KlarnaUtils::getShopConfVar('sKlarnaPassword'),
            );
        }

        return $aCredentials;
    }

    /**
     * @param $sCountryISO
     * @param bool $filterKcoList
     * @return bool
     */
    public static function isCountryActiveInKlarnaCheckout($sCountryISO, $filterKcoList = true)
    {
        if ($sCountryISO === null) {
            return true;
        }

        /** @var CountryList | \TopConcepts\Klarna\Model\KlarnaCountryList $activeKlarnaCountries */
        $activeKlarnaCountries = Registry::get(CountryList::class);
        $activeKlarnaCountries->loadActiveKlarnaCheckoutCountries($filterKcoList);
        if (!count($activeKlarnaCountries)) {
            return false;
        }
        foreach ($activeKlarnaCountries as $country) {
            if (strtoupper($sCountryISO) == $country->oxcountry__oxisoalpha2->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function isNonKlarnaCountryActive()
    {
        $activeNonKlarnaCountries = Registry::get(CountryList::class);
        $activeNonKlarnaCountries->loadActiveNonKlarnaCheckoutCountries();
        if (count($activeNonKlarnaCountries) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param int|null $iLang
     * @return CountryList|KlarnaCountryList
     */
    public static function getKlarnaGlobalActiveShopCountries($iLang = null)
    {
        $oCountryList = oxNew(CountryList::class);
        $oCountryList->loadActiveKlarnaCheckoutCountries($iLang);

        return $oCountryList;

    }

    /**
     * @return array
     *
     */
    public static function getKlarnaGlobalActiveShopCountryISOs($iLang = null)
    {
        $oCountryList = oxNew(CountryList::class);
        $oCountryList->loadActiveKlarnaCheckoutCountries($iLang);

        $result = array();
        foreach ($oCountryList as $country) {
            $result[] = $country->oxcountry__oxisoalpha2->value;
        }

        return $result;
    }

    /**
     * @param null $iLang
     * @return CountryList|KlarnaCountryList
     */
    public static function getAllActiveKCOGlobalCountryList($iLang = null)
    {
        $oCountryList = oxNew(CountryList::class);
        $oCountryList->loadActiveKCOGlobalCountries($iLang);

        return $oCountryList;
    }

    /**
     * @param BasketItem $oItem
     * @param $isOrderMgmt
     * @return array
     * @throws \oxArticleInputException
     * @throws \oxNoArticleException
     */
    public static function calculateOrderAmountsPricesAndTaxes($oItem, $isOrderMgmt)
    {
        $quantity           = self::parseFloatAsInt($oItem->getAmount());
        $regular_unit_price = 0;
        $basket_unit_price  = 0;

        if (!$oItem->isBundle()) {
            $regUnitPrice = $oItem->getRegularUnitPrice();
            if ($isOrderMgmt) {
                $unitPrice = $oItem->getArticle()->getUnitPrice();
            } else {
                $unitPrice = $oItem->getUnitPrice();
            }

            $regular_unit_price = self::parseFloatAsInt($regUnitPrice->getBruttoPrice() * 100);
            $basket_unit_price  = self::parseFloatAsInt($unitPrice->getBruttoPrice() * 100);
        }

        $total_discount_amount = ($regular_unit_price - $basket_unit_price) * $quantity;
        $total_amount          = $basket_unit_price * $quantity;

        if ($oItem->isBundle()) {
            $tax_rate = self::parseFloatAsInt($oItem->getUnitPrice()->getVat() * 100);
        } else {
            $tax_rate = self::parseFloatAsInt($oItem->getUnitPrice()->getVat() * 100);
        }
//        $total_tax_amount = self::parseFloatAsInt($oItem->getPrice()->getVatValue() * 100);
        $total_tax_amount = self::parseFloatAsInt(
            $total_amount - round($total_amount / ($tax_rate / 10000 + 1), 0)
        );

        $quantity_unit = 'pcs';

        return array($quantity, $regular_unit_price, $total_amount, $total_discount_amount, $tax_rate, $total_tax_amount, $quantity_unit);
    }

    /**
     * @param $number
     *
     * @return int
     */
    public static function parseFloatAsInt($number)
    {
        return (int)(Registry::getUtils()->fRound($number));
    }

    /**
     * @param Category $oCat
     * @param array $aCategories
     * @return array
     */
    public static function getSubCategoriesArray(Category $oCat, $aCategories = array())
    {
        $aCategories[] = $oCat->getTitle();

        if ($oParentCat = $oCat->getParentCategory()) {
            return self::getSubCategoriesArray($oParentCat, $aCategories);
        }

        return $aCategories;
    }

    /**
     * @param $sCountryISO
     * @return string
     */
    public static function resolveLocale($sCountryISO)
    {
        $lang = Registry::getLang()->getLanguageAbbr();
        Registry::getSession()->setVariable('klarna_iframe_lang', $lang);

        return strtolower($lang) . '-' . strtoupper($sCountryISO);
    }

    /**
     * @return bool
     */
    public static function is_ajax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower(getenv('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest'));
    }

    /**
     *
     */
    public static function fullyResetKlarnaSession()
    {
        Registry::getSession()->deleteVariable('paymentid');
        Registry::getSession()->deleteVariable('klarna_checkout_order_id');
        Registry::getSession()->deleteVariable('kp_order_id');
        Registry::getSession()->deleteVariable('amazonOrderReferenceId');
        Registry::getSession()->deleteVariable('klarna_checkout_user_email');
        Registry::getSession()->deleteVariable('externalCheckout');
        Registry::getSession()->deleteVariable('sAuthToken');
        Registry::getSession()->deleteVariable('klarna_session_data');
        Registry::getSession()->deleteVariable('finalizeRequired');
        Registry::getSession()->deleteVariable('sCountryISO');
        Registry::getSession()->deleteVariable('sFakeUserId');
    }

    /**
     * @param $text
     * @return string|null
     */
    public static function stripHtmlTags($text)
    {
        $result = preg_replace('/<(\/)?[a-z]+[^<]*>/', '', $text);

        return $result ?: null;
    }

    /**
     * @param $iso3
     * @return false|string
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public static function getCountryIso2fromIso3($iso3)
    {
        $sql = 'SELECT oxisoalpha2 FROM oxcountry WHERE oxisoalpha3 = ?';

        return DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getOne($sql, [$iso3]);
    }

    /**
     * @param $orderId
     * @return Order
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public static function loadOrderByKlarnaId($orderId)
    {
        $oOrder = oxNew(Order::class);
        $oxid   = DatabaseProvider::getDb()->getOne('SELECT oxid from oxorder where tcklarna_orderid=?', array($orderId));
        $oOrder->load($oxid);

        return $oOrder;
    }

    public static function registerKlarnaAckRequest($orderId)
    {
        $sql = 'INSERT INTO `tcklarna_ack` (`oxid`, `klreceived`, `tcklarna_orderid`) VALUES (?,?,?)';
        DatabaseProvider::getDb()->Execute(
            $sql,
            array(UtilsObject::getInstance()->generateUID(), date('Y-m-d H:i:s'), $orderId)
        );
    }

    public static function getKlarnaAckCount($orderId)
    {
        $sql = 'SELECT COUNT(*) FROM `tcklarna_ack` WHERE `tcklarna_orderid` = ?';

        return DatabaseProvider::getDb()->getOne($sql, array($orderId));
    }

    /**
     * @param $e \Exception
     */
    public static function logException($e) {
        if (method_exists(Registry::class, 'getLogger')) {
            Registry::getLogger()->error($e->getMessage(), [$e]);
        } else {
            $e->debugOut();
        }
    }
}
