<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 27.03.2018
 * Time: 18:59
 */

namespace TopConcepts\Klarna\Testes\Unit\Controllers;


use OxidEsales\Eshop\Application\Controller\ThankYouController;
use OxidEsales\Eshop\Application\Model\BasketItem;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaThankYouControllerTest
 * @covers \TopConcepts\Klarna\Controllers\KlarnaThankYouController
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

        $apiClient = $this->getMock(KlarnaCheckoutClient::class, ['getOrder', 'getHtmlSnippet']);
        $apiClient->expects($this->once())
            ->method('getOrder')
            ->willReturn([]);
        $apiClient->expects($this->once())
            ->method('getHtmlSnippet')
            ->willReturn($snippet);

        $oBasketItem = oxNew($this->getProxyClassName(BasketItem::class));
        $oBasketItem->setNonPublicVar('_sProductId', '_testArt');
        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, array('getContents', 'getProductsCount', 'getOrderId'));
        $oBasket->expects($this->once())->method('getContents')->will($this->returnValue(array($oBasketItem)));
        $oBasket->expects($this->once())->method('getProductsCount')->will($this->returnValue(1));
        $oBasket->expects($this->once())->method('getOrderId')->will($this->returnValue(1));

        $thankYouController = $this->getMock($this->getProxyClassName(ThankYouController::class), ['getKlarnaClient']);
        $thankYouController->expects($this->exactly(2))
            ->method('getKlarnaClient')
            ->willReturn($apiClient);
        $thankYouController->setNonPublicVar('_oBasket', $oBasket);
        $thankYouController->render();

        $this->assertEquals(null, $this->getSessionParam('klarna_checkout_order_id'));

    }

    public function testRender_nonKCO()
    {
        $payId = 'other';
        $klSessionId = 'fake_session';
        $this->setSessionParam('paymentid', $payId);
        $this->setSessionParam('klarna_checkout_order_id', $klSessionId);

        $oBasketItem = oxNew($this->getProxyClassName(BasketItem::class));
        $oBasketItem->setNonPublicVar('_sProductId', '_testArt');
        $oBasket = $this->getMock(\OxidEsales\Eshop\Application\Model\Basket::class, array('getContents', 'getProductsCount', 'getOrderId'));
        $oBasket->expects($this->once())->method('getContents')->will($this->returnValue(array($oBasketItem)));
        $oBasket->expects($this->once())->method('getProductsCount')->will($this->returnValue(1));
        $oBasket->expects($this->once())->method('getOrderId')->will($this->returnValue(1));

        $thankYouController = oxNew($this->getProxyClassName(ThankYouController::class));
        $thankYouController->setNonPublicVar('_oBasket', $oBasket);
        $thankYouController->render();

        $this->assertEquals(null, $this->getSessionParam('klarna_checkout_order_id'));
    }

    public function renderDataProvider()
    {
        return [
            ['klarna_checkout',  '16302e97f6249d2b65004954b1a8b0d1'],
            ['other', '16302e97f6249d2b65004954b1a8b0d1']
        ];
    }

//    public function testGetKlarnaClient()
//    {
//
//    }
}
