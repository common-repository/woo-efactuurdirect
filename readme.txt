=== efactuurdirect for WooCommerce ===
Contributors: efactuurdirect
Donate link: https://www.efactuurdirect.nl
Tags: woocommerce, efactuurdirect, factuur, efactuur, online facturatie, invoice, ubl, ideal, Exact, Twinfield
Requires at least: 4.8
Tested up to: 6.3
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The efactuurdirect for WooCommerce extension allows you to automatically generate contacts and PDF invoices from WooCommerce orders.
Invoices can be automatically synchronized to Exact Online or Twinfield.
For more information please contact us at help@efactuurdirect.nl

== Installation ==

1. Install and activate the plugin.
2. Enable Tax setting "Round tax at subtotal level, instead of rounding per line".
3. Get the API key from the efactuurdirect settings page.
4. Add your API key and credentials on the Intergration settings.
5. Enable "Screen Options->Actions" at the Orders page to see our action buttons.
6. Setup Address Line 1 as Streetname and Address Line 2 as Housenumber.
 
== Frequently Asked Questions ==

= Where can I found the API key =

The API key can be found on the efactuurdirect settings page.

= The vat value from the order is not identical on my invoice =

Enable Tax setting "Round tax at subtotal level, instead of rounding per line" to use the correct vat calculation method for invoices.

= How are multiple orders treated for the same contact =

The plugin checks if the contact already exist on efactuurdirect to prevent duplicate contacts.

= When are new contacts created =

Contacts are created together with the invoice.

= How can I customize the checkout page.

The checkout page can be customized by editing your-theme/woocommerce/checkout/form-checkout.php

== Screenshots ==

1. The efactuurdirect for WooCommerce extension allows you to automatically generate contacts and PDF invoices from WooCommerce orders.

== Changelog ==
= 1.1.3 =
* Compatibility check WordPress 6.3.
* Compatibility check WooCommerce 7.9.

= 1.1.2 =
* Compatibility check WordPress 6.1.
* Compatibility check WooCommerce 7.2.
* Compatibility check PHP 8.1.

= 1.1.1 =
* Compatibility check WordPress 6.0.
* Compatibility check WooCommerce 7.0.

= 1.1.0 =
* Fixed connectivity problems.

= 1.0.24 =
* Bugfixes.

= 1.0.23 =
* Bugfixes.

= 1.0.22 =
* Bugfixes.

= 1.0.21 =
* Bugfixes.

= 1.0.20 =
* Compatibility check WooCommerce 4.5.

= 1.0.19 =
* Compatibility check WordPress 5.5.

= 1.0.18 =
* Bugfixes.

= 1.0.17 =
* Add protection to prevent generation of invoices for old orders when enabling the plugin.

= 1.0.16 =
* Bugfixes.

= 1.0.15 =
* Bugfixes.

= 1.0.14 =
* Bugfixes.

= 1.0.13 =
* Add option to change API connection method.

= 1.0.12 =
* Bugfixes.

= 1.0.11 =
* Add payment methods for already paid invoices.
* Bugfixes.

= 1.0.10 =
* Add custom fields for invoice remark and textline at checkout.
* Improvements for WooCommerce 3.3.5.

= 1.0.9 =
* Bugfixes.

= 1.0.8 =
* Bugfixes.

= 1.0.7 =
* Bugfixes.

= 1.0.6 =
* Bugfixes.

= 1.0.5 =
* Bugfixes.

= 1.0.4 =
* Bugfixes.

= 1.0.3 =
* Process shippingcosts and discounts. 
* Enable Tax setting "Round tax at subtotal level, instead of rounding per line" is required.

= 1.0.2 =
* Bugfixes.

= 1.0.1 =
* Bugfixes.

= 1.0.0 =
* Initial version.