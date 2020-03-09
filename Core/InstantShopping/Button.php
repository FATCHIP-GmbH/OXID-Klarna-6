<?php


namespace TopConcepts\Klarna\Core\InstantShopping;


use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\CountryList;
use OxidEsales\Eshop\Application\Model\DeliveryList;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Adapters\ShippingAdapter;
use TopConcepts\Klarna\Core\Adapters\BasketAdapter;
use TopConcepts\Klarna\Core\Exception\KlarnaConfigException;
use TopConcepts\Klarna\Core\KlarnaConsts;
use TopConcepts\Klarna\Core\KlarnaUserManager;
use TopConcepts\Klarna\Core\KlarnaUtils;
use TopConcepts\Klarna\Model\KlarnaInstantBasket;
use TopConcepts\Klarna\Model\KlarnaCountryList;
use TopConcepts\Klarna\Model\KlarnaPayment;
use TopConcepts\Klarna\Model\KlarnaUser;

class Button
{
    const ENV_TEST = 'playground';
    const ENV_LIVE = 'production';
    const AVAILABLE_COUNTRIES = ['SE', 'NO', 'FI', 'DE', 'NL', 'AT', 'CH', 'US', 'UK'];

    protected $errors = [];

    /** @var User  */
    protected $oUser;

    /** @var Basket */
    protected $oBasket;
    /**
     * @var object|BasketAdapter
     */
    protected  $basketAdapter;

    protected $_klarnaCountryList;

    /** @var ShippingAdapter $_oShippingAdapter */
    protected $_oShippingAdapter;

