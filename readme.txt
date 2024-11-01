=== Bob Go smart shipping solution for WooCommerce ===

Contributors: bobgroup, janwoostendorp
Tags: courier, shipping, e-commerce, ecommerce, shipping rates, rates at checkout, woocommerce, delivery, parcel delivery, logistics, checkout shipping, order fulfillment
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.0.0
Stable tag: 3.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Smart shipping and order management solution in South Africa

== Description ==

#__Smart shipping solution__
Orders are automatically synchronised from your WooCommerce store, making it easy to generate electronic shipping labels with one click. Receive quotes and ship with multiple delivery partners, whilst managing everything - including billing - within a single interface.

Once products are shipped, customers will automatically be kept up to date with Bob Go customer tracking notification emails.

Delivery partners include The Courier Guy, Internet Express, SkyNet, RAM, Fastway, Pargo and Bob Box, Bob Group’s very own locker and counter pickup solution.

#__Streamline your order processes__
Easily integrate Bob Go with your ecommerce stores, sync with your sales channels and automatically pull orders from your store. Use features like the address book and rule engine to customise your shipping processes to best suit your requirements. Bulk fulfillments, manifests and downloadable packing slips are also available for your convenience.

#__Other features include:__
- Multiple user roles, including a shipping agent user role linked to shipping classes or vendors (Dokan plugin)
- Creating manual orders and/or shipments
- Rates at checkout
- Rule engine automations
- VAT-inclusive shipping rates
- Custom-branded tracking notification emails (from Starter plan)
- Promotions in tracking notifications  (Advanced and Premium plans only)
- Zebra sticker waybill integrations (Advanced and Premium plans only)
- Open API

#__Manage the shipping rates you charge your customers at checkout__
__Rates at checkout__ is a Bob Go service that allows accounts to set up shipping rates that will be charged to their buyers at checkout. These rates will be configured based on certain criteria and can be set up for different service levels with other specific requirements. As an additional option, you can allow customers to select a collection locker or counter which will automatically be presented when a pickup location is available within their address radius.

== Screenshots ==

1. Image of the dashboard page rollup on the Bob Go app
2. Image of the dashboard page stats on the Bob Go app
3. Image of the orders page on the Bob Go app
4. Image of the shipment page on the Bob Go app
5. Image of adding a sales channel on the Bob Go app
6. Image of the rates at checkout setup on the Bob Go app
7. Image of shipping page settings to set up packages on the Bob Go app
8. Image of the rule engine page on the Bob Go app
9. Image of the resource hub page on the Bob Go app
10. Image of Bob Go rates at checkout on a Woocommerce store

== Installation ==

1. Log into your WordPress admin site.
2. Click on the “WooCommerce” tab and then “Settings”.
3. Click on “Advanced” in the tab, and then on “REST API” in the sub-menu.
4. Firstly, if Bob Go is already on your WooCommerce site, make sure it has READ/WRITE access.
__NOTE:__ Bob Go needs “Read/Write” access to ensure that order updates are sent back to your WooCommerce store.
5. If you do not have Bob Go on your WooCommerce site yet, click “Add key”.
6. A “Key Details” page will load:
In the “Description” text box, add __Bob Go__.
In the “User” box, select the default __admin user__.
In the “Permissions” box, select __Read/Write__.
7. Then click on the “Generate API key” button.
The consumer key and consumer secret will now be shown to you. You will need to copy these values and paste them into the relevant spaces in Bob Go. We highly recommend saving these values somewhere, as you might not be able to view them at a later stage.
8. Next, go to your WordPress Settings, select “Permalinks” and make sure “Post Name” is selected.
9. Once those settings are all activated, please copy the URL.
You must use your admin site URL to integrate your WooCommerce store to the Bob Go platform. You can find this by logging into your admin site and copying the URL from the address bar (it should NOT have /wp-admin/ in it).
10. On the Bob Go platform, click on the “Sales Channels” tab and select WooCommerce from the options on the screen.
11. Paste your WordPress admin site URL (copied in the previous steps) into the relevant fields and complete the WooCommerce installer on Bob Go to grant access to your WooCommerce store.
12. To ensure access, tick the box next to “Use alternative authentication method”.

