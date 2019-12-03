<?php


namespace TopConcepts\Klarna\Core\Adapters;


use OxidEsales\Eshop\Application\Model\Wrapping;
use OxidEsales\Eshop\Core\Registry;

class GiftCardAdapter extends BasketCostAdapter
{
    /** @var Wrapping */
    protected $oCard;

    public function prepareItemData($iLang)
    {
        $this->oCard = $this->oBasket->getCard();
        if ($this->oCard) {
            parent::prepareItemData($iLang);
            $this->itemData['image_url'] = $this->oCard->getPictureUrl();
        }

        return $this;
    }

    protected function getName()
    {
        return Registry::getLang()->translateString("GREETING_CARD").' "'.$this->oCard->getFieldData('oxname').'"';
    }

    protected function getReference()
    {
        return $this->oCard->getId();
    }

}