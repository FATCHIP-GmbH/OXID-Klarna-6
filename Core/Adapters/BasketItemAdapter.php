<?php


namespace TopConcepts\Klarna\Core\Adapters;


use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use TopConcepts\Klarna\Model\KlarnaArticle;

/**
 * Class BasketItemAdapter
 * @package TopConcepts\Klarna\Core
 */
class BasketItemAdapter extends BaseBasketItemAdapter
{

    public function isBundle() {
        return $this->itemData['merchant_data']['type'] === static::BUNDLE_TYPE;
    }

    /**
     * Adds Article to oBasket
     * @return void
     * @throws ArticleInputException
     * @throws NoArticleException
     * @throws OutOfStockException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function addItemToBasket()
    {
        $itemData = $this->prepareArticleData()->getItemData();
        /** @var BasketItem $oBasketItem */
        $oBasketItem = $this->oBasket->addToBasket(
            $itemData['id'],
            $itemData['quantity'],
            null,
            null,
            null,
            $this->isBundle(),
            null
        );
        $this->oItem = $oBasketItem;
    }

    public function getItemKey()
    {
        return $this->oItem->getBasketItemKey();
    }

    protected function getArticle($iLang)
    {
        /** @var Article | BasketItem | KlarnaArticle $oArt */
        $oArt = $this->oItem->getArticle();
        if (!$oArt instanceof Article) {
            $oArt = $oArt->getArticle();
        }
        if ($iLang) {
            $oArt->loadInLang($iLang, $oArt->getId());
        }

        return $oArt;
    }

    /**
     * Gets Article oxid
     * @return $this
     * @throws NoArticleException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function prepareArticleData()
    {
        $viewName = getViewName('oxarticles');
        $sSQL = "SELECT oxid FROM {$viewName} WHERE OXACTIVE=1 AND OXARTNUM = ?";
        $id = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)
            ->getOne($sSQL, array($this->itemData['reference']));
        if ($id == false) {
            throw new NoArticleException();
        }
        $this->itemData['id'] = $id;

        return $this;
    }

    /**
     * Collects BasketItem data
     * @param $iLang
     * @param $isOrderMgmt
     * @return $this
     * @throws \oxArticleInputException
     * @throws \oxNoArticleException
     */
    public function prepareItemData($iLang)
    {
        $this->itemData = static::DEFAULT_ITEM_DATA;
        $this->itemData['merchant_data'] = [
            'type' => $this->oItem->isBundle() ? static::BUNDLE_TYPE : static::BASKET_ITEM_TYPE,
        ];
        $this->itemData['type'] = $this->getKlarnaType();
        $oArticle = $this->getArticle($iLang);
        $this->itemData['reference'] = $oArticle->getFieldData('OXARTNUM');
        $this->itemData['name'] = substr($oArticle->getFieldData('OXTITLE'), 0, 64);
        $this->itemData['quantity'] = (int)$this->oItem->getAmount();
        $basketUnitPrice = 0;
        if (!$this->oItem->isBundle()) {
            if ((bool)$this->oOrder) {
                $oBasketUnitPrice = $this->oItem->getArticle()->getUnitPrice();
            } else {
                $oBasketUnitPrice = $this->oItem->getUnitPrice();
            }
            $this->itemData['unit_price'] = $this->formatAsInt($this->oItem->getRegularUnitPrice()->getBruttoPrice());
            $basketUnitPrice = $this->formatAsInt($oBasketUnitPrice->getBruttoPrice());
        }
        $this->itemData['total_discount_amount'] = ($this->itemData['unit_price'] - $basketUnitPrice) * $this->itemData['quantity'];
        $this->itemData['total_amount'] = $basketUnitPrice * $this->itemData['quantity'];
        $this->itemData['tax_rate'] = $this->formatAsInt($this->oItem->getUnitPrice()->getVat());
        $this->itemData['total_tax_amount'] = $this->calcTax($this->itemData['total_amount'], $this->itemData['tax_rate']);
        $this->itemData['quantity_unit'] = 'pcs';
        $this->itemData['product_url'] = $oArticle->tcklarna_getArticleUrl();
        $this->itemData['image_url'] = $oArticle->tcklarna_getArticleImageUrl();
        $this->itemData['product_identifiers'] = array(
            'category_path'            => $oArticle->tcklarna_getArticleCategoryPath(),
            'global_trade_item_number' => $oArticle->tcklarna_getArticleEAN(),
            'manufacturer_part_number' => $oArticle->tcklarna_getArticleMPN(),
            'brand'                    => $oArticle->tcklarna_getArticleManufacturer(),
        );

        return $this;
    }

    /**
     * Compares Klarna OrderData price to oItem  price representing oxid basket item
     * @throws ArticleInputException
     */
    public function validateItem()
    {
        $validPrice = $this->formatAsInt($this->oItem->getPrice()->getBruttoPrice());
        if ($this->itemData['total_amount'] !== (int)$validPrice) {
            throw new ArticleInputException("INVALID_ITEM_PRICE:\n " . json_encode(['item' => $this->itemData], JSON_PRETTY_PRINT));
        }
    }
}