<?php

namespace TopConcepts\Klarna\Core;


use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use TopConcepts\Klarna\Model\KlarnaUser;

class KlarnaUserManager
{

    /**
     * @param $orderData
     * @return object|User
     */
    public function initUser($orderData)
    {
        /** @var User | KlarnaUser $oUser */
        $user = oxNew(User::class);
        $user->loadByEmail($orderData['billing_address']['email']);

        //Build user info
        $oCountry = oxNew(Country::class);
        $user->oxuser__oxcountryid = new Field(
            $oCountry->getIdByCode(strtoupper($orderData['billing_address']['country'])),
            Field::T_RAW
        );

        $user->assign(KlarnaFormatter::klarnaToOxidAddress($orderData['order'], 'billing_address'));

        Registry::getSession()->setUser($oUser);

        return $user;
    }
}