=== Eupago Gateway For Woocommerce ===
Contributors: eupagoip
Tags: woocommerce, payment, gateway, multibanco, atm, debit card, credit card, bank, ecommerce, e-commerce, eupago, mb way, payshop, bizum, europix, pagamento, refund, reembolso
Author URI: https://www.eupago.pt/
Plugin URI: 
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 7.1
WC tested up to: 11.1.0
Stable tag: 4.7.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Plugin para recebimento de pagamentos via Multibanco, PayShop, MB WAY, Cartão de Crédito, Paysafecard, Bizum e EuroPix. O plugin permite ainda fazer reembolsos directamente pela plataforma do WooCommerce.

== Description ==

Testado até à versão 8.3 de PHP

= Features: =


Este plugin permite disponibilizar aos clientes finais novos meios de pagamento nacionais e internacionais. O plugin atualiza automaticamente o estado da encomenda quando o pagamento é feito, assim como o stock do produto.

* geração de referências Multibanco Reference com ou sem data limite para pagamento. Os pagamentos são realizados via ATM ou Homebanking;
* geração de referências PayShop Reference. O pagamento é realizado numa vasta rede nacional;
* geração de um pedido de pagamento MB WAY. O pagamento é realizado na aplicação da MB WAY;
* geração de pedido de pagamentos via Cartão de Crédito;
* geração de pedido de pagamentos via Bizum;
* geração de pedido de pagamentos via EuroPix;
* possibilita de fazer reembolsos directamente através da plataforma e-commerce;
* alteração automática do estado das encomendas para "Em Processamento" após o pagamento do cliente e informa o cliente final e o administrador da loja.


== Frequently Asked Questions ==

= Posso começar a receber pagamentos apenas instalando o plugin? =

