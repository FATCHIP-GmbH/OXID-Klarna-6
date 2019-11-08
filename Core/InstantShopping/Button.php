<?php


namespace TopConcepts\Klarna\Core\InstantShopping;


use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsView;
use TopConcepts\Klarna\Core\Exception\KlarnaConfigException;
use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Core\ShippingAdapter;
use TopConcepts\Klarna\Model\KlarnaPayment;
use TopConcepts\Klarna\Model\KlarnaUser;

class Button
{
    const ENV_TEST = 'playground';
    const ENV_LIVE = 'production';

    protected $errors = [];

    public function getConfig(Article $product = null, $update = false) {

        if ($update) {
            return [
                "order_lines" => $this->getOrderLines($product)
            ];
        }
        $config = [
            "setup"=> [
                "key" => $this->getButtonKey(),
                "environment" => $this->getEnvironment(),
                "region" => "eu"
            ],
            "styling" => [
                "theme" => $this->getButtonStyling()
            ],
            "locale" => KlarnaConsts::getLocale(),
            "merchant_urls" => $this->getMerchantUrls(),
            "order_lines" => $this->getOrderLines($product)
        ];

        $shippingOptions = [];
        try {
            $shippingOptions["shipping_options"] = $this->getShippingOptions();
        } catch (KlarnaConfigException $e) {
            $this->errors[] = $e->getMessage();
        }

        if (count($this->errors) === 0) {
            return array_merge(
                $config,
                $this->getPurchaseInfo(),
                $shippingOptions
            );
        }
        return false;
    }

    public function getMerchantUrls() {
        $shopBaseUrl = Registry::getConfig()->getSslShopUrl();
        return [
            "terms"             =>  $shopBaseUrl . "?cl=terms",
            "push"              =>  $shopBaseUrl . "?cl=push",
            "confirmation"      =>  $shopBaseUrl . "?cl=confirmation",
            "notification"      =>  $shopBaseUrl . "?cl=notification",
            "update"            =>  $shopBaseUrl . "?cl=KlarnaInstantShoppingController&fnc=updateOrder",
            "country_change"    =>  $shopBaseUrl . "?cl=country_change",
            "place_order"       =>  $shopBaseUrl . "?cl=KlarnaInstantShoppingController&fnc=placeOrder"
        ];
    }

    public function getButtonKey() {
        return KlarnaUtils::getShopConfVar('strKlarnaISButtonKey');
    }

    public function getPurchaseInfo() {
        $result = [
            "purchase_country"  => 'DE',
            "purchase_currency" => 'EUR',
        ];
        /** @var User|KlarnaUser $user */
        $user = Registry::getSession()->getUser();

        if($user) {
            $sCountryISO = $user->resolveCountry();
            $oBasket = Registry::getSession()->getBasket();
            $currencyName = $oBasket->getBasketCurrency()->name;
            $data = $user->getKlarnaPaymentData();
            $data['billing_address']['country'] = strtoupper($data['billing_address']['country']);

            $result = [
                'purchase_country'  => $sCountryISO,
                'purchase_currency' => $currencyName,
                'billing_address' => $data['billing_address']
            ];
        }

        return $result;
    }

    protected function getOrderLines(Article $product = null) {
        if($product !== null) {
            $taxRate = KlarnaUtils::parseFloatAsInt($product->getPrice()->getVat() * 100);
            $totalAmount = KlarnaUtils::parseFloatAsInt($product->getPrice()->getBruttoPrice() * 100);
            $orderLines[] = [
                "type" => "physical",
                "reference" => $product->tcklarna_getArtNum(),
                "name" => $product->tcklarna_getOrderArticleName(),
                "quantity" => 1,
                "unit_price" => KlarnaUtils::parseFloatAsInt($product->getPrice()->getBruttoPrice() * 100),
                "tax_rate" => $taxRate,
                "total_amount" => $totalAmount,
                "total_discount_amount" => 0,
                "total_tax_amount" => KlarnaUtils::parseFloatAsInt($totalAmount - round($totalAmount / ($taxRate / 10000 + 1), 0)),
                "image_url" => $product->tcklarna_getArticleImageUrl()
            ];
        } else {
            $orderLines = current(Registry::getSession()->getBasket()->getKlarnaOrderLines());
        }

        return $orderLines;

    }

    /**
     * @return array|null
     * @throws KlarnaConfigException
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    protected function getShippingOptions() {
        $oSession = Registry::getSession();
        $oShippingAdapter = oxNew(
            ShippingAdapter::class,
            $oSession->getUser(),
            $oSession->getBasket()
        );

        return $oShippingAdapter->getShippingOptions(KlarnaPayment::KLARNA_INSTANT_SHOPPING);
    }

    protected function getEnvironment() {
        $test = KlarnaUtils::getShopConfVar('blIsKlarnaTestMode');
        return $test ? self::ENV_TEST : self::ENV_LIVE;
    }

    protected function getButtonStyling() {
        $style = [
            "tagline" => "dark",
            "variation" => "klarna",
            "type" => "express"
        ];
        $oConfig = Registry::getConfig();
        $savedStyle = $oConfig->getConfigParam('aarrKlarnaISButtonStyle');
        if($savedStyle) {
            return $savedStyle;
        }

        return $style;
    }

    public function getGenericConfig()
    {

        return [
            "setup"=> [
                "key" => "45a2837c-aa16-46df-9a93-69fcddbc4810",
                "environment" => $this->getEnvironment(),
                "region" => "eu"
            ],
            "styling" => [
                "theme" => $this->getButtonStyling()
            ],
            "purchase_country" => "DE",
            "purchase_currency" => "EUR",
            "locale" => KlarnaConsts::getLocale(true),
            "merchant_urls" => $this->getMerchantUrls(),
            "order_lines" => [[
                "type" => "physical",
                "reference" => "12345",
                "name" => "Testprodukt",
                "quantity" => 1,
                "unit_price" => 125000,
                "tax_rate" => 2500,
                "total_amount" => 125000,
                "total_discount_amount" => 0,
                "total_tax_amount" => 25000,
                "image_url" => ""
            ]],
            "billing_address" => [
                "given_name" => "John",
                "family_name" => "Doe",
                "email" => "jane@doeklarna.com",
                "title" => "Mr",
                "street_address" => "Theresienhöhe 12.",
                "postal_code" => "80339 ",
                "city" => "Munich",
                "phone" => "333444555",
                "country" => "DE",
            ],
        ];
    }
}