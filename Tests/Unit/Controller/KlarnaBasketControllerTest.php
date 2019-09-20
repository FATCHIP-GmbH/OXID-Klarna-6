<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller;


use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Controller\KlarnaBasketController;
use TopConcepts\Klarna\Model\KlarnaBasket;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaBasketControllerTest extends ModuleUnitTestCase
{

    public function testRender()
    {
        $basket = $this->getMockBuilder(KlarnaBasket::class)->setMethods(['getPaymentId'])->getMock();
        $basket->expects($this->once())->method('getPaymentId')->willReturn('klarna_checkout');
        $session = Registry::getSession();
        $session->setBasket($basket);
        $this->setRequestParameter('openAmazonLogin', true);
        $this->setRequestParameter('klarnaInvalid', true);
        $basketController = $this->getMockBuilder(KlarnaBasketController::class)->setMethods(['displayKlarnaValidationErrors'])->getMock();
        $basketController->expects($this->once())->method('displayKlarnaValidationErrors')->willReturn(true);
        $result = $basketController->render();
        $this->assertEquals('page/checkout/basket.tpl', $result);
    }
}
