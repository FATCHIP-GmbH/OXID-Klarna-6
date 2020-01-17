<?php


namespace TopConcepts\Klarna\Core\Adapters;


use DateInterval;
use DateTime;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
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
        $oBasket->calculateBasket(true);
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
            KlarnaUtils::log('error', 'UNRECOGNIZED_ORDER_LINE_TYPE', $orderLine);
            throw new StandardException('UNRECOGNIZED_ORDER_LINE_TYPE ' . $orderLine['merchant_data']['type']);
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
         * @var Price $oCost
         */
        foreach($this->oBasket->getCosts() as $costKey => $oCost) {
            if($oCost === null || $costKey == "oxwrapping") {
                continue;
            }
            $itemAdapter = $this->createItemAdapterForType(
                ['merchant_data' => ['type' => $costKey]],
                $oCost
            );

            yield $itemAdapter;
        }
        /** @var \stdClass $oVoucher */
        foreach((array)$this->oBasket->getVouchers() as $ref => $oVoucher) {
            $itemAdapter = $this->createItemAdapterForType(
                ['merchant_data' => ['type' => 'voucher']],
                $oVoucher
            );

            yield $itemAdapter;
        }
        /** @var \stdClass $oDiscount */
        foreach((array)$this->oBasket->getDiscounts() as $ref => $oDiscount) {
            $itemAdapter = $this->createItemAdapterForType(
                ['merchant_data' => ['type' => 'discount']],
                $oDiscount
            );

            yield $itemAdapter;
        }

        /**
         * @var  $itemKey
         * @var BasketItem $oItem
         */
        foreach ($this->oBasket->getContents() as $itemKey =>  $oItem) {

            if(!($oItem instanceof BasketItem) || !$oItem->getWrapping()) {
                continue;
            }

            $itemAdapter = $this->createItemAdapterForType(
                ['merchant_data' => ['type' => 'oxwrapping']],
                $oItem
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

        // recalculateBasket updateOrderLines
        $globalFlags = '00';
        $oShippingAdapter = null;
        foreach ($this->requestedOrderLines as $orderLine) {
            $itemKey = $getTypeFromOrderLine($orderLine) . '_' . $orderLine['reference'];
            if (isset($this->itemAdapters[$itemKey]) === false) {
                throw new StandardException("INVALID_ITEM: $itemKey");
            }
            /** @var  BasketItemAdapter $oItemAdapter */
            $oItemAdapter = $this->itemAdapters[$itemKey];
            if ($this->handleBasketUpdates) {
                // capture shipping adapter
                if ($oItemAdapter->getType() === BaseBasketItemAdapter::SHIPPING_TYPE) {
                    $oShippingAdapter = $oItemAdapter;
                    continue;
                }
                try {
                    $oItemAdapter->validateItem($orderLine);
                } catch (InvalidItemException $itemException) {
                    $globalFlags |= $oItemAdapter->handleUpdate($this->updateData);
                }
            } else {
                $oItemAdapter->validateItem($orderLine);
            }
        }

        if ($this->handleBasketUpdates) {
            [$recalculateBasket, $updateOrderLines] = str_split($globalFlags);
            if($recalculateBasket) {
                $this->oBasket->calculateBasket(true);
                $this->buildOrderLinesFromBasket();
            }

            if ($updateOrderLines) {
                $this->updateData['order_lines'] = $this->orderData['order_lines'];
            }

            // make sure that shipping is calculated at the end
            if ($oShippingAdapter) {
                $oShippingAdapter->handleUpdate($this->updateData);
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
     * @param null $type
     * @return void
     * @throws \Exception
     */
    public function storeBasket($type = null)
    {
        if ($this->oInstantShoppingBasket === null) {
            $this->oInstantShoppingBasket = Registry::get(KlarnaInstantBasket::class);
            $this->oInstantShoppingBasket->setType($type);
            $this->oInstantShoppingBasket->setStatus(KlarnaInstantBasket::OPENED_STATUS);
        }

        $this->oInstantShoppingBasket->setBasketInfo(serialize($this->oBasket));
        $this->oInstantShoppingBasket->save();
    }

    /**
     * $orderId
     * @param $orderId
     * @return void
     * @throws \Exception
     */
    public function closeBasket($orderId)
    {
        $this->oBasket->setOrderId($orderId);
        $this->oInstantShoppingBasket->setBasketInfo(serialize($this->oBasket));
        $this->oInstantShoppingBasket->setStatus(KlarnaInstantBasket::FINALIZED_STATUS);
        $this->oInstantShoppingBasket->save();

        $this->removeOldBaskets();
    }

    /**
     * Sets Instant Shopping basket id
     */
    public function getMerchantData()
    {
        return $this->oInstantShoppingBasket
            ? $this->oInstantShoppingBasket->getId()
            : $this->oBasket->tcklarnaISType;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setInstantShoppingBasket(KlarnaInstantBasket $oInstantShoppingBasket)
    {
        $this->oInstantShoppingBasket = $oInstantShoppingBasket;
    }

    /**
     * Remove Temporary baskets that might been left out.(24h)
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    protected function removeOldBaskets()
    {
        $db   = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sql  = 'DELETE FROM tcklarna_instant_basket WHERE TIMESTAMP < ?';
        $date = new DateTime();
        $date->add(DateInterval::createFromDateString('yesterday'));
        $date = $date->format('Y-m-d H:i:s');
        $db->execute($sql, [$date]);
    }

}