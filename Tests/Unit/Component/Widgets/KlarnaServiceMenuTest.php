<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 08.08.2018
 * Time: 11:16
 */

namespace TopConcepts\Klarna\Tests\Unit\Component\Widgets;


use OxidEsales\Eshop\Application\Component\Widget\ServiceMenu;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\PayPalModule\Controller\FrontendController;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaServiceMenuTest extends ModuleUnitTestCase
{
    public function testInit()
    {
        $topViewAny = $this->createStub(FrontendController::class, ['getClassName' => 'test', 'isKlarnaFakeUser' => true]);
        $config = $this->createStub(Config::class, ['getTopActiveView' => $topViewAny]);
        $serviceMenuMock = $this->getMock(ServiceMenu::class, ['getConfig']);
        $serviceMenuMock->expects($this->any())->method('getConfig')->willReturn($config);
        $serviceMenuMock->init();

        $componentNames = $this->getProtectedClassProperty($serviceMenuMock, '_aComponentNames');
        $this->assertArrayHasKey('oxcmp_user', $componentNames);


        $topViewKlarnaExpress = $this->createStub(FrontendController::class, ['getClassName' => 'klarnaexpress', 'isKlarnaFakeUser' => true]);
        $config = $this->createStub(Config::class, ['getTopActiveView' => $topViewKlarnaExpress]);
        $serviceMenuMock = $this->getMock(ServiceMenu::class, ['getConfig']);
        $serviceMenuMock->expects($this->any())->method('getConfig')->willReturn($config);
        $serviceMenuMock->init();

        $componentNames = $this->getProtectedClassProperty($serviceMenuMock, '_aComponentNames');
        $this->assertEmpty($componentNames);
    }
}
