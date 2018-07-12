<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller\Admin;


use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Controller\Admin\KlarnaConfiguration;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Model\KlarnaCountryList;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

class KlarnaConfigurationTest extends ModuleUnitTestCase
{

    public function testRender()
    {
        $payment = $this->createStub(Payment::class, ['getKPMethods' => 'methods', 'load' => null]);
        $payment->oxpayments__oxactive = new Field(2, Field::T_RAW);
        UtilsObject::setClassInstance(Payment::class, $payment);
        $this->setConfigParam('sSSLShopURL', null);
        $this->setModuleConfVar('sKlarnaActiveMode', 'KCO');
        $controller = new KlarnaConfiguration();
        $result = $controller->render();


        $viewData = $controller->getViewData();
        $this->assertEquals('methods', $viewData['aKPMethods']);
        $this->assertEquals('de-DE', $viewData['sLocale']);
        $this->assertTrue($viewData['sslNotSet']);
        $this->assertTrue($viewData['KCOinactive']);
        $this->assertTrue($viewData['blGermanyActive']);
        $this->assertTrue($viewData['blAustriaActive']);
        $this->assertInstanceOf(KlarnaCountryList::class, $viewData['activeCountries']);
        $this->assertEquals('{}', $viewData['tcklarna_countryList']);
        $this->assertEquals('tcklarna_kco_config.tpl', $result);
        $this->setModuleConfVar('sKlarnaActiveMode', 'KP');
        $result = $controller->render();
        $this->assertEquals('tcklarna_kp_config.tpl', $result);

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        putenv("HTTP_X_REQUESTED_WITH=xmlhttprequest");
        $obj = $this->createStub(KlarnaConfiguration::class, ['getMultiLangData' => 'test']);
        $result = $obj->render();
        $this->assertEquals('"test"', $result);

    }

    public function testGetActiveCheckbox()
    {
        $controller = new KlarnaConfiguration();
        $result = $controller->getActiveCheckbox();
        $this->assertEquals(0, $result);

        $this->setModuleConfVar('iKlarnaActiveCheckbox', 10);
        $result = $controller->getActiveCheckbox();
        $this->assertEquals(10, $result);
    }

    public function testGetErrorMessages()
    {
        $controller = new KlarnaConfiguration();
        $result = $controller->getErrorMessages();

        $this->assertNotEmpty($result);

    }

    public function testGetKlarnaValidationOptions()
    {
        $controller = new KlarnaConfiguration();
        $result = $controller->getKlarnaValidationOptions();

        $expected = [
            "Keine Validierung",
            "Validierung durchführen, aber Timeouts ignorieren",
            "Erfolgreiche Validierung erforderlich",
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetKlarnaCheckboxOptions()
    {
        $controller = new KlarnaConfiguration();
        $result = $controller->getKlarnaCheckboxOptions();
        $expected = [
            "Keine Checkbox anzeigen",
            "Kundenkonto im OXID eShop anlegen",
            "Newsletter-Anmeldung",
            "Kundenkonto anlegen UND Newsletter-Anmeldung",
        ];

        $this->assertEquals($expected, $result);
    }

    public function testTrueIsGBActiveShopcountry()
    {
        $controller = new KlarnaConfiguration();
        $result = $controller->isGBActiveShopCountry();
        $this->assertTrue($result);
    }

    public function testGetChosenValidation()
    {
        $controller = new KlarnaConfiguration();
        $result = $controller->getChosenValidation();
        $this->assertEquals(0, $result);

        $this->setModuleConfVar('iKlarnaValidation', 10);
        $result = $controller->getActiveCheckbox();
        $this->assertEquals(10, $result);
    }

    /**
     * @dataProvider activeShopCountryDataProvider
     */
    public function testIsActiveShopCountry($method)
    {
        $controller = new KlarnaConfiguration();
        $country = $this->createStub(Country::class, []);
        $country->oxcountry__oxisoalpha2 = new Field('invalid', Field::T_RAW);
        UtilsObject::setClassInstance(Country::class, $country);

        $result = $controller->$method();
        $this->assertFalse($result);
    }

    public function activeShopCountryDataProvider()
    {
        return [
            ['isGermanyActiveShopCountry'],
            ['isAustriaActiveShopCountry'],
            ['isGBActiveShopCountry'],
        ];
    }

    public function testSave()
    {
        $this->setRequestParameter('kpMethods', []);
        $config = oxNew(KlarnaConfiguration::class);
        $this->setModuleConfVar('sKlarnaActiveMode' , 'KCO');
        $config->save();

        $this->assertFalse(KlarnaUtils::isKlarnaPaymentsEnabled());

        $this->setModuleConfVar('sKlarnaActiveMode' , 'KP');
        $config->save();

        $this->assertTrue(KlarnaUtils::isKlarnaPaymentsEnabled());
    }
}
