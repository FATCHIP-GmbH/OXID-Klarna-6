<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use OxidEsales\Eshop\Application\Model\Actions;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Controller\Admin\KlarnaDesign;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaDesignTest extends ModuleUnitTestCase
{

    public function testRender()
    {
        $obj    = new KlarnaDesign();
        $result = $obj->render();

        $viewData = $obj->getViewData();
        $this->assertEquals('tcklarna_design.tpl', $result);
        $this->assertEquals('de_de', $viewData['locale']);
        $this->assertEquals('KCO', $viewData['mode']);

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        putenv("HTTP_X_REQUESTED_WITH=xmlhttprequest");
        $obj    = $this->createStub(KlarnaDesign::class, ['getMultiLangData' => 'test']);
        $result = $obj->render();
        $this->assertEquals('"test"', $result);

    }

    /**
     * @dataProvider testSaveDataProvider
     */
    public function testSave($input)
    {
        $this->setRequestParameter('settings', ['blKlarnaTeaserActive' => $input]);

        $controller = new KlarnaDesign;
        $controller->save();

        $teaserAction = oxNew(Actions::class);
        $teaserAction->load('klarna_teaser_' . Registry::getConfig()->getActiveShop()->getId());
        $result = $teaserAction->oxactions__oxactive->value;

        $this->assertEquals($input, $result);
    }

    public function testSaveDataProvider()
    {
        return [
            [1],
            [0],
        ];
    }
}
