<?php

namespace TopConcepts\Klarna\Core;


use TopConcepts\Klarna\Models\KlarnaCountryList;
use TopConcepts\Klarna\Models\KlarnaUser;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\CountryList;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\ResultSet;
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

        Registry::getConfig()->setUser($oUser);

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

        return $config->getShopConfVar($name, $shopId, 'klarna');
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
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public static function getKlarnaAllowedExternalPayments()
    {
        $result      = array();
        $db          = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sql         = 'SELECT oxid FROM oxpayments WHERE OXACTIVE=1 AND KLEXTERNALPAYMENT=1';
        /** @var ResultSet $oRs */
        $oRs = $db->select($sql);
        foreach ($oRs->getIterator() as $payment) {
            $result[] = $payment['oxid'];
        }

        return $result;
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
     * @return bool
     * @throws SystemComponentException
     */
    public static function isCountryActiveInKlarnaCheckout($sCountryISO)
    {
        if ($sCountryISO === null) {
            return true;
        }

        /** @var CountryList | \TopConcepts\Klarna\Models\KlarnaCountryList $activeKlarnaCountries */
        $activeKlarnaCountries = oxNew(CountryList::class);
        $activeKlarnaCountries->loadActiveKlarnaCheckoutCountries();
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
        $activeNonKlarnaCountries = oxNew(CountryList::class);
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
     * @return bool
     * @throws SystemComponentException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public static function isKlarnaExternalPaymentMethod()
    {
        if (
            in_array(Registry::getSession()->getBasket()->getPaymentId(), self::getKlarnaAllowedExternalPayments()) &&
            KlarnaUtils::isCountryActiveInKlarnaCheckout(Registry::getSession()->getVariable('sCountryISO'))
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param $oItem
     * @return array
     */
    public static function calculateOrderAmountsPricesAndTaxes($oItem)
    {
        $quantity = self::parseFloatAsInt($oItem->getAmount());
        if ($oItem->isBundle()) {
            $regular_unit_price = 0;
            $basket_unit_price  = 0;
        } else {
            $regUnitPrice       = $oItem->getRegularUnitPrice();
            $unitPrice          = $oItem->getUnitPrice();
            $regular_unit_price = self::parseFloatAsInt($regUnitPrice->getBruttoPrice() * 100);
            $basket_unit_price  = self::parseFloatAsInt($unitPrice->getBruttoPrice() * 100);
        }

        if ($regular_unit_price === $basket_unit_price) {
            $total_amount          = $basket_unit_price * $quantity;
            $total_discount_amount = 0;
        } else {
            $unit_price_diff       = $regular_unit_price - $basket_unit_price;
            $total_discount_amount = $unit_price_diff * $quantity;
            $total_amount          = $basket_unit_price * $quantity;
        }

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
//        Registry::getSession()->deleteVariable('deladrid');
        Registry::getSession()->deleteVariable('externalCheckout');
        Registry::getSession()->deleteVariable('sFakeUserId');
        Registry::getSession()->deleteVariable('sAuthToken');
        Registry::getSession()->deleteVariable('klarna_session_data');
        Registry::getSession()->deleteVariable('finalizeRequired');
        Registry::getSession()->deleteVariable('sCountryISO');
//        Registry::getSession()->setVariable('blshowshipaddress', 0);
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
}
