<?php

namespace TopConcepts\Klarna\Tests\Unit\Controller;


use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Application\Controller\PaymentController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use ReflectionClass;
use TopConcepts\Klarna\Core\KlarnaPaymentsClient;
use TopConcepts\Klarna\Core\Exception\KlarnaClientException;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;
use OxidEsales\Eshop\Core\UtilsObject;

class KlarnaPaymentControllerTest extends ModuleUnitTestCase
{
    const COUNTRIES = [
        'AT' => 'a7c40f6320aeb2ec2.72885259',
        'DE' => 'a7c40f631fc920687.20179984',
        'AF' => '8f241f11095306451.36998225',
    ];

    /**
     * @beforeClass
     */
    public function setUpKP()
    {
        $this->setModuleMode('KP');
    }

    /**
     * @afterClass
     */
    public function tearDownKP()
    {
        $this->setModuleMode('KCO');
    }

    public function initDataProvider()
    {

        $oUser                        = oxNew(User::class);
        $oUserAT                      = clone $oUser;
        $oUserAT->oxuser__oxcountryid = new Field(self::COUNTRIES['AT'], Field::T_RAW);
        $oUserDE                      = clone $oUser;
        $oUserDE->oxuser__oxcountryid = new Field(self::COUNTRIES['DE'], Field::T_RAW);
        $oUserAF                      = clone $oUser;
        $oUserAF->oxuser__oxcountryid = new Field(self::COUNTRIES['AF'], Field::T_RAW);

        $redirectUrl = $this->getConfig()->getSSLShopURL() . 'index.php?cl=KlarnaExpress';

        return [
            ['KCO', false, null, 'DE', null,
             [null, true, $redirectUrl],
            ],
            ['KP', false, null, 'DE', null,
             [null, true, null],
            ],
            ['KCO', false, null, 'AF', null,
             [null, true, null],
            ],
            ['KCO', $oUser, null, null, null,
             [null, true, $redirectUrl],
            ],
            ['KCO', $oUser, null, null, 'nonKCORequest',
             [null, true, null],
            ],
            ['KCO', $oUserAT, 'amazonRef', null, null,
             ['AT', false, null],
            ],
            ['KCO', $oUserDE, null, null, null,
             ['DE', true, $redirectUrl],
            ],
            ['KCO', $oUserAF, null, null, null,
             ['AF', true, null],
            ],
        ];
    }

    /**
     * @dataProvider initDataProvider
     * @param $oUser
     * @param $amzRef
     * @param $sessionCountryISO
     * @param $nonKCOinRequest
     * @param $results
     */
    public function testInit($mode, $oUser, $amzRef, $sessionCountryISO, $nonKCOinRequest, $results)
    {
        $this->setModuleMode($mode);
        $this->setSessionParam('amazonOrderReferenceId', $amzRef);
        $this->setSessionParam('sCountryISO', $sessionCountryISO);
        $this->setRequestParameter('non_kco_global_country', $nonKCOinRequest);
        $oPaymentController = $this->getMockBuilder(PaymentController::class)->setMethods(['getUser'])->getMock();
        $oPaymentController->expects($this->once())->method('getUser')->willReturn($oUser);
        $oPaymentController->init();

        $this->assertEquals($results[0], $this->getProtectedClassProperty($oPaymentController, 'userCountryISO'));
        $this->assertEquals($results[1], $this->getProtectedClassProperty($oPaymentController, 'loadKlarnaPaymentWidget'));
        $this->assertEquals($results[2], \oxUtilsHelper::$sRedirectUrl);

        $this->setModuleMode('KP');
    }

    public function testRemoveKlarnaPrefix()
    {
        $oPaymentController = oxNew(PaymentController::class);
        $result             = $oPaymentController->removeKlarnaPrefix('Klarna String');
        $this->assertNotContains('Klarna ', $result);
    }

