<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 18.04.2018
 * Time: 17:31
 */

namespace TopConcepts\Klarna\Tests\Unit\Controllers;


use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\PayPalModule\Controller\ExpressCheckoutDispatcher;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Exception\KlarnaClientException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaOrderControllerTest extends ModuleUnitTestCase
{

    public function testIsPayPalAmazon()
    {

    }

    public function testUpdateKlarnaAjax()
    {

    }

    public function testKlarnaExternalPayment()
    {

    }

    public function testExecute()
    {

    }

    public function testRender()
    {

    }

    public function testIsCountryHasKlarnaPaymentsAvailable()
    {

    }

    public function testKpBeforeExecute()
    {

    }

    public function initDP()
    {
        $userClassName = User::class;
        $kcoExternalPayments = ['oxidpaypal'];
        return [
            ['KP', 'payId', null, null, false, $kcoExternalPayments ],
            ['KCO', 'klarna_checkout', 'DE', null, $userClassName, $kcoExternalPayments ],
            ['KCO', 'klarna_checkout', 'AT', null, $userClassName, $kcoExternalPayments ],
            ['KCO', 'klarna_checkout', 'DE', 1, false, $kcoExternalPayments ],
            ['KCO', 'klarna_checkout', 'AF', null, $userClassName, $kcoExternalPayments ],
            ['KCO', 'bestitamazon', 'DE', null, false, $kcoExternalPayments ],
            ['KCO', 'oxidpaypal', 'DE', null, false, $kcoExternalPayments ],
            ['KCO', 'oxidpaypal', 'AF', null, false, $kcoExternalPayments ],
        ];
    }

    /**
     * @dataProvider initDP
     * @param $mode
     * @param $payId
     * @param $countryISO
     * @param $externalCheckout
     * @param $userClassName
     * @param $kcoExternalPayments
     */
    public function testInit($mode, $payId, $countryISO, $externalCheckout, $userClassName, $kcoExternalPayments)
    {
        $this->setModuleMode($mode);
        $this->setRequestParameter('externalCheckout', $externalCheckout);
        $this->setSessionParam('sCountryISO', $countryISO);

        $oBasket = oxNew(Basket::class);
        $oBasket->setPayment($payId);
        Registry::getSession()->setBasket($oBasket);
        Registry::getSession()->freeze();

        $client = $this->getMock(KlarnaCheckoutClient::class, ['getOrder']);
        $userClassName && $client->expects($this->once())->method('getOrder');

        $oOrderController = $this->getMock(OrderController::class, ['getKlarnaCheckoutClient', 'getKlarnaAllowedExternalPayments']);
        $userClassName && $oOrderController->expects($this->once())->method('getKlarnaCheckoutClient')->willReturn($client);
        $oOrderController->expects($this->any())->method('getKlarnaAllowedExternalPayments')->willReturn($kcoExternalPayments);
        $oOrderController->init();

        $oUser = $oOrderController->getUser();
        $userClassName ? $this->assertInstanceOf($userClassName, $oUser) : $this->assertFalse($oUser);
        $this->assertEquals($externalCheckout, $this->getSessionParam('externalCheckout'));
        $this->assertEquals($externalCheckout, $this->getProtectedClassProperty($oOrderController, 'isExternalCheckout'));
        // back to default
        $this->setModuleMode('KCO');
    }

    public function testInit_exception()
    {
        $this->setSessionParam('sCountryISO', 'DE');

        $oBasket = oxNew(Basket::class);
        $oBasket->setPayment('klarna_checkout');
        Registry::getSession()->setBasket($oBasket);
        Registry::getSession()->freeze();

        $e = $this->getMock(KlarnaClientException::class, ['debugOut']);
        $e->expects($this->once())->method('debugOut');
        $client = $this->getMock(KlarnaCheckoutClient::class, ['getOrder']);
        $client->expects($this->once())->method('getOrder')->willThrowException($e);

        $oOrderController = $this->getMock(OrderController::class, ['getKlarnaCheckoutClient']);
        $oOrderController->expects($this->once())->method('getKlarnaCheckoutClient')->willReturn($client);
        $oOrderController->init();
    }

    public function klarnaExternalCheckoutDP()
    {
        return [
            ['bestitamazon', 0, $this->getConfig()->getSslShopUrl() . 'index.php?cl=KlarnaEpmDispatcher&fnc=amazonLogin'],
            ['oxidpaypal', 1, null],
            ['other', 0, $this->getConfig()->getSslShopUrl() . 'index.php?cl=KlarnaExpress']
        ];
    }

    /**
     * @dataProvider klarnaExternalCheckoutDP
     * @param $paymentId
     * @param $dispatcherCallsCount
     * @param $rUrl
     */
    public function testKlarnaExternalCheckout($paymentId, $dispatcherCallsCount, $rUrl)
    {
        $dispatcher = $this->getMock(BaseController::class, ['setExpressCheckout']);
        $dispatcher->expects($this->exactly($dispatcherCallsCount))->method('setExpressCheckout');
        \oxTestModules::addModuleObject(ExpressCheckoutDispatcher::class, $dispatcher);
        $oOrderController = oxNew(OrderController::class);
        $this->setProtectedClassProperty($oOrderController, 'selfUrl', $rUrl);
        $oOrderController->klarnaExternalCheckout($paymentId);
        $this->assertEquals($rUrl, \oxUtilsHelper::$sRedirectUrl);
    }

    public function testIncludeKPWidget()
    {
        $oSession = Registry::getSession();
        $oBasket = oxNew(Basket::class);
        $oBasket->setPayment('other');
        $oSession->setBasket($oBasket);
        $oSession->freeze();
        $oOrderController = oxNew(OrderController::class);
        $this->assertFalse($oOrderController->includeKPWidget());

        $oSession = Registry::getSession();
        $oBasket = oxNew(Basket::class);
        $oBasket->setPayment('klarna_pay_now');
        $oSession->setBasket($oBasket);
        $oSession->freeze();
        $oOrderController = oxNew(OrderController::class);
        $this->assertTrue($oOrderController->includeKPWidget());
    }
}
