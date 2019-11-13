<?php


namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Registry;

abstract class BaseBasketItemAdapter
{
    const BASKET_ITEM_TYPE = 'basket_item';
    const BUNDLE_TYPE = 'bundle';
    const SHIPPING_TYPE = 'oxdelivery';
    const WRAPPING_TYPE = 'oxwrapping';
    const GIFT_CARD_TYPE = 'oxgiftcard';
    const PAYMENT_TYPE = 'oxpayment';
    const DISCOUNT_TYPE = 'discount';
    const VOUCHER_TYPE = 'voucher';

    const ITEM_TYPE_MAP = [
        self::BASKET_ITEM_TYPE => 'physical',
        self::BUNDLE_TYPE => 'physical',
        self::SHIPPING_TYPE => 'shipping_fee',
        self::WRAPPING_TYPE => 'surcharge',
        self::GIFT_CARD_TYPE => 'gift_card',
        self::PAYMENT_TYPE => 'surcharge',
        self::DISCOUNT_TYPE => 'discount',
        self::VOUCHER_TYPE => 'discount'
    ];

    const ITEM_ADAPTER_CLASS_MAP = [
        self::BASKET_ITEM_TYPE => BasketItemAdapter::class,
        self::BUNDLE_TYPE => BasketItemAdapter::class,
        self::SHIPPING_TYPE => ShippingAdapter::class,
        self::GIFT_CARD_TYPE => GiftCardAdapter::class,
        self::PAYMENT_TYPE => PaymentAdapter::class,
    ];

    const DEFAULT_ITEM_DATA = [
        'type'                  => '',
        'reference'             => '',
        'name'                  => '',
        'quantity'              => 0,
        'unit_price'            => 0,
        'tax_rate'              => 0,
        'total_amount'          => 0,
        'total_discount_amount' => 0,
        'total_tax_amount'      => 0,
        'image_url'             => '',
        'product_identifiers'   => [],
//        'shipping_attributes'   => []
    ];

    /** @var array Klarna Order Line */
    protected $itemData;

    /** @var BasketItem */
    protected $oItem;

    /** @var Basket */
    protected $oBasket;

    /** @var User */
    protected $oUser;

    /** @var Order */
    protected $oOrder;

    public function __construct(array $itemData, $oItem = null, $oBasket = null, $oUser = null, $oOrder = null)
    {
        $this->itemData = $itemData;
        $this->oItem = $oItem;
        $this->oBasket = $oBasket;
        $this->oUser = $oUser;
        $this->oOrder = $oOrder;
    }

    /**
     * Adds Klarna Order Line to oBasket
     *
     * @return mixed
     */
    abstract public function addItemToBasket();

    /**
     * Adds oxid basket object to Order Lines
     * @param array $orderLines
     * @param $iLang
     * @param $isOrderMgmt
     * @return mixed
     */
    public function addItemToOrderLines(&$orderLines, $iLang)
    {
        $itemData = $this
            ->prepareItemData($iLang)
            ->getItemData()
        ;

        if ($this->isValidItemData()) {
            $orderLines[] = $itemData;
        }
    }

    /**
     * Compares Klarna Order Line to oxid basket object
     */
    abstract public function validateItem();

    /**
     * Prepares item data before adding it to Order Lines
     * @param $iLang
     * @param $isOrderMgmt
     * @return $this
     */
    abstract protected function prepareItemData($iLang);

    /**
     * @return array
     */
    public function getItemData()
    {
        $this->itemData['merchant_data'] = json_encode($this->itemData['merchant_data']);                               // encode merchant data

        return $this->itemData;
    }


    /**
     * Returns item identifier
     * @return string
     */
    public function getItemKey() {
        return $this->getType();
    }

    /**
     * Return item type
     * @return string
     */
    public function getType()
    {
        return $this->itemData['merchant_data']['type'];
    }

    /**
     * @param $number
     *
     * @return int
     */
    public function parseFloatAsInt($number)
    {
        return (int)(Registry::getUtils()->fRound($number));
    }

    /**
     * Validates itemData
     * @return bool
     */
    protected function isValidItemData() {
        return isset($this->itemData['name'])
            && isset($this->itemData['reference']);
    }

    protected function getKlarnaType() {
        return self::ITEM_TYPE_MAP[$this->getType()];
    }
}