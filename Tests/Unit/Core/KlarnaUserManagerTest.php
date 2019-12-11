<?php

namespace TopConcepts\Klarna\Tests\Unit\Core;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use TopConcepts\Klarna\Core\KlarnaUserManager;
use TopConcepts\Klarna\Tests\Unit\ModuleUnitTestCase;

class KlarnaUserManagerTest extends ModuleUnitTestCase
{

    public function testInitUser()
    {
        $orderData = [
            'billing_address' =>
                [
                    "country" => "DE",
                ],
            'shipping_address' =>
                [
                    "country" => "AT"
                ],
        ];

        $manager = oxNew(KlarnaUserManager::class);

        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->setMethods(
            ["updateDeliveryAddress", "assign"]
        )->getMock();

        $user->oxuser__oxlname = new Field("testname",Field::T_RAW);
        $user->oxuser__oxfname = false;

        $user->expects($this->once())->method("updateDeliveryAddress");

        $user = $manager->initUser($orderData, $user);

        $this->assertSame($user->oxuser__oxfname->value, "");
        $this->assertSame($user->oxuser__oxlname->value, "testname");

        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->setMethods(
            ["clearDeliveryAddress"]
        )->getMock();

        $user->expects($this->once())->method("clearDeliveryAddress");

        $orderData = [
            'billing_address' => ["country" => "DE"],
            'shipping_address' => ["country" => "DE"],
        ];

        $manager->initUser($orderData, $user);
    }

}