    public function testIncludeKPWidget()
    {
        $oPaymentController = oxNew(PaymentController::class);
        $oPayment           = $this->getMockBuilder(Payment::class)->setMethods(['isKPPayment'])->getMock();
        $oPayment->expects($this->at(0))->method('isKPPayment')->willReturn(true);
        $oPayment->expects($this->at(1))->method('isKPPayment')->willReturn(false);
        $oPayment->expects($this->at(2))->method('isKPPayment')->willReturn(true);
        $oPayment->expects($this->at(3))->method('isKPPayment')->willReturn(false);
        $oPayment->expects($this->at(4))->method('isKPPayment')->willReturn(true);

        $oPayment->expects($this->at(5))->method('isKPPayment')->willReturn(false);
        $oPayment->expects($this->at(6))->method('isKPPayment')->willReturn(false);
        $oPayment->expects($this->at(7))->method('isKPPayment')->willReturn(false);
        $oPayment->expects($this->at(8))->method('isKPPayment')->willReturn(false);

        $payList = [
            'id_0' => $oPayment,
            'id_1' => $oPayment,
            'id_2' => $oPayment,
            'id_3' => $oPayment,
            'id_4' => $oPayment,
        ];

        $this->setProtectedClassProperty($oPaymentController, 'aPaymentList', $payList);

        $result = $oPaymentController->includeKPWidget();
        $this->assertEquals(3, $result);

        array_pop($payList);
        $this->setProtectedClassProperty($oPaymentController, 'aPaymentList', $payList);
        $result = $oPaymentController->includeKPWidget();
        $this->assertEquals(0, $result);
    }

    public function testGetCheckoutShippingSets()
    {
        $oUser            = oxNew(User::class);
        $sActShipSet      = 'setId';
        $aDelSetData      = [['allSets' => 'value'], 'other_value', []];
        $oDeliverySetList = $this->getMockBuilder(DeliverySetList::class)->setMethods(['getDeliverySetData'])->getMock();
        $oDeliverySetList->expects($this->exactly(2))
            ->method('getDeliverySetData')
            ->with($sActShipSet, $oUser)
            ->willReturn($aDelSetData);;


        Registry::set(DeliverySetList::class, $oDeliverySetList);
        $oPaymentController = oxNew(PaymentController::class);

        $this->setRequestParameter('sShipSet', $sActShipSet);
        $result = $oPaymentController->getCheckoutShippingSets($oUser);
        $this->assertEquals($aDelSetData[0], $result);

        $this->setRequestParameter('sShipSet', null);
        $this->setSessionParam('sShipSet', $sActShipSet);
        $result = $oPaymentController->getCheckoutShippingSets($oUser);
        $this->assertEquals($aDelSetData[0], $result);

    }

    public function renderDataProvider()
    {
        $validTimeStump   = (new \DateTime())->getTimestamp();
        $invalidTimeStump = $validTimeStump - 86400;

        return [
            [
                [
                    'userCountryId'        => self::COUNTRIES['DE'],
                    'countKPMethodsBefore' => 1,
                    'timeStamp'            => $validTimeStump,
                    'loadKPWidget'         => true,
                    'companyError'         => '',
                    'countKPMethodsAfter'  => 11,
                ],
                [
                    'isError'                   => false,
                    'shouldLocale'              => true,
                    'removeUnavailableKP_calls' => 1,
                    'kp_session_data'           => ['empty'],
                ],
            ],
            [
                [
                    'userCountryId'        => self::COUNTRIES['DE'],
                    'countKPMethodsBefore' => 1,
                    'timeStamp'            => $invalidTimeStump,
                    'loadKPWidget'         => true,
                    'companyError'         => '',
                    'countKPMethodsAfter'  => 11,
                ],
                [
                    'isError'                   => false,
                    'shouldLocale'              => true,
                    'removeUnavailableKP_calls' => 1,
                    'kp_session_data'           => null,
                ],
            ],
            [
                [
                    'userCountryId'        => self::COUNTRIES['AT'],
                    'countKPMethodsBefore' => 1,
                    'timeStamp'            => $validTimeStump,
                    'loadKPWidget'         => true,
                    'companyError'         => '',
                    'countKPMethodsAfter'  => 11,
                ],
                [
                    'isError'                   => false,
                    'shouldLocale'              => true,
                    'removeUnavailableKP_calls' => 1,
                    'kp_session_data'           => null,
                ],
            ],
        ];
    }

