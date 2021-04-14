<?php

namespace TopConcepts\Klarna\Testes\Unit\Controllers;


use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\CountryList;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ViewConfig;
use TopConcepts\Klarna\Controller\KlarnaViewConfig;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaViewConfigTest
 * @package TopConcepts\Klarna\Testes\Unit\Controllers
 * @covers \TopConcepts\Klarna\Controller\KlarnaViewConfig
 */
class KlarnaViewConfigTest extends ModuleUnitTestCase
{

    public function testAddBuyNow()
    {
        $this->getConfig()->saveShopConfVar('bool', 'blKlarnaDisplayBuyNow', true, null, 'module:tcklarna');

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
        $user = $this->getMockBuilder(User::class)->setMethods(['getFieldData'])->getMock();
        $user->expects($this->any())->method('getFieldData')->with('oxcountryid')->willReturn($oxCountryId);
        $oViewConfig = $this->getMockBuilder(ViewConfig::class)->setMethods(['getUser'])->getMock();
        $oViewConfig->expects($this->once())->method('getUser')->willReturn($user);
        $this->assertEquals($result, $oViewConfig->getIsAustria());

    }

    public function testGetIsAustria_noUser_defaultCountry()
    {
        $oViewConfig = $this->getMockBuilder(ViewConfig::class)->setMethods(['getUser'])->getMock();
        $oViewConfig->expects($this->once())->method('getUser')->willReturn(null);
        $this->assertFalse( $oViewConfig->getIsAustria());
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
        $user = $this->getMockBuilder(User::class)->setMethods(['getFieldData'])->getMock();
        $user->expects($this->any())->method('getFieldData')->with('oxcountryid')->willReturn($oxCountryId);
        $oViewConfig = $this->getMockBuilder(ViewConfig::class)->setMethods(['getUser'])->getMock();
        $oViewConfig->expects($this->once())->method('getUser')->willReturn($user);
        $this->assertEquals($expectedResult, $oViewConfig->getIsGermany());
    }

