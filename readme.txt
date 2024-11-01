=== WooCommerce Billogram Integration ===
Contributors:      WooBill
Plugin Name:       WooCommerce Billogram Plugin
Plugin URI:        www.woobill.com/
Tags:              WooCommerce, Order, E-Commerce, Accounting, Bookkeeping, invoice, invoicing, Billogram, WooCommerce, order sync, customer sync, product sync, sync, Customers, Integration, woocommerce billogram integration, woocommerce integration, billogram integration, woocommercebillogram, wocommerce bilogram, woocomerce bilogram, wocomerce bilogram
Author URI:        www.woobill.com
Author:            WooBill
Requires at least: 3.8
Tested up to:      4.7
Stable tag:        2.4.7
Version:           2.4.7

Completely synchronize your WooCommerce Orders, Customers and Products to your Billogram invoicing service account.

== Description ==

WooCommerce Billogram Integration
Completely synchronize your WooCommerce Orders, Customers and Products to your Billogram invoicing service account.
Billogram invoices can be automatically created and sent.
Requires the WooCommerce plugin. Now works with Billogram API2.

[vimeo https://vimeo.com/125486646]

= Data export to Billogram: =
	*	CUSTOMER:
		*	Name
		*	Email
		*	Address
	*	PRODUCT/ARTICLE:
		*	Product name
		*	ArticleNumber (SKU)
		*	Price
		*	Description
	*	INVOICE:
		*	Articles details
		*	Customer details
		*	VAT
		*	Price
		*	Invoice fee
		*	Shipping cost 

Features of WooCommerce Billogram Integration:

1.	Automatic (and manual) sync of all Customers from WooCommerce to Billogram invoicing service dashboard.
2.	Automatic (and manual) sync of all Orders from WooCommerce to Billogram invoicing service dashboard. Sync initiated when order status is changed to 'Completed'.
3.	Automatic (and manual) sync of all products from WooCommerce to Billogram invoicing service Items. This function also updates products data are modified after initial sync. Supports variable products.
4.	Sync Order, Products, Customers to Billogram when Order status is changed to 'Completed' at WooCommerce->Orders Management section.

== Plugin Requirement ==

*	PHP version : 5.3 or higher, tested upto 5.5
*	WordPress   : Wordpress 3.8 or higher
*	Woocommerce: 2.6.0 or higher, tested upto 2.7.0

== Installation ==

[vimeo https://vimeo.com/125486646]

1.	Install WooCommerce Billogram Integration either via the WordPress.org plugin directory, or by uploading the files to your server
2.	Activate the plugin in your WordPress Admin and go to the admin panel Setting -> WooCommerce Billogram Integration.
3.	Active the plugin with your License Key that you have received by mail and your Billogram API-USER ID.
4.	Configure your plugin as needed.
5.	That's it. You're ready to focus on sales, marketing and other cool stuff :-)

== Screenshots ==

1.	*General settings*

2.	*Order setting*

3.	*Manual Sync function*

4.	*Support*

5.	*Welcome Screen*

Read the FAQ or business hours mail support except weekends and holidays.

== Frequently Asked Questions ==

http://woobill.com/category/faq/

== Changelog ==

= 2.4.7 =
* Replaced  depreciated WooCommerce functions.
* WooCommerce 3.0.x object properties and methods are used.

= 2.4.6 =
* Company name is used in invoices for Business type users.

= 2.4.5 =
* Company name is used for contact name for business type users.

= 2.4.4 =
* Added option for selecting "Email + Letter"

= 2.4.3 =
* Bug fixes

= 2.4.2 =
* Bug fixes

= 2.4.1 =
* Added feature to independently specify sync option for “Billogram Checkout” and “Other Checkout”. Added options: Create Invoice, Draft Invoice, Send via Mail/Letter.

= 2.4 =
* Selective order sync feature added.

= 2.3 =
* Support for WooCommerce Subscription 2.0.12 is added.

= 2.2 =
* WooCommerce Customer billing country and shipping country synced to Billogram.

= 2.1 =
* WooCommerce Product variation attirbutes added to Billogram invoice items.

= 2.0 =
* WooCommerce Billogram Integration plugin now supports WooCommerce Subscriptions.

= 1.9.1 =
* Option for stock reduction management added. Now you can select if the stock reduction should be done after the payment complete or after the checkout.

= 1.9 =
* Woobill plugin now handles the WooCommerce refund feature. If a refund had been completed for the entire order, the order status will be automatically changed to refunded and a credit invoice for the refund amount will be send to the customer. If a partial refund was awarded, the status will not change.

= 1.8 =
* Bug fixe (order date changed by payment_complete hook is fixed).

= 1.7 =
* Coupons and Shipping order lines added to invoice
* Payment gateway name changed from "Billogram Invoice" to "Faktura"

= 1.6 =
* Order status change to completed bug fixed.

= 1.5 =
* Bug fixes for Product title and description length.

= 1.4 =
* Bug fixes for handling VAT.

= 1.3 =
* Now the plugin support Invoice Due days.
* BillogramEnded state will change order status to Completed.


= 1.2 =
* Billogram Invoice as Checkout Payment option.
* Sync all orders / Billogram orders option.

= 1.1 =
* Bug fixes
* Form validation
* Additional invoicing option
* Connection testing

= 1.0 =
* Initial Release