    /**
     * @dataProvider renderDataProvider
     * @param $args
     * @param $results
     */
    public function testRender($args, $results)
    {
        $oUser                      = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field($args['userCountryId'], Field::T_RAW);
        $oUser->oxuser__oxcompany   = new Field($args['companyError'], Field::T_RAW);
        $this->setSessionParam('sCountryISO', 'DE');
        $this->setSessionParam('klarna_session_data', ['empty']);
        $this->setSessionParam('sSessionTimeStamp', $args['timeStamp']);

        $oPaymentController = $this->getMockBuilder(PaymentController::class)->setMethods(['countKPMethods', 'getUser', 'removeUnavailableKP'])->getMock();

        // "at" method behaves incorrect here - indexes should be 0 an 1 not 2 and 5
        // possibly PHPUnit bug
        $oPaymentController->expects($this->at(2))
            ->method('countKPMethods')->willReturn($args['countKPMethodsBefore']);
        $oPaymentController->expects($this->at(5))
            ->method('countKPMethods')->willReturn(0);


        $oPaymentController->expects($this->any())
            ->method('getUser')->willReturn($oUser);
        $oPaymentController->expects($this->exactly($results['removeUnavailableKP_calls']))
            ->method('removeUnavailableKP');

        $oPaymentController->loadKlarnaPaymentWidget = $args['loadKPWidget'];

        $client = $this->getMockBuilder(KlarnaPaymentsClient::class)->setMethods(['initOrder', 'createOrUpdateSession'])->getMock();
        $client->expects($this->any())->method('initOrder')->will($this->returnSelf());
        $this->setProtectedClassProperty($oPaymentController, 'client', $client);

        $tpl = $oPaymentController->render();
        $this->assertEquals("page/checkout/payment.tpl", $tpl);

        $viewData = $oPaymentController->getViewData();
        $results['shouldError'] ? $this->assertArrayHasKey('kpError', $viewData) : $this->assertArrayNotHasKey('kpError', $viewData);
        $results['shouldLocale'] ? $this->assertArrayHasKey('sLocale', $viewData) : $this->assertArrayNotHasKey('sLocale', $viewData);
        $args['countKPMethodsAfter'] === 0 && $this->assertFalse($oPaymentController->loadKlarnaPaymentWidget);


        $this->assertEquals($results['kp_session_data'], $this->getSessionParam('klarna_session_data'));


    }

    public function testRender_error()
    {
        $oUser                      = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field(self::COUNTRIES['AT'], Field::T_RAW);
        $oUser->oxuser__oxcompany   = new Field('Company Name should lead to error', Field::T_RAW);
        $this->setSessionParam('sCountryISO', 'DE');
        $this->setSessionParam('klarna_session_data', ['empty']);
        $this->setSessionParam('sSessionTimeStamp', (new \DateTime())->getTimestamp());

        $oPaymentController = $this->getMockBuilder(PaymentController::class)->setMethods(['countKPMethods', 'getUser'])->getMock();
        $oPaymentController->expects($this->any())
            ->method('getUser')->willReturn($oUser);
        $oPaymentController->expects($this->any())
            ->method('countKPMethods')->willReturn(3);

        $tpl = $oPaymentController->render();
        $this->assertEquals("page/checkout/payment.tpl", $tpl);
        $viewData = $oPaymentController->getViewData();
        $this->assertArrayHasKey('kpError', $viewData);
    }


