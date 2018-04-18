<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 26.03.2018
 * Time: 13:44
 */

namespace TopConcepts\Klarna\Tests\Unit\Components;

use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Core\ViewConfig;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaUserComponentTest
 * @package TopConcepts\Klarna\Tests\Unit\Components
 * @covers \TopConcepts\Klarna\Components\KlarnaUserComponent
 */
class KlarnaUserComponentTest extends ModuleUnitTestCase
{

    protected function setUp()
    {
        parent::setUp();
    }

    public function loginDataProvider()
    {
        $redirectUrl = $this->removeQueryString($this->getConfig()->getShopSecureHomeUrl()) . 'cl=KlarnaExpress';

        return [
            ['KCO', true, false, null ],
            ['KCO', true, true, $redirectUrl],
            ['KP', true, true, null]
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
        $this->getConfig()->saveShopConfVar(null, 'sKlarnaActiveMode', $klMode, $shopId = $this->getShopId(), $module = 'klarna');

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
            ['KP', 1, null, null, null]
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
        $this->getConfig()->saveShopConfVar(null, 'sKlarnaActiveMode', $klMode, $shopId = $this->getShopId(), $module = 'klarna');
        $this->setRequestParameter('blshowshipaddress', $showShippingAddress);
        $this->setRequestParameter('oxaddressid', $addressIdResult);

        $cmpUser = $this->getMock(UserComponent::class, ['getParent']);
        $cmpUser->expects($this->once())->method('getParent')->willReturn('account_user');

        $cmpUser->changeuser_testvalues();
        $this->assertEquals($resetResult, $this->getSessionParam('resetKlarnaSession'));
        $this->assertEquals($showShippingAddressResult, $this->getSessionParam('blshowshipaddress'));
        $this->assertEquals($addressIdResult, $this->getSessionParam('deladrid'));
    }

    /**
     * covers \TopConcepts\Klarna\Components\KlarnaUserComponent::_getLogoutLink
     */
    public function testLogout()
    {
        \oxTestModules::addFunction("oxUser", "logout", "{ return true;}");

        $this->setRequestParameter('cl', 'KlarnaExpress');
        $oParent = $this->getMock(\OxidEsales\Eshop\Application\Controller\FrontendController::class, array("isEnabledPrivateSales"));
        $oParent->expects($this->once())->method('isEnabledPrivateSales')->will($this->returnValue(false));

        $aMockFnc = array('_afterLogout', 'getParent');
        $oUserView = $this->getMock(\OxidEsales\Eshop\Application\Component\UserComponent::class, $aMockFnc);
        $oUserView->expects($this->once())->method('_afterLogout');

        $oUserView->expects($this->any())->method('getParent')->will($this->returnValue($oParent));

        $this->setRequestParameter('redirect', true);
        $oViewConfig = $this->getMock(ViewConfig::class, ['isKlarnaCheckoutEnabled']);
        $oViewConfig->expects($this->any())
            ->method('isKlarnaCheckoutEnabled')->willReturn(true);
       \oxTestModules::addModuleObject(ViewConfig::class, $oViewConfig);

        $oUserView->logout();

        $this->markTestIncomplete("Capture and check redirect link. UserComponent:336 _getLogoutLink call");

    }
}
