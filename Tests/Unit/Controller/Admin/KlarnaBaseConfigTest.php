<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use OxidEsales\Eshop\Core\Database\Adapter\Doctrine\ResultSet;
use TopConcepts\Klarna\Controller\Admin\KlarnaBaseConfig;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaBaseConfigTest extends ModuleUnitTestCase
{
    public function testGetAllActiveOxPaymentIds()
    {
        $stub   = $this->createStub(KlarnaBaseConfig::class, ['_authorize' => true]);
        $result = $stub->getAllActiveOxPaymentIds();
        $this->assertInstanceOf(ResultSet::class, $result);
    }

    public function testRender()
    {
        $stub = $this->createStub(KlarnaBaseConfig::class, ['_authorize' => true, 'getEditObjectId' => 'test']);
        $stub->init();
        $stub->render();
        $confaarrs = $stub->getViewData()['confaarrs'];

        $this->assertEmpty($confaarrs);
    }

    public function testSave()
    {
        $stub = $this->createStub(KlarnaBaseConfig::class, ['_authorize' => true]);
        $stub->init();
        $stub->save();
        $this->assertNull($stub->getParameter('confaarrs'));

        $stub = $this->createStub(KlarnaBaseConfig::class, ['_authorize' => true, 'getParameter' => ['test' => 'test']]);
        $this->setProtectedClassProperty($stub, '_aConfParams', ['test' => 'test']);
        $stub->init();
        $stub->save();

        $this->assertEquals(['test' => 'test'], $stub->getParameter('confaarrs'));

        $stub = $this->createStub(KlarnaBaseConfig::class, ['_authorize' => true, 'getParameter' => ['test' => 'test']]);
        $this->setProtectedClassProperty($stub, '_aConfParams', ['test' => 'test']);
        $stub->init();
        $stub->save();
    }

    public function testGetFlippedLangArray()
    {
        $stub   = $this->createStub(KlarnaBaseConfig::class, ['init' => true]);
        $result = $stub->getFlippedLangArray();
        $de     = $result['de'];
        $en     = $result['en'];

        $deExpected           = $this->getLangExpected();
        $deExpected->selected = 0;
        $this->assertEquals($de, $deExpected);

        $enExpected = $this->getLangExpected('en');

        $this->assertEquals($en, $enExpected);

    }

    public function testSetParameter()
    {
        $stub = $this->createStub(KlarnaBaseConfig::class, ['init' => true]);
        $stub->setParameter('test', 'test');
        $this->assertEquals($stub->getParameter('test'), 'test');
    }

    public function testInit()
    {
        $stub = $this->createStub(KlarnaBaseConfig::class, ['_authorize' => true]);
        $this->assertNull($this->getProtectedClassProperty($stub, '_oRequest'));
        $stub->init();
        $this->assertNotEmpty($this->getProtectedClassProperty($stub, '_oRequest'));
    }

    public function testGetManualDownloadLink()
    {
        $stub   = $this->createStub(KlarnaBaseConfig::class, ['init' => true]);
        $result = $stub->getManualDownloadLink();
        $this->assertStringStartsWith('https://', $result);
    }

    public function testGetLangs()
    {
        $stub   = $this->createStub(KlarnaBaseConfig::class, ['init' => true]);
        $result = json_decode(html_entity_decode($stub->getLangs()));
        $de     = $result[0];
        $en     = $result[1];

        $deExpected           = $this->getLangExpected();
        $deExpected->selected = 0;
        $this->assertEquals($de, $deExpected);

        $enExpected = $this->getLangExpected('en');

        $this->assertEquals($en, $enExpected);

    }

    protected function getLangExpected($lang = 'de')
    {
        if ($lang == 'de') {
            return (object)[
                'id'     => 0,
                'oxid'   => "de",
                'abbr'   => "de",
                'name'   => "Deutsch",
                'active' => "1",
                'sort'   => "1",
            ];

        } else {
            return (object)[
                'id'       => 1,
                'oxid'     => "en",
                'abbr'     => "en",
                'name'     => "English",
                'active'   => "1",
                'sort'     => "2",
                'selected' => 0,
            ];
        }
    }

    public function testGetMultiLangData()
    {
        $confstrs       = [
            'tcklarna_iKlarnaActiveCheckbox'               => '3',
            'tcklarna_iKlarnaValidation'                   => '2',
            'tcklarna_sKlarnaActiveMode'                   => 'KCO',
            'tcklarna_sKlarnaAnonymizedProductTitle'       => 'anonymized product',
            'tcklarna_sKlarnaAnonymizedProductTitle_DE'    => 'Produktname',
            'tcklarna_sKlarnaAnonymizedProductTitle_EN'    => 'Product name',
            'tcklarna_sKlarnaBannerSrc_DE'                 => '&lt;script src=&quot;https://embed.bannerflow.com/599d7ec18d988017005eb279?targeturl=https://www.klarna.com&amp;politeloading=off&amp;merchantid={{merchantid}}&amp;responsive=on&quot; async&gt;&lt;/script&gt;',
            'tcklarna_sKlarnaCancellationRightsURI_DE'     => 'https://www.example.com/cancel_deutsch.pdf',
            'tcklarna_sKlarnaCancellationRightsURI_EN'     => 'https://www.example.com/cancel_english.pdf',
            'tcklarna_sKlarnaDefaultCountry'               => 'DE',
            'tcklarna_sKlarnaFooterDisplay'                => '1',
            'tcklarna_sKlarnaFooterValue'                  => 'longBlack',
            'tcklarna_sKlarnaMerchantId'                   => 'K501664_9c5b3285c29f',
            'tcklarna_sKlarnaPassword'                     => '7NvBzZ5irjFqXcbA',
            'tcklarna_sKlarnaTermsConditionsURI_DE'        => 'https://www.example.com/tc_deutsch.pdf',
            'tcklarna_sKlarnaTermsConditionsURI_EN'        => 'https://www.example.com/tc_english.pdf',
            'tcklarna_tcklarna_sKlarnaCahippingDetails_DE' => '',
            'tcklarna_tcklarna_sKlarnaCahippingDetails_EN' => '',
            'sVersion'                                     => null,
        ];
        $expectedResult = [
            'confstrs[tcklarna_sKlarnaAnonymizedProductTitle_DE]' => 'Produktname',
            'confstrs[tcklarna_sKlarnaAnonymizedProductTitle_EN]' => 'Product name',
        ];

        $controller = $this->createStub(KlarnaBaseConfig::class, [
            'getViewDataElement' => $confstrs,
        ]);
        $this->setProtectedClassProperty($controller, 'MLVars', ['tcklarna_sKlarnaAnonymizedProductTitle_']);
        $methodReflection = new \ReflectionMethod(KlarnaBaseConfig::class, 'getMultiLangData');
        $methodReflection->setAccessible(true);

        $result = $methodReflection->invoke($controller);
        $this->assertEquals($expectedResult, $result);
    }

}
