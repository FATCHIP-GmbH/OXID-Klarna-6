<?php
/**
 * Copyright 2018 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace TopConcepts\Klarna\Model;


use TopConcepts\Klarna\Core\KlarnaUtils;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Field;

class KlarnaOrderArticle extends KlarnaOrderArticle_parent
{
    public function getAmount()
    {
        return $this->oxorderarticles__oxamount->value;
    }

    /**
     * @codeCoverageIgnore
     * @return mixed
     */
    public function getRegularUnitPrice()
    {
        return $this->getBasePrice();
    }

    /**
     * @codeCoverageIgnore
     * @return mixed
     */
    public function getUnitPrice()
    {
        return $this->getPrice();
    }

    /**
     * @param $index
     * @param int|string $iOrderLang
     */
    public function tcklarna_setTitle($index, $iOrderLang = '')
    {
        $name                           = KlarnaUtils::getShopConfVar('sKlarnaAnonymizedProductTitle_' . $this->getLangTag($iOrderLang));
        $this->oxorderarticles__tcklarna_title = new Field(html_entity_decode("$name $index", ENT_QUOTES));
    }

    public function tcklarna_setArtNum()
    {
        $this->oxorderarticles__tcklarna_artnum = new Field(md5($this->oxorderarticles__oxartnum->value));
    }

    protected function getLangTag($iOrderLang)
    {
        return strtoupper(Registry::getLang()->getLanguageAbbr($iOrderLang));
    }
}