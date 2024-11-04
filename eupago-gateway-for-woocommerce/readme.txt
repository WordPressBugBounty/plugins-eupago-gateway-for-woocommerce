=== Eupago Gateway For Woocommerce ===
Contributors: eupagoip
Tags: woocommerce, payment, gateway, multibanco, atm, debit card, credit card, bank, ecommerce, e-commerce, eupago, mb way, payshop, cofidispay, pagamento, refund, reembolso
Author URI: https://www.eupago.pt/
Plugin URI: 
Requires at least: 4.4
Tested up to: 6.4.2
Requires PHP: 7.0
Stable tag: 4.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Plugin para recebimento de pagamentos via Multibanco, PayShop, MB WAY, Cartão de Crédito, Paysafecard e CofidisPay. O plugin permite ainda fazer reembolsos directamente pela plataforma do WooCommerce.

== Description ==

Testado até à versão 8.2.11 de PHP

= Features: =


Este plugin permite disponibilizar aos clientes finais novos meios de pagamento nacionais e internacionais. O plugin atualiza automaticamente o estado da encomenda quando o pagamento é feito, assim como o stock do produto.

* geração de referências Multibanco Reference com ou sem data limite para pagamento. Os pagamentos são realizados via ATM ou Homebanking;
* geração de referências PayShop Reference. O pagamento é realizado numa vasta rede nacional;
* geração de um pedido de pagamento MB WAY. O pagamento é realizado na aplicação da MB WAY;
* geração de pedido de pagamentos via Cartão de Crédito;
* geração de pedido de pagamentos via CofidisPay (para valores estipulados pela Cofidis);
* possibilita de fazer reembolsos directamente através da plataforma e-commerce;
* alteração automática do estado das encomendas para "Em Processamento" após o pagamento do cliente e informa o cliente final e o administrador da loja.


== Frequently Asked Questions ==

= Posso começar a receber pagamentos apenas instalando o plugin? =

Para começar a receber pagamentos deve primeiro aderir aos serviços da Eupago. Saiba mais em [https://www.eupago.pt](https://www.eupago.pt).

= Quanto tempo o meu cliente tem para realizar o pagamento de um pedido MB WAY? =

O cliente dispõe de cerca de 4 minutos para realizar o pagamento após a finalização da compra. Este tempo é definido pela própria MB WAY.

== Changelog ==

= 4.2.1(04/11/2024) =
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