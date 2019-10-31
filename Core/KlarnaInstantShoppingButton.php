<?php


namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\Registry;

class KlarnaInstantShoppingButton
{
    const ENV_TEST = 'playground';
    const ENV_LIVE = 'production';
    /**
     * @var array
     */
    protected $buttonInstance = [];

    public function getConfig(Article $product = null, $instance = null) {

        $config = [
            "setup"=> [
                "key" => self::getButtonKey(),
                "environment" => self::getEnvironment(),
                "region" => "eu"
            ],
            "styling" => [
                "theme" => [
                    "variation" => self::getButtonStyling()['variation'],
                    "type" => self::getButtonStyling()['type'],
                    "tagline" => self::getButtonStyling()['tagline']
                ]
            ],

            "purchase_country" => self::getPurchaseInfo()['purchase_country'],
            "purchase_currency" => self::getPurchaseInfo()['purchase_currency'],
            "locale" => KlarnaConsts::getLocale(),
            "merchant_urls" => self::getMerchantUrls(),
            "billing_address" => self::getPurchaseInfo()['billing_address'],
            "order_lines" => self::getOrderlines($product)
        ];

        if($instance !== null) {
            $config["setup"]["instance_id"] = $instance;
        }

        return $config;
    }

    public function getButtonInstance()
    {
        $instance = 'button-instance-'.hash('crc32', random_bytes(8));
        $this->buttonInstance[] = $instance;

        return $instance;
    }

    public static function getMerchantUrls()
    {
       return [
            "terms"             =>  Registry::getConfig()->getSslShopUrl() . "?cl=terms",
            "push"              =>  Registry::getConfig()->getSslShopUrl() . "?cl=push",
            "confirmation"      =>  Registry::getConfig()->getSslShopUrl() . "?cl=confirmation",
            "notification"      =>  Registry::getConfig()->getSslShopUrl() . "?cl=notification",
            "update"            =>  Registry::getConfig()->getSslShopUrl() . "?cl=update",
            "country_change"    =>  Registry::getConfig()->getSslShopUrl() . "?cl=country_change",
            "place_order"       =>  Registry::getConfig()->getSslShopUrl() . "?cl=place_order"
        ];
    }

    public static function getButtonKey()
    {
        return KlarnaUtils::getShopConfVar('strKlarnaISButtonKey');
    }

    public static function getPurchaseInfo()
    {
        $result = [
            "purchase_country"  => 'DE',
            "purchase_currency" => 'EUR',
        ];

        $user = Registry::getSession()->getUser();

        if(!empty($user)) {
            $sCountryISO = $user->resolveCountry();
            $oBasket = Registry::getSession()->getBasket();
            $currencyName = $oBasket->getBasketCurrency()->name;
            $result = [
                "purchase_country"  => $sCountryISO,
                "purchase_currency" => $currencyName,
            ];
        }

        $data = $user->getKlarnaPaymentData();
        $data['billing_address']['country'] = strtoupper($data['billing_address']['country']);
        $result['billing_address'] = $data['billing_address'];

        return $result;
    }

    protected static function getOrderlines(Article $product = null)
    {
        if($product !== null) {
            $taxRate = KlarnaUtils::parseFloatAsInt($product->getPrice()->getVat() * 100);
            $totalAmount = KlarnaUtils::parseFloatAsInt($product->getPrice()->getBruttoPrice() * 100);
            $orderLines[] = [
                "type" => "physical",
                "reference" => $product->tcklarna_getArtNum(),
                "name" => $product->tcklarna_getOrderArticleName(),
                "quantity" => 1,
                "unit_price" => KlarnaUtils::parseFloatAsInt($product->getTPrice()->getBruttoPrice() * 100),
                "tax_rate" => $taxRate,
                "total_amount" => KlarnaUtils::parseFloatAsInt($product->getPrice()->getBruttoPrice() * 100),
                "total_discount_amount" => KlarnaUtils::parseFloatAsInt(($product->getTPrice()->getBruttoPrice() - $product->getPrice()->getBruttoPrice()) * 100),
                "total_tax_amount" => KlarnaUtils::parseFloatAsInt($totalAmount - round($totalAmount / ($taxRate / 10000 + 1), 0)),
                "image_url" => $product->tcklarna_getArticleImageUrl()
            ];
        } else {
            $orderLines = current(Registry::getSession()->getBasket()->getKlarnaOrderLines());
        }

        return $orderLines;

    }

    protected static function getEnvironment()
    {
        $test = KlarnaUtils::getShopConfVar('blIsKlarnaTestMode');
        return $test ? self::ENV_TEST : self::ENV_LIVE;
    }

    protected static function getButtonStyling()
    {
        $style = [
            "tagline" => "dark",
            "variation" => "klarna",
            "type" => "express"
        ];

        if(KlarnaUtils::getShopConfVar('aarrKlarnaISButtonStyle')) {
            $style = KlarnaUtils::getShopConfVar('aarrKlarnaISButtonStyle');
        }

        return $style;
    }

    public function getButtonInstances()
    {
        return $this->buttonInstance;
    }

}