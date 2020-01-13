<?php


namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use TopConcepts\Klarna\Controller\Admin\KlarnaInstantShopping;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Core\InstantShopping\HttpClient;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\Request;

class KlarnaInstantShoppingTest extends ModuleUnitTestCase
{

    public function testInit()
    {
        $class = $this->getMockBuilder(KlarnaInstantShopping::class)
            ->disableOriginalConstructor()
            ->setMethods(['_authorize'])
            ->getMock();

        $class->expects($this->once())->method('_authorize')->willReturn(true);

        $class->init();
        $result = $this->getProtectedClassProperty($class, 'instantShoppingClient');
        $this->assertNotEmpty($result);
    }

    public function testIsReplaceButtonRequest()
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequestParameter'])
            ->getMock();
        $request->expects($this->once())->method('getRequestParameter')->willReturn('1');

        $class = $this->getMockBuilder(KlarnaInstantShopping::class)
            ->disableOriginalConstructor()
            ->setMethods(['_authorize'])
            ->getMock();

        $this->setProtectedClassProperty($class, '_oRequest', $request);

        $result = $class->isReplaceButtonRequest();

        $this->assertTrue($result);
    }

    public function testRender()
    {
        $class = $this->getMockBuilder(KlarnaInstantShopping::class)
            ->disableOriginalConstructor()
            ->setMethods(['addTplParam','setEditObjectId', 'generateAndSaveButtonKey', 'isReplaceButtonRequest'])
            ->getMock();

        $this->setConfigParam('blKlarnaInstantShoppingEnabled', true);
        $class->expects($this->once())->method('isReplaceButtonRequest')->willReturn(true);
        $class->expects($this->once())->method('setEditObjectId');
        $class->expects($this->any())->method('generateAndSaveButtonKey')->willReturn(true);

        $result = $class->render();
        $this->assertSame("tcklarna_instant_shopping.tpl", $result);
    }

    public function testGenerateAndSaveButtonKey()
    {
        $instantShoppingClient = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['createButton'])
            ->getMock();

        $button['button_key'] = 'key';
        $instantShoppingClient->expects($this->any())->method('createButton')->willReturn($button);

        $class = $this->getMockBuilder(KlarnaInstantShopping::class)
            ->disableOriginalConstructor()
            ->setMethods(['isReplaceButtonRequest'])
            ->getMock();

        $this->setProtectedClassProperty($class, 'instantShoppingClient', $instantShoppingClient);
        $class->generateAndSaveButtonKey();
        $result = $this->getConfig()->getShopConfVar('strKlarnaISButtonKey');
        $this->assertNotEmpty($result);

        $instantShoppingClient->expects($this->any())->method('createButton')->willThrowException(new KlarnaClientException('error'));
        $this->setProtectedClassProperty($class, 'instantShoppingClient', $instantShoppingClient);
        $result = $class->generateAndSaveButtonKey();
        $this->assertEmpty($result);

    }

    public function testGetButtonRequestData()
    {
        $this->setConfigParam('sKlarnaDefaultCountry', 'DE');
        $class = $this->getMockBuilder(KlarnaInstantShopping::class)
            ->disableOriginalConstructor()
            ->setMethods(['addTplParam'])
            ->getMock();

        $result = $class->getButtonRequestData();

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('merchant_urls', $result);
        $this->assertArrayHasKey('purchase_country', $result);
        $this->assertArrayHasKey('purchase_currency', $result);
        $this->assertArrayHasKey('options', $result);
        $this->assertArrayHasKey('styling', $result);
    }

    public function testGetErrorMessages() {
        $controller = new KlarnaInstantShopping();
        $result = $controller->getErrorMessages();

        $this->assertNotEmpty($result);

    }
}