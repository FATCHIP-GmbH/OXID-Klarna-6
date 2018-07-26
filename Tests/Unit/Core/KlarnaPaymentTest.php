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
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\UtilsView;
use TopConcepts\Klarna\Core\KlarnaPayment;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

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

    public function ChecksumCheckDataProvider()
    {
        return [
            [
                [
                    '_aOrderLines' => false,
                    '_aUserData' => false,
                    '_sPaymentMethod' => false
                ],
                [
                    '_aOrderLines' => ['orderLines' => 'val'],
                    '_aUserData' => ['userData' => 'val'],
                    '_sPaymentMethod' => 'pay_name'
                ],
                [
                    'orderLines' => 'val',
                    'userData' => 'val'
                ],
                '', true,
            ],

            [
                [
                    '_aOrderLines' => '41e571b35e719e23f57f6763a70087cc',
                    '_aUserData' => 'd05c7d45632b1b3ff7d53e21991c4006',
                    '_sPaymentMethod' => false
                ],
                [
                    '_aOrderLines' => ['orderLines' => 'val'],
                    '_aUserData' => ['userData' => 'val'],
                    '_sPaymentMethod' => 'pay_name'
                ],
                [
                ],
                'addUserData', null,
            ],

            [
                [
                    '_aOrderLines' => '41e571b35e719e23f57f6763a70087cc',
                    '_aUserData' => 'd05c7d45632b1b3ff7d53e21991c4006',
                    '_sPaymentMethod' => 'da442e4fc8855a43f9061c96caa96bf7'
                ],
                [
                    '_aOrderLines' => ['orderLines' => 'val2'],
                    '_aUserData' => ['userData' => 'val2'],
                    '_sPaymentMethod' => 'pay_name'
                ],
                [
                    'orderLines' => 'val2',
                    'userData' => 'val2',
                ],
                '', null,
            ],

        ];

    }


    /**
     * @dataProvider ChecksumCheckDataProvider
     * @param $currentCheckSums
     * @param $properties
     * @param $toUpdate
     * @param $action
     * @param $paymentChanged
     */
    public function testChecksumCheck($currentCheckSums, $properties, $toUpdate, $action, $paymentChanged)
    {

        $oKlarnaOrder = $this->createStub(KlarnaPayment::class,
            ['fetchCheckSums' => null]
        );
        $this->setProtectedClassProperty($oKlarnaOrder, 'checkSums', $currentCheckSums);
        foreach($properties as $name => $value){
            $this->setProtectedClassProperty($oKlarnaOrder, $name, $value);
        }

        $this->setProtectedClassProperty($oKlarnaOrder, 'action', $action);
        $oKlarnaOrder->checksumCheck();
        $this->assertEquals($toUpdate, $oKlarnaOrder->getChangedData());
        $this->assertEquals($paymentChanged, $this->getProtectedClassProperty($oKlarnaOrder, 'paymentChanged'));


    }

    public function saveCheckSumsDataProvider()
    {
        return [
            [
                null,
                [ 'userData' => 'newData', 'orderData' => ''],
                ['_aUserData' => '7422beec444f8d3b06f6c3181f9402ca']
            ],
            [
                [
                    '_aOrderLines' => '41e571b35e719e23f57f6763a70087cc',
                    '_aUserData' => 'd05c7d45632b1b3ff7d53e21991c4006',
                    '_sPaymentMethod' => 'da442e4fc8855a43f9061c96caa96bf7'
                ],
                [],
                [
                    '_aOrderLines' => '41e571b35e719e23f57f6763a70087cc',
                    '_aUserData' => 'd05c7d45632b1b3ff7d53e21991c4006',
                    '_sPaymentMethod' => 'da442e4fc8855a43f9061c96caa96bf7'
                ],
            ],
            [
                [
                    '_aOrderLines' => '41e571b35e719e23f57f6763a70087cc',
                    '_aUserData' => 'd05c7d45632b1b3ff7d53e21991c4006',
                    '_sPaymentMethod' => 'da442e4fc8855a43f9061c96caa96bf7'
                ],
                [ 'userData' => 'newData', 'orderData' => ''],
                [
                    '_aOrderLines' => '41e571b35e719e23f57f6763a70087cc',
                    '_aUserData' => '7422beec444f8d3b06f6c3181f9402ca',
                    '_sPaymentMethod' => 'da442e4fc8855a43f9061c96caa96bf7'
                ],
            ],
            [
                [
                    '_aOrderLines' => '41e571b35e719e23f57f6763a70087cc',
                    '_aUserData' => 'd05c7d45632b1b3ff7d53e21991c4006',
                    '_sPaymentMethod' => 'da442e4fc8855a43f9061c96caa96bf7'
                ],
                [ 'userData' => '', 'orderData' => 'newData'],
                [
                    '_aOrderLines' => '7422beec444f8d3b06f6c3181f9402ca',
                    '_aUserData' => 'd05c7d45632b1b3ff7d53e21991c4006',
                    '_sPaymentMethod' => 'da442e4fc8855a43f9061c96caa96bf7'
                ],

            ]
        ];
    }

    /**
     * @dataProvider saveCheckSumsDataProvider
     * @param $currentCheckSums
     * @param $arg
     * @param $eRes
     */
    public function testSaveCheckSums($currentCheckSums, $arg, $eRes)
    {
        $oKlarnaOrder = $this->createStub(KlarnaPayment::class,
            ['fetchCheckSums' => $currentCheckSums]
        );
        $oKlarnaOrder->saveCheckSums($arg);
        $this->assertEquals($eRes, $this->getSessionParam('kpCheckSums'));
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
        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);
        $this->setProtectedClassProperty($oKlarnaOrder, 'errors', ['Error1', 'Error2']);
        $oUtilsView = $this->getMock(UtilsView::class, ['addErrorToDisplay']);
        $oUtilsView->expects($this->at(0))->method('addErrorToDisplay')->with('Error1');
        $oUtilsView->expects($this->at(1))->method('addErrorToDisplay')->with('Error2');
        \oxTestModules::addModuleObject(UtilsView::class, $oUtilsView);
        $oKlarnaOrder->displayErrors();
    }

    public function testAddErrorMessage()
    {
        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);

        $this->setLanguage(0);
        $oKlarnaOrder->addErrorMessage('TCKLARNA_KP_INVALID_TOKEN');
        $expectedErrors = ['Ungültiger Authorization Token. Bitte probieren Sie es noch einmal.'];
        $this->assertEquals($expectedErrors, $this->getProtectedClassProperty($oKlarnaOrder, 'errors'));

        $this->setLanguage(1);
        $expectedErrors[] = 'Invalid authorization token. Please try again.';
        $oKlarnaOrder->addErrorMessage('TCKLARNA_KP_INVALID_TOKEN');
        $this->assertEquals($expectedErrors, $this->getProtectedClassProperty($oKlarnaOrder, 'errors'));
    }

    public function testGetUserData()
    {
        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);
        $this->setProtectedClassProperty($oKlarnaOrder, '_aUserData', ['userData']);
        $result = $oKlarnaOrder->getUserData();
        $this->assertEquals(['userData'], $result);
    }

    public function testGetChangedData()
    {
        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);
        $this->setProtectedClassProperty($oKlarnaOrder, 'aUpdateData', ['aaaaa']);
        $result = $oKlarnaOrder->getChangedData();
        $this->assertEquals(['aaaaa'], $result);
    }

    public function testSetStatus()
    {
        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);

        $oKlarnaOrder->setStatus('NewStatus');
        $result = $this->getProtectedClassProperty($oKlarnaOrder, 'status');
        $this->assertEquals('NewStatus', $result);
    }

    public function testFetchCheckSums()
    {
        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);

        $this->setSessionParam('kpCheckSums', ['myCheckSums']);
        $result = $oKlarnaOrder->fetchCheckSums();
        $expectedResult = ['myCheckSums'];
        $this->assertEquals($expectedResult, $result);

        $this->setSessionParam('kpCheckSums', null);
        $result = $oKlarnaOrder->fetchCheckSums();
        $expectedResult = [
            '_aOrderLines' => false,
            '_aUserData' => false,
            '_sPaymentMethod' => false
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function paymentCategoryNameDataProvider()
    {
        return [
            ['ss_name', null],
            ['klarna_pay_now', 'pay_now'],
            ['klarna_pay_later', 'pay_later'],
            ['klarna_slice_it', 'pay_over_time'],
        ];
    }

    /**
     * @dataProvider paymentCategoryNameDataProvider
     * @param $paymentMethodName
     * @param $eRes
     */
    public function testGetPaymentMethodCategory($paymentMethodName, $eRes)
    {
        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);
        $this->setProtectedClassProperty($oKlarnaOrder, '_sPaymentMethod', $paymentMethodName);
        $result = $oKlarnaOrder->getPaymentMethodCategory();
        $this->assertEquals($eRes, $result);

    }

    public function testValidateClientToken()
    {
        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);

        $this->setSessionParam('klarna_session_data', ['client_token' => 'the_token']);
        $this->assertTrue($oKlarnaOrder->validateClientToken('the_token'));

        $this->setSessionParam('klarna_session_data', ['client_token' => 'the_token']);
        $this->assertFalse($oKlarnaOrder->validateClientToken('another_token'));
        $this->assertEquals(['TCKLARNA_INVALID_CLIENT_TOKEN'], $this->getProtectedClassProperty($oKlarnaOrder, 'errors'));

    }

    public function testValidateKlarnaUserData()
    {

        $oBasket = oxNew(Basket::class);
        $oUser = oxNew(User::class);
        $oUser->load('92ebae5067055431aeaaa6f75bd9a131');
        $this->setSessionParam('deladrid', '41b545c65fe99ca2898614e563a7108a');
        $this->setLanguage(1);

        $expectedError = 'In order to being able to use Klarna payments, both person and country in billing and shipping address must match.';

        // valid
        $oUser->oxuser__oxlname = new Field('Dabrowski', Field::T_RAW);
        $oUser->oxuser__oxfname = new Field('Gregory', Field::T_RAW);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);
        $errors = $this->getProtectedClassProperty($oKlarnaOrder, 'errors');
        $this->assertEmpty($errors);

        // company name present
        $oUser->oxuser__oxcompany = new Field('FakeCompany', Field::T_RAW);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);
        $errors = $this->getProtectedClassProperty($oKlarnaOrder, 'errors');
        $this->assertNotEmpty($errors);
        $companyNameError = 'Payment with this Klarna payment method is currently not available for companies.';
        $this->assertEquals($companyNameError, $errors[0]);

        // different country
        $this->setSessionParam('sCountryISO', null);
        $oUser->oxuser__oxcountryid = new Field('a7c40f6320aeb2ec2.72885259', Field::T_RAW);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);
        $errors = $this->getProtectedClassProperty($oKlarnaOrder, 'errors');
        $this->assertNotEmpty($errors);
        $this->assertEquals($expectedError, $errors[0]);

        // different family_name
        $this->setSessionParam('sCountryISO', null);
        $oUser->oxuser__oxcountryid = new Field('a7c40f631fc920687.20179984', Field::T_RAW);
        $oUser->oxuser__oxlname = new Field('notDabrowski', Field::T_RAW);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);
        $errors = $this->getProtectedClassProperty($oKlarnaOrder, 'errors');
        $this->assertNotEmpty($errors);
        $this->assertEquals($expectedError, $errors[0]);

        // different given name
        $oUser->oxuser__oxlname = new Field('Dabrowski', Field::T_RAW);
        $oUser->oxuser__oxfname = new Field('notGregory', Field::T_RAW);
        $oKlarnaOrder = new  KlarnaPayment($oBasket, $oUser, []);
        $errors = $this->getProtectedClassProperty($oKlarnaOrder, 'errors');
        $this->assertNotEmpty($errors);
        $this->assertEquals($expectedError, $errors[0]);
    }


    public function validateOrderDataProvider()
    {
        $invalidTokenMessage = "Invalid authorization token. Please try again.";
        $orderChangedMessage = "Order data have been changed. Please try again.";
        return [
            [true, false, [$orderChangedMessage], true, false],
            [true, true, [$orderChangedMessage], true, false],
            [false, true, [$orderChangedMessage], true, false],
            [false, false, [$invalidTokenMessage], false, false],
            [false, false, [], true, true]
        ];
    }

    /**
     * @dataProvider validateOrderDataProvider
     * @param $stateChanged
     * @param $paymentChanged
     * @param $errors
     * @param $validToken
     * @param $eRes
     */
    public function testValidateOrder($stateChanged, $paymentChanged, $errors, $validToken, $eRes)
    {
        $this->setLanguage(1);
        $oKlarnaOrder = $this->createStub(KlarnaPayment::class,
            [
                'isOrderStateChanged' => $stateChanged,
                'isTokenValid' => $validToken,
                'validateKlarnaUserData' => null,
                'validateCountryAndCurrency' => null
            ]
        );
        $this->setProtectedClassProperty($oKlarnaOrder,'paymentChanged', $paymentChanged);
        $result = $oKlarnaOrder->validateOrder();
        $this->assertEquals($errors, $this->getProtectedClassProperty($oKlarnaOrder,'errors'));
        $this->assertEquals($eRes, $result);

    }

    public function testCountryWasChanged()
    {
        $this->setSessionParam('sCountryISO', 'DE');
        $oUser = $this->createStub(User::class, ['resolveCountry' => 'DE']);
        $this->assertFalse(KlarnaPayment::countryWasChanged($oUser));
        $oUser = $this->createStub(User::class, ['resolveCountry' => 'AT']);
        $this->assertTrue(KlarnaPayment::countryWasChanged($oUser));
    }

    public function testSetCheckSum()
    {
        $currentCheckSums = [];
        $oKlarnaOrder = $this->createStub(KlarnaPayment::class,
            ['fetchCheckSums' => null]
        );
        $this->setProtectedClassProperty($oKlarnaOrder, 'checkSums', $currentCheckSums);
        $oKlarnaOrder->setCheckSum('key', 'value');
        $this->assertEquals(['key' => 'value'], $this->getSessionParam('kpCheckSums'));

    }

    public function testCleanUpSession()
    {
        foreach(KlarnaPayment::$aSessionKeys as $key){
            $this->setSessionParam($key, 'someValue');
        }
        KlarnaPayment::cleanUpSession();

        foreach(KlarnaPayment::$aSessionKeys as $key) {
            $this->assertNull($this->getSessionParam($key));
        }
    }
}
