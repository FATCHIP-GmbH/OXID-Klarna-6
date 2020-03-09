<?php

namespace TopConcepts\Klarna\Tests\Unit\Core\InstantShopping;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Adapters\BasketAdapter;
use TopConcepts\Klarna\Core\Adapters\ShippingAdapter;
use TopConcepts\Klarna\Core\InstantShopping\Button;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Model\KlarnaPayment;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class ButtonTest extends ModuleUnitTestCase
{
    public function testGetConfig()
    {
        $this->getConfig()->saveShopConfVar('str', 'strKlarnaISButtonKey', 'buttonkeytest', null, 'module:tcklarna');
        $this->getConfig()->saveShopConfVar('bool', 'blIsKlarnaTestMode', true, null, 'module:tcklarna');
        $this->getConfig()->saveShopConfVar('aarr', 'aarrKlarnaISButtonStyle', [
            "tagline" => "dark",
            "variation" => "dark",
            "type" => "express"
        ], null, 'module:tcklarna');


        $button = $this->getMockBuilder(Button::class)->disableOriginalConstructor()->setMethods(
            ['instantiateBasketAdapter', 'instantiateShippingAdapter'])->getMock();

        $basketAdapter = $this->getMockBuilder(BasketAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMerchantData', 'buildOrderLinesFromBasket', 'getOrderData'])
            ->getMock();

        $basketAdapter->expects($this->once())->method('getMerchantData')->willReturn("12345");
        $basketAdapter->expects($this->once())->method('buildOrderLinesFromBasket');
        $basketAdapter->expects($this->once())->method('getOrderData')->willReturn($this->constructOrderData());


        $shippingAdapter = $this->getMockBuilder(ShippingAdapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingOptions'])
            ->getMock();

        $shippingAdapter->expects($this->any())->method('getShippingOptions')->willReturn([[
                'id' => 'oxidstandard',
                'name' => 'Standard',
                'description' => [
                        'tax_amount' => 0,
                        'price' => 0,
                        'tax_rate' => 1900,
                        'preselected' => 1,
                ]
        ]]);

        $button->expects($this->once())->method('instantiateBasketAdapter')->willReturn($basketAdapter);
        $button->expects($this->once())->method('instantiateShippingAdapter')->willReturn($shippingAdapter);

        $button->getConfig();

    }

    public function testGetMerchantUrls()
    {
        $button = $this->getMockBuilder(Button::class)->disableOriginalConstructor()->setMethods(
            ['instantiateBasketAdapter'])->getMock();

        $shopBaseUrl = Registry::getConfig()->getSslShopUrl();
        $lang = strtoupper(Registry::getLang()->getLanguageAbbr());
        $terms = KlarnaUtils::getShopConfVar('sKlarnaTermsConditionsURI_' . $lang);
        $expected =  [
            "terms"             =>  $terms,
            "confirmation"      =>  $shopBaseUrl . "?cl=thankyou",
            "update"            =>  $shopBaseUrl . "?cl=KlarnaInstantShoppingController&fnc=updateOrder",
            "place_order"       =>  $shopBaseUrl . "?cl=KlarnaInstantShoppingController&fnc=placeOrder"
        ];

        $result = $button->getMerchantUrls();

       $this->assertSame($expected, $result);
    }

    public function testGetGenericConfig()
    {
        $this->getConfig()->saveShopConfVar('aarr', 'aarrKlarnaISButtonStyle', null, null, 'module:tcklarna');

        $button = $this->getMockBuilder(Button::class)->disableOriginalConstructor()->setMethods(
            ['instantiateBasketAdapter'])->getMock();

        $result = $button->getGenericConfig();

        $this->assertNotEmpty($result);
    }

    public function testGetBasket()
    {
        $button = $this->getMockBuilder(Button::class)->disableOriginalConstructor()->setMethods(
            ['instantiateBasketAdapter'])->getMock();

        $getBasket = self::getMethod('getBasket', Button::class);
        $result = $getBasket->invokeArgs($button, []);
        $this->assertSame(KlarnaPayment::KLARNA_INSTANT_SHOPPING, $result->getPaymentId());

        $basket = $this->getMockBuilder(Basket::class)->disableOriginalConstructor()->setMethods(
            ['getCosts'])->getMock();

        $this->setProtectedClassProperty($button, 'oBasket', $basket);

        $getBasket = self::getMethod('getBasket', Button::class);
        $result = $getBasket->invokeArgs($button, []);

        $this->assertSame($basket, $result);

    }

    public function testGetPurchaseInfo()
    {
        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->setMethods(
            ['resolveCountry', 'getKlarnaPaymentData', 'getAttachmentsData'])->getMock();

        $user->expects($this->once())->method('resolveCountry')->willReturn('DE');

        $data = [
            "billing_address" => [
                "street_address" => "street",
                "country" => "de"
            ],
            "shipping_address"=>[
                "street_address" => "street",
                "country" => "de"
            ],
            "customer" => [
                "date_of_birth" => null
            ],
            "attachment" => null
        ];

        $user->expects($this->once())->method('getKlarnaPaymentData')->willReturn($data);
        $user->expects($this->any())->method('getAttachmentsData')->willReturn("test");

        Registry::getSession()->setUser($user);

        $button = $this->getMockBuilder(Button::class)->disableOriginalConstructor()->setMethods(
            ['instantiateBasketAdapter'])->getMock();

        $result = $button->getPurchaseInfo();

        $expected = [
            'purchase_country' => 'DE',
            'purchase_currency' => 'EUR',
            'billing_address' =>
                [
                    'street_address' => 'street',
                    'country' => 'DE',
                ],
            'attachment' => 'test',
            'customer' => [
                'date_of_birth' => null,
                'gender' => null
            ],
        ];

        $this->assertSame($expected,$result);

    }

    protected function constructOrderData()
    {
        $orderData['order_lines'] = [
                'type' => 'physical',
                'reference' => 1302,
                'name' => 'Kiteboard CABRINHA CALIBER 2011',
                'quantity' => 1,
                'unit_price' => 47900,
                'tax_rate' => 1900,
                'total_amount' => 47900,
                'total_discount_amount' => 0,
                'total_tax_amount' => 7648,
                'merchant_data' => '{"type":"basket_item"}',
                'quantity_unit' => 'pcs',
                'product_url' => 'prod_url',
                'image_url' => 'img_ur',
                'product_identifiers' => [
                    'category_path' => 'Kiteboarding > Kiteboards',
                ],
                [
                    'type' => 'shipping_fee',
                    'reference' => 'oxidstandard',
                    'name' => 'Standard',
                    'quantity' => 1,
                    'unit_price' => 0,
                    'tax_rate' => 1900,
                    'total_amount' => 0,
                    'total_discount_amount' => 0,
                    'total_tax_amount' => 0,
                    'merchant_data' => '{"type":"oxdelivery"}',
                ],
        ];

        return $orderData;
    }

}