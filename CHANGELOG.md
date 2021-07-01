### 5.5.1
* Fix spelling on new Klarna admin start page DE/EN
* Bugfix: Shipping costs add up
* Update tests to be compatible with OXID 6.3.x
* Update tests to be compatible with PHPUnit8 as well as older versions.

### 5.5.0
* Remove Instant Shopping
* Backend Rebranding
* Bugfix: Order management order item price
* Bugfix: Calculate delivery cost parent call

### 5.4.0
* Add klarna credit card payment method

### 5.3.0
* Order Refund button in admin
* Bugfix: Separate discount blocks for each VAT rate
* PR: OXID module compatibility
* Add Klarna Support for new countries: IT, ES, FR and BE

### 5.2.0
* Packstation implementation for KCO
* Improved configuration options for a B2B and B2C store
* oxDiscout object with negative oxPrice value transferred as surcharge to Klarna API
* Word "Klarna" removed from payment method name on user views: order overview, email
* Improved logging for patch order request

### 5.1.4
* Change Klarna Contact information
* Bugfix: Don't show KCO country selector popup when only one country is active
* Adjust unit tests

### 5.1.3
* Adjust Unit Tests

### 5.1.2
* Adjust Codeception Tests

On-Site Messaging: 
* Field data-purchase_amount was renamed into data-purchase-amount
* Adjust styling on details page and remove from order overview


### 5.1.1
* Remove Klarna homepage banner and teaser
* Remove On-Site Messaging Footer Promotion
* Replace Footer Logo for KP
* Fix Javascript problem when custom theme is in use
* Rename Klarna Payment methods
* Update Pay Now and Pay later translation
* add birthdate and gender to Instant Shopping initialization
* Test and support for OXID 6.2.0 RC 2
* Add support for PHP 7.3 and PHP 7.4

### 5.1.0
* Implementation of Klarna Instant Shopping
* Implementation or Klarna On-Site Messaging
* PaymentGateway implementation for KP
* Bugfix for KCO Ext. Payment PayPal: Salutation, Company or c/o field and billing address lost
* Update of Klarna merchant center URLs in admin backend
* Rename of "Slice it" to "Financing"
* Hide KCO country selector with only one active country

### 5.0.2
* Acknowledge bug fix
* Acknowledge Add proper http response
* Acknowledge unit tests 

### 5.0.1
* KCO codeception tests
* Thank you page iframe fix (when email sending fail)
* Phpunit shopconfvar null oxvarname bug fix

### 5.0.0
* KP Pay Now Split changes
* EU Geoblocking regulation implementation
* KCO: Bugfix for Country list drop-down
* OXID Wave Theme support
* Porting of Selenium Tests to Codeception Framework
* Rewriting of PHPUnit Tests for phpunit Version 6

### 4.3.0
* Added B2B to KCO
* Added support for KP in CH
* Klarna Logs BugFix
* Klarna KCO execute bug fix
* Fix payment incompatibility modules 
* Fatal Errors bug fix
* CH wrong badge fix

### 4.2.2
* Added street_address2/oxaddinfo mapping so c/o or company names will be transmitted from Klarna Checkout to OXID eShop
* Prefill KCO care_of field so oxaddinfo will be transmitted from OXID eShop to Klarna Checkout

### 4.2.1
* Admin general Settings, country select options bugfix
* fixed issue when general terms checkbox was active
* onDeactivate method removed. BugFix for EE
* fixed http client signature

### 4.2.0
* Link BugFix for EE
* Design changes in the admin panel

### 4.1.0
* Added KP B2B feature
* Fixed issue about Amazon Payments integration

### 4.0.1
* Applied some changes to meet OXID module certification requirements

### 4.0.0 
* Initial Release