##Support
[Book your seat at our next webinar](https://www.bobgo.co.za/webinar)
[Help centre](https://help.bobgo.co.za/portal/en/home)
[Contact us](https://www.bobgo.co.za/contact)

== Changelog ==

= 3.0.3 =
* Update the plugin listing content
* Send the custom suburb field in the rates at checkout requests
* Add support for custom suburb field for WooCommerce Checkout Blocks
* Ensure backwards compatibility for the custom suburb field for Classic Checkout
* Declare WooCommerce Cart and Checkout Blocks compatibility

= 2.5.22 =
* Declare and implement HPOS compatibility
* Declare WooCommerce Cart and Checkout Blocks incompatibility
* Fix "Track order" button link
* Include shipping suburb in rates at checkout requests
* Include product shipping classes in rates at checkout requests

= 2.5.20 =
* Add order item shipping classes order meta
* Add plugin version to order meta
* Update the Bob Go logo

= 2.5.19 =
* Add order item information to order meta

= 2.5.18 =
* Update Bob Go logo

= 2.5.17 =
* Add item dimensions for rates at checkout, only if they are not empty

= 2.5.16 =
* Add item dimensions for rates at checkout

= 2.5.15 =
* Fix - Rename the plugin to "Bob Go smart shipping solution"

= 2.5.14 =
* Rename the plugin to "Bob Go smart shipping solution"

= 2.5.13 =
* Rebranding uAfrica.com to Bob Go

= 2.5.12 =
* Wordpress plugin listing content update - more updates

= 2.5.11 =
* Wordpress plugin listing content update

= 2.5.10 =
* Fixes a display bug in delivery timeframes (ie: 1 to 1 business days)

= 2.5.9 =
* UX updates to the tracking page
* Adds a setting to show delivery timeframe and additional service level description, as configured on uAfrica.com, below each shipping rate.

= 2.5.8 =
* Fixes a bug that caused users to be unable to checkout

= 2.5.7 =
* Remove rates at checkout support for uAfrica V2

= 2.5.6 =
Bug fix (minor) - Removes possible PHP warning if setting does not exist.

= 2.5.5 =
* Adds support for sites using subfolders (e.g myshop.com/shop) as their primary domain when configuring shipping rates.
* When a shipping rate is selected at checkout, save the service code to the order meta data.
* Small UI improvements

= 2.5.4 =
Technical change to tracking requests.

= 2.5.3 =
Alter screenshots on plugin listing.

= 2.5.2 =
Change included screenshots and remove a deprecated courier.

= 2.5.1 =
Updated the plugin description and included screenshots.

= 2.5.0 =
uAfrica accounts that have been migrated to ship.uafrica.com will now have their shipping rates pulled from the new platform, with a fallback to app.uafrica.com.

= 2.4.3 =
Fixes an issue that caused the plugin's Elementor widget to cause clashes with some other plugins.

= 2.4.2 =
Fixes fetching of tracking info from ship.uafrica.com.

= 2.4.1 =
Minor Javascript bugfix.

= 2.4.0 =
Tracking will now query ship.uafrica.com for tracking information if not available at app.uafrica.com.

= 2.3.4 =
* The timeout to uAfrica's Realtime Rates service has been increased slightly.
* A deprecation warning has been fixed.
* The "Tested up to" version of WordPress has been bumped to 5.9.1

= 2.3.3 =
Minor bugfix related to another plugin compatibility issue.

= 2.3.1 =
Bugfix related to compatibility issues with the WooCommerce Subscriptions plugin.

= 2.3.0 =
Slight look and feel improvements to the tracking form and error response.

= 2.2.0 =
* A "uAfrica Tracking" Elementor widget is now available, in the "uAfrica" widget category. Thus, you can now add tracking functionality to your Elementor-built pages.
* Tracking statuses from uAfrica are now displayed in a friendlier way (e.g. "Out for delivery" instead of "out-for-delivery").

= 2.1.0 =
Suburb fields for billing and shipping addresses are now displayed at checkout by default. These can be disabled in uAfrica shipping settings in WordPress admin.

= 2.0.4 =
Removed irrelevant instructions from the uAfrica Shipping settings page.

= 2.0.3 =
Increased timeout for the calls to uAfrica API

= 2.0.2 =
Fixed a bug where wrong price was sent to the shipping method calculation.

= 2.0.0 =
Added uAfrica Shipping Rates functionality. This will allow you to show rates to your buyers at checkout directly from uAfrica.

= 1.2.2 =
Fixed bug in a filter when no post object was returned.

= 1.2.1 =
Fixed a bug in the deployment.

= 1.2.0 =
Fixed a bug in the shortcode.

= 1.0.0 =
Initial Release.
