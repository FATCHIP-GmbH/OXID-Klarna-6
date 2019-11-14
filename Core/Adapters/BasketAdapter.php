<?php


namespace TopConcepts\Klarna\Core\Adapters;


use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Price;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Core\Exception\KlarnaBasketTooLargeException;
use TopConcepts\Klarna\Model\KlarnaBasket;
use TopConcepts\Klarna\Model\KlarnaUser;

class BasketAdapter
{
    /** @var Basket|KlarnaBasket  */
    protected $oBasket;

    /** @var User|KlarnaUser */
    protected $oUser;

    /** @var array Klarna order data */
    protected $orderData;

    /** @var null|int  */
    protected $iLang;

    /** @var Order */
    protected $oOrder;

    protected $itemAdapters = [];

    /**
     * BasketAdapter constructor.
     * @param Basket|KlarnaBasket $oBasket
     * @param User $oUser
     * @param array $orderData
     * @param null $iLang
     * @param bool $isOrderMgmt
     */
    public function __construct(Basket $oBasket, User $oUser, $orderData = [], $iLang = null, $oOrder = false)
    {
        $this->oBasket = $oBasket;
        $this->oUser = $oUser;
        $this->orderData = $orderData;
        $this->iLang = $iLang;
        $this->oOrder = $oOrder;
        $this->oBasket->setBasketUser($oUser);
    }

    /**
     *
     * @param array $orderLine
     * @param null|BasketItem $oItem
     * @return BasketItemAdapter|ShippingAdapter
     * @throws StandardException
     */
    protected function createItemAdapterForType(array $orderLine, $oItem = null)
    {
        $adapterClassMap = BaseBasketItemAdapter::ITEM_ADAPTER_CLASS_MAP;
        if (isset($orderLine['merchant_data']['type'])) {
            return oxNew($adapterClassMap[$orderLine['merchant_data']['type']],
                $orderLine,
                $oItem,
                $this->oBasket,
                $this->oUser,
                $this->oOrder
            );
        }
        throw new StandardException('UNRECOGNIZED_ORDER_LINE_TYPE ' .$orderLine['merchant_data']['type']);
    }

    /**
     * Builds Basket with articles form order data
     * Sets Shipping id on the basket
     *
     * @return $this
     * @throws ArticleInputException
     * @throws NoArticleException
     * @throws OutOfStockException
     * @throws StandardException
     */
    public function buildBasketFromOrderData()
    {
        foreach ($this->orderData['order_lines'] as $klItem) {
            $klItem['merchant_data'] = json_decode($klItem['merchant_data'], true);                               // decode merchant_data
            /** @var BasketItemAdapter|ShippingAdapter $itemAdapter */
            $itemAdapter = $this->createItemAdapterForType($klItem, null);
            $itemAdapter->addItemToBasket();
            $this->itemAdapters[$itemAdapter->getItemKey()] = $itemAdapter;
        }
        $this->oBasket->calculateBasket();

        return $this;
    }

    /**
     * @throws KlarnaBasketTooLargeException
     * @throws StandardException
     */
    public function buildOrderLinesFromBasket()
    {
        $this->orderData['order_lines'] = [];
        /** @var BaseBasketItemAdapter|ShippingAdapter|BasketCostAdapter $oItemAdapter */
        foreach($this->generateBasketItemAdapters() as $oItemAdapter) {
            $oItemAdapter->addItemToOrderLines($this->orderData['order_lines'], $this->iLang);
        }
        $this->addOrderTotals();
    }

    /**
     * @return \Generator
     * @throws StandardException
     */
    public function generateBasketItemAdapters()
    {
        $this->itemAdapters = [];
        /* @var BasketItem $oBasketItem @var String $itemKey */
        foreach ($this->oBasket->getContents() as $itemKey =>  $oItem) {
            $itemAdapter = oxNew(BasketItemAdapter::class,
                [],
                $oItem,
                $this->oBasket,
                $this->oUser,
                $this->oOrder
            );
            $this->itemAdapters[$itemKey] = $itemAdapter;

            yield $itemAdapter;
        }

        /**
         * @var string $costKey oxdelivery, oxwrapping, oxgifcard, oxpayment
         * @var Price $oPrice
         */
        foreach($this->oBasket->getCosts() as $costKey => $oPrice) {
            if($oPrice === null) {
                continue;
            }
            $itemAdapter = $this->createItemAdapterForType(
                ['merchant_data' => ['type' => $costKey]],
                $oPrice
            );
            $this->itemAdapters[$costKey] = $itemAdapter;

            yield $itemAdapter;
        }

        // voucher
        // discount
    }

    /**
     * @throws KlarnaBasketTooLargeException
     */
    protected function addOrderTotals()
    {
        $order_amount = $order_tax_amount = 0;
        foreach ($this->orderData['order_lines'] as $orderLine) {
            $order_amount     += $orderLine['total_amount'];
            $order_tax_amount += $orderLine['total_tax_amount'];
        }

        if ($order_amount > 100000000) {
            throw new KlarnaBasketTooLargeException('TCKLARNA_ORDER_AMOUNT_TOO_HIGH');
        }

        $this->orderData['order_amount'] = $order_amount;
        $this->orderData['order_tax_amount'] = $order_tax_amount;
    }

    /**
     * @return $this
     * @throws ArticleInputException
     * @throws InvalidItemException
     */
    public function validateItems() {
        foreach ($this->itemAdapters as $oItemAdapter) {
            /** @var  BasketItemAdapter|ShippingAdapter $oItemAdapter */
            $oItemAdapter->validateItem();
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getOrderData()
    {
        return $this->orderData;
    }
}