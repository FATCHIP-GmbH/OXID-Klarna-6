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
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\Exception\KlarnaBasketTooLargeException;
use TopConcepts\Klarna\Core\Exception\KlarnaConfigException;
use TopConcepts\Klarna\Model\KlarnaUser;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaExpressControllerTest
 * @package TopConcepts\Klarna\Testes\Unit\Controllers
 * @covers \TopConcepts\Klarna\Controller\KlarnaExpressController
 */
class KlarnaExpressControllerTest extends ModuleUnitTestCase {
    /**
     * @dataProvider getBreadCrumbDataProvider
     * @param $iLang
     * @param $expectedResult
     */
    public function testGetBreadCrumb($iLang, $expectedResult) {
        $this->setLanguage($iLang);
        $expressController = new KlarnaExpressController();
        $result = $expressController->getBreadCrumb();

        $this->assertEquals($result[0]['title'], $expectedResult['title']);
    }

    public function getBreadCrumbDataProvider() {
        return [
            [0, ['title' => 'Kasse']],
            [1, ['title' => 'Checkout']],
        ];
    }

    public function testGetKlarnaModalFlagCountries() {
        $countryList = ['DE', 'AT', 'CH'];
        $expressController = oxNew(KlarnaExpressController::class);
        $result = $expressController->getKlarnaModalFlagCountries();

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
    public function testGetFormattedUserAddresses($isFake, $userId, $expectedResult) {
        $oUser = $this->getMockBuilder(User::class)->setMethods(['isFake', 'getId'])->getMock();
        $oUser->expects($this->once())
            ->method('isFake')->willReturn($isFake);
        $oUser->expects($this->any())
            ->method('getId')->willReturn($userId);
        $kcoController = oxNew($this->getProxyClassName(KlarnaExpressController::class));
        $kcoController->setNonPublicVar('_oUser', $oUser);
        $result = $kcoController->getFormattedUserAddresses();
        $this->assertEquals($expectedResult, $result);

    }

    public function userDataProvider() {
        $address = ["41b545c65fe99ca2898614e563a7108a" => "Gregory Dabrowski, Karnapp 25, 21079 Hamburg"];

        return [
            [true, null, false],
            [false, '92ebae5067055431aeaaa6f75bd9a131', $address],
            [false, 'fake-id', false],
        ];
    }

    public function testSetKlarnaDeliveryAddress() {
        $this->setRequestParameter('klarna_address_id', 'delAddressId');
        $kcoController = new KlarnaExpressController();
        $kcoController->init();
        $kcoController->setKlarnaDeliveryAddress();
        $this->assertEquals('delAddressId', $this->getSessionParam('deladrid'));
        $this->assertEquals(1, $this->getSessionParam('blshowshipaddress'));
        $this->assertTrue($this->getSessionParam('klarna_checkout_order_id') === null);
    }

    public function testGetKlarnaModalOtherCountries() {
        $kcoController = new KlarnaExpressController();
        $result = $kcoController->getKlarnaModalOtherCountries();

        $this->assertEquals(1, count($result));
    }

    public function testGetActiveShopCountries() {
        $kcoController = new KlarnaExpressController();
        $result = $kcoController->getActiveShopCountries();

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

    public function testInit_KP_mode() {
        $this->setModuleMode('KP');
        $kcoController = new KlarnaExpressController();
        $kcoController->init();

        $this->assertEquals($this->getConfig()->getShopSecureHomeUrl() . 'cl=order', \oxUtilsHelper::$sRedirectUrl);
    }

    public function testInit_reset() {
        $this->setModuleMode('KCO');
        $this->setSessionParam('klarna_checkout_order_id', 'fake_id');
        $this->setSessionParam('resetKlarnaSession', 1);

        $kcoController = new KlarnaExpressController();
        $kcoController->init();

        $this->assertEquals(null, $this->getSessionParam('klarna_checkout_order_id'));
    }


    public function initPopupDataProvider() {
        $oUser = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field('a7c40f6320aeb2ec2.72885259');
        $baseUrl = $this->getConfig()->getSSLShopURL() . 'index.php?cl=KlarnaExpress';
        $nonKCOUrl = $this->getConfig()->getSSLShopURL() . 'index.php?cl=user&non_kco_global_country=AF';

        return [
            ['AT', null, $baseUrl],
            ['AT', $oUser, $baseUrl],
            ['DE', $oUser, $baseUrl],
            ['AF', $oUser, $nonKCOUrl],
        ];
    }

    /**
     * @dataProvider initPopupDataProvider
     * @param $selectedCountry
     * @param $oUser
     * @param $expectedKlarnaSessionId
     */
    public function testInit_popupSelection($selectedCountry, $oUser, $redirectUrl) {
        $this->setSessionParam('klarna_checkout_order_id', 'fake-value');
        $this->setRequestParameter('selected-country', $selectedCountry);
        $this->setSessionParam('blshowshipaddress', 1);
        $kcoController = $this->getMockBuilder(KlarnaExpressController::class)->setMethods(['getUser'])->getMock();
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

        $this->assertEquals($redirectUrl, \oxUtilsHelper::$sRedirectUrl);
    }


    /**
     * @param $sslredirect
     * @param $getCurrentShopURL
     *
     * @param $expectedResult
     * @dataProvider testCheckSslDataProvider
     */
    public function testCheckSsl($sslredirect, $getCurrentShopURL, $expectedResult) {
        $oRequest = $this->getMockBuilder(Request::class)->setMethods(['getRequestEscapedParameter'])->getMock();
        $oRequest->expects($this->once())->method('getRequestEscapedParameter')->willReturn($sslredirect);
        $oConfig = $this->getMockBuilder(Config::class)->setMethods(['getCurrentShopURL', 'getSSLShopURL'])->getMock();
        $oConfig->expects($this->once())->method('getCurrentShopURL')->willReturn($getCurrentShopURL);
        $oConfig->expects($this->any())->method('getSSLShopURL')->willReturn(str_replace('http://', 'https://', $getCurrentShopURL));
        \OxidEsales\Eshop\Core\Registry::set(\OxidEsales\Eshop\Core\Config::class, $oConfig);

        $kcoController = $this->getMockBuilder(KlarnaExpressController::class)->setMethods(['getConfig'])->getMock();

        $kcoController->checkSsl($oRequest);

        $this->assertEquals($expectedResult, \oxUtilsHelper::$sRedirectUrl);
    }

    public function testCheckSslDataProvider() {
        $forceSslUrl = $this->getConfig()->getSSLShopURL() . 'index.php?sslredirect=forced&cl=KlarnaExpress';

        return [
            ['forced', $this->getConfig()->getShopUrl(), null],
            ['forced', $this->getConfig()->getSSLShopURL(), null],
            ['asdf', $this->getConfig()->getSSLShopURL(), null],
            ['asdf', $this->getConfig()->getShopUrl(), $forceSslUrl],
        ];
    }

    public function renderDataProvider() {
        $ssl_url = $this->getConfig()->getSSLShopURL();
        $oUser = oxNew(User::class);
        $oUser->setType(KlarnaUser::LOGGED_IN);
        $email = 'info@topconcepts.de';
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
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testRender_noShippingSet($currentUrl, $oUser, $email, $expectedShowPopUp) {
        $oBasket = $this->prepareBasketWithProduct();
        $this->getSession()->setBasket($oBasket);
        $this->setSessionParam('sShipSet', '1b842e732a23255b1.91207751');
        $this->setSessionParam('klarna_checkout_user_email', $email);
        $oConfig = $this->getMockBuilder(Config::class)->setMethods(['getCurrentShopURL'])->getMock();
        $oConfig->expects($this->any())->method('getCurrentShopURL')->willReturn($currentUrl);

        $methodReflection = new \ReflectionProperty(KlarnaExpressController::class, 'blShowPopup');
        $methodReflection->setAccessible(true);
        $kcoController = $this->getMockBuilder(KlarnaExpressController::class)->setMethods(['getConfig', 'getUser', 'rebuildFakeUser'])->getMock();
        $kcoController->expects($this->any())
            ->method('rebuildFakeUser')->willReturn(true);
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
            $this->assertEquals($email, $kcoController->getUser()->oxuser__oxemail->rawValue, "User email mismatch.");
        }
        $this->assertEquals($expectedShowPopUp, $methodReflection->getValue($kcoController), "Show popup mismatch.");
    }

    public function testRenderBlockIframeRender() {
        $this->setRequestParameter('sslredirect', 'forced');
        $keController = $this->getMockBuilder(KlarnaExpressController::class)->setMethods(['addTplParam', 'getKlarnaOrder'])->getMock();
        $this->setProtectedClassProperty($keController, 'blockIframeRender', true);
        $keController->expects($this->once())->method('addTplParam')->with('blShowPopUp');
        $keController->expects($this->never())->method('getKlarnaOrder');
        $keController->init();
        $result = $keController->render();
        $this->assertEquals('tcklarna_checkout.tpl', $result);
    }

    public function testRenderException() {
        $this->setRequestParameter('sslredirect', 'forced');
        $keController = $this->getMockBuilder(KlarnaExpressController::class)->setMethods(['getKlarnaOrder'])->getMock();
        $keController->expects($this->any())->method('getKlarnaOrder')->will($this->throwException(new KlarnaBasketTooLargeException()));
        $keController->init();
        $result = $keController->render();
        $this->assertEquals(Registry::getConfig()->getShopSecureHomeUrl() . 'cl=basket', \oxUtilsHelper::$sRedirectUrl);
        $this->assertEquals('tcklarna_checkout.tpl', $result);
    }

    public function testGetKlarnaClient() {
        $keController = $this->getMockBuilder(KlarnaExpressController::class)->setMethods(['init'])->getMock();
        $result = $keController->getKlarnaClient('DE');
        $this->assertInstanceOf(KlarnaCheckoutClient::class, $result);
    }

    public function testShowCountryPopup() {
        $this->setSessionParam('sCountryISO', 'test');
        $methodReflection = new \ReflectionMethod(KlarnaExpressController::class, 'showCountryPopup');
        $methodReflection->setAccessible(true);
        $keController = oxNew(KlarnaExpressController::class);
        $keController->init();
        $result = $methodReflection->invoke($keController);
        $this->assertFalse($result);

        $this->setSessionParam('sCountryISO', false);
        $keController = oxNew(KlarnaExpressController::class);
        $keController->init();
        $result = $methodReflection->invoke($keController);
        $this->assertTrue($result);

        $this->setRequestParameter('reset_klarna_country', true);
        $keController = oxNew(KlarnaExpressController::class);
        $keController->init();
        $result = $methodReflection->invoke($keController);
        $this->assertTrue($result);
    }

    public function testRenderWrongMerchantUrls() {
        $this->setRequestParameter('sslredirect', 'forced');
        $this->setSessionParam('wrong_merchant_urls', 'sds');
        $keController = oxNew(KlarnaExpressController::class);
        $keController->init();
        $result = $keController->render();
        $viewData = $this->getProtectedClassProperty($keController, '_aViewData');
        $this->assertTrue($viewData['confError']);
        $this->assertEquals('tcklarna_checkout.tpl', $result);

    }

    public function testRenderKlarnaClient() {
        $this->setRequestParameter('sslredirect', 'forced');
        $checkoutClient = $this->getMockBuilder(KlarnaCheckoutClient::class)
            ->setMethods(['createOrUpdateOrder'])->getMock();
        $checkoutClient->expects($this->any())->method('createOrUpdateOrder')
            ->will($this->throwException(new KlarnaWrongCredentialsException()));
        $keController = $this->getMockBuilder(KlarnaExpressController::class)
            ->setMethods(['getKlarnaClient', 'rebuildFakeUser'])->getMock();
        $keController->expects($this->any())->method('getKlarnaClient')->willReturn($checkoutClient);
        $keController->expects($this->any())->method('rebuildFakeUser')->willReturn(true);
        $keController->init();
        $keController->render();
    }

    /**
     * @dataProvider testLastResortRenderRedirectDataProvider
     * @param $sCountryISO
     * @param $expectedResult
     */
    public function testLastResortRenderRedirect($sCountryISO, $expectedResult) {
        $mockObj = $this->getMockBuilder(\stdClass::class)->setMethods(['createOrUpdateOrder'])->getMock();
        $mockObj->expects($this->any())->method('createOrUpdateOrder')->willReturn(true);

        $oKlarnaOrder = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['getOrderData', 'initOrder', 'getHtmlSnippet'])->getMock();
        $oKlarnaOrder->expects($this->once())->method('getOrderData')->willReturn(['purchase_country' => $sCountryISO]);
        $oKlarnaOrder->expects($this->any())->method('initOrder')->willReturn($mockObj);
        $oKlarnaOrder->expects($this->any())->method('getHtmlSnippet')->willReturn(true);
        $controller = $this->getMockBuilder(KlarnaExpressController::class)
            ->setMethods(['getKlarnaOrder', 'checkSsl', 'showCountryPopup', 'getKlarnaClient'])->getMock();
        $controller->expects($this->once())->method('getKlarnaOrder')->willReturn($oKlarnaOrder);
        $controller->expects($this->once())->method('checkSsl')->willReturn(null);
        $controller->expects($this->once())->method('showCountryPopup')->willReturn(true);
        $controller->expects($this->any())->method('getKlarnaClient')->willReturn($oKlarnaOrder);
        $controller->render();
        $this->assertEquals($expectedResult, \oxUtilsHelper::$sRedirectUrl);
    }

    public function testLastResortRenderRedirectDataProvider() {
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
    public function testHandleLoggedInUserWithNonKlarnaCountry($resetCountry, $expectedResult) {
        $oUser = $this->getMockBuilder(User::class)->setMethods(['getUserCountryISO2'])->getMock();
        $oUser->expects($this->any())->method('getUserCountryISO2')->willReturn('AF');
        $oRequest = $this->getMockBuilder(Request::class)
            ->setMethods(['getRequestEscapedParameter'])->getMock();
        $oRequest->expects($this->at(0))->method('getRequestEscapedParameter')->will($this->returnValue(null));
        $oRequest->expects($this->at(1))->method('getRequestEscapedParameter')->will($this->returnValue($resetCountry));
        $controller = $controller = $this->getMockBuilder(KlarnaExpressController::class)->setMethods(['getUser'])->getMock();
        $controller->expects($this->any())->method('getUser')->willReturn($oUser);
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
    public function testHandleLoggedInUserWithNonKlarnaCountryDataProvider() {
        return [
            [1, null],
            [null, 'cl=user&non_kco_global_country=AF'],
        ];
    }

    /**
     *
     */
    public function testResolveFakeUserRegistered() {
        $mockUser = $this->getMockBuilder(User::class)->setMethods(['checkUserType'])->getMock();
        $mockUser->oxuser__oxpassword = new Field('testPass');
        $mockUser->expects($this->once())->method('checkUserType');
        $this->setSessionParam('klarna_checkout_user_email', 'test@email');
        $controller = $this->getMockBuilder(KlarnaExpressController::class)->setMethods(['getUser'])->getMock();
        $controller->expects($this->any())->method('getUser')->willReturn($mockUser);
        $result = $controller->resolveUser();
        $this->assertInstanceOf(User::class, $result);
    }

    public function testResolveFakeUserNew() {
        $this->setSessionParam('klarna_checkout_user_email', 'test@email');
        $controller = $this->getMockBuilder(KlarnaExpressController::class)->setMethods(['getUser'])->getMock();
        $controller->expects($this->any())->method('getUser')->willReturn(false);
        $result = $controller->resolveUser();
        $this->assertInstanceOf(User::class, $result);
    }

    /**
     *
     */
    public function testRebuildFakeUser() {
        $orderId = 'testId';
        $email = 'test@mail.com';
        $oUser = oxNew(User::class);
        $oUser->oxuser__oxpassword = new Field('');
        $oBasket = new \stdClass();
        $aOrderData = [
            'order_id'        => $orderId,
            'billing_address' => ['email' => $email]
        ];
        $oClient = $this->getMockBuilder(KlarnaCheckoutClient::class)->setMethods(['getOrder'])->getMock();
        $oClient->expects($this->any())->method('getOrder')->willReturn($aOrderData);
        $controller = $this->getMockBuilder(KlarnaExpressController::class)->setMethods(['getUser', 'getKlarnaCheckoutClient'])->getMock();
        $controller->expects($this->any())->method('getUser')->willReturn($oUser);
        $controller->expects($this->any())->method('getKlarnaCheckoutClient')->willReturn($oClient);
        $controller->rebuildFakeUser($oBasket);

        // assert we rebuild user context
        $this->assertEquals($orderId, $this->getSessionParam('klarna_checkout_order_id'));
        $this->assertEquals($email, $this->getSessionParam('klarna_checkout_user_email'));
        $this->assertEquals($oBasket, $this->getSession()->getBasket());
        $this->getSession()->setBasket(null); // clean up

        // exception
        $oClient = $this->getMockBuilder(KlarnaCheckoutClient::class)->setMethods(['getOrder'])->getMock();
        $oClient->expects($this->once())->method('getOrder')->willThrowException(new KlarnaClientException('Test'));
        $oUser = $this->getMockBuilder(User::class)->setMethods(['logout'])->getMock();
        $oUser->expects($this->once())->method('logout');
        $oUser->oxuser__oxpassword = new Field('');
        $controller = $this->getMockBuilder(KlarnaExpressController::class)->setMethods(['getUser', 'getKlarnaCheckoutClient'])->getMock();
        $controller->expects($this->any())->method('getUser')->willReturn($oUser);
        $controller->expects($this->any())->method('getKlarnaCheckoutClient')->willReturn($oClient);
        $controller->rebuildFakeUser($oBasket);
    }
}
