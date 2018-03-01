<?php
use SeleniumTests\KlarnaSeleniumBaseTestCase;

class KCOWidgetsTest extends KlarnaSeleniumBaseTestCase
{

    public function loginWidgetsDataProvider()
    {
        return array(
            // test method will be called in a loop with each of array element passed as array of arguments
            array(
                // a set of data
                array(
                    'usersData' => array(
                        array('kostrzeba@topconcepts.de', '12345'),
                        array('info@topconcepts.de', 'muhipo2015'),
                    ),

                     'langData' => array(
                        'my-account' => array('my-account', 'mein-konto')
                    )
                ),

                // another set of data
            )
        );
    }

    /**
     * Purpose of this method is to setUp shop configuration
     */
    public function setUpPage()
    {

        $this->setUpShopConfig(
            array(
                'str' => array(
                    'sKlarnaActiveMode' => 'KCO'
                ),
            )
        );
    }

    public function testVoucherWidget()
    {

        $this->goToKCOIframe();

        $widget = $this->byId('klarnaVouchersWidget');
        $widget->byCssSelector('.drop-trigger')->click();
        $input = $widget->byCssSelector('[name=voucherNr]');
        $submit = $widget->byId('submitVoucher');

        // add first Voucher
        $input->value('666');
        $this->clickWhenReady($submit);
        $this->waitForAjaxComplete();
        $usedVouchers = $widget->elements($this->using('css selector')->value('.couponData'));
        $this->assertEquals(1, count($usedVouchers));

        // add second Voucher
        $input->clear();
        $input->value('666');
        $submit->click();
        $this->waitForAjaxComplete();
        $usedVouchers = $widget->elements($this->using('css selector')->value('.couponData'));
        $this->assertEquals(2, count($usedVouchers));

        // remove one Voucher
        $widget->byCssSelector('.couponData a')->click();
        $this->waitForAjaxComplete();
        $usedVouchers = $widget->elements($this->using('css selector')->value('.couponData'));
        $this->assertEquals(1, count($usedVouchers));
        $oOrderData = $this->getCurrentOrderData();

        // assert total discount
        $this->assertEquals(
            $oOrderData->oxidOrder->totalDiscount * 100,
            $this->getKlarnaDiscount($oOrderData->klarnaOrder->order_lines)
        );
    }

    /**
     * @dataProvider loginWidgetsDataProvider
     * @param $data
     */
    public function testLoginWidgets($data)
    {
        $this->goToKCOIframe();

        $this->useLoginWidget($data['usersData']);
        sleep(20);
        $this->assertContains($this->byCssSelector('.service-menu > button')->attribute('data-href'), $data['langData']['my-account'] );

    }

    protected function getKlarnaDiscount($orderLines)
    {
        $klarnaDiscounts = $this->filterOrderLines('type', 'discount', $orderLines);
        // we send all coupon discounts as one order line
        return abs($klarnaDiscounts[0]->total_amount);
    }

    protected function filterOrderLines($filterBy, $value, $aOrderLines)
    {
        $result = array();
        foreach($aOrderLines as $line){
            if($line->{$filterBy} ===  $value){
                $result[] = $line;
            }
        }

        return $result;
    }
}
