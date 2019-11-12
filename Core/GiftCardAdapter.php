<?php


namespace TopConcepts\Klarna\Core;


class GiftCardAdapter extends BasketCostAdapter
{
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
        return $this->oCard->getFildData('oxname');
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