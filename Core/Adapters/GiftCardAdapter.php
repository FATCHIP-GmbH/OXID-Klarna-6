<?php


namespace TopConcepts\Klarna\Core\Adapters;


use OxidEsales\Eshop\Application\Model\Wrapping;

class GiftCardAdapter extends BasketCostAdapter
{
    /** @var Wrapping */
    protected $oCard;

    public function prepareItemData($iLang)
    {
        $this->oCard = $this->oBasket->getCard();
        if ($this->oCard) {
            parent::prepareItemData($iLang);
        }

        return $this;
    }

    protected function getName()
    {
        return $this->oCard->getFieldData('oxname');
    }

    protected function getReference()
    {
        return $this->oCard->getId();
    }

    public function addItemToBasket()
    {
        $this->oBasket->setCardId(
            $this->itemData['reference']
        );
    }

}