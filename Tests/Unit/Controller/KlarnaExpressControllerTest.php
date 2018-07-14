<?php

namespace TopConcepts\Klarna\Testes\Unit\Controllers;


use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\UtilsObject;
use OxidEsales\Eshop\Core\UtilsView;
use TopConcepts\Klarna\Controller\KlarnaExpressController;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\Exception\KlarnaBasketTooLargeException;
use TopConcepts\Klarna\Core\Exception\KlarnaConfigException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaExpressControllerTest
 * @package TopConcepts\Klarna\Testes\Unit\Controllers
 * @covers \TopConcepts\Klarna\Controller\KlarnaExpressController
 */
class KlarnaExpressControllerTest extends ModuleUnitTestCase
{
    /**
     * @dataProvider getBreadCrumbDataProvider
     * @param $iLang
     * @param $expectedResult
     */
    public function testGetBreadCrumb($iLang, $expectedResult)
    {
        $this->setLanguage($iLang);
        $expressController = oxNew(KlarnaExpressController::class);
        $result            = $expressController->getBreadCrumb();

        $this->assertEquals($result[0]['title'], $expectedResult['title']);
    }

    public function getBreadCrumbDataProvider()
    {
        return [
            [0, ['title' => 'Kasse']],
            [1, ['title' => 'Checkout']],
        ];
    }

    public function testGetKlarnaModalFlagCountries()
    {
        $countryList       = ['DE', 'AT', 'CH'];
        $expressController = oxNew(KlarnaExpressController::class);
        $result            = $expressController->getKlarnaModalFlagCountries();

        $this->assertEquals(3, count($result));
        foreach ($result as $index => $country) {
            if (in_array($country->oxcountry__oxisoalpha2->rawValue, $countryList)) {
                unset($result[$index]);
            }
        }
        $this->assertEquals(0, count($result));
    }

    /**
     * @dataProvider userDataProvider
     * @param $isFake
     * @param $userId
     * @param $expectedResult
     */
    public function testGetFormattedUserAddresses($isFake, $userId, $expectedResult)
    {
        $oUser = $this->getMock(User::class, ['isFake', 'getId']);
        $oUser->expects($this->once())
            ->method('isFake')->willReturn($isFake);
        $oUser->expects($this->any())
            ->method('getId')->willReturn($userId);

        $kcoController = oxNew($this->getProxyClassName(KlarnaExpressController::class));
        $kcoController->setNonPublicVar('_oUser', $oUser);

        $result = $kcoController->getFormattedUserAddresses();

        $this->assertEquals($expectedResult, $result);

    }

    public function userDataProvider()
    {
        $address = ["41b545c65fe99ca2898614e563a7108a" => "Gregory Dabrowski, Karnapp 25, 21079 Hamburg"];

        return [
            [true, null, false],
            [false, '92ebae5067055431aeaaa6f75bd9a131', $address],
            [false, 'fake-id', false],
        ];
    }

    public function testSetKlarnaDeliveryAddress()
    {
        $this->setRequestParameter('klarna_address_id', 'delAddressId');
        $kcoController = new KlarnaExpressController();
        $kcoController->init();
        $kcoController->setKlarnaDeliveryAddress();

        $this->assertEquals('delAddressId', $this->getSessionParam('deladrid'));
        $this->assertEquals(1, $this->getSessionParam('blshowshipaddress'));
        $this->assertTrue($this->getSessionParam('klarna_checkout_order_id') === null);
    }

    public function testGetKlarnaModalOtherCountries()
    {
        $kcoController = new KlarnaExpressController();
        $result        = $kcoController->getKlarnaModalOtherCountries();

        $this->assertEquals(1, count($result));
    }

    public function testGetActiveShopCountries()
    {
        $kcoController = new KlarnaExpressController();
        $result        = $kcoController->getActiveShopCountries();

        $this->assertEquals(6, count($result));

        $active = ['DE', 'AT', 'CH', 'US', 'GB'];
        foreach ($result as $country) {
            $index = array_search($country->oxcountry__oxisoalpha2->value, $active);
            if ($index !== null) {
                unset($active[$index]);
            }
        }
        $this->assertEquals(0, count($active));
    }

    public function testInit_KP_mode()
    {
        $this->setModuleMode('KP');
        $kcoController = new KlarnaExpressController();
        $kcoController->init();

        $this->assertEquals($this->getConfig()->getShopSecureHomeUrl() . 'cl=order', \oxUtilsHelper::$sRedirectUrl);
    }

