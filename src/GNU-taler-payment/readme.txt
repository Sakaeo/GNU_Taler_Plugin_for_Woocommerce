=== GNU Taler Payment Gateway for Woocommerce ===
Contributors: hofmd2, sakaeo
Donate link: https://donations.demo.taler.net/
Tags: woocommerce, e-commerce, GNU Taler, Taler, Payment Gateway
Requires at least: 5.1
Tested up to: 5.2.1
Stable tag: 5.2
Requires PHP: 7.2
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Online payment plugin for Woocommerce powered by GNU Taler

== Description ==

This plugin provides a safe and secure way to pay via the GNU Taler system. The plugin sends a request to and receives a respones from the GNU Taler Backend.
After that the plugin confirms the transaction again and redirect the customer to his own wallet to confirm the transaction.
The plugin provides the possibilitiy for the admininstrator to send the costumer a refund.
For that the plugin sends a refund request to the GNU Taler backend and receives a refund-url, which will be forwarded to the customer via an email to confirm the refund.

The GNU Taler payment system has some certificate and includes latest fraud and risk management.

== Installation ==

1. Ensure you have latest version of WooCommerce plugin installed
2. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
3. Activate the GNU Taler Payment for Woocommerce plugin through the 'Plugins' screen in WordPress.
4. Use WooCommerce settings-> payment tab -> GNU Taler Payment for Woocommerce Settings to configure the plugin.

== Frequently Asked Questions ==

= Do I have to have a GNU Taler account to use the plugin? =

Yes, you need to have an account.
You can join the GNU Taler family on: https://bank.demo.taler.net/

= Does the customer  need to have the GNU Taler Wallet installed to pay with GNU Taler? =

Yes, the customer needs the GNU Taler Wallet.
The customer can download it here: https://bank.demo.taler.net/


= Can the plugin work without Woocommerce =

For the plugin to work perfectly you need to have the Woocommerce plugin installed

== Screenshots ==

1. No screenshots

== Changelog ==

= 0.6.0 =
* First Public Release

== Upgrade Notice ==

= 0.6.0 =

