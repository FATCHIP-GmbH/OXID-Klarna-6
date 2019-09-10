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

class KlarnaServiceMenuTest extends ModuleUnitTestCase {
    public function testInit() {
        $topViewAny = $this->getMockBuilder(FrontendController::class)->setMethods(['getClassName', 'isKlarnaFakeUser'])->getMock();
        $topViewAny->expects($this->once())->method('getClassName')->willReturn('test');
        $topViewAny->expects($this->any())->method('isKlarnaFakeUser')->willReturn(true);
        $config = $this->getMockBuilder(Config::class)->setMethods(['getTopActiveView'])->getMock();
        $config->expects($this->any())->method('getTopActiveView')->willReturn($topViewAny);
        $serviceMenuMock = $this->getMockBuilder(ServiceMenu::class)->setMethods(['getConfig'])->getMock();
        $serviceMenuMock->expects($this->any())->method('getConfig')->willReturn($config);
        $serviceMenuMock->init();

        $componentNames = $this->getProtectedClassProperty($serviceMenuMock, '_aComponentNames');
        $this->assertArrayHasKey('oxcmp_user', $componentNames);


        $topViewKlarnaExpress = $this->getMockBuilder(FrontendController::class)
            ->setMethods(['getClassName', 'isKlarnaFakeUser'])
            ->getMock();
        $topViewKlarnaExpress->expects($this->once())->method('getClassName')->willReturn('klarnaexpress');
        $topViewKlarnaExpress->expects($this->once())->method('isKlarnaFakeUser')->willReturn(true);
        $config = $this->getMockBuilder(Config::class)->setMethods(['getTopActiveView'])->getMock();
        $config->expects($this->once())->method('getTopActiveView')->willReturn($topViewKlarnaExpress);
        $serviceMenuMock = $this->getMockBuilder(ServiceMenu::class)->setMethods(['getConfig'])->getMock();
        $serviceMenuMock->expects($this->any())->method('getConfig')->willReturn($config);
        $serviceMenuMock->init();

        $componentNames = $this->getProtectedClassProperty($serviceMenuMock, '_aComponentNames');
        $this->assertEmpty($componentNames);
    }
}