    public function testInit_reset()
    {
        $this->setModuleMode('KCO');
        $this->setSessionParam('klarna_checkout_order_id', 'fake_id');
        $this->setSessionParam('resetKlarnaSession', 1);

        $kcoController = new KlarnaExpressController();
        $kcoController->init();

        $this->assertEquals(null, $this->getSessionParam('klarna_checkout_order_id'));
    }


    public function initPopupDataProvider()
    {
        $oUser                      = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field('a7c40f6320aeb2ec2.72885259');
        $baseUrl                    = $this->getConfig()->getSSLShopURL() . 'index.php?cl=KlarnaExpress';
        $nonKCOUrl                  = $this->getConfig()->getSSLShopURL() . 'index.php?cl=user&non_kco_global_country=AF';

        return [
            ['AT', null, null, $baseUrl],
            ['AT', $oUser, null, $baseUrl],
            ['DE', $oUser, null, $baseUrl],
            ['AF', $oUser, 'fake-value', $nonKCOUrl],
        ];
    }

    /**
     * @dataProvider initPopupDataProvider
     * @param $selectedCountry
     * @param $oUser
     * @param $expectedKlarnaSessionId
     */
    public function testInit_popupSelection($selectedCountry, $oUser, $expectedKlarnaSessionId, $redirectUrl)
    {
        $this->setSessionParam('klarna_checkout_order_id', 'fake-value');
        $this->setRequestParameter('selected-country', $selectedCountry);
        $this->setSessionParam('blshowshipaddress', 1);

        $kcoController = $this->getMock(KlarnaExpressController::class, ['getUser']);
        $kcoController->expects($this->any())
            ->method('getUser')->willReturn($oUser);

        $kcoController->init();

        $this->assertEquals(0, $this->getSessionParam('blshowshipaddress'));
        $this->assertEquals($selectedCountry, $this->getSessionParam('sCountryISO'));

        if ($oUser) {
            $oCountry = oxNew(Country::class);
            $oCountry->load($oUser->oxuser__oxcountryid);
            $this->assertEquals($selectedCountry, $oCountry->oxcountry__oxisoalpha2);
        }

        $this->assertEquals($expectedKlarnaSessionId, $this->getSessionParam('klarna_checkout_order_id'));
        $this->assertEquals($redirectUrl, \oxUtilsHelper::$sRedirectUrl);
    }


    /**
     * @param $sslredirect
     * @param $getCurrentShopURL
     *
     * @param $expectedResult
     * @dataProvider testCheckSslDataProvider
     */
    public function testCheckSsl($sslredirect, $getCurrentShopURL, $expectedResult)
    {
        $oRequest = $this->createStub(Request::class, ['getRequestEscapedParameter' => $sslredirect]);

        $oConfig = $this->getMock(Config::class, ['getCurrentShopURL']);
        $oConfig->expects($this->once())->method('getCurrentShopURL')->willReturn($getCurrentShopURL);

        $kcoController = $this->getMock(KlarnaExpressController::class, ['getConfig']);
        $kcoController->expects($this->any())
            ->method('getConfig')->willReturn($oConfig);

        $kcoController->checkSsl($oRequest);

        $this->assertEquals($expectedResult, \oxUtilsHelper::$sRedirectUrl);
    }

    public function testCheckSslDataProvider()
    {
        $forceSslUrl = $this->getConfig()->getSSLShopURL() . 'index.php?sslredirect=forced&cl=KlarnaExpress';

        return [
            ['forced', $this->getConfig()->getShopUrl(), null],
            ['forced', $this->getConfig()->getSSLShopURL(), null],
            ['asdf', $this->getConfig()->getSSLShopURL(), null],
            ['asdf', $this->getConfig()->getShopUrl(), $forceSslUrl],
        ];
    }

    public function renderDataProvider()
    {
        $ssl_url  = $this->getConfig()->getSSLShopURL();
        $oUser    = oxNew(User::class);
        $email    = 'info@topconcepts.de';
        $apiCreds = [];

        return [
            [$ssl_url, $oUser, null, false, $apiCreds],
            [$ssl_url, null, $email, true],
            [$ssl_url, null, null, true],
        ];
    }

