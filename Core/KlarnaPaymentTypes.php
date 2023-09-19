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

namespace TopConcepts\Klarna\Core;

class KlarnaPaymentTypes extends KlarnaClientBase
{
    /**
     * Oxid value of Klarna One payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_ID = 'klarna';

    /**
     * Oxid value of Klarna Checkout payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_CHECKOUT_ID = 'klarna_checkout';

    /**
     * Oxid value of Klarna Pay Now payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_PAY_NOW = 'klarna_pay_now';

    /**
     * Oxid value of Klarna Pay Now payment
     *
     * @var string
     */
    const KLARNA_DIRECTDEBIT = 'klarna_directdebit';

    /**
     * Oxid value of Klarna card
     *
     * @var string
     */
    const KLARNA_CARD = 'klarna_card';

    /**
     * Oxid value of Klarna Pay Now payment
     *
     * @var string
     */
    const KLARNA_SOFORT = 'klarna_sofort';

    /**
     * Oxid value of Klarna Invoice payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_PAY_LATER_ID = 'klarna_pay_later';

    /**
     * Oxid value of Klarna Part payment
     *
     * @var string
     */
    const KLARNA_PAYMENT_SLICE_IT_ID = 'klarna_slice_it';
}