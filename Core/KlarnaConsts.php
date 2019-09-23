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


use OxidEsales\Eshop\Core\Registry;

/**
 * @codeCoverageIgnore
 * Class KlarnaConsts
 * @package TopConcepts\Klarna\Core
 */
class KlarnaConsts
{

    const MODULE_MODE_KCO = 'KCO';

    const MODULE_MODE_KP = 'KP';

    const EXTRA_CHECKBOX_NONE = 0;

    const EXTRA_CHECKBOX_CREATE_USER = 1;

    const EXTRA_CHECKBOX_SIGN_UP = 2;

    const EXTRA_CHECKBOX_CREATE_USER_SIGN_UP = 3;

    const NO_VALIDATION = 0;

    const VALIDATION_WITH_SUCCESS = 1;

    const VALIDATION_WITH_NO_ERROR = 2;

    const EMD_ORDER_HISTORY_ALL = 0;

    const EMD_ORDER_HISTORY_PAID = 1;

    const EMD_ORDER_HISTORY_NONE = 2;

    const KLARNA_PREFILL_NOTIF_URL = 'https://cdn.klarna.com/1.0/shared/content/legal/terms/%s/%s/checkout';

    const KLARNA_MANUAL_DOWNLOAD_LINK = 'https://www.cgrd.de/customer/klarna/docs/klarna-module-for-oxid-%s-%s.pdf';

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public static function getKlarnaGlobalCountries()
    {
        return array('AX', 'AL', 'DZ', 'AS', 'AD', 'AI', 'AQ', 'AG', 'AR', 'AM', 'AW', 'AU', 'AT', 'AZ', 'BS', 'BH',
                     'BD', 'BB', 'BE', 'BZ', 'BJ', 'BM', 'BT', 'BO', 'BQ', 'BW', 'BV', 'BR', 'IO', 'BN', 'BG', 'BF',
                     'KH', 'CM', 'CA', 'CV', 'KY', 'TD', 'CL', 'CN', 'CX', 'CC', 'CO', 'KM', 'CG', 'CK', 'CR', 'CI',
                     'HR', 'CU', 'CW', 'CY', 'CZ', 'DK', 'DJ', 'DM', 'DO', 'EC', 'EG', 'SV', 'GQ', 'EE', 'ET', 'FK',
                     'FO', 'FJ', 'FI', 'FR', 'GF', 'PF', 'TF', 'GA', 'GM', 'GE', 'DE', 'GH', 'GI', 'GR', 'GL', 'GD',
                     'GP', 'GU', 'GT', 'GG', 'HM', 'VA', 'HN', 'HK', 'HU', 'IS', 'IN', 'ID', 'IE', 'IM', 'IL', 'IT',
                     'JM', 'JP', 'JE', 'JO', 'KZ', 'KE', 'KI', 'KR', 'KW', 'KG', 'LV', 'LB', 'LS', 'LR', 'LI', 'LT',
                     'LU', 'MO', 'MK', 'MG', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MQ', 'MR', 'MU', 'YT', 'MX', 'FM',
                     'MD', 'MC', 'MN', 'ME', 'MS', 'MA', 'MZ', 'NA', 'NR', 'NP', 'NL', 'NC', 'NZ', 'NI', 'NE', 'NG',
                     'NU', 'NF', 'MP', 'NO', 'OM', 'PK', 'PW', 'PS', 'PA', 'PY', 'PE', 'PH', 'PN', 'PL', 'PT', 'PR',
                     'QA', 'RE', 'RO', 'RU', 'RW', 'BL', 'SH', 'KN', 'LC', 'MF', 'PM', 'VC', 'WS', 'SM', 'ST', 'SA',
                     'SN', 'RS', 'SC', 'SL', 'SG', 'SX', 'SK', 'SI', 'SB', 'ZA', 'GS', 'ES', 'LK', 'SR', 'SJ', 'SZ',
                     'SE', 'CH', 'TW', 'TJ', 'TZ', 'TH', 'TL', 'TG', 'TK', 'TO', 'TT', 'TN', 'TR', 'TM', 'TC', 'TV',
                     'AE', 'GB', 'US', 'UM', 'UY', 'UZ', 'VE', 'VN', 'VG', 'VI', 'WF', 'EH', 'ZM');
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public static function getKlarnaCoreCountries()
    {
        return array('SE', 'NO', 'FI', 'DE', 'AT', 'NL', 'GB', 'DK', 'CH');
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public static function getKlarnaKCOB2BCountries()
    {
        return array('SE', 'NO', 'FI');
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public static function getKlarnaKPB2BCountries()
    {
        return array('SE', 'NO', 'FI', 'DK', 'DE');
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public static function getCountry2CurrencyArray()
    {
        return array(
            'SE' => 'SEK',
            'NO' => 'NOK',
            'DK' => 'DKK',
            'DE' => 'EUR',
            'FI' => 'EUR',
            'NL' => 'EUR',
            'AT' => 'EUR',
            'GB' => 'GBP',
            'CH' => 'CHF',
            'BE' => 'EUR'
        );
    }

    /**
     * Override to add other possible payment methods
     * @codeCoverageIgnore
     * @return array
     */
    public static function getKlarnaExternalPaymentNames()
    {
        return array(
            'Nachnahme', 'Vorkasse', 'Amazon Payments', 'PayPal',
        );
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public static function getEmdPaymentTypeOptions()
    {
        return array(
            'other'          => Registry::getLang()->translateString('TCKLARNA_OTHER_PAYMENT'),
            'direct banking' => Registry::getLang()->translateString('TCKLARNA_DIRECT_BANKING'),
            'card'           => Registry::getLang()->translateString('TCKLARNA_CARD'),
        );
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public static function getFullHistoryOrdersOptions()
    {
        return array(
            self::EMD_ORDER_HISTORY_ALL  => Registry::getLang()->translateString('TCKLARNA_EMD_ORDER_HISTORY_ALL'),
            self::EMD_ORDER_HISTORY_PAID => Registry::getLang()->translateString('TCKLARNA_EMD_ORDER_HISTORY_PAID'),
            self::EMD_ORDER_HISTORY_NONE => Registry::getLang()->translateString('TCKLARNA_EMD_ORDER_HISTORY_NONE'),
        );
    }

    /**
     * @param null $key
     * @return array|mixed
     */
    public static function getFooterImgUrls($key = null)
    {
        $aFooterImgUrls = array(
            'longBlack'  => '//cdn.klarna.com/1.0/shared/image/generic/badge/%s/checkout/long-blue.png?width=440',
            'longWhite'  => '//cdn.klarna.com/1.0/shared/image/generic/badge/%s/checkout/long-white.png?width=440',
            'shortBlack' => '//cdn.klarna.com/1.0/shared/image/generic/badge/%s/checkout/short-blue.png?width=312',
            'shortWhite' => '//cdn.klarna.com/1.0/shared/image/generic/badge/%s/checkout/short-white.png?width=312',
            'logoBlack'  => '//cdn.klarna.com/1.0/shared/image/generic/logo/en_gb/basic/logo_black.png',
            'logoWhite'  => '//cdn.klarna.com/1.0/shared/image/generic/logo/en_gb/basic/logo_white.png',
        );

        if ($key)
            return $aFooterImgUrls[$key];
        else
            return $aFooterImgUrls;
    }

    /**
     * @param bool $default
     * @return mixed
     */
    public static function getLocale($default = false)
    {
        $oLang = Registry::getLang();

        $lang = $oLang->getLanguageAbbr();
        if ($default) {
            $langArray = $oLang->getLanguageArray();
            $lang      = $langArray[$oLang->getTplLanguage()]->abbr;
        }

        $defaultLocales = [
            'en' => 'en-GB',
            'nb' => 'nb-NO',
            'da' => 'da-DK',
            'de' => 'de-DE',
            'nl' => 'nl-NL',
            'fi' => 'fi-FI',
            'sv' => 'sv-SE',
            'at' => 'de-AT',
            'us' => 'en-US'
        ];

        $sCountryISO = Registry::getSession()->getVariable('sCountryISO');

        $locale = $lang.'-'.$sCountryISO;
        if($default || !$lang || !$sCountryISO)
        {
            $locale = isset($defaultLocales[$lang]) ? $defaultLocales[$lang] : 'en-GB';

            if($sCountryISO){
                $locale = isset($defaultLocales[strtolower($sCountryISO)]) ? $defaultLocales[strtolower($sCountryISO)] : 'en-GB';
            }

        }

        return $locale;
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public static function getDefaultBannerSrc()
    {
        return array(
            'de' => '<script src="https://embed.bannerflow.com/599d7ec18d988017005eb279?targeturl=https%3A//www.klarna.com&politeloading=off&merchantid={{merchantid}}&responsive=on" async></script>',
            'en' => '<script src="https://embed.bannerflow.com/599d7ec18d988017005eb27d?targeturl=https%3A//www.klarna.com&politeloading=off&merchantid={{merchantid}}&responsive=on" async></script>',
        );
    }

    /**
     * @codeCoverageIgnore
     * Override to change which countries are shown separately with a flag in the Klarna Checkout country popup.
     * Need to
     *
     * @return array
     */
    public static function getKlarnaPopUpFlagCountries()
    {
        return array('DE', 'AT', 'CH');
    }
}