    /**
     * @dataProvider renderDataProvider
     * @param $currentUrl
     * @param $oUser User
     * @param $email
     * @param $expectedShowPopUp
     */
    public function testRender_noShippingSet($currentUrl, $oUser, $email, $expectedShowPopUp)
    {
        $oBasket = $this->prepareBasketWithProduct();
        $this->getSession()->setBasket($oBasket);
        $this->setSessionParam('sShipSet', '1b842e732a23255b1.91207751');
        $this->setSessionParam('klarna_checkout_user_email', $email);

        $oConfig = $this->getMock(Config::class, ['getCurrentShopURL']);
        $oConfig->expects($this->once())->method('getCurrentShopURL')->willReturn($currentUrl);

        $kcoController = $this->getMock($this->getProxyClassName(KlarnaExpressController::class), ['getConfig', 'getUser']);
        $kcoController->expects($this->atLeastOnce())
            ->method('getConfig')->willReturn($oConfig);
        $kcoController->expects($this->any())
            ->method('getUser')->willReturn($oUser);


        \oxTestModules::addFunction('oxutilsview', 'addErrorToDisplay', '{$this->selectArgs = $aA[0]; return $aA[0];}');
        $this->setLanguage(1);

        $kcoController->init();
        $kcoController->render();

        $oException = Registry::get(UtilsView::class)->selectArgs;

        $this->assertTrue($oException instanceof KlarnaConfigException);

        if ($kcoController->getUser() && $email) {
            $this->assertEquals($email, $this->User->oxuser__oxemail->rawValue, "User email mismatch.");
        }
        $this->assertEquals($expectedShowPopUp, $kcoController->getNonPublicVar('blShowPopup'), "Show popup mismatch.");
    }

    public function testRenderBlockIframeRender()
    {
        $this->setRequestParameter('sslredirect', 'forced');
        $url     = $this->getConfig()->getCurrentShopURL();
        $oConfig = $this->getMock(Config::class, ['getCurrentShopURL']);
        $oConfig->expects($this->once())->method('getCurrentShopURL')->willReturn($url);

        $keController = $this->createStub(KlarnaExpressController::class, ['getConfig' => $oConfig]);
        $this->setProtectedClassProperty($keController, 'blockIframeRender', true);
        $keController->init();
        $result = $keController->render();
        $this->assertEquals('tcklarna_checkout.tpl', $result);
    }

    public function testRenderException()
    {
        $this->setRequestParameter('sslredirect', 'forced');
        $url     = $this->getConfig()->getCurrentShopURL();
        $oConfig = $this->getMock(Config::class, ['getCurrentShopURL']);
        $oConfig->expects($this->once())->method('getCurrentShopURL')->willReturn($url);

        $keController = $this->getMock(KlarnaExpressController::class, ['getKlarnaOrder', 'getConfig']);
        $keController->expects($this->any())->method('getKlarnaOrder')->will($this->throwException(new KlarnaBasketTooLargeException()));
        $keController->expects($this->any())->method('getConfig')->will($this->returnValue($oConfig));
        $keController->init();
        $result = $keController->render();

        $this->assertEquals(Registry::getConfig()->getShopSecureHomeUrl() . 'cl=basket', \oxUtilsHelper::$sRedirectUrl);
        $this->assertEquals('tcklarna_checkout.tpl', $result);
    }

    public function testGetKlarnaClient()
    {
        $keController = $this->createStub(KlarnaExpressController::class, ['init' => null]);
        $result       = $keController->getKlarnaClient('DE');

        $this->assertInstanceOf(KlarnaCheckoutClient::class, $result);
    }

    public function testShowCountryPopup()
    {
        $this->setSessionParam('sCountryISO', 'test');
        $methodReflection = new \ReflectionMethod(KlarnaExpressController::class, 'showCountryPopup');
        $methodReflection->setAccessible(true);

        $keController = $this->createStub(KlarnaExpressController::class, ['getSession' => $this->getSession()]);
        $keController->init();
        $result = $methodReflection->invoke($keController);
        $this->assertFalse($result);

        $this->setSessionParam('sCountryISO', false);
        $keController = $this->createStub(KlarnaExpressController::class, ['getSession' => $this->getSession()]);
        $keController->init();
        $result = $methodReflection->invoke($keController);

        $this->assertTrue($result);

        $this->setRequestParameter('reset_klarna_country', true);
        $keController->init();
        $result = $methodReflection->invoke($keController);
        $this->assertTrue($result);
    }

    public function testRenderWrongMerchantUrls()
    {
        $this->setRequestParameter('sslredirect', 'forced');
        $url = $this->getConfig()->getCurrentShopURL();

        $oConfig = $this->getMock(Config::class, ['getCurrentShopURL']);
        $oConfig->expects($this->once())->method('getCurrentShopURL')->willReturn($url);

        $this->setSessionParam('wrong_merchant_urls', 'sds');
        $keController = $this->createStub(KlarnaExpressController::class, ['getConfig' => $oConfig, 'getSession' => $this->getSession()]);

        $keController->init();
        $result = $keController->render();

        $viewData = $this->getProtectedClassProperty($keController, '_aViewData');
        $this->assertTrue($viewData['confError']);
        $this->assertEquals('tcklarna_checkout.tpl', $result);

    }

