<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 26.03.2018
 * Time: 18:19
 */

namespace TopConcepts\Klarna\Testes\Unit\Controllers;


use OxidEsales\Eshop\Application\Model\CountryList;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ViewConfig;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaViewConfigTest extends ModuleUnitTestCase
{

    public function testAddBuyNow()
    {
        $this->getConfig()->saveShopConfVar(null, 'blKlarnaDisplayBuyNow', true);

        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->addBuyNow();

        $this->assertEquals($result, $this->getConfigParam('blKlarnaDisplayBuyNow'));
    }

    public function isKPEnabledDataProvider()
    {
        return [
            ['KCO', false],
            ['KP', true]
        ];
    }

    /**
     * @dataProvider isKPEnabledDataProvider
     * @param $mode
     * @param $expectedResult
     */
    public function testIsKlarnaPaymentsEnabled($mode, $expectedResult)
    {
        $this->setModuleMode($mode);

        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->isKlarnaPaymentsEnabled();
        $this->assertEquals($expectedResult, $result);
    }

    public function isKarnaExternalPaymentDataProvider()
    {
        return [
            ['oxidcashondel', 'DE', true],
            ['oxidcashondel', 'AF', false],
            ['oxidpayadvance', 'DE',  false]
        ];
    }

    /**
     * @dataProvider isKarnaExternalPaymentDataProvider
     * @param $paymentId
     * @param $country
     * @param $expectedResult
     */
    public function testIsKlarnaExternalPayment($paymentId, $country, $expectedResult)
    {
        $this->setupKlarnaExternals();
        Registry::getSession()->getBasket()->setPayment($paymentId);
        $this->setSessionParam('sCountryISO', $country);

        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->isKlarnaExternalPayment();
        $this->assertEquals($result, $expectedResult);
    }

    public function isATDataProvider()
    {
        return [
            ['AT', 'a7c40f6320aeb2ec2.72885259', true],
            ['DE', 'a7c40f631fc920687.20179984', false]
        ];
    }

    /**
     * @dataProvider isATDataProvider
     * @param $iso
     * @param $oxCountryId
     * @param $result
     */
    public function testGetIsAustria($iso, $oxCountryId, $result)
    {
        $user = $this->getMock(User::class, ['getFieldData']);
        $user->expects($this->any())->method('getFieldData')->with('oxcountryid')->willReturn($oxCountryId);
        $oViewConfig = $this->getMock(ViewConfig::class, ['getUser']);
        $oViewConfig->expects($this->once())->method('getUser')->willReturn($user);
        $this->assertEquals($result, $oViewConfig->getIsAustria());

    }

    public function getKlarnaHomepageBannerDataProvider()
    {
        return [
            [true, 'mid'],
            [false, 'mid2']
        ];
    }

    public function isDEDataProvider()
    {
        return [
            ['a7c40f6320aeb2ec2.72885259', false],
            ['a7c40f631fc920687.20179984', true],
        ];
    }

    /**
     * @dataProvider isDEDataProvider
     * @param $mode
     * @param $oxCountryId
     * @param $expectedResult
     */
    public function testGetIsGermany($oxCountryId, $expectedResult)
    {
        $user = $this->getMock(User::class, ['getFieldData']);
        $user->expects($this->any())->method('getFieldData')->with('oxcountryid')->willReturn($oxCountryId);
        $oViewConfig = $this->getMock(ViewConfig::class, ['getUser']);
        $oViewConfig->expects($this->once())->method('getUser')->willReturn($user);
        $this->assertEquals($expectedResult, $oViewConfig->getIsGermany());
    }

    /**
     * @dataProvider getKlarnaHomepageBannerDataProvider
     * @param $displayBanner
     * @param $merchantId
     */
    public function testGetKlarnaHomepageBanner($displayBanner, $merchantId)
    {

        $this->getConfig()->saveShopConfVar(null, 'blKlarnaDisplayBanner', $displayBanner, $shopId = $this->getShopId(), $module = 'klarna');
        $this->getConfig()->saveShopConfVar(null, 'sKlarnaMerchantId', $merchantId, $shopId = $this->getShopId(), $module = 'klarna');

        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->getKlarnaHomepageBanner();

        if($displayBanner){
            $this->assertContains($merchantId, $result);
        } else {
            $this->assertEquals(null, $result);
        }

    }

    public function showCheckoutTermsDataProvider()
    {
        return [
            ['a7c40f6320aeb2ec2.72885259', true, true, true],  //AT
            ['a7c40f6320aeb2ec2.72885259', true, false, false],  //AT
            ['a7c40f631fc920687.20179984', false, true, false],  //DE
            ['a7c40f631fc920687.20179984', true, true, true],   //DE
            ['8f241f11095306451.36998225', true, true, false]  //AF
        ];
    }

    /**
     * @dataProvider showCheckoutTermsDataProvider
     * @param $oxCountryId
     * @param $isKCOEnabled
     * @param $showPFN
     * @param $expectedResult
     */
    public function testShowCheckoutTerms($oxCountryId, $isKCOEnabled, $showPFN, $expectedResult)
    {
        $user = $this->getMock(User::class, ['getFieldData']);
        $user->expects($this->any())->method('getFieldData')->with('oxcountryid')->willReturn($oxCountryId);
        $viewConfig = $this->getMock(ViewConfig::class, ['getUser','isKlarnaCheckoutEnabled','isShowPrefillNotif']);
        $viewConfig->expects($this->any())->method('getUser')->willReturn($user);
        $viewConfig->expects($this->any())->method('isKlarnaCheckoutEnabled')->willReturn($isKCOEnabled);
        $viewConfig->expects($this->any())->method('isShowPrefillNotif')->willReturn($showPFN);

        $result = $viewConfig->showCheckoutTerms();
        $this->assertEquals($expectedResult, $result);
    }

    public function isShowPrefillNotifDataProvider()
    {
        return [
            [0, false],
            [null, false],
            [1, true],
            ['1', true],
            [true, true]
        ];
    }

    /**
     * @dataProvider isShowPrefillNotifDataProvider
     * @param $value
     */
    public function testIsShowPrefillNotif($value, $expectedResult)
    {
        $this->getConfig()->saveShopConfVar(null, 'blKlarnaPreFillNotification',  $value, $this->getShopId(), 'klarna');

        $oViewConfig = oxNew(ViewConfig::class);
        $this->assertEquals($expectedResult, $oViewConfig->isShowPrefillNotif());
    }

    public function getModeDataProvider()
    {
        return [
            ['KCO'],
            ['KP']
        ];
    }
    /**
     * @dataProvider getModeDataProvider
     * @param $value
     */
    public function testGetMode($value)
    {
        $this->getConfig()->saveShopConfVar(null, 'sKlarnaActiveMode', $value, $this->getShopId(), 'klarna');
        $oViewConfig = oxNew(ViewConfig::class);
        $this->assertEquals($value, $oViewConfig->getMode());
    }

    public function isKCODataProvider()
    {
        return [
            [null, false],
            ['KP', false],
            ['KCO', true]
        ];
    }

    /**
     * @dataProvider isKCODataProvider
     * @param $mode
     * @param $expectedResult
     */
    public function testIsKlarnaCheckoutEnabled($mode, $expectedResult)
    {
        $this->setModuleMode($mode);

        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->isKlarnaCheckoutEnabled();
        $this->assertEquals($expectedResult, $result);

    }

    public function getKlarnaFooterContentDataProvider()
    {
        return [
            ['KP', 0, 'longBlack', false],
            ['KP', 1, 'logoBlack', false],
            ['KP', 2, 'logoBlack', [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/logo/en_gb/basic/logo_black.png',
                'class' => 'logoBlack'
            ]],
            ['KP', 2, 'logoWhite', [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/logo/en_gb/basic/logo_white.png',
                'class' => 'logoWhite'
            ]],
            ['KCO', 0, 'longBlack', false],
            ['KCO', 1, 'longBlack', [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/badge/de-de/checkout/long-blue.png?width=440',
                'class' => 'longBlack'
            ]],
            ['KCO', 1, 'longWhite', [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/badge/de-de/checkout/long-white.png?width=440',
                'class' => 'longWhite'
            ]],
            ['KCO', 1, 'shortBlack', [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/badge/de-de/checkout/short-blue.png?width=312',
                'class' => 'shortBlack'
            ]],
            ['KCO', 1, 'shortWhite', [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/badge/de-de/checkout/short-white.png?width=312',
                'class' => 'shortWhite'
            ]],
            ['KCO', 2, 'longBlack', [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/badge//checkout/long-blue.png?width=440',
                'class' => 'longBlack'
            ]],
            ['KCO', 2, 'logoWhite', [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/logo/en_gb/basic/logo_white.png',
                'class' => 'logoWhite'
            ]],
        ];
    }

    /**
     * @dataProvider getKlarnaFooterContentDataProvider
     * @param $mode
     * @param $klFooterType
     * @param $klFooterValue
     * @param $expectedResult
     */
    public function testGetKlarnaFooterContent($mode, $klFooterType, $klFooterValue, $expectedResult)
    {
        $this->getConfig()->saveShopConfVar(null, 'sKlarnaFooterDisplay', $klFooterType, $this->getShopId(), 'klarna');
        $this->getConfig()->saveShopConfVar(null, 'sKlFooterValue', $klFooterValue, $this->getShopId(), 'klarna');
        $this->setModuleMode($mode);

        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->getKlarnaFooterContent();

        $this->assertEquals($expectedResult, $result);
    }

    public function getCountryListDataProvider()
    {
        $oCountryList = oxNew(CountryList::class);
        $oCountryList->loadActiveCountries();
        $parentCountryListCount = $oCountryList->count();
        $oCountryList->loadActiveNonKlarnaCheckoutCountries();
        $nonKlarnaCountriesCount = $oCountryList->count();

        return [
            [false, true, 'some_cc', $nonKlarnaCountriesCount ],
            [false, false, 'some_cc', $parentCountryListCount ],
            [false, true, 'account_user', $parentCountryListCount ],
            [true, false, 'some_cc', $parentCountryListCount ]
        ];
    }

    /**
     * @dataProvider getCountryListDataProvider
     * @param $blShipping
     * @param $isCheckoutNonKlarnaCountry
     * @param $activeClassName
     * @param $expectedResult
     */
    public function testGetCountryList($blShipping, $isCheckoutNonKlarnaCountry, $activeClassName, $expectedResult)
    {

        $viewConfig = $this->getMock(ViewConfig::class, ['isCheckoutNonKlarnaCountry', 'getActiveClassName']);
        $viewConfig->expects($this->any())->method('isCheckoutNonKlarnaCountry')->willReturn($isCheckoutNonKlarnaCountry);
        $viewConfig->expects($this->any())->method('getActiveClassName')->willReturn($activeClassName);

        $result = $viewConfig->getCountryList($blShipping);

        $this->assertEquals($expectedResult, $result->count());
    }

    public function getLawNotificationsLinkKcoDataProvider()
    {
        return [
            ['DE', 'fakeMID_someSuffix', 'https://cdn.klarna.com/1.0/shared/content/legal/terms/fakeMID/de_de/checkout'],
            ['AT', 'F4k3MID_someSuffix', 'https://cdn.klarna.com/1.0/shared/content/legal/terms/F4k3MID/de_at/checkout'],
        ];
    }

    /**
     * @dataProvider getLawNotificationsLinkKcoDataProvider
     * @param $iso
     * @param $expectedResult
     */
    public function testGetLawNotificationsLinkKco($iso, $mid, $expectedResult)
    {
        $this->setSessionParam('sCountryISO', $iso);
        $this->setModuleConfVar('sKlarnaMerchantId', $mid);
        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->getLawNotificationsLinkKco();

        $this->assertEquals($expectedResult, $result);
    }

    public function testIsPrefillIframe()
    {
        $this->setModuleConfVar('blKlarnaPreFillNotification', true);
        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->isShowPrefillNotif();

        $this->assertEquals(true, $result);
    }

    public function isCheckoutNonKlarnaCountryDataProvider()
    {
        return [
            ['DE', false],
            ['AT', false],
            ['AF', true]
        ];
    }

    /**
     * @dataProvider isCheckoutNonKlarnaCountryDataProvider
     * @param $iso
     */
    public function testIsCheckoutNonKlarnaCountry($iso, $expectedResult)
    {
        $this->setSessionParam('sCountryISO', $iso);
        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->isCheckoutNonKlarnaCountry();

        $this->assertEquals($expectedResult, $result);

    }

    public function isUserLoggedInDataProvider()
    {
        return [
            ['fake_id', 'fake_id', true],
            ['some_id', 'fake_id', false]
        ];
    }

    /**
     * @dataProvider isUserLoggedInDataProvider
     * @param $userId
     * @param $usrSession
     * @param $expectedResult
     */
    public function testIsUserLoggedIn($userId, $usrSession, $expectedResult)
    {
        $this->setSessionParam('usr', $usrSession);
        $user = new \stdClass();
        $user->oxuser__oxid = new Field($userId, Field::T_RAW);
        $viewConfig = $this->getMock(ViewConfig::class, ['getUser']);
        $viewConfig->expects($this->once())->method('getUser')->willReturn($user);

        $this->assertEquals($expectedResult, $viewConfig->isUserLoggedIn());
    }

//    public function testIsActiveThemeFlow()
//    {
//
//    }
//
//    public function testIsActiveControllerKlarnaExpress()
//    {
//
//    }
//

}
