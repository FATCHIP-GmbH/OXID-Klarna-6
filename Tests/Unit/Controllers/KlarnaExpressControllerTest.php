<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 27.03.2018
 * Time: 19:03
 */

namespace TopConcepts\Klarna\Testes\Unit\Controllers;

use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsView;
use TopConcepts\Klarna\Controllers\KlarnaExpressController;
use TopConcepts\Klarna\Exception\KlarnaConfigException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaExpressControllerTest
 * @package TopConcepts\Klarna\Testes\Unit\Controllers
 * @covers \TopConcepts\Klarna\Controllers\KlarnaExpressController
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
        $result = $expressController->getBreadCrumb();

        $this->assertEquals($result[0]['title'], $expectedResult['title']);
    }

    public function getBreadCrumbDataProvider()
    {
        return[
            [0, ['title' => 'Kasse']],
            [1, ['title' => 'Checkout']],
        ];
    }

    public function testGetKlarnaModalFlagCountries()
    {
        $countryList = ['DE', 'AT', 'CH'];
        $expressController = oxNew(KlarnaExpressController::class);
        $result = $expressController->getKlarnaModalFlagCountries();

        $this->assertEquals(3, count($result));
        foreach($result as $index => $country){
            if(in_array($country->oxcountry__oxisoalpha2->rawValue, $countryList)){
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
            [ true, null, false ],
            [ false, '92ebae5067055431aeaaa6f75bd9a131', $address],
            [false, 'fake-id', false]
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
        $result = $kcoController->getKlarnaModalOtherCountries();

        $this->assertEquals(0, count($result));

    }

    public function testCleanUpSession()
    {
        $this->setSessionParam('sCountryISO', 'val1');
        $this->setSessionParam('klarna_checkout_order_id', 'val2');
        $this->setSessionParam('klarna_checkout_user_email', 'val3');

        $kcoController = new KlarnaExpressController();
        $kcoController->cleanUpSession();

        $this->assertTrue($this->getSessionParam('sCountryISO') === null);
        $this->assertTrue($this->getSessionParam('klarna_checkout_order_id') === null);
        $this->assertTrue($this->getSessionParam('klarna_checkout_user_email') === null);

    }

    public function testGetActiveShopCountries()
    {
        $kcoController = new KlarnaExpressController();
        $result = $kcoController->getActiveShopCountries();

        $this->assertEquals(5, count($result));

        $active = ['DE', 'AT', 'CH', 'US', 'GB'];
        foreach($result as $country){
            $index = array_search($country->oxcountry__oxisoalpha2->value, $active);
            if($index !== null){
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
        $oUser = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field('a7c40f6320aeb2ec2.72885259');
        $baseUrl = $this->getConfig()->getSSLShopURL() . 'index.php?cl=KlarnaExpress';
        $nonKCOUrl = $this->getConfig()->getSSLShopURL() . 'index.php?cl=user&non_kco_global_country=AF';
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

        if($oUser){
            $oCountry = oxNew(Country::class);
            $oCountry->load($oUser->oxuser__oxcountryid);
            $this->assertEquals($selectedCountry, $oCountry->oxcountry__oxisoalpha2);
        }

        $this->assertEquals($expectedKlarnaSessionId , $this->getSessionParam('klarna_checkout_order_id'));
        $this->assertEquals($redirectUrl , \oxUtilsHelper::$sRedirectUrl);
    }

    public function testInit_next()
    {
        $this->markTestIncomplete("More tests");
    }


    public function testRender_forceSsl()
    {
        $url = $this->getConfig()->getCurrentShopURL();
        $forceSslUrl = $this->getConfig()->getSSLShopURL() . 'index.php?sslredirect=forced&cl=KlarnaExpress';

        $oConfig = $this->getMock(Config::class, ['getCurrentShopURL']);
        $oConfig->expects($this->once())->method('getCurrentShopURL')->willReturn($url);

        $kcoController = $this->getMock(KlarnaExpressController::class, ['getConfig']);
        $kcoController->expects($this->any())
            ->method('getConfig')->willReturn($oConfig);

        $kcoController->init();
        $kcoController->render();

        $this->assertEquals($forceSslUrl, \oxUtilsHelper::$sRedirectUrl);
    }

    public function renderDataProvider()
    {
        $ssl_url = $this->getConfig()->getSSLShopURL();
        $oUser = oxNew(User::class);
        $email = 'info@topconcepts.de';
        $apiCreds = [];

        return [
            [$ssl_url, $oUser, null, false, $apiCreds],
            [$ssl_url, null, $email, true],
            [$ssl_url, null, null, true]
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

        if($kcoController->getUser() && $email){
            $this->assertEquals($email, $this->User->oxuser__oxemail->rawValue, "User email mismatch.");
        }
        $this->assertEquals($expectedShowPopUp, $kcoController->getNonPublicVar('blShowPopup'), "Show popup mismatch.");
    }

    public function testRender()
    {

        $userUrl = $this->getConfig()->getSSLShopURL() . 'index.php?cl=user';
        $orderUrl = $this->getConfig()->getSSLShopURL() . 'index.php?cl=order';

//        $testShippingList = array('testId' => new \stdClass());
//        $oPayment = $this->getMock(PaymentController::class, ['getCheckoutShippingSets']);
//        $oPayment->expects($this->once())
//            ->method('getCheckoutShippingSets')->willReturn($testShippingList);
//        \oxTestModules::addModuleObject(PaymentController::class, $oPayment);


        $this->markTestIncomplete("Render method requires more tests");
        //$this->assertEquals($redirectUrl, \oxUtilsHelper::$sRedirectUrl, "Redirect url mismatch.");
    }

//    public function testGetKlarnaClient()
//    {
//
//    }
//    public function testIsUserLoggedIn()
//    {
//
//    }
}
