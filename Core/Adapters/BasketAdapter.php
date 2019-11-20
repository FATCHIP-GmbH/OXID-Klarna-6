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
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Core\Exception\KlarnaBasketTooLargeException;
use TopConcepts\Klarna\Model\KlarnaBasket;
use TopConcepts\Klarna\Model\KlarnaUser;
use TopConcepts\Klarna\Model\KlarnaInstantBasket;

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
     * @var object|KlarnaInstantBasket
     */
    protected $oInstantShoppingBasket;

    /** @var  */
    protected $requestedOrderLines;

    /** @var bool  */
    protected $handleBasketUpdates = false;

    /** @var array */
    protected $updateData = [];

    /**
     * @return array
     */
    public function getUpdateData(): array
    {
        return $this->updateData;
    }

    /**
     * @param bool $handleBasketUpdates
     */
    public function setHandleBasketUpdates(bool $handleBasketUpdates): void
    {
        $this->handleBasketUpdates = $handleBasketUpdates;
    }

    /**
     * BasketAdapter constructor.
     * @param Basket|KlarnaBasket $oBasket
     * @param User $oUser
     * @param array $orderData
     * @param Order $oOrder
     */
    public function __construct(Basket $oBasket, User $oUser, $orderData = [], Order $oOrder = null)
    {
        $this->oBasket = $oBasket;
        $this->oUser = $oUser;
        $this->orderData = $orderData;
        $this->iLang = $oOrder ? $oOrder->getFieldData('oxlang') : null;
        $this->oOrder = $oOrder;
        $this->oBasket->setBasketUser($oUser);
        // copy original order data
        $this->requestedOrderLines = $this->orderData['order_lines'];
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
        $typeClass = null;
        $adapterClassMap = BaseBasketItemAdapter::ITEM_ADAPTER_CLASS_MAP;
        if (isset($orderLine['merchant_data']['type'])) {
            $typeClass = $adapterClassMap[$orderLine['merchant_data']['type']];
        } elseif (isset($orderLine['type'])) {
            $typeMap = BaseBasketItemAdapter::ITEM_TYPE_MAP;
            $typeName = array_search($orderLine['type'], $typeMap);
            $orderLine['merchant_data']['type'] = $typeName;
            $typeClass = $adapterClassMap[$typeName];
        } else {
            Registry::getLogger()->log('error', 'UNRECOGNIZED_ORDER_LINE_TYPE', $orderLine);
//        throw new StandardException('UNRECOGNIZED_ORDER_LINE_TYPE ' . $orderLine['merchant_data']['type']);
        }

        return oxNew($typeClass,
            $orderLine,
            $oItem,
            $this->oBasket,
            $this->oUser,
            $this->oOrder
        );
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
            $klItem['merchant_data'] = json_decode($klItem['merchant_data'], true);  // decode merchant_data
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
            $added = $oItemAdapter->addItemToOrderLines($this->orderData['order_lines'], $this->iLang);
            if ($added) {
                $this->itemAdapters[$oItemAdapter->getItemKey()] = $oItemAdapter;
            }
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

            yield $itemAdapter;
        }

        foreach($this->oBasket->getVouchers() as $ref => $oVoucher) {
            $itemAdapter = $this->createItemAdapterForType(
                ['merchant_data' => ['type' => 'voucher']],
                $oVoucher
            );

            yield $itemAdapter;
        }

        foreach($this->oBasket->getDiscounts() as $ref => $oDiscount) {
            $itemAdapter = $this->createItemAdapterForType(
                ['merchant_data' => ['type' => 'discount']],
                $oDiscount
            );

            yield $itemAdapter;
        }
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
     * @throws StandardException
     * @throws InvalidItemException
     */
    public function validateOrderLines() {

        $getTypeFromOrderLine = function($orderLine) {
            if (isset($orderLine['merchant_data'])) {
                return json_decode($orderLine['merchant_data'], true)['type'];
            }

            return array_search($orderLine['type'], BaseBasketItemAdapter::ITEM_TYPE_MAP);
        };

        foreach ($this->requestedOrderLines as $orderLine) {
            /** @var  BasketItemAdapter|ShippingAdapter $oItemAdapter */
            $itemKey = $getTypeFromOrderLine($orderLine) . '_' . $orderLine['reference'];
            if (isset($this->itemAdapters[$itemKey]) === false) {
                throw new StandardException("INVALID_ITEM: $itemKey");
            }
            if ($this->handleBasketUpdates) {
                try {
                    $this->itemAdapters[$itemKey]->validateItem($orderLine);
                } catch (InvalidItemException $itemException) {
                    $this->itemAdapters[$itemKey]->handleUpdate($this->updateData);
                }
            } else {
                $this->itemAdapters[$itemKey]->validateItem($orderLine);
            }
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

    /**
     * @return KlarnaInstantBasket
     * @throws \Exception
     */
    public function storeBasket()
    {
        $tempBasket = oxNew(KlarnaInstantBasket::class);
        $tempBasket->loadByUser($this->oUser->getId());
        $tempBasket->setOxuserId($this->oUser->getId());
        $tempBasket->setBasketInfo(serialize($this->oBasket));
        $tempBasket->save();
        Registry::getSession()->setVariable('instant_shopping_basket_id', $tempBasket->getId());
        $this->oInstantShoppingBasket = $tempBasket;
    }

    /**
     * $orderId
     * @param $orderId
     * @return void
     * @throws \Exception
     */
    public function finalizeBasket($orderId)
    {
        $this->oBasket->setOrderId($orderId);
        $this->oInstantShoppingBasket->setBasketInfo(serialize($this->oBasket));
        $this->oInstantShoppingBasket->setStatus(KlarnaInstantBasket::FINALIZED_STATUS);
        $this->oInstantShoppingBasket->save();
    }

    /**
     * Sets Instant Shopping basket id
     */
    public function getMerchantData()
    {
        return $this->oInstantShoppingBasket->getId();
    }

    public function setInstantShoppingBasket(KlarnaInstantBasket $oInstantShoppingBasket)
    {
        $this->oInstantShoppingBasket = $oInstantShoppingBasket;
    }
}