<?php

namespace TopConcepts\Klarna\Testes\Unit\Controllers;


use OxidEsales\Eshop\Application\Controller\ThankYouController;
use OxidEsales\Eshop\Application\Model\BasketItem;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaThankYouControllerTest
 * @covers \TopConcepts\Klarna\Controller\KlarnaThankYouController
 * @package TopConcepts\Klarna\Testes\Unit\Controllers
 */
class KlarnaThankYouControllerTest extends ModuleUnitTestCase
{

    /**
     * @throws \Exception
     */
    public function testRender_KCO()
    {
        $payId = 'klarna_checkout';
        $snippet = '<html snippet>';
        $klSessionId = 'fake_session';

        $this->setSessionParam('paymentid', $payId);
        $this->setSessionParam('klarna_checkout_order_id', $klSessionId);




        $apiClient = $this->getMockBuilder(KlarnaCheckoutClient::class)->setMethods(['getOrder', 'getHtmlSnippet'])->getMock();
        $apiClient->expects($this->once())->method('getOrder')->willReturn([]);
        $apiClient->expects($this->once())->method('getHtmlSnippet')->willReturn($snippet);

        $oBasketItem = oxNew(BasketItem::class);
        $oBasket = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class)->setMethods(['getContents', 'getProductsCount', 'getOrderId'])->getMock();
        $oBasket->expects($this->any())->method('getContents')->will($this->returnValue(array($oBasketItem)));
        $oBasket->expects($this->any())->method('getProductsCount')->will($this->returnValue(1));
        $oBasket->expects($this->any())->method('getOrderId')->will($this->returnValue(1));
        $this->setProtectedClassProperty($oBasketItem, '_sProductId', '_testArt');

        $thankYouController = oxNew(ThankYouController::class);
        $this->setProtectedClassProperty($thankYouController, '_oBasket', $oBasket);

        // check client
        // base code will log KlarnaWrongCredentialsException in this case, because getOrder will be called on not configured client
        $thankYouController->render();
        $this->assertLoggedException(KlarnaWrongCredentialsException::class, '');
        $this->assertInstanceOf(KlarnaCheckoutClient::class, $this->getProtectedClassProperty($thankYouController, 'client'));

        // check success
        $this->setProtectedClassProperty($thankYouController, 'client', $apiClient);
        $thankYouController->render();
        $this->assertNull($this->getSessionParam('klarna_checkout_order_id'));

        // check exception
        $this->setSessionParam('klarna_checkout_order_id', 'test');
        $apiClient->expects($this->once())->method('getOrder')->willThrowException(new KlarnaClientException('Test'));
        $thankYouController->render();
        $this->assertLoggedException(KlarnaClientException::class, 'Test');
    }

    public function testRender_nonKCO()
    {
        $payId = 'other';
        $klSessionId = 'fake_session';

        $oBasketItem = oxNew(BasketItem::class);
        $this->setProtectedClassProperty($oBasketItem,'_sProductId', '_testArt');
        $oBasket = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class)->setMethods(['getContents', 'getProductsCount', 'getOrderId'])->getMock();
        $oBasket->expects($this->once())->method('getContents')->will($this->returnValue(array($oBasketItem)));
        $oBasket->expects($this->once())->method('getProductsCount')->will($this->returnValue(1));
        $oBasket->expects($this->once())->method('getOrderId')->will($this->returnValue(1));

        $thankYouController = oxNew(ThankYouController::class);
        $this->setProtectedClassProperty($thankYouController, '_oBasket', $oBasket);
        $thankYouController->render();

        $this->assertArrayNotHasKey('sKlarnaIframe', $thankYouController->getViewData());
    }
}
