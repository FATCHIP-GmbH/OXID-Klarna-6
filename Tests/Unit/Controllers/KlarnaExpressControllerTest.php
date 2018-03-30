<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 27.03.2018
 * Time: 19:03
 */

namespace TopConcepts\Klarna\Testes\Unit\Controllers;


use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ViewConfig;
use TopConcepts\Klarna\Controllers\KlarnaExpressController;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaExpressControllerTest extends ModuleUnitTestCase
{

//    public function testGetKlarnaClient()
//    {
//
//    }

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
        $userUrl = $this->getConfig()->getSSLShopURL() . 'index.php?cl=user';
        $orderUrl = $this->getConfig()->getSSLShopURL() . 'index.php?cl=order';
        $oUser = oxNew(User::class);
        $email = 'info@topconcepts.de';
        return [
            [$ssl_url, $orderUrl, $oUser, null],
            [$ssl_url, $orderUrl, null, $email],
//            [$ssl_url, $orderUrl, $oUser, $email],
            [$ssl_url, $orderUrl, null, null]
        ];
    }

    /**
     * @dataProvider renderDataProvider
     * @param $curentUrl
     * @param $redirectUrl
     * @param $oUser User
     * @param $email
     */
    public function testRender($curentUrl, $redirectUrl, $oUser, $email)
    {
        $this->setSessionParam('klarna_checkout_user_email', $email);
        $oConfig = $this->getMock(Config::class, ['getCurrentShopURL']);
        $oConfig->expects($this->once())->method('getCurrentShopURL')->willReturn($curentUrl);

//        $oViewConfig = $this->getMock(ViewConfig::class, ['isUserLoggedIn']);
//        $oViewConfig->expects($this->once())
//            ->method('isUserLoggedIn')->willReturn($isUserLoggedIn);

        $kcoController = $this->getMock(KlarnaExpressController::class, ['getConfig', 'getUser']);
        $kcoController->expects($this->atLeastOnce())
            ->method('getConfig')->willReturn($oConfig);
        $kcoController->expects($this->any())
            ->method('getUser')->willReturn($oUser);
//        $kcoController->expects($this->any())
//            ->method('getViewConfig')->willReturn($oViewConfig);

        $kcoController->init();
        $kcoController->render();

        $this->assertEquals($redirectUrl, \oxUtilsHelper::$sRedirectUrl);

        if($kcoController->getUser() && $email){
            $this->assertEquals($email, $this->User->oxuser__oxemail->rawValue);
        }

    }
//
//    public function testGetFormattedUserAddresses()
//    {
//
//    }
//
//    public function testSetKlarnaDeliveryAddress()
//    {
//
//    }
//
//    public function testGetKlarnaModalOtherCountries()
//    {
//
//    }
//
//    public function testIsUserLoggedIn()
//    {
//
//    }
//
//    public function testInit()
//    {
//
//    }
//
//    public function testCleanUpSession()
//    {
//
//    }
//
//    public function testGetActiveShopCountries()
//    {
//
//    }
}
