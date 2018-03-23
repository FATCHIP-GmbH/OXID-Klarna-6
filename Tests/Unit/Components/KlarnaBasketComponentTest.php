<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 22.03.2018
 * Time: 17:12
 */
//namespace TopConcepts\Klarna\Tests\Unit\Components;

use OxidEsales\Eshop\Application\Component\BasketComponent;
use OxidEsales\Eshop\Core\Request;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaBasketComponentTest extends ModuleUnitTestCase
{

    protected function setUp()
    {
        parent::setUp();



    }

    public function testActionKlarnaExpressCheckoutFromDetailsPage()
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

        /** @var oxBasketItem|PHPUnit_Framework_MockObject_MockObject $oBItem */
        $oBItem = $this->getMock(\OxidEsales\Eshop\Application\Model\BasketItem::class, array('getTitle', 'getProductId', 'getAmount', 'getdBundledAmount'));
        $oBItem->expects($this->once())->method('getTitle')->will($this->returnValue('ret:getTitle'));
        $oBItem->expects($this->once())->method('getProductId')->will($this->returnValue('ret:getProductId'));
        $oBItem->expects($this->once())->method('getAmount')->will($this->returnValue('ret:getAmount'));
        $oBItem->expects($this->once())->method('getdBundledAmount')->will($this->returnValue('ret:getdBundledAmount'));

        /** @var oxConfig|PHPUnit_Framework_MockObject_MockObject $oConfig */
        $oConfig = $this->getMock(\OxidEsales\Eshop\Core\Config::class, array('getConfigParam'));
        $oConfig->expects($this->at(0))->method('getConfigParam')->with($this->equalTo('iNewBasketItemMessage'))->will($this->returnValue('2'));
        $oConfig->expects($this->at(1))->method('getConfigParam')->with($this->equalTo('iNewBasketItemMessage'))->will($this->returnValue('2'));

        /** @var oxcmp_basket|PHPUnit_Framework_MockObject_MockObject $o */
        $o = $this->getMock(\OxidEsales\Eshop\Application\Component\BasketComponent::class, array('_getItems', '_setLastCallFnc', '_addItems', 'getConfig'));
        $o->expects($this->once())->method('_getItems')->will($this->returnValue($aProducts));
        $o->expects($this->once())->method('_setLastCallFnc')->with($this->equalTo('tobasket'))->will($this->returnValue(null));
        $o->expects($this->once())->method('_addItems')->with($this->equalTo($aProducts))->will($this->returnValue($oBItem));
        $o->expects($this->exactly(2))->method('getConfig')->will($this->returnValue($oConfig));

        $o->actionKlarnaExpressCheckoutFromDetailsPage();

        $redirectUrl = $this->getConfig()->getShopSecureHomeUrl() . 'cl=KlarnaExpress';
        $this->assertEquals($redirectUrl, oxUtilsHelper::$sRedirectUrl);
    }

    public function testChangebasket()
    {
        $this->setSessionParam('klarna_checkout_order_id', 'fakeSessionId');

        // create a mock class with expectations
        var_dump(\TopConcepts\Klarna\Core\KlarnaUtils::isKlarnaCheckoutEnabled());


        $cmpBasket = oxNew(BasketComponent::class);
        $cmpBasket->changebasket('abc', 11, 'sel', 'persparam', 'override');

    }

    public function testTobasket()
    {

    }

}
