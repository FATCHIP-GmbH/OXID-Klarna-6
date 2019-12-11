<?php
namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Application\Model\Order;

interface PaymentHandlerInterface {

    public function execute(Order $oOrder): bool;

    public function getError();
}