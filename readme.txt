=== Haven Protocol Gateway for WooCommerce ===

== Features ==

* Payment validation done through either `haven-wallet-rpc` or the [explorer.havenprotocol.org blockchain explorer](https://explorer.havenprotocol.org/).
* Validates payments with `cron`, so does not require users to stay on the order confirmation page for their order to validate.
* Order status updates are done through AJAX instead of Javascript page reloads.
* Customers can pay with multiple transactions and are notified as soon as transactions hit the mempool.
* Configurable block confirmations, from `0` for zero confirm to `60` for high ticket purchases.
* Live price updates every minute; total amount due is locked in after the order is placed for a configurable amount of time (default 60 minutes) so the price does not change after order has been made.
* Hooks into emails, order confirmation page, customer order history page, and admin order details page.
* View all payments received to your wallet with links to the blockchain explorer and associated orders.
* Optionally display all prices on your store in terms of Haven Protocol.
* Shortcodes! Display exchange rates in numerous currencies.

== Requirements ==

* Haven Protocol wallet to receive payments - [PRODUCTS](https://havenprotocol.org/products/)
* [BCMath](http://php.net/manual/en/book.bc.php) - A PHP extension used for arbitrary precision maths

== Installing the plugin ==

* Download the plugin from the [releases page](https://github.com/haven-protocol-org/haven-woocommerce-gateway) or clone with `git clone https://github.com/haven-protocol-org/haven-woocommerce-gateway`
* Unzip or place the `haven-woocommerce-gateway` folder in the `wp-content/plugins` directory.
* Activate "Haven Protocol Woocommerce Gateway" in your WordPress admin dashboard.
* It is highly recommended that you use native cronjobs instead of WordPress's "Poor Man's Cron" by adding `define('DISABLE_WP_CRON', true);` into your `wp-config.php` file and adding `* * * * * wget -q -O - https://yourstore.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1` to your crontab.
* Your store's currency needs to be set to a supported xAsset currency in WooCommerce > Settings > General - Currency  (currently USD, EUR, CNY, GOLD)
* Switch on Haven as a Payment method in WooCommerce > Settings > Payment
* Note: The receiving wallet should be not be used to make payments to the store.

== Option 1: Use your wallet address and viewkey ==

This is the easiest way to start accepting Haven Protocol on your website. You'll need:

* Your Haven Protocol wallet address starting with `hvx`
* Your wallet's Private View Key

Then click the `Show Keys` button and copy the `Private View Key` option in the settings page and paste your address and viewkey. You're all set!

Note on privacy: when you validate transactions with your private viewkey, your viewkey is sent to (but not stored on) https://explorer.havenprotocol.org/ over HTTPS. This could potentially allow an attacker to see your incoming, but not outgoing, transactions if they were to get his hands on your viewkey. Even if this were to happen, your funds would still be safe and it would be impossible for somebody to steal your money. For maximum privacy use your own `haven-wallet-rpc` instance.

== Option 2: Using `haven-wallet-rpc` ==

The most secure way to accept xUSD, xEUR and other xAssets on your website. You'll need:

* Root access to your webserver
* Latest [Haven-currency binaries](https://havenprotocol.org/products/)

After downloading (or compiling) the Haven Protocol binaries on your server, install the [systemd unit files](https://github.com/haven-protocol-org/haven-woocommerce-gateway/tree/master/assets/systemd-unit-files) or run `havend` and `haven-wallet-rpc` with `screen` or `tmux`. You can skip running `havend` by using a remote node with `haven-wallet-rpc` by adding `--daemon-address https://vault.havenprotocol.org` to the `haven-wallet-rpc.service` file.

Note on security: using this option, while the most secure, requires you to run the Haven wallet RPC program on your server. Best practice for this is to use a view-only wallet since otherwise your server would be running a hot-wallet and a security breach could allow hackers to empty your funds.

== Configuration ==

* `Enable / Disable` - Turn on or off Haven Protocol gateway. (Default: Disable)
* `Title` - Name of the payment gateway as displayed to the customer. (Default: Haven Protocol Gateway)
* `Discount for using Haven Protocol` - Percentage discount applied to orders for paying with Haven Protocol. Can also be negative to apply a surcharge. (Default: 0)
* `Order valid time` - Number of seconds after order is placed that the transaction must be seen in the mempool. (Default: 3600 [1 hour])
* `Number of confirmations` - Number of confirmations the transaction must recieve before the order is marked as complete. Use `0` for nearly instant confirmation. (Default: 5)
* `Confirmation Type` - Confirm transactions with either your viewkey, or by using `haven-wallet-rpc`. (Default: viewkey)
* `Haven Protocol Address` (if confirmation type is viewkey) - Your public Haven Protocol address starting with 4. (No default)
* `Secret Viewkey` (if confirmation type is viewkey) - Your *private* viewkey (No default)
* `Haven Protocol wallet RPC Host/IP` (if confirmation type is `haven-wallet-rpc`) - IP address where the wallet rpc is running. It is highly discouraged to run the wallet anywhere other than the local server! (Default: 127.0.0.1)
* `Haven Protocol wallet RPC port` (if confirmation type is `haven-wallet-rpc`) - Port the wallet rpc is bound to with the `--rpc-bind-port` argument. (Default 18080)
* `Testnet` - Check this to change the blockchain explorer links to the testnet explorer. (Default: unchecked)
* `SSL warnings` - Check this to silence SSL warnings. (Default: unchecked)
* `Show QR Code` - Show payment QR codes. (Default: unchecked)
* `Show Prices in Haven Protocol` - Convert all prices on the frontend to Haven Protocol. Experimental feature, only use if you do not accept any other payment option. (Default: unchecked)
* `Display Decimals` (if show prices in Haven Protocol is enabled) - Number of decimals to round prices to on the frontend. The final order amount will not be rounded and will be displayed down to the nano Haven Protocol. (Default: 12)

== Shortcodes ==

This plugin makes available a [haven-accepted-here] shortcode that you can use in your theme.

#### Haven accepted here badge

This will display a badge showing that you accept Haven-currency.

`[haven-accepted-here]`

![Haven Accepted Here](/assets/images/haven-accepted-here2x.png?raw=true "Haven Accepted Here")

== Setting up a wallet ==

The easiest and quickest way is to visit [the Haven web wallet](https://vault.havenprotocol.org/) for an in depth guide visit the [Haven Protocol knowledge base](https://havenprotocol.org/knowledge/create-account/) 

== Donations ==

By donating to the following Haven address, you are supporting the Haven Project

any haven-currency: hvxy8SJK1xoEMZv7wyDspxH8gwTwcRvhVRaLQhq4o8rPYoGA8c6Q9Ey9KFNhQUZcn62q9jZTuU5E2QpoXDM63jyy6YAHZwV1Db

Thank you for your support. The team are very grateful.