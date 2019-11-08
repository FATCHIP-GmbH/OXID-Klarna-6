<?php


namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use TopConcepts\Klarna\Model\KlarnaBasket;

class BasketAdapter
{

    /** @var Basket|KlarnaBasket  */
    protected $oBasket;

    /** @var array Klarna order data */
    protected $orderData;

    protected $itemAdapters = [];

    /**
     * BasketAdapter constructor.
     * @param Basket|KlarnaBasket $oBasket
     * @param array $orderData
     */
    public function __construct($oBasket, $orderData = [])
    {
        $this->oBasket = $oBasket;
        $this->orderData = $orderData;
    }

    /**
     * Converts Klarna Order Data in
     */
    public function fillBasketFromOrderData()
    {
        foreach ($this->orderData['order_lines'] as $klItem) {
            /** @var BasketItemAdapter $itemAdapter */
            $itemAdapter = oxNew(BasketItemAdapter::class, $klItem);

            if ($itemAdapter->isArticle()) {
                $this->addItemToBasket(
                    $itemAdapter->prepareArticleData()
                );
//              $itemAdapter->validateArticlePrice($basketItem); // needs to be done after basket calculation

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
        $this->oBasket->setShipping($itemData['reference']);
    }

    public function validateItems() {
        foreach ($this->oBasket->getContents() as $itemKey => $oBasketItem) {
            $this->getItemAdapter($itemKey)
                ->validateArticlePrice($oBasketItem);
        }
        return $this;
    }

    /**
     * @param $id
     * @return BasketItemAdapter
     */
    protected function getItemAdapter($id)
    {
        return $this->itemAdapters[$id];
    }

    public function validateShipping()
    {
        // delegate it to shipping adapter
        return $this;
    }
}