<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 13.04.2018
 * Time: 15:23
 */

namespace TopConcepts\Klarna\Tests\Unit\Core;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use TopConcepts\Klarna\Core\KlarnaPayment;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaPaymentTest extends ModuleUnitTestCase
{

    public function testValidateToken()
    {

    }

    protected function getArgs()
    {
        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);
        $aPost = ['paymentId'];
        return [$oBasket, $oUser, $aPost];
    }

    public function constructorDataProvider()
    {

        $validTimeStump = (new \DateTime())->getTimestamp();
        $invalidTimeStump = $validTimeStump - 86400;
        return [
            [   // controller name = payment when not authorized
                // currency don't match the country
                [
                    'paymentId' => 'fromPOST'
                ],
                [
                    'authTokenValue' => null,
                    'finalizeRequired' => false,
                    'controllerName' => 'payment',
                    '_sPaymentMethod' => 'fromPOST',
                    'currencyid' => 1,
                    'currencyToCountryMatch' => false,
                    'error' => 'selected currency has to match the official currency',
                    'reauthorizeRequired' => null,
                    'klarna_session_data' => null,
                    'sSessionTimeStamp' => $validTimeStump,
                    'sessionValid' => true,
                    'sCountryISO' => 'DE',
                ],
            ],
            [   // controller name = order when isAuthorized (auth token is present)
                [],
                [
                    'authTokenValue' => 'the_token',
                    'finalizeRequired' => false,
                    'controllerName' => 'order',
                    '_sPaymentMethod' => 'default',
                    'currencyid' => 0,
                    'currencyToCountryMatch' => true,
                    'error' => null,
                    'reauthorizeRequired' => true,
                    'klarna_session_data' => ['sessionData'],
                    'sSessionTimeStamp' => $validTimeStump,
                    'sessionValid' => true,
                    'sCountryISO' => 'DE',
                ]
            ],
            [   // controller name = order when isAuthorized (finalizeRequired)
                [],
                [
                    'authTokenValue' => null,
                    'finalizeRequired' => true,
                    'controllerName' => 'order',
                    '_sPaymentMethod' => 'default',
                    'currencyid' => 0,
                    'currencyToCountryMatch' => true,
                    'error' => null,
                    'reauthorizeRequired' => true,
                    'klarna_session_data' => ['sessionData'],
                    'sSessionTimeStamp' => $validTimeStump,
                    'sessionValid' => true,
                    'sCountryISO' => 'DE',
                ]
            ],
        [       // invalid time stump
                [],
                [
                    'authTokenValue' => null,
                    'finalizeRequired' => true,
                    'controllerName' => 'order',
                    '_sPaymentMethod' => 'default',
                    'currencyid' => 0,
                    'currencyToCountryMatch' => true,
                    'error' => null,
                    'reauthorizeRequired' => true,
                    'klarna_session_data' => ['sessionData'],
                    'sSessionTimeStamp' => $invalidTimeStump,
                    'sessionValid' => false,
                    'sCountryISO' => 'DE',
                ]
            ],
            [   // Not klarna global country
                [],
                [
                    'authTokenValue' => null,
                    'finalizeRequired' => true,
                    'controllerName' => 'order',
                    '_sPaymentMethod' => 'default',
                    'currencyid' => 0,
                    'currencyToCountryMatch' => false,
                    'error' => 'No Klarna payment methods available',
                    'reauthorizeRequired' => true,
                    'klarna_session_data' => ['sessionData'],
                    'sSessionTimeStamp' => $invalidTimeStump,
                    'sessionValid' => false,
                    'sCountryISO' => 'AF',
                ]
            ],
        ];
    }

    /**
     * @dataProvider constructorDataProvider
     * @param $aPost
     * @param $results
     * @throws \oxSystemComponentException
     */
    public function test__construct($aPost, $results)
    {
        $this->getConfig()->setActShopCurrency($results['currencyid']);
        $this->setLanguage(1);

        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);

        $this->setSessionParam('sAuthToken', $results['authTokenValue']);
        $this->setSessionParam('finalizeRequired', $results['finalizeRequired']);
        $this->setSessionParam('klarna_session_data', $results['klarna_session_data']);
        $this->setSessionParam('sSessionTimeStamp', $results['sSessionTimeStamp']);
        $this->setSessionParam('sCountryISO', $results['sCountryISO']);
        $this->setSessionParam('paymentid', 'default');

        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, $aPost);

        $this->assertContains($results['controllerName'], $oKlarnaOrder->refreshUrl);
        $this->assertEquals($results['_sPaymentMethod'], $this->getProtectedClassProperty($oKlarnaOrder, '_sPaymentMethod'));
        $this->assertEquals($results['currencyToCountryMatch'], $this->getProtectedClassProperty($oKlarnaOrder, 'currencyToCountryMatch'));
        $this->assertEquals((bool)$results['error'], $oKlarnaOrder->isError());
        $results['error'] && $this->assertContains($results['error'], $oKlarnaOrder->getError()[0]);
        $this->assertEquals($results['reauthorizeRequired'], $this->getSessionParam('reauthorizeRequired'));
        $this->assertEquals($results['sessionValid'], $oKlarnaOrder->isSessionValid());

        $this->setSessionParam('sAuthToken', null);
        $this->setSessionParam('finalizeRequired', null);
        $this->setSessionParam('klarna_session_data', null);
        $this->setSessionParam('sSessionTimeStamp', null);
        $this->setSessionParam('sCountryISO', null);
        $this->setSessionParam('paymentid', null);
    }

    public function testIsAuthorized()
    {

        list($oBasket, $oUser, $aPost) = $this->getArgs();
        $oKlarnaOrder = $this->getMock(KlarnaPayment::class, ['requiresFinalization'], [$oBasket, $oUser, $aPost]);
        $oKlarnaOrder->expects($this->at(0))->method('requiresFinalization')->willReturn(true);
        $oKlarnaOrder->expects($this->at(1))->method('requiresFinalization')->willReturn(false);
        $oKlarnaOrder->expects($this->at(2))->method('requiresFinalization')->willReturn(false);

        $result = $oKlarnaOrder->isAuthorized();
        $this->assertTrue($result);
        $result = $oKlarnaOrder->isAuthorized();
        $this->assertFalse($result);
        $result = $oKlarnaOrder->isAuthorized();
        $this->assertFalse($result);

        $this->setSessionParam('sAuthToken', 'tokenValue');
        $result = $oKlarnaOrder->isAuthorized();
        $this->assertTrue($result);
        $this->setSessionParam('sAuthToken', null);
    }

    public function testIsOrderStateChanged()
    {
        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);

        $this->setProtectedClassProperty($oKlarnaOrder, 'aUpdateData', []);
        $this->assertFalse($oKlarnaOrder->isOrderStateChanged());
        $this->setProtectedClassProperty($oKlarnaOrder, 'aUpdateData', ['update_data']);
        $this->assertTrue($oKlarnaOrder->isOrderStateChanged());
    }

    public function testIsTokenValid()
    {
        $oKlarnaOrder = $this->createStub(KlarnaPayment::class, ['requiresFinalization' => true]);
        $this->assertTrue($oKlarnaOrder->isTokenValid());

        $oKlarnaOrder = $this->createStub(KlarnaPayment::class, ['requiresFinalization' => false]);
        $this->assertFalse($oKlarnaOrder->isTokenValid());

        $validTimeStump = (new \DateTime())->getTimestamp();
        $invalidTimeStump = $validTimeStump - 3590;

        $this->setSessionParam('sTokenTimeStamp', $validTimeStump);
        $this->assertTrue($oKlarnaOrder->isTokenValid());

        $this->setSessionParam('sTokenTimeStamp', $invalidTimeStump);
        $this->assertFalse($oKlarnaOrder->isTokenValid());

        $this->setSessionParam('sTokenTimeStamp', null);
    }

    public function testChecksumCheck()
    {

    }

    public function testSaveCheckSums()
    {

    }

    public function testGetOrderData_AddOptions()
    {
        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);

        $this->setModuleConfVar('aKlarnaDesign', null);
        $this->setModuleConfVar('aKlarnaDesignKP', null);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);
        $this->assertArrayNotHasKey('options', $oKlarnaOrder->getOrderData());

        $this->setModuleConfVar('aKlarnaDesign', ['aKlarnaDesign'], 'arr');
        $this->setModuleConfVar('aKlarnaDesignKP', ['aKlarnaDesignKP'], 'arr');
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);
        $this->assertArrayHasKey('options', $oKlarnaOrder->getOrderData());
        $this->assertEquals(['aKlarnaDesign', 'aKlarnaDesignKP'], $oKlarnaOrder->getOrderData()['options']);
    }

    public function testDisplayErrors()
    {

    }

    public function testAddErrorMessage()
    {

    }

    public function testGetUserData()
    {

    }

//    public function testValidateCountryAndCurrency()
//    {
//
//    }

    public function testGetChangedData()
    {

    }

    public function testSetStatus()
    {

    }

    public function testFetchCheckSums()
    {

    }

    public function testGetPaymentMethodCategory()
    {

    }

    public function testValidateClientToken()
    {

    }

    public function testValidateKlarnaUserData()
    {

    }

    public function testValidateOrder()
    {

    }

    public function testGetStatus()
    {

    }

    public function testCountryWasChanged()
    {

    }

    public function testSetCheckSum()
    {

    }

    public function testCleanUpSession()
    {

    }
}