    public function testRender_exception()
    {
        $oUser                      = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field(self::COUNTRIES['DE'], Field::T_RAW);
        $this->setSessionParam('sCountryISO', 'DE');
        $this->setSessionParam('klarna_session_data', ['empty']);
        $this->setSessionParam('sSessionTimeStamp', (new \DateTime())->getTimestamp());

        $oPaymentController = $this->getMockBuilder(PaymentController::class)->setMethods(['countKPMethods', 'getUser'])->getMock();
        $oPaymentController->expects($this->any())
            ->method('countKPMethods')->willReturn(3);
        $oPaymentController->expects($this->any())
            ->method('getUser')->willReturn($oUser);
        $client = $this->getMockBuilder(KlarnaPaymentsClient::class)->setMethods(['initOrder'])->getMock();
        $client->expects($this->any())->method('initOrder')->willThrowException(new KlarnaClientException('Test'));
        $this->setProtectedClassProperty($oPaymentController, 'client', $client);

        $oPaymentController->render();
        $this->assertLoggedException(KlarnaClientException::class, 'Test');
    }

    public function testGetPaymentList()
    {

        $oPaymentController = oxNew(PaymentController::class);
        $oPayment           = 'fakeObject';

        $payList = [
            'klarna_pay_later' => $oPayment,
            'id_1'             => $oPayment,
            'klarna_checkout'  => $oPayment,
            'klarna_slice_it'  => $oPayment,
            'id_4'             => $oPayment,
        ];

        $this->setProtectedClassProperty($oPaymentController, 'aPaymentList', $payList);
        $result = $oPaymentController->getPaymentList();
        $this->assertEquals([
            'klarna_pay_later' => 'fakeObject',
            'id_1'             => 'fakeObject',
            'klarna_slice_it'  => 'fakeObject',
            'id_4'             => 'fakeObject'
        ], $result);

        $this->setModuleMode('KCO');
        $this->setProtectedClassProperty($oPaymentController, 'aPaymentList', $payList);
        $result = $oPaymentController->getPaymentList();
        $this->assertEquals([
            'id_1'             => 'fakeObject',
            'id_4'             => 'fakeObject'
        ], $result);

        $this->setModuleMode('KP');
    }

    public function testRemoveUnavailableKP()
    {
        $oPaymentController = oxNew(PaymentController::class);
        $oPayment           = $this->getMockBuilder(Payment::class)->setMethods(['getPaymentCategoryName'])->getMock();
        $oPayment->expects($this->at(0))->method('getPaymentCategoryName')->willReturn('pay_later');
        $oPayment->expects($this->at(1))->method('getPaymentCategoryName')->willReturn('zzzzz');
        $oPayment->expects($this->at(2))->method('getPaymentCategoryName')->willReturn('fffff');
        $oPayment->expects($this->at(3))->method('getPaymentCategoryName')->willReturn('pay_over_time');
        $oPayment->expects($this->at(4))->method('getPaymentCategoryName')->willReturn('aaaaaa');

        $payList = [
            'id_0' => $oPayment,
            'id_1' => $oPayment,
            'id_2' => $oPayment,
            'id_3' => $oPayment,
            'id_4' => $oPayment,
        ];

        $this->setProtectedClassProperty($oPaymentController, 'aPaymentList', $payList);

        $sessionData = [
            'payment_method_categories' => [
                [
                    'identifier' => 'pay_later',
                    'name'       => 'Rechnung.',
                    'asset_urls' => [
                        'descriptive' => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/pay_later/descriptive/pink.svg',
                        'standard'    => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/pay_later/standard/pink.svg',
                    ],
                ],
                [
                    'identifier' => 'pay_over_time',
                    'name'       => 'Ratenkauf.',
                    'asset_urls' => [
                        'descriptive' => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/slice_it/descriptive/pink.svg',
                        'standard'    => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/de_de/slice_it/standard/pink.svg',
                    ],
                ],
            ],
        ];


        $class  = new ReflectionClass(get_class($oPaymentController));
        $method = $class->getMethod('removeUnavailableKP');
        $method->setAccessible(true);

        $method->invokeArgs($oPaymentController, [$sessionData]);

        $this->assertEquals(2, count($this->getProtectedClassProperty($oPaymentController, 'aPaymentList')));
    }

