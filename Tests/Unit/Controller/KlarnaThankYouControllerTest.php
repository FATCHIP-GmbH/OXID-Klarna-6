<?php

namespace TopConcepts\Klarna\Testes\Unit\Controllers;


use OxidEsales\Eshop\Application\Controller\ThankYouController;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Controller\KlarnaThankYouController;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\Exception\KlarnaWrongCredentialsException;
use TopConcepts\Klarna\Model\KlarnaInstantBasket;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaThankYouControllerTest
 * @covers \TopConcepts\Klarna\Controller\KlarnaThankYouController
 * @package TopConcepts\Klarna\Testes\Unit\Controllers
 */
class KlarnaThankYouControllerTest extends ModuleUnitTestCase
{

    protected function getSUT() {
        $payId = 'klarna_checkout';
        $klSessionId = 'fake_session';

        $this->setSessionParam('paymentid', $payId);
        $this->setSessionParam('klarna_checkout_order_id', $klSessionId);

        $oOrderMock = $this->getMockBuilder(Order::class)->setMethods(['isLoaded'])->getMock();
        $oOrderMock->expects($this->once())->method('isLoaded')->willReturn(true);
        Registry::set(Order::class, $oOrderMock);

        $oBasketItem = oxNew(BasketItem::class);
        $oBasket = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class)->setMethods(['getContents', 'getProductsCount', 'getOrderId'])->getMock();
        $oBasket->expects($this->any())->method('getContents')->will($this->returnValue(array($oBasketItem)));
        $oBasket->expects($this->any())->method('getProductsCount')->will($this->returnValue(1));
        $oBasket->expects($this->any())->method('getOrderId')->will($this->returnValue(1));
        $this->setProtectedClassProperty($oBasketItem, '_sProductId', '_testArt');

        $thankYouController = oxNew(ThankYouController::class);
        $this->setProtectedClassProperty($thankYouController, '_oBasket', $oBasket);

        return $thankYouController;
    }
    /**
     * @throws \Exception
     */
    public function testRender_KCO_client_exception()
    {
        $thankYouController = $this->getSUT();
        // check client
        // base code will log KlarnaWrongCredentialsException in this case, because getOrder will be called on not configured client
        $thankYouController->render();
        $this->assertLoggedException(KlarnaWrongCredentialsException::class, '');
        $this->assertInstanceOf(KlarnaCheckoutClient::class, $this->getProtectedClassProperty($thankYouController, 'client'));
    }

    public function testRender_KCO_success()
    {
        $snippet = '<html snippet>';
        $apiClient = $this->getMockBuilder(KlarnaCheckoutClient::class)->setMethods(['getOrder', 'getHtmlSnippet'])->getMock();
        $apiClient->expects($this->once())->method('getOrder')->willReturn([]);
        $apiClient->expects($this->once())->method('getHtmlSnippet')->willReturn($snippet);

        $thankYouController = $this->getSUT();
        // check success
        $this->setProtectedClassProperty($thankYouController, 'client', $apiClient);
        $thankYouController->render();
        $this->assertNull($this->getSessionParam('klarna_checkout_order_id'));
    }

    public function testRender_KCO_client_exception_2()
    {
        $snippet = '<html snippet>';
        // check exception
        $this->setSessionParam('klarna_checkout_order_id', 'test');
        $apiClient = $this->getMockBuilder(KlarnaCheckoutClient::class)->setMethods(['getOrder', 'getHtmlSnippet'])->getMock();
        $apiClient->expects($this->once())->method('getHtmlSnippet')->willReturn($snippet);
        $apiClient->expects($this->once())->method('getOrder')->willThrowException(new KlarnaClientException('Test'));
        $thankYouController = $this->getSUT();
        $this->setProtectedClassProperty($thankYouController, 'client', $apiClient);

        $thankYouController->render();
        $this->assertLoggedException(KlarnaClientException::class, 'Test');
    }

    public function testRender_nonKCO()
    {
        $payId = 'other';
        $klSessionId = 'fake_session';

        $oBasketItem = oxNew(BasketItem::class);
        $this->setProtectedClassProperty($oBasketItem,'_sProductId', '_testArt');
        $oBasket = $this->getMockBuilder(Basket::class)->setMethods(['getContents', 'getProductsCount', 'getOrderId'])->getMock();
        $oBasket->expects($this->once())->method('getContents')->will($this->returnValue(array($oBasketItem)));
        $oBasket->expects($this->once())->method('getProductsCount')->will($this->returnValue(1));
        $oBasket->expects($this->once())->method('getOrderId')->will($this->returnValue(1));

        $thankYouController = oxNew(ThankYouController::class);
        $this->setProtectedClassProperty($thankYouController, '_oBasket', $oBasket);
        $thankYouController->render();

        $this->assertArrayNotHasKey('sKlarnaIframe', $thankYouController->getViewData());
    }

    public function testSimpleRender()
    {
        $oBasketItem = oxNew(BasketItem::class);
        $this->setProtectedClassProperty($oBasketItem,'_sProductId', '_testArt');
        $oBasket = $this->getMockBuilder(Basket::class)->setMethods(['getContents', 'getProductsCount', 'getOrderId'])->getMock();
        $oBasket->expects($this->once())->method('getContents')->will($this->returnValue(array($oBasketItem)));
        $oBasket->expects($this->once())->method('getProductsCount')->will($this->returnValue(1));
        $oBasket->expects($this->once())->method('getOrderId')->will($this->returnValue(1));

        $controller = $this->getMockBuilder(KlarnaThankYouController::class)->
        setMethods(['getNewKlarnaInstantBasket'])->getMock();

        $this->setProtectedClassProperty($controller, '_oBasket', $oBasket);

        $result = $controller->render();

        $expected = $this->getProtectedClassProperty($controller, '_sThisTemplate');

        $this->assertSame($expected, $result);
    }
}
