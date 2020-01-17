<?php


namespace TopConcepts\Klarna\Core\Adapters;


use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\ArticleInputException;
use OxidEsales\Eshop\Core\Exception\NoArticleException;
use OxidEsales\Eshop\Core\Exception\OutOfStockException;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Core\Exception\InvalidItemException;
use TopConcepts\Klarna\Core\KlarnaUtils;
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
     * @param $updateData
     * @return string
     */
    public function handleUpdate(&$updateData)
    {
        $recalculateBasket = false;
        $updateOrderLines = false;

        // item quantity changed in the iframe - updated basket amount
        if ($this->diffData['key'] === 'quantity') {
            /** @var BasketItem $oBasketItem */
            try {
                $oBasketItem = $this->oBasket->addToBasket(
                    $this->oItem->getProductId(),
                    $this->diffData['requestedValue'],
                    $this->oItem->getSelList(),
                    $this->oItem->getPersParams(),
                    true,
                    $this->oItem->isBundle()
                );
                $this->oItem = $oBasketItem;
                $recalculateBasket = true;
                $updateOrderLines = true;

            } catch (\Exception $changeBasketException) {
                // ArticleInputException
                // NoArticleException
                // OutOfStockException
                // Currently there is no proper way to handle it in update callback request
                KlarnaUtils::logException($changeBasketException);
            }
        }

        // item price is changed
        // example: item discount assigned to particular country
        // conclusion: country change in the iframe should trigger order_lines update
        if ($this->diffData['key'] === 'total_amount') {
            $updateOrderLines = true;
        }

        KlarnaUtils::log('debug', 'ITEM_UPDATED: ' .
            print_r([
                'reference' => $this->itemData['reference'],
                'title' => $this->oItem->getTitle(),
                'diff' => $this->diffData
            ], true)
        );

        return join([(int)$recalculateBasket, (int)$updateOrderLines]);
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
        $name = $oArticle->getFieldData('OXTITLE') . ' ' . $oArticle->getFieldData('OXVARSELECT');
        $this->itemData['name'] = substr($name, 0, 64);
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
     * @param $orderLine
     * @throws InvalidItemException
     */
    public function validateItem($orderLine)
    {
        $this->validateData(
            $orderLine,
            'quantity',
            (int)$this->oItem->getAmount()
        );
        $this->validateData($orderLine,
            'total_amount',
            $this->formatAsInt($this->oItem->getPrice()->getBruttoPrice())
        );
    }

    public function getReference()
    {
        if(isset($this->itemData['reference'])) {
            return $this->itemData['reference'];
        }

        return $this->getArticle(null)->getFieldData('OXARTNUM');
    }
}