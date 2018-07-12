<?php

namespace TopConcepts\Klarna\Tests\Unit\Component;


use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\ViewConfig;
use ReflectionClass;
use TopConcepts\Klarna\Component\KlarnaUserComponent;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

/**
 * Class KlarnaUserComponentTest
 * @package TopConcepts\Klarna\Tests\Unit\Components
 * @covers \TopConcepts\Klarna\Component\KlarnaUserComponent
 */
class KlarnaUserComponentTest extends ModuleUnitTestCase
{
    public function loginDataProvider()
    {
        $redirectUrl = $this->removeQueryString($this->getConfig()->getShopSecureHomeUrl()) . 'cl=KlarnaExpress';

        return [
            ['KCO', true, false, null],
            ['KCO', true, true, $redirectUrl],
            ['KP', true, true, null],
        ];
    }

    /**
     * @dataProvider loginDataProvider
     * @param $klMode
     * @param $isEnabledPrivateSales
     * @param $isKlarnaController
     * @param $redirectUrl
     */
    public function testLogin_noredirect($klMode, $isEnabledPrivateSales, $isKlarnaController, $redirectUrl)
    {
        $this->getConfig()->saveShopConfVar(null, 'sKlarnaActiveMode', $klMode, $shopId = $this->getShopId(), $module = 'tcklarna');

        $cmpUser = $this->getMock(UserComponent::class, ['klarnaRedirect']);
        $cmpUser->expects($this->any())->method('klarnaRedirect')->willReturn($isKlarnaController);

        $oParent = $this->getMock('stdClass', array('isEnabledPrivateSales'));
        $oParent->expects($this->any())->method('isEnabledPrivateSales')->willReturn($isEnabledPrivateSales);
        $cmpUser->setParent($oParent);

        $cmpUser->login_noredirect();

        $this->assertEquals($redirectUrl, \oxUtilsHelper::$sRedirectUrl);
    }

    public function stateDataProvider()
    {
        return [
            ['KCO', 1, 1, 1, null],
            ['KCO', 1, 1, 1, 'fake_id'],
            ['KCO', 0, 1, null, null],
            ['KP', 1, null, null, null],
        ];
    }

    /**
     * @dataProvider stateDataProvider
     * @param $klMode
     * @param $showShippingAddress
     * @param $resetResult
     * @param $showShippingAddressResult
     * @param $addressIdResult
     */
    public function testChangeuser_testvalues($klMode, $showShippingAddress, $resetResult, $showShippingAddressResult, $addressIdResult)
    {
        $this->getConfig()->saveShopConfVar(null, 'sKlarnaActiveMode', $klMode, $shopId = $this->getShopId(), $module = 'tcklarna');
        $this->setRequestParameter('blshowshipaddress', $showShippingAddress);
        $this->setRequestParameter('oxaddressid', $addressIdResult);

        $cmpUser = $this->getMock(UserComponent::class, ['_changeUser_noRedirect']);
        $cmpUser->expects($this->once())->method('_changeUser_noRedirect')->willReturn(true);

        $cmpUser->changeuser_testvalues();
        $this->assertEquals($resetResult, $this->getSessionParam('resetKlarnaSession'));
        $this->assertEquals($showShippingAddressResult, $this->getSessionParam('blshowshipaddress'));
        $this->assertEquals($addressIdResult, $this->getSessionParam('deladrid'));
    }

    /**
     * @dataProvider getLogoutLinkDataProvider
     * @param $isKlarnaCheckoutEnabled
     * @param $isKlarnaRedirect
     * @throws \ReflectionException
     */
    public function test_getLogoutLink($isKlarnaCheckoutEnabled, $isKlarnaRedirect, $expectedResult)
    {
        $oViewConfig = $this->getMock(ViewConfig::class, ['isKlarnaCheckoutEnabled']);
        $oViewConfig->expects($this->any())
            ->method('isKlarnaCheckoutEnabled')->willReturn($isKlarnaCheckoutEnabled);
        UtilsObject::setClassInstance(ViewConfig::class, $oViewConfig);

        $baseController = $this->getMock(BaseController::class, ['getDynUrlParams']);
        $userComponent  = $this->getMock(UserComponent::class, ['klarnaRedirect', 'getParent']);
        $userComponent->expects($this->any())->method('getParent')->willReturn($baseController);
        $userComponent->expects($this->any())->method('getDynUrlParams')->willReturn('dyna');
        $userComponent->expects($this->any())->method('klarnaRedirect')->willReturn($isKlarnaRedirect);

        $class = new ReflectionClass(get_class($userComponent));
        $sut   = $class->getMethod('_getLogoutLink');
        $sut->setAccessible(true);

        $result = $sut->invokeArgs($userComponent, []);

        $this->assertEquals($expectedResult, $result);
    }

    public function getLogoutLinkDataProvider()
    {
        $res1 = $this->getConfig()->getShopUrl() . 'index.php?cl=basket&amp;fnc=logout';
        $res2 = $this->getConfig()->getShopUrl() . 'index.php?cl=&amp;fnc=logout';

        return [
            [false, false, $res2],
            [true, true, $res1],
            [true, false, $res2],
            [false, true, $res2],
        ];
    }

    public function testGetKlarnaRedirect()
    {
        $this->setRequestParameter('cl', 'test');
        $userComp = oxNew(KlarnaUserComponent::class);

        $class  = new \ReflectionClass(KlarnaUserComponent::class);
        $method = $class->getMethod('klarnaRedirect');
        $method->setAccessible(true);

        $this->setProtectedClassProperty($userComp, '_aClasses', ['test']);

        $result = $method->invoke($userComp);

        $this->assertTrue($result);
    }
}
