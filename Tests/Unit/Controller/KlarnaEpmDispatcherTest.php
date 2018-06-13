<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller;


use OxidEsales\Eshop\Core\ViewConfig;
use TopConcepts\Klarna\Controller\KlarnaEpmDispatcher;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaEpmDispatcherTest extends ModuleUnitTestCase
{

    public function testAmazonLogin()
    {
        $view = $this->createStub(ViewConfig::class, ['getAmazonProperty' => 'https://widgetUrl', 'getAmazonConfigValue' => 'test', 'getModuleUrl' => 'https://moduleUrl']);
        $epmDispatcher = $this->createStub(KlarnaEpmDispatcher::class, ['init' => null, 'getViewConfig' => $view]);
        $epmDispatcher->amazonLogin();
        $result = $this->getProtectedClassProperty($epmDispatcher, '_aViewData');

        $expected = [
            'sAmazonWidgetUrl' => 'https://widgetUrl',
            'sAmazonSellerId' => 'test',
            'sModuleUrl' => 'https://moduleUrl'
        ];
        $this->assertEquals($expected, $result);

    }

    public function testRender()
    {
        $epmDispatcher = $this->createStub(KlarnaEpmDispatcher::class, ['init' => null]);
        $result = $epmDispatcher->render();
        $this->assertNull($result);

        $this->setProtectedClassProperty($epmDispatcher, '_sThisTemplate', 'templatetest');
        $result = $epmDispatcher->render();
        $this->assertEquals($result, 'templatetest');

    }
}