    public function testGetIsGermany_noUser_defaultCountry()
    {
        $oViewConfig = $this->getMockBuilder(ViewConfig::class)->setMethods(['getUser'])->getMock();
        $oViewConfig->expects($this->once())->method('getUser')->willReturn(null);
        $this->assertTrue( $oViewConfig->getIsGermany());
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
        $user = $this->getMockBuilder(User::class)->setMethods(['getFieldData'])->getMock();
        $user->expects($this->any())->method('getFieldData')->with('oxcountryid')->willReturn($oxCountryId);
        $viewConfig = $this->getMockBuilder(ViewConfig::class)->setMethods(['getUser','isKlarnaCheckoutEnabled','isShowPrefillNotif'])->getMock();
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
        $this->getConfig()->saveShopConfVar('bool', 'blKlarnaPreFillNotification',  $value, $this->getShopId(), 'module:tcklarna');

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
        $this->getConfig()->saveShopConfVar('str', 'sKlarnaActiveMode', $value, $this->getShopId(), 'module:tcklarna');
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
            ['KP', 0, 'longBlack',false, false],
            ['KP', 1, 'logoFooter',false, false],
            ['KP', 2, 'logoFooter',false, [
                'url' => 'https://x.klarnacdn.net/payment-method/assets/badges/generic/klarna.svg',
                'class' => 'logoFooter'
            ]],
            ['KCO', 0, 'longBlack', false, false],
            ['KCO', 1, 'longBlack',false, [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/badge/de_de/checkout/long-blue.png?width=440',
                'class' => 'longBlack'
            ]],
            ['KCO', 1, 'longWhite',false, [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/badge/de_de/checkout/long-white.png?width=440',
                'class' => 'longWhite'
            ]],
            ['KCO', 1, 'shortBlack',false, [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/badge/de_de/checkout/short-blue.png?width=312',
                'class' => 'shortBlack'
            ]],
            ['KCO', 1, 'shortWhite',false, [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/badge/de_de/checkout/short-white.png?width=312',
                'class' => 'shortWhite'
            ]],
            ['KCO', 2, 'logoBlack',false, [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/logo/en_gb/basic/logo_black.png',
                'class' => 'logoBlack'
            ]],
            ['KCO', 2, 'logoWhite',false, [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/logo/en_gb/basic/logo_white.png',
                'class' => 'logoWhite'
            ]],
            ['KCO', 2, 'logoWhite','script', [
                'url' => '//cdn.klarna.com/1.0/shared/image/generic/logo/en_gb/basic/logo_white.png',
                'class' => 'logoWhite',
                'script' => 'script'
            ]],
        ];
    }

    public function testGetKlarnaFooterContent_nonKlarnaSetAsDefault()
    {
        $this->setModuleConfVar('sKlarnaDefaultCountry', 'AF');
        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->getKlarnaFooterContent();
        $this->assertFalse($result);
        $this->setModuleConfVar('sKlarnaDefaultCountry', 'DE');
    }

    /**
     * @dataProvider getKlarnaFooterContentDataProvider
     * @param $mode
     * @param $klFooterType
     * @param $klFooterValue
     * @param $klScript
     * @param $klPromo
     * @param $expectedResult
     */
    public function testGetKlarnaFooterContent($mode, $klFooterType, $klFooterValue, $klScript, $expectedResult)
    {
        $this->getConfig()->saveShopConfVar('str', 'sKlarnaFooterDisplay', $klFooterType, $this->getShopId(), 'module:tcklarna');
        $this->getConfig()->saveShopConfVar('str', 'sKlarnaFooterValue', $klFooterValue, $this->getShopId(), 'module:tcklarna');

        $this->getConfig()->saveShopConfVar('str', 'sKlarnaMessagingScript', $klScript, $this->getShopId(), 'module:tcklarna');
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

        $viewConfig = $this->getMockBuilder(ViewConfig::class)->setMethods(['isCheckoutNonKlarnaCountry', 'getActiveClassName'])->getMock();
        $viewConfig->expects($this->any())->method('isCheckoutNonKlarnaCountry')->willReturn($isCheckoutNonKlarnaCountry);
        $viewConfig->expects($this->any())->method('getActiveClassName')->willReturn($activeClassName);

        $result = $viewConfig->getCountryList($blShipping);

        $this->assertEquals($expectedResult, $result->count());
    }

    public function getLawNotificationsLinkKcoDataProvider()
    {
        return [
            ['DE', 'fakeMID_someSuffix', 'https://cdn.klarna.com/1.0/shared/content/legal/terms/fakeMID/de/checkout'],
            [null, 'fakeMID_someSuffix', 'https://cdn.klarna.com/1.0/shared/content/legal/terms/fakeMID/de/checkout'],
            ['AT', 'F4k3MID_someSuffix', 'https://cdn.klarna.com/1.0/shared/content/legal/terms/F4k3MID/de/checkout'],
        ];
    }

    /**
     * @dataProvider getLawNotificationsLinkKcoDataProvider
     * @param $iso
     * @param $mid
     * @param $expectedResult
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function testGetLawNotificationsLinkKco($iso, $mid, $expectedResult)
    {
        if(!$iso) {
            $this->setModuleConfVar('sKlarnaDefaultCountry', 'DE');
        } else {
            $this->setSessionParam('sCountryISO', $iso);
        }

        $this->setModuleConfVar('sKlarnaMerchantId', $mid);
        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->getLawNotificationsLinkKco();

        $this->assertEquals($expectedResult, $result);
    }


    public function testIsPrefillIframe()
    {
        $this->setModuleConfVar('blKlarnaEnablePreFilling', true);
        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->isPrefillIframe();

        $this->assertTrue($result);
    }

    public function testIsPrefillIframe_false()
    {
        $this->setModuleConfVar('blKlarnaEnablePreFilling', false, 'bool');
        $oViewConfig = oxNew(ViewConfig::class);
        $result = $oViewConfig->isPrefillIframe();

        $this->assertFalse($result);
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
     * @param $expectedResult
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
        $userId = 'fake_id';
        $user = new \stdClass();
        $user->oxuser__oxid = new Field($userId, Field::T_RAW);

        return [
            [$userId, $user, true],
            [null, null, false],
        ];
    }

    /**
     * @dataProvider isUserLoggedInDataProvider
     * @param $userId
     * @param $usrSession
     * @param $user
     * @param $expectedResult
     */
    public function testIsUserLoggedIn($usrSession, $user, $expectedResult)
    {
        $this->setSessionParam('usr', $usrSession);

        $viewConfig = $this->getMockBuilder(ViewConfig::class)->setMethods(['getUser'])->getMock();
        $viewConfig->expects($this->once())->method('getUser')->willReturn($user);

        $this->assertEquals($expectedResult, $viewConfig->isUserLoggedIn());
    }

    /**
     * @dataProvider isActiveFlowThemDataProvider
     * @param $themeName
     * @param $expectedResult
     */
    public function testIsActiveThemeFlow($themeName, $expectedResult)
    {
        $oViewConfig = $this->getMockBuilder(ViewConfig::class)->setMethods(['getActiveTheme'])->getMock();
        $oViewConfig->expects($this->once())->method('getActiveTheme')->willReturn($themeName);
        $result = $oViewConfig->isActiveThemeFlow();

        $this->assertEquals($expectedResult, $result);
    }


    /**
     * @dataProvider isKlarnaControllerActiveDataProvider
     * @param $controllerName
     * @param $expectedResult
     */
    public function testIsActiveControllerKlarnaExpress($controllerName, $expectedResult)
    {
        $oViewConfig = $this->getMockBuilder(ViewConfig::class)->setMethods(['getActiveClassName'])->getMock();
        $oViewConfig->expects($this->once())->method('getActiveClassName')->willReturn($controllerName);
        $result = $oViewConfig->isActiveControllerKlarnaExpress();

        $this->assertEquals($expectedResult, $result);
    }

    public function isKlarnaControllerActiveDataProvider()
    {
        return [
            ['KlarnaExpress', true],
            ['klarnaexpress', true],
            ['otherName', false]
        ];
    }

    public function isActiveFlowThemDataProvider()
    {
        return [
            ['azure', false],
            ['flow', true],
            ['Flow', true]
        ];
    }

    public function testGetOnSitePromotionInfo()
    {
        //Non promotion key
        $this->getConfig()->saveShopConfVar('bool', 'blKlarnaDisplayBuyNow', true, null, 'module:tcklarna');

        $oViewConfig = $this->getMockBuilder(KlarnaViewConfig::class)->setMethods(['getActiveClassName'])->getMock();
        $result = $oViewConfig->getOnSitePromotionInfo('blKlarnaDisplayBuyNow');

        $this->assertEquals($result, $this->getConfigParam('blKlarnaDisplayBuyNow'));

        $price = $this->getMockBuilder(Price::class)
            ->setMethods(['getBruttoPrice', 'getVat'])
            ->getMock();

        $price->expects($this->any())->method('getBruttoPrice')->willReturn(10);
        $price->expects($this->any())->method('getVat')->willReturn(0.23);

        //promotion product key
        $this->getConfig()->saveShopConfVar('str', 'sKlarnaCreditPromotionProduct', 'data-purchase-amount="%s"', null, 'module:tcklarna');
        $product = $this->getMockBuilder(Article::class)->setMethods(['getPrice'])->getMock();

        $product->expects($this->any())->method('getPrice')->willReturn($price);

        $result = $oViewConfig->getOnSitePromotionInfo('sKlarnaCreditPromotionProduct', $product);

        $this->assertSame('data-purchase-amount="1000"', $result);

        //promotion basket key
        $oViewConfig->expects($this->any())->method('getActiveClassName')->willReturn('basket');
        $this->getConfig()->saveShopConfVar('str', 'sKlarnaCreditPromotionBasket', 'data-purchase-amount="%s"', null, 'module:tcklarna');

        $basket = $this->getMockBuilder(Basket::class)->setMethods(['getPrice'])->getMock();

        $basket->expects($this->any())->method('getPrice')->willReturn($price);

        Registry::getSession()->setBasket($basket);

        $result = $oViewConfig->getOnSitePromotionInfo('sKlarnaCreditPromotionBasket');

        $this->assertSame('data-purchase-amount="998"', $result);

    }
}
