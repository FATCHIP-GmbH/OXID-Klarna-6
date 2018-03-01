<?php

class klarna_oxorderarticle extends klarna_oxorderarticle_parent
{
    public function getAmount()
    {
        return $this->oxorderarticles__oxamount->value;
    }

    public function getRegularUnitPrice()
    {
        return $this->getBasePrice();
    }

    public function getUnitPrice()
    {
        return $this->getPrice();
    }

    /**
     * @param $index
     * @param int|string $iOrderLang
     */
    public function kl_setTitle($index, $iOrderLang = '')
    {
        $name = KlarnaUtils::getShopConfVar('sKlarnaAnonymizedProductTitle_' . $this->getLangTag($iOrderLang));
        $this->oxorderarticles__kltitle = new oxField(html_entity_decode( "$name $index", ENT_QUOTES));
    }

    public function kl_setArtNum()
    {
        $this->oxorderarticles__klartnum = new oxField( md5($this->oxorderarticles__oxartnum->value));
    }

    protected function getLangTag($iOrderLang)
    {
        return strtoupper(oxRegistry::getLang()->getLanguageAbbr($iOrderLang));
    }
}