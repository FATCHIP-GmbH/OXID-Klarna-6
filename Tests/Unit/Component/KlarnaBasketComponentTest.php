<?php

namespace TopConcepts\Klarna\Tests\Unit\Component;


use OxidEsales\Eshop\Application\Component\BasketComponent;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Component\KlarnaBasketComponent;
use TopConcepts\Klarna\Core\KlarnaCheckoutClient;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaBasketComponentTest
 * @package TopConcepts\Klarna\Tests\Unit\Components
 */
class KlarnaBasketComponentTest extends ModuleUnitTestCase
{

    protected function setUp()
    {
        parent::setUp();
    }

    /** returns basket component ready to call 'tobasket' on it
     * @param array $aStubMethods skip internally included array('_getItems', '_setLastCallFnc', '_addItems', 'getConfig')
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getBasketComponentMock($aStubMethods = array())
    {
        $aProducts = array(
            'sProductId' => array(
                'am'           => 10,
                'sel'          => null,
                'persparam'    => null,
                'override'     => 0,
                'basketitemid' => ''
            )
        );

        /** @var \oxBasketItem|\PHPUnit_Framework_MockObject_MockObject $oBItem */
        $oBItem = $this->getMock(\OxidEsales\Eshop\Application\Model\BasketItem::class, array('getTitle', 'getProductId', 'getAmount', 'getdBundledAmount'));
        $oBItem->expects($this->once())->method('getTitle')->will($this->returnValue('ret:getTitle'));
        $oBItem->expects($this->once())->method('getProductId')->will($this->returnValue('ret:getProductId'));
        $oBItem->expects($this->once())->method('getAmount')->will($this->returnValue('ret:getAmount'));
        $oBItem->expects($this->once())->method('getdBundledAmount')->will($this->returnValue('ret:getdBundledAmount'));

        /** @var \oxConfig|\PHPUnit_Framework_MockObject_MockObject $oConfig */
        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array('getConfigParam'));
        $oConfig->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('iNewBasketItemMessage'))->will($this->returnValue('2'));
        $oConfig->expects($this->at(1))->method('getConfigParam')->with($this->equalTo('iNewBasketItemMessage'))->will($this->returnValue('2'));

        /** @var \oxcmp_basket|\PHPUnit_Framework_MockObject_MockObject $o */
        $stubList = array_merge($aStubMethods, array('_getItems', '_setLastCallFnc', '_addItems', 'getConfig'));
        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, $stubList);
        $o->expects($this->once())->method('_getItems')->will($this->returnValue($aProducts));
        $o->expects($this->once())->method('_setLastCallFnc')->with($this->equalTo('tobasket'))->will($this->returnValue(null));
        $o->expects($this->once())->method('_addItems')->with($this->equalTo($aProducts))->will($this->returnValue($oBItem));
        $o->expects($this->exactly(2))->method('getConfig')->will($this->returnValue($oConfig));

        return $o;
    }

    public function testActionKlarnaExpressCheckoutFromDetailsPage()
    {

        $cmpBasket = $this->getBasketComponentMock();
        $cmpBasket->actionKlarnaExpressCheckoutFromDetailsPage();
        $redirectUrl = $this->getConfig()->getShopSecureHomeUrl() . 'cl=KlarnaExpress';
        $this->assertEquals($redirectUrl, \oxUtilsHelper::$sRedirectUrl);

    }

    public function testChangebasket_kcoModeOn()
    {
        $klMode = 'KCO';
        $klSessionId = 'fakeSessionId';
        $this->getConfig()->saveShopConfVar(null, 'sKlarnaActiveMode', $klMode, $shopId = $this->getShopId(), $module = 'tcklarna');
        $this->setSessionParam('klarna_checkout_order_id', $klSessionId);

        $cmpBasket = $this->getMock(BasketComponent::class, ['updateKlarnaOrder']);
        $cmpBasket->expects($this->once())->method('updateKlarnaOrder');

        $cmpBasket->changebasket('abc', 11, 'sel', 'persparam', 'override');
    }

    public function testChangebasket_kcoModeOn_exception()
    {
        $klMode = 'KCO';
        $klSessionId = 'fakeSessionId';
        $this->getConfig()->saveShopConfVar(null, 'sKlarnaActiveMode', $klMode, $shopId = $this->getShopId(), $module = 'tcklarna');
        $this->setSessionParam('klarna_checkout_order_id', $klSessionId);

        $cmpBasket = $this->getMock(BasketComponent::class, ['updateKlarnaOrder']);
        $cmpBasket->expects($this->once())->method('updateKlarnaOrder')->will($this->throwException(new StandardException('Test')));

        $cmpBasket->changebasket('abc', 11, 'sel', 'persparam', 'override');

        $this->assertLoggedException(StandardException::class, 'Test');
        $this->assertEquals(null, $this->getSessionParam('klarna_checkout_order_id'));
    }

    public function testChangebasket_kpModeOne()
    {
        $klMode = 'KP';
        $klSessionId = 'fakeSessionId';
        $this->getConfig()->saveShopConfVar(null, 'sKlarnaActiveMode', $klMode, $shopId = $this->getShopId(), $module = 'tcklarna');
        $this->setSessionParam('klarna_checkout_order_id', $klSessionId);

        $cmpBasket = $this->getMock(BasketComponent::class, ['updateKlarnaOrder']);
        $cmpBasket->expects($this->never())->method('updateKlarnaOrder');

        $cmpBasket->changebasket('abc', 11, 'sel', 'persparam', 'override');
    }


    public function testTobasket()
    {
        $klMode = 'KCO';
        $klSessionId = 'fakeSessionId';
        $this->getConfig()->saveShopConfVar(null, 'sKlarnaActiveMode', $klMode, $shopId = $this->getShopId(), $module = 'tcklarna');

        $this->setSessionParam('klarna_checkout_order_id', $klSessionId);

        $cmpBasket = $this->getBasketComponentMock(['updateKlarnaOrder']);
        $cmpBasket->expects($this->once())->method('updateKlarnaOrder');

        $cmpBasket->tobasket();
    }

    public function testTobasket_WithException()
    {
        $klMode = 'KCO';
        $klSessionId = 'fakeSessionId';
        $this->getConfig()->saveShopConfVar(null, 'sKlarnaActiveMode', $klMode, $shopId = $this->getShopId(), $module = 'tcklarna');
        $this->setSessionParam('klarna_checkout_order_id', $klSessionId);

        $cmpBasket = $this->getBasketComponentMock(['updateKlarnaOrder']);
        $cmpBasket->expects($this->once())->method('updateKlarnaOrder')->will($this->throwException(new StandardException('Test')));

        $cmpBasket->tobasket();

        $this->assertLoggedException(StandardException::class, 'Test');
        $this->assertEquals(null, $this->getSessionParam('klarna_checkout_order_id'));
    }

    public function testUpdateKlarnaOrder()
    {
        $basket = $this->createStub(Basket::class, ['getKlarnaOrderLines' => ['test']]);
        Registry::getSession()->setBasket($basket);

        $client = $this->createStub(KlarnaCheckoutClient::class, ['createOrUpdateOrder' => ['testResult']]);

        $basketComponent = $this->createStub(KlarnaBasketComponent::class, ['getKlarnaCheckoutClient' => $client]);

        $class  = new \ReflectionClass(KlarnaBasketComponent::class);
        $method = $class->getMethod('updateKlarnaOrder');
        $method->setAccessible(true);

        $result = $method->invoke($basketComponent);

        $this->assertEquals(['testResult'], $result);
    }

}
