=== WooCommerce - Payphone Gateway ===
Contributors: Payphone
Tags: WooCommerce, Payment Gateway, PayPhone
Requires at least: 2.5
Tested up to: 6.1.1
Requires PHP: 5.3
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WooCommerce - PayPhone Gateway introduces a new payment gateway to process WooCommerce product payments via PayPhone.

== Description ==

The WooCommerce - PayPhone Gateway plugin adds a new payment gateway for processing payments through PayPhone. To use this plugin, you must first register as a PayPhone Store. If you're not yet registered, you can sign up at [PayPhone](https://payphone.app).

The plugin retrieves the total amount, taxes, shipping, and order ID, and sends them to PayPhone for payment processing. Once the payment is completed, the response is received and the corresponding order is updated accordingly.

== Installation ==

= Minimum Requirements =

* WordPress 2.5 or greater
* WooCommerce installed and activated

= Automatic Installation =

Automatic installation is the easiest option, as WordPress handles the file transfers automatically, and you don’t need to leave your web browser. To automatically install the plugin, log in to your WordPress dashboard, navigate to **Plugins > Add New**, and search for "WooCommerce - PayPhone Gateway". Once you find the plugin, click **Install Now** and then **Activate**.

= Manual Installation =

If you prefer manual installation, download the plugin and upload it to your server via your favorite FTP client. For detailed instructions, refer to the [WordPress Codex on Manual Plugin Installation](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work seamlessly. However, we recommend always backing up your site before updating.  

If you encounter any issues with callback URLs after an update, you may need to flush your permalinks by going to **WordPress > Settings > Permalinks** and clicking **Save Changes**. This should resolve any issues.

== Configuration ==

You can access the plugin settings by navigating to **WooCommerce > Settings > Payments > PayPhone**.

- **Enable/Disable**: Use this checkbox to enable or disable the payment gateway on the checkout page.
- **Gateway Description**: Provides a brief description of PayPhone during checkout.
- **Token & Test Token**: Required to communicate with PayPhone. You can retrieve the credentials by visiting [this page](https://appdeveloper.payphonetodoesposible.com).
- **Test Mode**: Enable or disable test mode, where all transactions are simulated (fake).
- **Success & Failure Pages**: Choose the page displayed when a payment is either approved or declined.

== Frequently Asked Questions ==

= Does this plugin work with credit cards or just PayPhone? =

This plugin supports payments via **credit and debit cards**, as well as PayPhone.

= Does this support both production mode and sandbox mode for testing? =

Yes, the plugin supports both **production** and **sandbox** modes. You can choose the mode based on your credentials, and switch between them as needed. To obtain the correct credentials, visit [this page](https://appdeveloper.payphonetodoesposible.com).

= Where can I find documentation? =

For help with setup and configuration, please refer to our [user guide](https://docs.payphone.app/).

= Where can I get support? =

If you need assistance, you can reach out via email at **info@payphone.app**.

== Changelog ==

= 3.2.0 =
* General improvements and optimizations.
* Enhancements to the plugin's performance and stability.
* Minor bug fixes and updates.

= 1.2.0 =
* Bug fix.

= 1.1.4 =
* Fix bugs (Coupon support).

= 1.1.3 =
* Fix bugs (convert from decimal to integer).

= 1.1.2 =
* Added support for direct payment compatibility in WordPress 5.3.

= 1.1.1 =
* Added hooks for canceled or approved payments:
  - **payphone_canceled_pay**: Triggered when a payment is canceled.
  - **payphone_approved_pay**: Triggered when a payment is approved.
  - These hooks receive the payment result as the first parameter.

= 1.1.0 =
* Bug fixes.
* Removed the test mode option. Test mode is now enabled via the developer console.

= 1.0.4 =
* Added support for the card add-on.

= 1.0.3 =
* Bug fixes.

= 1.0.2 =
* Updated the documentation page.

= 1.0.0 =
* Initial release.