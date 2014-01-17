=== AKW Store Locator ===
Contributors: Pradeep
Tags: Store locator, google maps, maps, store finder, store locations, business locator, geocoding, radius, stores
Author URI: http://www.aroundkwhosting.com
Version: 1.4
Requires at least: 3.6
Tested up to: 3.8
Stable tag: 1.4
License: GPLv2 or later

Simple, easy to install plugin to view stores around a location. Displays the results using Google map.

== Description ==

The AKW Store Locator plugin helps users view locations of business along with phone number, address and distance from the location in a map by just specifying a location and selecting a range or radius of search. Admin can add stores/locations to the database.

Requires WordPress 3.6, PHP 5, mySQL.

**Features**
* Add store by entering store name, address and other details.
* Add multiple stores at a time by uploading a CSV file.
* Display the plugin in a page by using a short code.
* Users can search for stores by street, city, postal code, province or country.
* Users can select the radius of the search.
* Displays store address, phone numbers and distance to store from seach area.
* Can use Google Maps Api key in the plugin.

**Coming soon**
* Plugin style customization.
* More store details.
* Preferred store location options.
* Current location detection.

== Installation ==

**Manual Install**
1. Upload the 'akw-store-locator' folder to the '/wp-content/plugins/' folder.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. (Optional) Add the Google API key to the 'storeLocatorConfig.php' page and set the 'USE_GOOGLE_KEY' to 'true'.
4. Add the short-code [akwstorelocator] to the page that you want to display the store locator.
5. Change the short-code to [akwstorelocator maplabel="Some label" mapbutton="Some Button"] for custom label and button names.
6. Plugin is ready for use!

**Auto Install**
1. Search for "AKW Store Locator" in the plugins page.
2. Click on install.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. (Optional) Add the Google API key to the 'storeLocatorConfig.php' page and set the 'USE_GOOGLE_KEY' to 'true'.
5. Add the short-code [akwstorelocator] to the page that you want to display the store locator.
6. Change the short-code to [akwstorelocator maplabel="Some label" mapbutton="Some Button"] for custom label and button names.
7. Plugin is ready for use!

== Frequently Asked Questions ==

= Why use the Google Maps API key? =

With Google Maps Api key, you can view the hits and statistics in the google developer console page.
Also an api key is required if the number of free requests limit is exceeded.

= What happens to the database table if the plugin is deactivated? =

The database table is not deleted when the plugin is deactivated. So the data stored previously remains if you want to reactivate the plugin.

= The table is not installed in the database after activation =

The 'Upgrade.php' file needs to be present in the 'wp-admin/includes' folder. This file is required to add the table to the database.

== Screenshots ==
1. Add Store screenshot
2. List of stores.
3. View of store locator.

== Upgrade Notice ==

= 1.4 =
This upgrade fixes the geocode over the limit issue with Google API.

== Changelog ==
= 1.4 =
* Fixed code to get geocode locations for multiple addresses.
= 1.3 =
* Fixed minor issue
= 1.2 =
* Fixed plugin to work with non-root wordpress installs. 3.8 support
= 1.1 =
* Changed address display issues
= 1.0 =
* First version released
