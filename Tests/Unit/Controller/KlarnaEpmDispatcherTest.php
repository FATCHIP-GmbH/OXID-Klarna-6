<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller;


use OxidEsales\Eshop\Core\ViewConfig;
use TopConcepts\Klarna\Controller\KlarnaEpmDispatcher;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaEpmDispatcherTest extends ModuleUnitTestCase
{

    public function testAmazonLogin()
    {
        $view = $this->getMockBuilder(ViewConfig::class)->setMethods(['getAmazonProperty', 'getAmazonConfigValue', 'getModuleUrl'])->getMock();
        $view->expects($this->once())->method('getAmazonProperty')->willReturn('https://widgetUrl');
        $view->expects($this->once())->method('getAmazonConfigValue')->willReturn('test');
        $view->expects($this->once())->method('getModuleUrl')->willReturn('https://moduleUrl');
        $epmDispatcher = $this->getMockBuilder(KlarnaEpmDispatcher::class)->setMethods(['init', 'getViewConfig'])->getMock();
        $epmDispatcher->expects($this->once())->method('getViewConfig')->willReturn($view);
        $epmDispatcher->amazonLogin();
        $result = $this->getProtectedClassProperty($epmDispatcher, '_aViewData');
        $expected = [
            'sAmazonWidgetUrl' => 'https://widgetUrl',
            'sAmazonSellerId' => 'test',
            'sModuleUrl' => 'https://moduleUrl'
        ];
        $this->assertEquals($expected, $result);
    }
}
