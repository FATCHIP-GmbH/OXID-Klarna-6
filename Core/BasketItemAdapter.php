<?php


namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;

class BasketItemAdapter
{
    protected $itemData;

    public function __construct($itemData)
    {
        $this->itemData = $itemData;
    }

    public function getItemData() {
        return $this->itemData;
    }

    public function isArticle()
    {
        return $this->itemData['type'] === 'physical';
    }

    public function isShipping()
    {
        return $this->itemData['type'] === 'shipping_fee';
    }

    /**
     * Gets Article oxid
     * @return $this
     * @throws NoArticleException
     */
    public function prepareArticleData() {
        $viewName = getViewName('oxarticles');
        $sSQL = "SELECT oxid FROM {$viewName} WHERE OXACTIVE=1 AND OXARTNUM = ?";
        $id = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getOne($sSQL, array($this->itemData['reference']));
        if ($id == false) {
            throw new NoArticleException();
        }
        $this->itemData['id'] = $id;

        return $this;
    }

    /**
     * Compares OrderData price to BasketItem price
     * @param BasketItem $oBasketItem
     * @throws ArticleInputException
     */
    public function validateArticlePrice(BasketItem $oBasketItem)
    {
        $requestedItemPrice = $this->itemData['total_amount'] / 100;
        if ($requestedItemPrice !==  $oBasketItem->getPrice()->getBruttoPrice()) {
            throw new ArticleInputException('INVALID_ARTICLE_PRICE');
        }
    }

}