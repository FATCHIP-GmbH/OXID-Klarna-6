<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use OxidEsales\Eshop\Application\Model\Basket;
use ReflectionClass;
use TopConcepts\Klarna\Core\BasketAdapter;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class BasketAdapterTest extends ModuleUnitTestCase
{

    protected function getBasketBasket($oBasket, $productsIds)
    {
        $oBasket = oxNew(Basket::class);
        $oBasket->setDiscountCalcMode(true);
        $this->setConfigParam('blAllowUnevenAmounts', true);
        foreach ($productsIds as $id) {
            $oBasket->addToBasket($id, 1);
        }
        $oBasket->calculateBasket(true);

        //basket name in session will be "basket"
        $this->getConfig()->setConfigParam('blMallSharedBasket', 1);

        return $oBasket;
    }

    public function testValidateItems()
    {

    }

    public function testBuildBasketFromOrderData()
    {

    }

    public function testConvertBasketIntoOrderData()
    {

    }

    public function testValidateShipping()
    {

    }
}
