<?php


namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use TopConcepts\Klarna\Model\KlarnaBasket;
use TopConcepts\Klarna\Model\KlarnaUser;

class BasketAdapter
{
    const SHIPPING_KEY = 'shipping';

    /** @var Basket|KlarnaBasket  */
    protected $oBasket;

    /** @var User|KlarnaUser */
    protected $oUser;

    /** @var array Klarna order data */
    protected $orderData;

    protected $itemAdapters = [];

    /**
     * BasketAdapter constructor.
     * @param Basket|KlarnaBasket $oBasket
     * @param User $oUser
     * @param array $orderData
     */
    public function __construct(Basket $oBasket, User $oUser, $orderData = [])
    {
        $this->oBasket = $oBasket;
        $this->oUser = $oUser;
        $this->orderData = $orderData;
        $this->oBasket->setBasketUser($oUser);
    }

    /**
     * Builds Basket with articles
     * Sets Shipping id on the basket
     */
    public function buildBasketFromOrderData()
    {
        foreach ($this->orderData['order_lines'] as $klItem) {
            /** @var BasketItemAdapter $itemAdapter */
            $itemAdapter = oxNew(BasketItemAdapter::class, $klItem);
            if ($itemAdapter->isArticle()) {
                $this->addItemToBasket(
                    $itemAdapter->prepareArticleData()
                );
            } elseif ($itemAdapter->isShipping()) {
                $this->setShipping($itemAdapter);
            }
        }
        $this->oBasket->calculateBasket();

        return $this;
    }

    /**
     *
     */
    public function convertBasketIntoOrderData()
    {

    }

    /**
     * @param BasketItemAdapter $itemAdapter
     * @return BasketItem
     * @throws ArticleInputException
     * @throws NoArticleException
     * @throws OutOfStockException
     */
    protected function addItemToBasket(BasketItemAdapter $itemAdapter)
    {
        $basketItem = null;
        $itemData = $itemAdapter->getItemData();
        $basketItem = $this->oBasket->addToBasket(
            $itemData['id'],
            $itemData['quantity']
//                $itemData['selectList'],
//                $itemData['persistentParameters'],
//                $itemData['override'],
//                $itemData['bundle'],
//                $itemData['oldBasketItemId']
        );
        $itemKey = $basketItem->getBasketItemKey();
        $this->itemAdapters[$itemKey] = $itemAdapter;
        return $basketItem;
    }

    protected function setShipping(BasketItemAdapter $itemAdapter)
    {
        $itemData = $itemAdapter->getItemData();
        $this->itemAdapters[static::SHIPPING_KEY] = $itemAdapter;
        $this->oBasket->setShipping($itemData['reference']);
    }

    public function validateItems() {
        foreach ($this->oBasket->getContents() as $itemKey => $oBasketItem) {
            $this->getItemAdapter($itemKey)
                ->validateItemPrice($oBasketItem);
        }
        return $this;
    }

    /**
     * @param $id string
     * @return BasketItemAdapter
     */
    protected function getItemAdapter($id)
    {
        return $this->itemAdapters[$id];
    }

    public function validateShipping()
    {
        /** @var ShippingAdapter $oShippingAdapter */
        $oShippingAdapter = oxNew(
            ShippingAdapter::class,
            $this->oUser,
            $this->oBasket
        );
        $oShippingAdapter->validateShipping(
            $this->getItemAdapter(static::SHIPPING_KEY)
        );
    }
}