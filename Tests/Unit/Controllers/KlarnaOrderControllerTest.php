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
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\PayPalModule\Controller\ExpressCheckoutDispatcher;
use TopConcepts\Klarna\Controllers\KlarnaOrderController;
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

    public function testInit()
    {

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
