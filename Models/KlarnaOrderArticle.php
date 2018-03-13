<?php

namespace Klarna\Klarna\Models;


use Klarna\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Field;

class KlarnaOrderArticle extends KlarnaOrderArticle_parent
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
        $name                           = KlarnaUtils::getShopConfVar('sKlarnaAnonymizedProductTitle_' . $this->getLangTag($iOrderLang));
        $this->oxorderarticles__kltitle = new Field(html_entity_decode("$name $index", ENT_QUOTES));
    }

    public function kl_setArtNum()
    {
        $this->oxorderarticles__klartnum = new Field(md5($this->oxorderarticles__oxartnum->value));
    }

    protected function getLangTag($iOrderLang)
    {
        return strtoupper(Registry::getLang()->getLanguageAbbr($iOrderLang));
    }
}