<?php
/**
 * Created by PhpStorm.
 * User: arekk
 * Date: 04.04.2018
 * Time: 13:15
 */

namespace TopConcepts\Klarna\Model;


use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

/**
 * Class KlarnaUserTest
 * @package TopConcepts\Klarna\Models
 * @covers \TopConcepts\Klarna\Model\KlarnaUser
 */
class KlarnaUserTest extends ModuleUnitTestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function isCreatableDataProvider()
    {
        return [
            [KlarnaUser::NOT_EXISTING, true],
            [KlarnaUser::REGISTERED, false],
            [KlarnaUser::NOT_REGISTERED, true],
            [KlarnaUser::LOGGED_IN, false],
        ];
    }

    /**
     * @dataProvider isCreatableDataProvider
     * @param $type
     * @param $result
     */
    public function testIsCreatable($type, $result)
    {
        $oUser = oxNew(User::class);
        $oUser->setType($type);
        $this->assertEquals($result, $oUser->isCreatable());
    }

    public function saveDataProvider()
    {
        return [
            ['KP', null],
            ['KCO', 'DE'],
        ];
    }

    /**
     * @dataProvider saveDataProvider
     * @param $mode
     * @param $expectedISO
     */
    public function testSave($mode, $expectedISO)
    {
        $this->setModuleMode($mode);
        $this->setSessionParam('sCountryISO', null);
        $oUser                      = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field('a7c40f631fc920687.20179984', Field::T_RAW);
        $oUser->save();

        $this->assertEquals($expectedISO, $this->getSessionParam('sCountryISO'));
    }

    public function loginDataProvider()
    {
        return [
            ['KP', null, 'fake@email', null],
            ['KCO', 'DE', null, KlarnaUser::LOGGED_IN],
        ];
    }

    /**
     * @dataProvider  loginDataProvider
     * @param $mode
     * @param $sessionISO
     * @param $sessionEmail
     * @param $userType
     */
    public function testLogin($mode, $sessionISO, $sessionEmail, $userType)
    {
        $this->setModuleMode($mode);
        $this->setSessionParam('sCountryISO', null);
        $this->setSessionParam('klarna_checkout_user_email', 'fake@email');
        $oUser                    = oxNew(User::class);
        $oUser->oxuser__oxcountry = new Field('a7c40f631fc920687.20179984', Field::T_RAW);
        $oUser->login('info@topconcepts.de', 'muhipo2015');

        $this->assertEquals($sessionISO, $this->getSessionParam('sCountryISO'));
        $this->assertEquals($sessionEmail, $this->getSessionParam('klarna_checkout_user_email'));
        $this->assertEquals($userType, $oUser->getType());
    }

    public function resolveLocaleDataProvider()
    {
        return [
            ['DE', 'de', 'de-DE'],
            ['AT', 'de', 'de-AT'],
            ['AF', 'de', 'de-AF'],
        ];
    }

    /**
     * @dataProvider resolveLocaleDataProvider
     * @param $iso
     * @param $langId
     * @param $expectedResult
     */
    public function testResolveLocale($iso, $langId, $expectedResult)
    {
        $oUser = oxNew(User::class);
        $this->setLanguage($langId);

        $this->assertEquals($expectedResult, $oUser->resolveLocale($iso));
    }

    public function testGetCountryISO_notSet()
    {
        $oUser = $this->getMock(User::class, ['resolveCountry']);
        $oUser->expects($this->once())->method('resolveCountry')->willReturn('DE');

        $result = $oUser->getCountryISO();
        $this->assertEquals('DE', $result);
    }

    public function testGetCountryISO()
    {
        $oUser = $this->getMock($this->getProxyClassName(User::class), ['resolveCountry']);
        $oUser->expects($this->never())->method('resolveCountry');
        $oUser->setNonPublicVar('_countryISO', 'DE');

        $result = $oUser->getCountryISO();
        $this->assertEquals('DE', $result);

    }

    public function testLoadByEmail_loggedIn()
    {
        $oUser = $this->getMock(User::class, ['load']);
        $oUser->expects($this->never())->method('load');
        $oUser->setType(KlarnaUser::LOGGED_IN);

        $this->assertEquals($oUser, $oUser->loadByEmail('steffen@topconcepts.de'));
    }

    /**
     * @dataProvider loadByEmailDataProvider
     * @param $email
     * @param $expectedType
     */
    public function testLoadByEmail($email, $expectedType)
    {
        $oUser = oxNew(User::class);

        $this->assertEquals($oUser, $oUser->loadByEmail($email));
        $this->assertEquals($expectedType, $oUser->getType());
    }

    public function loadByEmailDataProvider()
    {
        return [
            ['info@topconcepts.de', KlarnaUser::REGISTERED],
            ['not_registered@topconcepts.de', KlarnaUser::NOT_REGISTERED],
            ['not_existing@topconcepts.de', KlarnaUser::NOT_EXISTING],
        ];
    }

    public function testLogout()
    {
        $this->setSessionParam('klarna_checkout_order_id', 'some_fake_id');

        $oUser  = oxNew(User::class);
        $result = $oUser->logout();

        $this->assertNull($this->getSessionParam('klarna_checkout_order_id'));
        $this->assertTrue($result);

    }

    public function resolveCountryDataProvider()
    {
        return [
            ['a7c40f631fc920687.20179984', 'DE'],
            ['a7c40f6320aeb2ec2.72885259', 'AT'],
            ['8f241f11095306451.36998225', 'AF'],

        ];
    }

    /**
     * @dataProvider resolveCountryDataProvider
     * @param $countryId
     * @param $iso
     */
    public function testResolveCountry($countryId, $iso)
    {
        $oUser                      = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field($countryId, Field::T_RAW);

        $this->assertEquals($iso, $oUser->resolveCountry());
    }

    public function clearDeliveryAddressDataProvider()
    {
        return [
            ['41b545c65fe99ca2898614e563a7108b', 1, false],
            ['41b545c65fe99ca2898614e563a7108a', 0, true],
        ];
    }

    /**
     * @dataProvider clearDeliveryAddressDataProvider
     * @param $addressId
     * @param $isTemp
     * @param $loaded
     */
    public function testClearDeliveryAddress($addressId, $isTemp, $loaded)
    {
        $this->setSessionParam('deladrid', $addressId);
        $this->setSessionParam('blshowshipaddress', 1);

        // Prepare temporary address
        $oAddress = oxNew(Address::class);
        $oAddress->load('41b545c65fe99ca2898614e563a7108b');
        $oAddress->oxaddress__tcklarna_temporary = new Field($isTemp, Field::T_RAW);
        $oAddress->save();


        $oUser = oxNew(User::class);
        $oUser->clearDeliveryAddress();

        $this->assertNull($this->getSessionParam('deladrid'));
        $this->assertEquals(0, $this->getSessionParam('blshowshipaddress'));

        $oAddress = oxNew(Address::class);
        $oAddress->load($addressId);
        $this->assertEquals($loaded, $oAddress->isLoaded());
    }

    public function countryISOProvider()
    {
        return [
            ['8f241f110953facc6.31621036', 'AW'],
            ['a7c40f632a0804ab5.18804076', 'GB'],
        ];
    }

    /**
     * @dataProvider countryISOProvider
     * @param $countryId
     * @param $expectedResult
     */
    public function testGetUserCountryISO2($countryId, $expectedResult)
    {
        $oUser                      = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field($countryId, Field::T_RAW);

        $result = $oUser->getUserCountryISO2();

        $this->assertEquals($expectedResult, $result);
    }

    public function paymentDataProvider()
    {
        return [
            ['0000-00-00', null, true],
            ['1988-01-01', null, false],
            ['1988-01-01', '41b545c65fe99ca2898614e563a7108f', false],
        ];
    }

    /**
     * @dataProvider paymentDataProvider
     * @param $bday
     * @param $deladrid
     * @param $bday_resultIsNull
     */
    public function testGetKlarnaPaymentData($bday, $deladrid, $bday_resultIsNull)
    {
        $oUser = oxNew(User::class);
        $oUser->load('oxdefaultadmin');
        $oUser->oxuser__oxbirthdate = new Field($bday, Field::T_RAW);
        $this->setSessionParam('deladrid', $deladrid);

        $result = $oUser->getKlarnaPaymentData();

        $this->assertTrue($bday_resultIsNull === is_null($result['customer']['date_of_birth']));
        $this->assertEquals($result['billing_address'] !== $result['shipping_address'], boolval($deladrid));
    }

    public function isWritableDataProvider()
    {
        return [
            [KlarnaUser::NOT_EXISTING, true],
            [KlarnaUser::NOT_REGISTERED, true],
            [KlarnaUser::LOGGED_IN, true],
            [KlarnaUser::REGISTERED, false],
        ];
    }

    /**
     * @dataProvider isWritableDataProvider
     * @param $type
     * @param $result
     */
    public function testIsWritable($type, $result)
    {
        $oUser = oxNew(User::class);
        $oUser->setType($type);
        $this->assertEquals($result, $oUser->isWritable());
    }

    public function updateDeliveryAddressDataProvider()
    {
        $aAddress = [
            'name'   => 'Zyggy',
            'street' => 'qwdqw',
        ];

        return [
            [$aAddress, true, 'addressId', true],
            [$aAddress, true, false, true],
        ];
    }

    /**
     * @dataProvider updateDeliveryAddressDataProvider
     * @param $aAddressData
     * @param $isValid
     * @param $klExists
     * @param $isFake
     */
    public function testUpdateDeliveryAddress($aAddressData, $isValid, $klExists, $isFake)
    {
        $oAddress = $this->getMock(Address::class, ['isValid', 'klExists']);
        $oAddress->expects($this->once())
            ->method('isValid')->willReturn($isValid);
        $oAddress->expects($this->once())
            ->method('klExists')->willReturn($klExists);


        $oUser = $this->getMock(User::class, ['buildAddress', 'isFake', 'updateSessionDeliveryAddressId']);
        $oUser->expects($this->once())->method('buildAddress')->willReturn($oAddress);
        $oUser->expects($this->any())->method('isFake')->willReturn($isFake);
        $oUser->expects($this->once())->method('updateSessionDeliveryAddressId');

        $oUser->updateDeliveryAddress($aAddressData);

    }