Para começar a receber pagamentos deve primeiro aderir aos serviços da Eupago. Saiba mais em [https://www.eupago.pt](https://www.eupago.pt).

= Quanto tempo o meu cliente tem para realizar o pagamento de um pedido MB WAY? =

O cliente dispõe de cerca de 4 minutos para realizar o pagamento após a finalização da compra. Este tempo é definido pela própria MB WAY.

== Changelog ==
= 4.7.5(17/09/2026) =
* Removed: CofidisPay payment method, discontinued by the provider. It is no longer offered on the checkout nor configurable; existing CofidisPay orders keep their payment details and callbacks
* Feature: WordPress 7.0 and 7.1 compatibility (tested up to WordPress 7.1 and WooCommerce 11.1)
* Fix: "Back" button and failed payments on Credit Card, Google Pay and Apple Pay return the customer to the checkout with the cart restored (order stays on-hold)
* Fix: Payment details block in the on-hold email now works for every payment method
* Fix: Google Pay and Apple Pay description was shown twice on the block checkout
* Fix: Remove third-party CDN jQuery from the settings page, use the one bundled with WordPress
* Fix: Callback no longer returns HTTP 500 when the Eupago settings page was never saved
* Fix: Refund request no longer fails with a fatal error on PHP 8 when the amount is empty or the order is invalid
* Fix: Saving the settings no longer performs a dead HTTP request and prints an error on screen
* Fix: Remove the unused Refund user and password fields.
* Fix: Remove PHP warnings on the order screen
* Fix: Telemetry headers (X-App-Source, X-App-Version, X-Runtime-Info) were never sent because they were built as associative array entries; API requests now carry them
* Fix: EuroPix SMS no longer falls through to the Google Pay case and sends the wrong payment details
* Security: webhook 2.0 callbacks are now signature-checked with the channel key whether or not the body is encrypted; callbacks are ignored for orders not paid through Eupago
* Fix: Apple Pay and Google Pay no longer crash at checkout when stock is reduced at order time
* Fix: Order screen (HPOS) no longer crashes when opening a Credit Card or Bizum order without a stored reference
* Fix: Block checkout no longer breaks with a server error when Multibanco, Payshop, Paysafecard or Pagaqui reject a payment; the error message is shown instead
* Fix: Block cart and checkout no longer crash when Floa is enabled but the terms have not been accepted yet
* Change: Credit Card requests now use the REST API v1.02
* Change: Multibanco, Payshop and Paysafecard now use the REST API first and only fall back to SOAP when the REST request cannot be completed; the SOAP extension is no longer required for Credit Card
* Change: Eupago no longer emails the customer directly (customer.notify = false), the shop's WooCommerce emails are used
* Improvement: New design for the payment details block in emails
* Improvement: Uniform payment method icons on both checkouts, new Pagaqui logo

= 4.7.4(17/07/2026) =
* Fix: Remove invalid use statements that caused PHP warnings visible on client websites

= 4.7.3(13/07/2026) =
* Feature: Add telemetry headers to API requests
* Feature: Add translations to plugin (entity/reference/value/Limit date)
* Fix: Bugfix on WC_VERSION

= 4.7.2(04/05/2026) =
* Fix: Broken Access Control (CVE-2025-62870)
* Fix: Added hooks for callback synchronism and update buttons

= 4.7.1(18/02/2026) =
* Fix: Translations on error messages

= 4.7.0(05/01/2026) =
* Feature: Added Pagaqui payment method

= 4.6.4(19/12/2025) =
* Added: ES translations to payment method instructions

= 4.6.3(25/11/2025) =
* Fix: Add manual order
* Fix: Amount order value

= 4.6.2(14/11/2025) =
* Fix: Bugfix HPOS order missing

= 4.6.1(12/11/2025) =
* Fix: Bugfix (CC) reference status
* Fix: Bugfix Webhooks 2.0

= 4.6.0(05/11/2025) =
* Fix: Bugfix (MB) reference status

= 4.5.9(04/11/2025) =
* Fix: Bugfix (MBWay) a when admin add a order manually

= 4.5.8(31/10/2025) =
* Fix: Update Webhook not Synchronize info

= 4.5.7(27/10/2025) =
* Feature: Added Floa payment method

= 4.5.6(22/10/2025) =
* Fix: CofidisPay and Credit Card missing url to redirect.

= 4.5.5(09/10/2025) =
* Fix: Several bugs with HPOS when compatibility mode is disabled (no legacy sync)

= 4.5.4(23/09/2025) =
* Feature: Minor bug on order details showing two tables

= 4.5.3(28/08/2025) =
* Feature: Minor bug fixes on callback responses.

= 4.5.2(05/08/2025) =
* Feature: Minor bug fixes in Google Pay and Apple Pay payment methods.

= 4.5.1(01/08/2025) =
* Feature: Added Google Pay and Apple Pay payment method.

= 4.5.0(22/07/2025) =
* Feature: Added compatibility for Eupago Webhook 2.0

= 4.4.2(30/06/2025) =
* Updated code for v9.8.5.

= 4.4.1(07/04/2025) =
* Changed Translations.
* Variable names changed to English.

= 4.4.0(03/03/2025) =
* Added EuroPix payment method.
* Included Bizum payment URL in SMS, e-mail, and admin order details page (order meta box).
* Minor bug fixes in admin page.
* Updated translations in admin page.

= 4.3.1(24/02/2025) =
* Changed compatibility version.

= 4.3.0(17/12/2024) =
* Added Bizum payment method.

= 4.2.3(13/11/2024) =
* Debug the method that calls to WC_EUPAGO_INTEGRATION 

= 4.2.2(07/11/2024) =
* Debug on variables that aren't created on constructor of payment method classes

= 4.2.1(03/10/2024) =
* Added Terms and Conditions
* Sending SMS from BIZIQ for each payment method

= 4.2.0(22/08/2024) =
* Added Country Code option for MBWay Service
* Changed the behaviour of MBWay Service when the request fail

= 4.1.9(20/08/2024) =
* Fixed bug with mbway checkout when description was empty

= 4.1.8(12/08/2024) =
* Hotfix to show instructions text
* Hotfix to variable to improve compatibility with templates when using MB Way

= 4.1.7(29/07/2024) =
* Hotfix to MBWay, Multibanco and Payshop causing conflicts

= 4.1.6(18/07/2024) =
* Fix defaultLabel and description view from payment methods

= 4.1.5(21/06/2024) =
* Add Translations for Portuguese and Spanish languages
* Add new function to check browser language and update credit card form

= 4.1.4 (07/06/2024) =
* Fix some problems with MBway and Credit Card methods.
* Reverted Translations for ES,PT and ENG.

= 4.1.3 (23/05/2024) =
* Allow direct refunds for MB Way and Credit Card.
* Add new translations in ENG, PT and ESP.

= 4.1.2 (06/03/2024) =
* Hotfix to cofidis values changes.

= 4.1.1 (22/01/2024) =
* Hotfix to problem with missing file.

= 4.1 (22/01/2024) =
* Added compatibility with woocommerce checkout blocks.
* Added option to change language on credit card payment form.
* Added REST request for mbway payment method.
* Small bug fixes and translations.

= 4.0 (30/11/2023) =
* Added compatibility for stores with high performance order storage enabled 

= 3.1.13 (08/11/2023) =
* Hotfix to wrong number of installments when selection 12x on Cofidis Settings
* 
= 3.1.12 (06/11/2023) =
* Updated sms sending url endpoints to match the biziq/intelidus updates

= 3.1.11 (04/10/2023) =
* Fixed an issue with the order refund feature in the woocommerce back office.
* Refunds request information are now displayed in the order notes.
* SMS notification service fixed.

= 3.1.10 (16/08/2023) =
* Fixed some issues with callback update in administration panel.
* Fixed issue with the button in backoffice that allows to generate a new reference.
* Added further error handling to administration panel.

= 3.1.9 (10/07/2023) =
* Added an extra area in the general settings to update the callback url.
* Added a button in backoffice that allows to generate a new reference for Multibanco and Payshop payment method.

= 3.1.8 (17/04/2023) =
* Fixed Cofidispay tax rate items problem.

= 3.1.7 (03/01/2022) =
* Fixed Cofidispay checkout description.

= 3.1.6 (02/12/2022) =
* Allow setting range of values for Cofidispay.
* Fixed reduce_order_stock() method on Multibanco class.

= 3.1.5 (25/07/2022) =
* Allow to generate payment references when the order is created by Woocommerce backoffice. 
* Added a meta box for each payment method.
* Added a VAT number hook for orders created on Woocommerce backoffice.
* Added a phone field validation for the MB Way payment method.
* Fixed SMS Hooks for Cofidispay, Credit Card and PaySafeCard methods.
* Removed PaySafeCash method.

= 3.1.4 (13/06/2022) =
* Fixed SMS Hooks for Multibanco, MBWay and Payshop methods.

= 3.1.3 (02/06/2022) =
* Updated to match the new visual identity of Eupago.

= 3.1.2 (01/06/2022) =
* Updated callback handler function for failover purposes.

= 3.1.1 (24/05/2022) =
* Removed Pagaqui method.

= 3.1.0 (29/03/2022) =
* Added CofidisPay method.

= 3.0 (31/12/2021) =
* New version launch.