    /**
     * @param Article|null $product
     * @return array|bool
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    public function getConfig(Article $product = null) {
        /** @var BasketAdapter $basketAdapter */
        $this->basketAdapter = $this->instantiateBasketAdapter($product);
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
            "merchant_urls" => $this->getMerchantUrls()
        ];

        $orderData = [];
        try {
            $orderData["order_lines"] = $this->getOrderLines();
            $orderData["shipping_options"] = $this->getShippingOptions($product);
            $orderData["merchant_reference2"] = $this->basketAdapter->getMerchantData();
        } catch (KlarnaConfigException $e) {
            $this->errors[] = $e->getMessage();
            KlarnaUtils::log('info', $e->getMessage(), [__METHOD__]);
        }

        $config["billing_countries"] = array_values($this->getKlarnaCountryList());
        $config["shipping_countries"] = array_values($this->getShippingCountries($this->oBasket));


        if (count($this->errors) === 0) {
            return array_merge(
                $config,
                $this->getPurchaseInfo(),
                $orderData
            );
        }
        return false;
    }

    public function getMerchantUrls() {
        $oConfig = Registry::getConfig();
        $shopBaseUrl = $oConfig->getSslShopUrl();
        $urlShopParam = method_exists($oConfig, 'mustAddShopIdToRequest')
        && $oConfig->mustAddShopIdToRequest()
            ? '&shp=' . $oConfig->getShopId()
            : '';
        $lang = strtoupper(Registry::getLang()->getLanguageAbbr());
        $terms = KlarnaUtils::getShopConfVar('sKlarnaTermsConditionsURI_' . $lang);




        return [
            "terms"             =>  $terms??$shopBaseUrl . "?cl=terms$urlShopParam",
            "confirmation"      =>  $shopBaseUrl . "?cl=thankyou$urlShopParam",
            "update"            =>  $shopBaseUrl . "?cl=KlarnaInstantShoppingController&fnc=updateOrder$urlShopParam",
            "place_order"       =>  $shopBaseUrl . "?cl=KlarnaInstantShoppingController&fnc=placeOrder$urlShopParam"
        ];


         $merchantUrls;
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
            $currencyName = Registry::getConfig()->getActShopCurrencyObject()->name;
            $data = $user->getKlarnaPaymentData();
            $result = [
                'purchase_country'  => $sCountryISO,
                'purchase_currency' => $currencyName,
            ];
            if(!empty(trim($data['billing_address']['street_address']))) {
                $data['billing_address']['country'] = strtoupper($data['billing_address']['country']);
                $result['billing_address'] = $data['billing_address'];
            }
            $attachment = $user->getAttachmentsData();
            if($attachment) {
                $result['attachment'] = $user->getAttachmentsData();
            }

            if($user->oxuser__oxbirthdate->value != "0000-00-00") {
                $result['customer']['date_of_birth'] = $user->oxuser__oxbirthdate->value;
            }

            $result['customer']['gender'] = $user->oxuser__oxsal->value;
        }

        return $result;
    }

    protected function getOrderLines() {
        $this->basketAdapter->buildOrderLinesFromBasket();

        return $this->basketAdapter->getOrderData()['order_lines'];

    }

    /**
     * @return array|null
     * @throws KlarnaConfigException
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     */
    protected function getShippingOptions(Article $product = null) {
        $this->_oShippingAdapter = $this->instantiateShippingAdapter($product);

        return $this->_oShippingAdapter->getShippingOptions(KlarnaPayment::KLARNA_INSTANT_SHOPPING);
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
        if(!$this->getButtonKey()){
            return [];
        }
        $defaultShopCountry = Registry::getConfig()->getConfigParam('sKlarnaDefaultCountry');
        $currencies = KlarnaConsts::getCountry2CurrencyArray();
        $currency = isset($currencies[$defaultShopCountry]) ? $currencies[$defaultShopCountry] : 'EUR';

        return [
            "setup"=> [
                "key" => $this->getButtonKey(),
                "environment" => $this->getEnvironment(),
                "region" => "eu"
            ],
            "styling" => [
                "theme" => $this->getButtonStyling()
            ],
            "purchase_country" => Registry::getConfig()->getConfigParam('sKlarnaDefaultCountry'),
            "purchase_currency" => $currency,
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

    /**
     * @codeCoverageIgnore
     */
    protected function getUser()
    {

        if ($this->oUser) {
            return $this->oUser;
        }
        $oUser = Registry::getSession()->getUser();
        if (!$oUser) {
            $userManager = oxNew(KlarnaUserManager::class);
            $oUser = $userManager->initUser(['billing_address' =>
                ['country' => Registry::getConfig()->getConfigParam('sKlarnaDefaultCountry')]]);

        }

        return  $this->oUser = $oUser;
    }

    protected function getBasket(Article $product = null)
    {
        $type = KlarnaInstantBasket::TYPE_BASKET;
        if ($this->oBasket) {
            return $this->oBasket;
        }
        if($product !== null) {
            $oBasket = oxNew(Basket::class);
            $oBasket->setBasketUser($this->oUser);
            $oBasket->enableSaveToDataBase(false);
            $oBasket->setStockCheckMode(false);
            try {
                $oBasket->addToBasket($product->getId(), 1);
            } catch (\Exception $e) {
                KlarnaUtils::log('error', print_r($e->getMessage(), true));
            }
            $type = KlarnaInstantBasket::TYPE_SINGLE_PRODUCT;
            Registry::getSession()->deleteVariable("blAddedNewItem"); // prevent showing notification to user
        } else {
            $oBasket = Registry::getSession()->getBasket();
        }
        $oBasket->setPayment(KlarnaPayment::KLARNA_INSTANT_SHOPPING);
        $oBasket->tcklarnaISType = $type;

        return $this->oBasket = $oBasket;
    }

    protected function getKlarnaCountryList()
    {
        if ($this->_klarnaCountryList === null) {
            $this->_klarnaCountryList = array();
            /** @var KlarnaCountryList $oCountryList */
            $oCountryList = oxNew(CountryList::class);
            $oCountryList->loadActiveKlarnaCountriesByPaymentId(KlarnaPayment::KLARNA_INSTANT_SHOPPING);
            foreach ($oCountryList as $oCountry) {
                if(in_array($oCountry->oxcountry__oxisoalpha2->value, self::AVAILABLE_COUNTRIES)) {
                    $this->_klarnaCountryList[$oCountry->oxcountry__oxid->value] = $oCountry->oxcountry__oxisoalpha2->value;
                }
            }
        }

        return $this->_klarnaCountryList;
    }

    protected function getShippingCountries($oBasket)
    {
        $list = $this->_oShippingAdapter->getShippingOptions(KlarnaPayment::KLARNA_INSTANT_SHOPPING);
        $aCountries = $this->getKlarnaCountryList();
        $oDelList = Registry::get(DeliveryList::class);
        $shippingCountries = [];
        foreach ($list as $l)
        {
            $sShipSetId = $l['id'];
            foreach ($aCountries as $sCountryId => $alpha2) {
                if ($oDelList->hasDeliveries($oBasket, $this->oBasket->getUser(), $sCountryId, $sShipSetId)
                && in_array($alpha2, self::AVAILABLE_COUNTRIES)) {
                    $shippingCountries[$alpha2] = $alpha2;
                }
            }

        }

        return $shippingCountries;
    }

    /**
     * @codeCoverageIgnore
     */
    public function instantiateBasketAdapter(Article $product = null)
    {
        return oxNew(
            BasketAdapter::class,
            $this->getBasket($product),
            $this->getUser(),
            []
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function instantiateShippingAdapter(Article $product = null)
    {
        return oxNew(
            ShippingAdapter::class,
            [],
            null,
            $this->getBasket($product),
            $this->getuser()
        );
    }
}