//    /**
//     * @covers       \TopConcepts\Klarna\Models\KlarnaUser::buildAddress()
//     * @dataProvider updateDeliveryAddressDataProvider
//     * @param $aAddressData
//     */
//    public function testBuildAddress($aAddressData)
//    {
//        $oUser = oxNew(User::class);
//        $oUser->updateDeliveryAddress($aAddressData);
//
//    }

    /**
     * @dataProvider isFakeDataProvider
     * @param $type
     */
    public function testgetType($type)
    {
        $oUser = oxNew(User::class);
        $oUser->setType($type);
        $this->assertEquals($type, $oUser->getType());
    }

    public function deliveryCountryDataProvider()
    {
        return [
            ['KCO', 'DE', null, 'a7c40f631fc920687.20179984'],
            ['KCO', null, 'a7c40f631fc920687.20179984', 'a7c40f631fc920687.20179984'],
            ['KCO', null, null, 'a7c40f631fc920687.20179984'],
            ['KP', 'AT', 'a7c40f6320aeb2ec2.72885259', 'a7c40f6320aeb2ec2.72885259'],
            ['KP', null, null, 'a7c40f631fc920687.20179984'],
        ];
    }

    /**
     * @dataProvider deliveryCountryDataProvider
     * @param $mode
     * @param $countryISO
     * @param $userCountryId
     * @param $expectedId
     */
    public function testGetKlarnaDeliveryCountry($mode, $countryISO, $userCountryId, $expectedId)
    {
        $this->setModuleMode($mode);
        $this->setModuleConfVar('sKlarnaDefaultCountry', 'DE');
        $this->setSessionParam('sCountryISO', $countryISO);

        $oUser                      = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field($userCountryId, Field::T_RAW);
        $result                     = $oUser->getKlarnaDeliveryCountry();

        $oCountry = oxNew(Country::class);
        $oCountry->load($expectedId);
        $this->assertEquals($oCountry, $result);
    }

    public function testsetType()
    {
        $oUser = oxNew(User::class);
        $oUser->setType('myType');
        $this->assertEquals('myType', $oUser->getType());
    }

    public function isFakeDataProvider()
    {
        return [
            [KlarnaUser::NOT_EXISTING, '', true],
            [KlarnaUser::REGISTERED, 'aaa', true],
            [KlarnaUser::NOT_REGISTERED, '', true],
            [KlarnaUser::LOGGED_IN, '', true],
            [KlarnaUser::LOGGED_IN, 'aaa', false],
        ];
    }

    /**
     * @dataProvider isFakeDataProvider
     * @param $type
     * @param $pass
     * @param $result
     */
    public function testIsFake($type, $pass, $result)
    {
        $oUser = oxNew(User::class);
        $oUser->setType($type);
        $oUser->oxuser__oxpassword = new Field($pass);
        $this->assertEquals($result, $oUser->isFake());
    }

    public function getAttachmentsDataProvider()
    {
        return [
            [false, ['content_type' => 'application/vnd.klarna.internal.emd-v2+json',
                     'body'         => json_encode(['one', 'two'])]],
            [true, null],
        ];
    }

    /**
     * @dataProvider getAttachmentsDataProvider
     * @param $isFake
     * @param $expectedResult
     */
    public function testGetAttachmentsData($isFake, $expectedResult)
    {
        $oUser = $this->getMock(User::class, ['isFake', 'getEMD']);
        $oUser->expects($this->any())->method('isFake')->willReturn($isFake);
        $oUser->expects($this->any())->method('getEMD')->willReturn(['one', 'two']);

        $this->assertEquals($expectedResult, $oUser->getAttachmentsData());

    }

    public function changeUserDataDataProvider()
    {
        return [
            ['KP', null],
            ['KCO', 'DE'],
        ];
    }

    /**
     * @dataProvider changeUserDataDataProvider
     * @param $mode
     * @param $expectedResult
     */
    public function testChangeUserData($mode, $expectedResult)
    {

        $this->setModuleMode($mode);
        $oUser = oxNew(User::class);
        $oUser->setId('_testUser');
        $oUser->oxuser__oxactive   = new Field(1, Field::T_RAW);
        $oUser->oxuser__oxshopid   = new Field($this->getConfig()->getBaseShopId(), Field::T_RAW);
        $oUser->oxuser__oxusername = new Field('aaa@bbb.lt', Field::T_RAW);
        $oUser->oxuser__oxfname    = new Field('a', Field::T_RAW);
        $oUser->oxuser__oxlname    = new Field('b', Field::T_RAW);
        $oUser->oxuser__oxusername = new Field('aaa@bbb.lt', Field::T_RAW);
        $oUser->oxuser__oxpassword = new Field('pass', Field::T_RAW);
        $oUser->oxuser__oxactive   = new Field(1, Field::T_RAW);
        $oUser->save();

        $aInvAdress = array('oxuser__oxfname'     => 'xxx',
                            'oxuser__oxlname'     => 'yyy',
                            'oxuser__oxstreetnr'  => '11',
                            'oxuser__oxstreet'    => 'zzz',
                            'oxuser__oxzip'       => '22',
                            'oxuser__oxcity'      => 'ooo',
                            'oxuser__oxcountryid' => 'a7c40f631fc920687.20179984');

        $oUser->changeUserData($oUser->oxuser__oxusername->value, $oUser->oxuser__oxpassword->value, $oUser->oxuser__oxpassword->value, $aInvAdress, array());
        $this->assertEquals($expectedResult, $this->getSessionParam('sCountryISO'));
    }

    public function getKlarnaDataProvider()
    {
        return [
            [null, null, []],
            ['DE', 1, ['billing_address']],
        ];
    }

    /**
     * @dataProvider getKlarnaDataProvider
     * @param $selectedCountry
     * @param $invadr
     */
    public function testGetKlarnaData($selectedCountry, $invadr, $resultKeys)
    {
        $this->setModuleConfVar('blKlarnaEnablePreFilling', false);
        $this->setRequestParameter('selected-country', $selectedCountry);
        $this->setSessionParam('invadr', $invadr);

        $oUser  = oxNew(User::class);
        $result = $oUser->getKlarnaData();

        $this->assertEquals(array_keys($result), $resultKeys);
        $this->assertNull($this->getSessionParam('invadr'));
    }

    public function getKlarnaDataProvider_PFE()
    {
        return [
            [0, 0, null, null, ['customer', 'billing_address']],
            [0, 0, null, 1, ['customer', 'billing_address']],
            [1, 0, null, null, ['customer', 'billing_address']],
            [3, 0, null, null, ['customer', 'billing_address']],
            [2, 1, '41b545c65fe99ca2898614e563a7108a', null, ['customer', 'billing_address', 'shipping_address']],
        ];
    }

    /**
     * @dataProvider getKlarnaDataProvider_PFE
     * @param $userType
     * @param $showSippingAddress
     * @param $addressId
     * @param $invadr
     * @param $resultKeys
     */
    public function testGetKlarnaData_PreFillingEnabled($userType, $showSippingAddress, $addressId, $invadr, $resultKeys)
    {
        $this->setModuleConfVar('blKlarnaEnablePreFilling', true);
        $this->setSessionParam('blshowshipaddress', $showSippingAddress);
        $this->setSessionParam('deladrid', $addressId);
        $this->setSessionParam('invadr', $invadr);

        $oUser = oxNew(User::class);
        $oUser->load('92ebae5067055431aeaaa6f75bd9a131');
        $oUser->setType($userType);
        $result = $oUser->getKlarnaData();

        $this->assertEquals(array_keys($result), $resultKeys);
    }

    /**
     * @dataProvider deliveryAddressDataProvider
     * @param $showShippingAddress
     * @param $addressId
     * @param $isLoaded
     */
    public function testGetDelAddressInfo($showShippingAddress, $addressId, $isLoaded)
    {
        $this->setSessionParam('blshowshipaddress', $showShippingAddress);
        $this->setSessionParam('deladrid', $addressId);

        $oUser  = oxNew(User::class);
        $result = $oUser->getDelAddressInfo();

        $this->assertEquals($isLoaded, $result->isLoaded());
        $this->assertNotEmpty($result->oxaddress__oxcountry->value);

    }

    public function deliveryAddressDataProvider()
    {
        return [
            [1, '41b545c65fe99ca2898614e563a7108a', true],
            [1, '41b545c65fe99ca2898614e563a7108f', true],
        ];
    }

    public function testGetDelAddressInfo_null()
    {
        $this->setSessionParam('blshowshipaddress', 0);
        $this->setSessionParam('deladrid', 'dawdawdawd');

        $oUser  = oxNew(User::class);
        $result = $oUser->getDelAddressInfo();

        $this->assertNull($result);

    }

    /**
     * @dataProvider userTypeDataProvider
     * @param $userId
     * @param $session_usr
     * @param $expectedResult
     */
    public function testcheckUserType($userId, $session_usr, $expectedResult)
    {
        $this->setSessionParam('usr', $session_usr);
        $oUser = $this->getMock(User::class, ['getId']);
        $oUser->expects($this->once())
            ->method('getId')->willReturn($userId);

        $this->assertEquals($expectedResult, $oUser->checkUserType());
    }

    public function userTypeDataProvider()
    {
        return [
            ['id', null, KlarnaUser::NOT_REGISTERED],
            ['id', 'id', KlarnaUser::LOGGED_IN],
        ];
    }

    /**
     * @dataProvider userDeliveryDataProvider
     * @param $resAddressId
     * @param $newAddressId
     * @param $isFake
     * @param $showShippingAddress
     */
    public function testUpdateSessionDeliveryAddressId($resAddressId, $newAddressId, $isFake, $showShippingAddress)
    {
        $oUser = $this->getMock(User::class, ['isFake']);
        $oUser->expects($this->once())
            ->method('isFake')->willReturn($isFake);

        $this->setSessionParam('deladrid', 'old-fake-id');

        $oUser->updateSessionDeliveryAddressId($newAddressId);

        $this->assertEquals($resAddressId, $this->getSessionParam('deladrid'));
        $this->assertEquals($showShippingAddress, $this->getSessionParam('blshowshipaddress'));


    }

    public function userDeliveryDataProvider()
    {
        return [
            ['new-id', 'new-id', true, 1],
            ['new-id', 'new-id', false, 1],
            ['new-id', 'new-id', true, 1],
            ['old-fake-id', null, false, null],
        ];
    }

    /**
     * @dataProvider userCurrencyDataProvider
     * @param $countryId
     * @param $expectedCurrency
     */
    public function testGetKlarnaPaymentCurrency($countryId, $expectedCurrency)
    {

        $oUser                      = oxNew(User::class);
        $oUser->oxuser__oxcountryid = new Field($countryId, Field::T_RAW);
        $result                     = $oUser->getKlarnaPaymentCurrency();

        $this->assertEquals($result, $expectedCurrency);
    }

    public function userCurrencyDataProvider()
    {
        // oxcountryid, oxcurrencyiso
        return [
            ['a7c40f632848c5217.53322339', 'SEK'],
            ['8f241f11096176795.61257067', 'NOK'],
            ['8f241f110957e6ef8.56458418', 'DKK'],
            ['a7c40f631fc920687.20179984', 'EUR'],
            ['a7c40f63293c19d65.37472814', 'EUR'],
            ['a7c40f632cdd63c52.64272623', 'EUR'],
            ['a7c40f6320aeb2ec2.72885259', 'EUR'],
            ['a7c40f632a0804ab5.18804076', 'GBP'],
            ['a7c40f632a0804ab5.18804076', 'GBP'],
            ['8f241f1109624d3f8.50953605', null],
        ];
    }

    public function modeDataProvider()
    {
        return [
            ['KP'],
            ['KCO'],
        ];
    }

    public function testSetFakeUserId()
    {
        $this->setSessionParam('sFakeUserId', 1);

        $class  = new \ReflectionClass(KlarnaUser::class);
        $method = $class->getMethod('setFakeUserId');
        $method->setAccessible(true);

        $user = $this->createStub(KlarnaUser::class, ['getKlarnaData' => []]);

        $method->invoke($user);
        $this->assertEquals(1, $this->getProtectedClassProperty($user, '_sOXID'));
    }
}
