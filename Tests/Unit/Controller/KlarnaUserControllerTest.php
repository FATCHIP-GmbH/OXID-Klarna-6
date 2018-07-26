<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller;


use OxidEsales\Eshop\Application\Controller\UserController;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaUserControllerTest
 * @package TopConcepts\Klarna\Tests\Unit\Controller
 * @covers \TopConcepts\Klarna\Controller\KlarnaUserController
 */
class KlarnaUserControllerTest extends ModuleUnitTestCase
{

    public function initDataProvider()
    {
        $url = $this->removeQueryString($this->getConfig()->getShopSecureHomeUrl()) . 'cl=KlarnaExpress';

        return [
            ['amz-ref', 'KCO', 'DE', null],
            [ null, 'KCO', 'DE', $url ],
            [ null, 'KCO', 'AF', null ]
        ];
    }

    /**
     * @dataProvider initDataProvider
     * @param $amazonRef
     * @param $mode
     * @param $countryISO
     * @param $rUrl
     */
    public function testInit($amazonRef, $mode, $countryISO, $rUrl)
    {
        $this->setRequestParameter('amazonOrderReferenceId', $amazonRef);
        $this->setModuleMode($mode);
        $this->setSessionParam('sCountryISO', $countryISO);

        $userController = oxNew(UserController::class);
        $userController->init();

        $this->assertEquals($rUrl, \oxUtilsHelper::$sRedirectUrl);
        $this->assertEquals($amazonRef, $this->getSessionParam('amazonOrderReferenceId'));
    }

    public function testKlarnaResetCountry()
    {
        $sUrl = $this->getConfig()->getShopSecureHomeURL() . 'cl=KlarnaExpress&reset_klarna_country=1';
        $invadr = [
            'oxuser__oxcountryid' => 'Some Fake value',
            'oxuser__oxzip' => 'Some Fake value',
            'oxuser__oxstreet' => 'Some Fake value',
            'oxuser__oxstreetnr' => 'Some Fake value',
        ];
        $this->setRequestParameter('invadr', $invadr);

        $oUserController = oxNew(UserController::class);
        $oUserController->klarnaResetCountry();
        $invadr = $this->getSessionParam('invadr');

        $this->assertArrayNotHasKey('oxuser__oxcountryid',$invadr);
        $this->assertArrayNotHasKey('oxuser__oxzip', $invadr);
        $this->assertArrayNotHasKey('oxuser__oxstreet', $invadr);
        $this->assertArrayNotHasKey('oxuser__oxstreetnr', $invadr);
        $this->assertEquals($sUrl, \oxUtilsHelper::$response);
    }

    /**
     * @dataProvider getInvoiceAddressDataProvider
     * @param $mode
     * @param $testValue
     * @param $countryISO
     * @param $expectedResult
     */
    public function testGetInvoiceAddress($mode, $testValue, $countryISO, $expectedResult)
    {
        $this->setModuleMode($mode);
        $this->setRequestParameter('invadr', $testValue);
        $this->setSessionParam('sCountryISO', $countryISO);

        $oUserController = oxNew(UserController::class);
        $this->assertEquals($expectedResult, $oUserController->getInvoiceAddress());
    }

    public function getInvoiceAddressDataProvider()
    {
        return[
            ['KCO', ['addrField' => 'addrVal'], 'DE', ['addrField' => 'addrVal'] ],
            ['KCO', ['addrField' => 'addrVal'], 'AF', ['addrField' => 'addrVal'] ],
            ['KCO', [], 'DE', null ],
            ['KCO', [], 'AF', ['oxuser__oxcountryid' => '8f241f11095306451.36998225'] ],
            ['KP', ['addrField' => 'addrVal'], 'DE', ['addrField' => 'addrVal'] ],
        ];
    }


}