    public function validatePaymentDataProvider()
    {
        return [
            // authorized (requires finalization)
            ['klarna_pay_later', true, null, self::COUNTRIES['DE'],
             ['KP_data'], 'order'],

            // authorized (token present)
            ['klarna_pay_now', null, 'authToken', self::COUNTRIES['DE'],
             ['KP_data'], 'order'],

            // error - not available in this country
            ['klarna_pay_now', null, 'authToken', self::COUNTRIES['AF'],
             ['KP_data'], null],

            // not authorized and no error
            ['klarna_slice_it', null, null, null,
             ['KP_data'], null],

            // not KP
            ['other', null, null, null,
            null, 'order']
        ];
    }


    /**
     * @dataProvider validatePaymentDataProvider
     * @param $payId
     * @param $finalizeRequired
     * @param $sAuthToken
     * @param $countryId
     * @param $kpSessionData
     * @param $result
     */
    public function testValidatepayment_KPEnabled($payId, $finalizeRequired, $sAuthToken, $countryId, $kpSessionData, $result)
    {
        $this->setLanguage(1);
        $this->setSessionParam('klarna_session_data', ['KP_data']);
        $this->setRequestParameter("paymentid", $payId);
        $this->setRequestParameter("finalizeRequired", $finalizeRequired);
        $this->setRequestParameter("sAuthToken", $sAuthToken);
        $oUser = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field($countryId, Field::T_RAW);
        $oPaymentController = $this->getMockBuilder(PaymentController::class)->setMethods(['getUser'])->getMock();
        $oPaymentController->expects($this->any())->method('getUser')->willReturn($oUser);
        $this->setProtectedClassProperty($oPaymentController, 'oRequest', Registry::get(Request::class));
        $oPayment = $this->getMockBuilder(Payment::class)->setMethods(['isValidPayment'])->getMock();
        $oPayment->expects($this->any())->method('isValidPayment')->willReturn(true);
        UtilsObject::setClassInstance(Payment::class, $oPayment);

        $this->assertEquals($result, $oPaymentController->validatePayment());
        $this->assertEquals($sAuthToken, $this->getSessionParam('sAuthToken'));
        $sAuthToken && $this->assertNotNull($this->getSessionParam('sTokenTimeStamp'));
        $this->assertEquals($finalizeRequired, $this->getSessionParam('finalizeRequired'));
        $this->assertEquals($kpSessionData, $this->getSessionParam('klarna_session_data'));
    }

    public function testValidatepayment_KCOEnabled()
    {
        $this->setModuleMode('KCO');
        $this->setRequestParameter("paymentid", null);
        $oPaymentController = oxNew(PaymentController::class);
        $this->assertNull($oPaymentController->validatePayment());

        $this->setRequestParameter("paymentid", "fake-id");
        $oUser = oxNew(User::class);
        $oPaymentController = $this->getMockBuilder(PaymentController::class)->setMethods(['getUser'])->getMock();
        $oPaymentController->expects($this->any())->method('getUser')->willReturn($oUser);$this->setProtectedClassProperty($oPaymentController, 'oRequest', Registry::get(Request::class));
        $oPayment = $this->getMockBuilder(Payment::class)->setMethods(['isValidPayment'])->getMock();
        $oPayment->expects($this->any())->method('isValidPayment')->willReturn(true);
        UtilsObject::setClassInstance(Payment::class, $oPayment);

        $this->assertEquals("order", $oPaymentController->validatePayment());
        $this->setModuleMode('KP');
    }
}
