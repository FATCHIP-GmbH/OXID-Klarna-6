<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
return [
    'existingUser' => [
        "userId" => "testuser",
        "userLoginName" => "example_test@oxid-esales.dev",
        "userPassword" => "useruser",
        "userName" => "UserNamešÄßüл",
        "userLastName" => "UserSurnamešÄßüл",
    ],
    'adminUser' => [
        "userId" => "admin",
        "userLoginName" => "admin@myoxideshop.com",
        "userPassword" => "admin0303",
        "userName" => "John",
        "userLastName" => "Doe",
    ],
    // oxpayments
    'oxpayments' => getPaymentsArray()
];

function getPaymentsArray() {
    $payments = [];
    $keys = array('OXID','OXACTIVE','OXDESC','OXADDSUM','OXADDSUMTYPE','OXADDSUMRULES','OXFROMBONI','OXFROMAMOUNT','OXTOAMOUNT','OXVALDESC','OXCHECKED','OXDESC_1','OXVALDESC_1','OXDESC_2','OXVALDESC_2','OXDESC_3','OXVALDESC_3','OXLONGDESC','OXLONGDESC_1','OXLONGDESC_2','OXLONGDESC_3','OXSORT','OXTIMESTAMP','TCKLARNA_PAYMENTTYPES','TCKLARNA_EXTERNALNAME','TCKLARNA_EXTERNALPAYMENT','TCKLARNA_EXTERNALCHECKOUT','TCKLARNA_PAYMENTIMAGEURL','TCKLARNA_PAYMENTIMAGEURL_1','TCKLARNA_PAYMENTIMAGEURL_2','TCKLARNA_PAYMENTIMAGEURL_3','TCKLARNA_CHECKOUTIMAGEURL','TCKLARNA_CHECKOUTIMAGEURL_1','TCKLARNA_CHECKOUTIMAGEURL_2','TCKLARNA_CHECKOUTIMAGEURL_3','TCKLARNA_PAYMENTOPTION','TCKLARNA_EMDPURCHASEHISTORYFULL');
    $paymentValues = [
        array('bestitamazon', 1, 'Amazon Pay', 0, 'abs', 15, 0, 0, 99999999, '', 0, 'Amazon Pay', '', '', '', '', '', '', '', '', '', 0, '2018-05-18 14:51:37', '', 'Amazon Payments', 1, 1, 'https://ekspress.delfi.ee/misc/crs/digi/gfx/digi/i/banklink-seb.png', 'https://ekspress.delfi.ee/misc/crs/digi/gfx/digi/i/banklink-seb.png', 'https://ekspress.delfi.ee/misc/crs/digi/gfx/digi/i/banklink-seb.png', 'https://ekspress.delfi.ee/misc/crs/digi/gfx/digi/i/banklink-seb.png', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT0z-2BWIP5lX_zA7wZYa6WMKoki2bCpJ5TM3gvfmp3IwWINBb8lg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT0z-2BWIP5lX_zA7wZYa6WMKoki2bCpJ5TM3gvfmp3IwWINBb8lg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT0z-2BWIP5lX_zA7wZYa6WMKoki2bCpJ5TM3gvfmp3IwWINBb8lg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT0z-2BWIP5lX_zA7wZYa6WMKoki2bCpJ5TM3gvfmp3IwWINBb8lg', 'other', 0),
        array('klarna_checkout', 1, 'Klarna Checkout', 0, 'abs', 31, 0, 0, 1000000, '', 0, 'Klarna Checkout', '', '', '', '', '', '', '', '', '', -350, '2018-04-30 16:05:58', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'other', 0),
        array('klarna_pay_later', 1, 'Klarna Rechnung', 0, 'abs', 31, 0, 0, 1000000, '', 0, 'Klarna Pay Later', '', '', '', '', '', '', '', '', '', -349, '2018-04-30 16:05:58', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'other', 0),
        array('klarna_pay_now', 1, 'Sofort bezahlen', 0, 'abs', 31, 0, 0, 1000000, '', 0, 'Klarna Pay Now', '', '', '', '', '', '', '', '', '', -347, '2018-04-30 16:05:58', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'other', 0),
        array('klarna_slice_it', 1, 'Klarna Ratenkauf', 0, 'abs', 31, 0, 0, 1000000, '', 0, 'Klarna Slice It', '', '', '', '', '', '', '', '', '', -348, '2018-04-30 16:05:58', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'other', 0),
        array('oxempty', 1, 'Empty', 0, 'abs', 0, 0, 0, 0, '', 0, 'Empty', '', '', '', '', '', 'for other countries', 'An example. Maybe for use with other countries', '', '', 100, '2018-04-30 16:04:55', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'other', 0),
        array('oxidcashondel', 1, 'Nachnahme', 7.5, 'abs', 0, 0, 0, 1000000, '', 1, 'COD (Cash on Delivery)', '', '', '', '', '', '', '', '', '', 600, '2018-04-30 16:05:58', '', 'Nachnahme', 1, 0, 'https://example.com', 'https://example.com', 'https://example.com', 'https://example.com', NULL, NULL, NULL, NULL, 'other', 0),
        array('oxidcreditcard', 1, 'Kreditkarte', 20.9, 'abs', 0, 500, 0, 1000000, 'kktype__@@kknumber__@@kkmonth__@@kkyear__@@kkname__@@kkpruef__@@', 1, 'Credit Card', 'kktype__@@kknumber__@@kkmonth__@@kkyear__@@kkname__@@kkpruef__@@', '', '', '', '', 'Die Belastung Ihrer Kreditkarte erfolgt mit dem Abschluss der Bestellung.', 'Your Credit Card will be charged when you submit the order.', '', '', 500, '2018-04-30 16:05:58', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'card', 0),
        array('oxiddebitnote', 1, 'Bankeinzug/Lastschrift', 0, 'abs', 0, 0, 0, 1000000, 'lsbankname__@@lsblz__@@lsktonr__@@lsktoinhaber__@@', 0, 'Direct Debit', 'lsbankname__@@lsblz__@@lsktonr__@@lsktoinhaber__@@', '', '', '', '', 'Die Belastung Ihres Kontos erfolgt mit dem Versand der Ware.', 'Your bank account will be charged when the order is shipped.', '', '', 400, '2018-04-30 16:05:58', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'direct banking', 0),
        array('oxidinvoice', 1, 'Rechnung', 0, 'abs', 0, 800, 0, 1000000, '', 0, 'Invoice', '', '', '', '', '', '', '', '', '', 200, '2018-04-30 16:04:55', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'other', 0),
        array('oxidpayadvance', 1, 'Vorauskasse', 0, 'abs', 0, 0, 0, 1000000, '', 1, 'Cash in advance', '', '', '', '', '', '', '', '', '', 300, '2018-04-30 16:04:55',  NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'other', 0),
        array('oxidpaypal', 1, 'PayPal', 0, 'abs', 0, 0, 0, 10000, '', 0, 'PayPal', '', '', '', '', '', '<div>Bei Auswahl der Zahlungsart PayPal werden Sie im nächsten Schritt zu PayPal weitergeleitet. Dort können Sie sich in Ihr PayPal-Konto einloggen oder ein neues PayPal-Konto eröffnen und die Zahlung autorisieren. Sobald Sie Ihre Daten für die Zahlung bestätigt haben, werden Sie automatisch wieder zurück in den Shop geleitet, um die Bestellung abzuschließen.</div> <div style="margin-top: 5px">Erst dann wird die Zahlung ausgeführt.</div>', '<div>When selecting this payment method you are being redirected to PayPal where you can login into your account or open a new account. In PayPal you are able to authorize the payment. As soon you have authorized the payment, you are again redirected to our shop where you can confirm your order.</div> <div style="margin-top: 5px">Only after confirming the order, transfer of money takes place.</div>', '', '', 0, '2018-04-30 13:52:37', NULL, 'PayPal', 1, 1, 'https://exmaple.com/img.img', 'https://exmaple.com/img.img', 'https://exmaple.com/img.img', 'https://exmaple.com/img.img', 'https://exmaple.com/img.img', 'https://exmaple.com/img.img', 'https://exmaple.com/img.img', 'https://exmaple.com/img.img', 'other', 0)
    ];
    foreach($paymentValues as $values) {
        $payments[] = array_combine($keys, $values);
    }

    return $payments;
}