    public function testRenderKlarnaClient()
    {
        $this->setRequestParameter('sslredirect', 'forced');
        $url = $this->getConfig()->getCurrentShopURL();

        $oConfig = $this->getMock(Config::class, ['getCurrentShopURL']);
        $oConfig->expects($this->any())->method('getCurrentShopURL')->willReturn($url);

        $keController = $this->createStub(KlarnaExpressController::class, ['getConfig' => $oConfig]);

        $keController->init();
        $result = $keController->render();
        $this->assertLoggedException(KlarnaWrongCredentialsException::class, 'KLARNA_UNAUTHORIZED_REQUEST');

        $this->assertEquals('tcklarna_checkout.tpl', $result);


        $keController = $this->getMock(KlarnaExpressController::class, ['getConfig']);
        $credException = $this->createStub(KlarnaWrongCredentialsException::class, ['debugOut' => null]);

        $checkoutClient = $this->getMock(KlarnaCheckoutClient::class, ['createOrUpdateOrder']);
        $checkoutClient->expects($this->any())->method('createOrUpdateOrder')->will($this->throwException($credException));

        $keController->expects($this->any())->method('getKlarnaClient')->will($this->returnValue($checkoutClient));
        $keController->expects($this->any())->method('getConfig')->will($this->returnValue($oConfig));

        $keController->init();
        $keController->render();
        $this->assertEquals('tcklarna_checkout.tpl', $result);
        $this->assertLoggedException(KlarnaWrongCredentialsException::class, 'KLARNA_UNAUTHORIZED_REQUEST');
    }

    /**
     * @dataProvider testLastResortRenderRedirectDataProvider
     * @param $sCountryISO
     * @param $expectedResult
     */
    public function testLastResortRenderRedirect($sCountryISO, $expectedResult)
    {
        $mockObj = $this->createStub(\stdClass::class, [
            'createOrUpdateOrder' => true,
        ]);

        $oKlarnaOrder = $this->createStub(\stdClass::class, [
            'getOrderData'   => ['purchase_country' => $sCountryISO],
            'initOrder'      => $mockObj,
            'getHtmlSnippet' => true,
        ]);
        $controller   = $this->createStub(KlarnaExpressController::class, [
            'getKlarnaOrder'   => $oKlarnaOrder,
            'checkSsl'         => null,
            'showCountryPopup' => true,
            'getKlarnaClient'  => $oKlarnaOrder,
        ]);

        $controller->render();

        $this->assertEquals($expectedResult, \oxUtilsHelper::$sRedirectUrl);
    }

    public function testLastResortRenderRedirectDataProvider()
    {
        return [
            ['AF', Registry::getConfig()->getShopUrl() . 'index.php?cl=user'],
            ['DE', null],
        ];
    }

    /**
     * @dataProvider testHandleLoggedInUserWithNonKlarnaCountryDataProvider
     * @param $resetCountry
     * @param $expectedResult
     */
    public function testHandleLoggedInUserWithNonKlarnaCountry($resetCountry, $expectedResult)
    {
        $oUser = $this->createStub(User::class, [
            'getUserCountryISO2' => 'AF',
        ]);

        $oRequest = $this->getMock(Request::class, ['getRequestEscapedParameter']);
        $oRequest->expects($this->at(0))->method('getRequestEscapedParameter')->will($this->returnValue(null));
        $oRequest->expects($this->at(1))->method('getRequestEscapedParameter')->will($this->returnValue($resetCountry));

        $controller = $controller = $this->createStub(KlarnaExpressController::class, [
            'getUser' => $oUser,
        ]);

        $controller->determineUserControllerAccess($oRequest);

        if ($expectedResult) {
            $this->assertStringEndsWith($expectedResult, \oxUtilsHelper::$sRedirectUrl);
        } else {
            $this->assertEquals($expectedResult, \oxUtilsHelper::$sRedirectUrl);
        }
    }

    /**
     * @return array
     */
    public function testHandleLoggedInUserWithNonKlarnaCountryDataProvider()
    {
        return [
            [1, null],
            [null, 'cl=user&non_kco_global_country=AF'],
        ];
    }

    /**
     *
     */
    public function testResolveFakeUser()
    {
        $mockUser = $this->createStub(\stdClass::class, ['isWritable' => true, 'save' => true]);

        $session = $this->createStub(\stdClass::class, [
            'hasVariable' => true,
            'getVariable' => $mockUser]);

        $controller = $controller = $this->createStub(KlarnaExpressController::class, [
            'getUser'    => null,
            'getSession' => $session,
        ]);

        $result = $controller->resolveUser();

        $this->assertEquals($mockUser, $result);
    }

//    /**
//     *
//     */
//    public function testNonKCOCountrySetAsDefault(){
//
//    }
}
