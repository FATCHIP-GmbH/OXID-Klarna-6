<?php


namespace TopConcepts\Klarna\Core\Adapters;


use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use stdClass;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Core\KlarnaUtils;

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
        self::WRAPPING_TYPE => 'physical',
        self::GIFT_CARD_TYPE => 'physical',
        self::PAYMENT_TYPE => 'surcharge',
        self::DISCOUNT_TYPE => 'discount',
        self::VOUCHER_TYPE => 'discount'
    ];

    const ITEM_ADAPTER_CLASS_MAP = [
        self::BASKET_ITEM_TYPE => BasketItemAdapter::class,
        self::BUNDLE_TYPE => BasketItemAdapter::class,
        self::SHIPPING_TYPE => ShippingAdapter::class,
        self::WRAPPING_TYPE => WrappingAdapter::class,
        self::GIFT_CARD_TYPE => GiftCardAdapter::class,
        self::PAYMENT_TYPE => PaymentAdapter::class,
        self::VOUCHER_TYPE => VoucherAdapter::class,
        self::DISCOUNT_TYPE => DiscountAdapter::class,
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
//        'image_url'             => '',
//        'product_identifiers'   => null,
//        'shipping_attributes'   => null
    ];

    /** @var array Klarna Order Line */
    protected $itemData;

    /** @var BasketItem|Price|stdClass */
    protected $oItem;

    /** @var Basket */
    protected $oBasket;

    /** @var User */
    protected $oUser;

    /** @var Order */
    protected $oOrder;

    /** @var array  */
    protected $diffData = [];

    protected $errorCode = 'other';

    public function __construct(array $itemData, $oItem = null, $oBasket = null, $oUser = null, $oOrder = null)
    {
        $this->itemData = $itemData;
        $this->oItem = $oItem;
        $this->oBasket = $oBasket;
        $this->oUser = $oUser;
        $this->oOrder = $oOrder;

        // if no type set match type by className
//        if (isset($this->itemData['merchant_data']['type']) === false) {
//            $flippedClassMap = array_flip(self::ITEM_ADAPTER_CLASS_MAP);
//            $this->itemData['merchant_data']['type'] = $flippedClassMap[get_called_class()];
//        }
    }

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
            ->getItemData();

        if ($this->isValidItemData()) {
            // itemData mapper function
            $mapper = function ($k, $v) {
                // decode single quotes for name field
                if ($k === 'name') {
                    return [$k, html_entity_decode($v, ENT_QUOTES, 'UTF-8')];
                }
                return [$k, $v];
            };
            $itemData = array_column(array_map($mapper, array_keys($itemData), $itemData), 1, 0);

            $orderLines[] = $this->encodeMerchantData($itemData);
            return true;
        }
        return false;
    }

    /**
     * Compares Klarna Order Line to oxid basket object
     * @param $orderLine
     */
    abstract public function validateItem($orderLine);

    /**
     * Updates oBasket according to diffData property
     * Diff data is set during validation process
     * @param $updateData array - Update data to send to Klarna
     * @return void
     */
    public function handleUpdate(&$updateData) {}

    /**
     * Prepares item data before adding it to Order Lines
     * @param $iLang
     * @return $this
     */
    abstract protected function prepareItemData($iLang);


    abstract protected function getReference();



    public function encodeMerchantData($itemData)
    {
        $itemData['merchant_data'] = json_encode($itemData['merchant_data']);
        return $itemData;
    }

    /**
     * @return array
     */
    public function getItemData()
    {
        return $this->itemData;
    }


    /**
     * Returns item identifier
     * @return string
     */
    public function getItemKey() {
        return $this->getType() . '_' . $this->getReference();
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
     * Format to Klarna value
     * @param $number
     * @return int
     */
    public function formatAsInt($number)
    {
        return (int)Registry::getUtils()->fRound($number * 100);
    }

    /**
     * @param $value
     * @return float
     */
    protected function formatPrice($value)
    {
        return Registry::getUtils()->fRound($value);
    }

    /**
     * Calculate tax on Klarna values
     * @param int $total
     * @param int $rate
     * @return float
     */
    public function calcTax(int $total, int $rate)
    {
        return (int)round($total - ($total * 10000 / (10000 + $rate)));
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

    /**
     * @param $orderLine
     * @param $key
     * @param $basketValue
     * @throws InvalidItemException
     */
    protected function validateData($orderLine, $key, $basketValue) {

        KlarnaUtils::log('debug', 'VALIDATING: ' . print_r([
            'type' => $this->getType(),
            'key' => $key,
            'requestedValue' => $orderLine[$key],
            'basketValue' => $basketValue
        ], true));

        if ($orderLine[$key] !== $basketValue) {
            $oEx = new InvalidItemException("TCKLARNA_INVALID_ITEM");
            $this->setDiffData([
                'key' => $key,
                'requestedValue' => $orderLine[$key],
                'basketValue' => $basketValue
            ]);
            $oEx->setItemAdapter($this);
            throw $oEx;
        }
    }

    /**
     * @return array
     */
    public function getDiffData(): array
    {
        return $this->diffData;
    }

    /**
     * @param array $diffData
     */
    public function setDiffData(array $diffData): void
    {
        $this->diffData = $diffData;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}