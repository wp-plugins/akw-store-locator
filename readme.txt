=== AKW Store Locator ===
Contributors: Pradeep
Tags: Store locator, google maps, maps, store finder, store locations, business locator, geocoding, radius, stores
Author URI: http://www.aroundkwhosting.com
Version: 1.0
Requires at least: 3.6
Tested up to: 3.7.1
Stable tag: 1.0
License: GPLv2 or later

Simple, easy to install plugin to view stores around a location. Displays the results using Google map.

== Description ==

The AKW Store Locator plugin helps users view locations of business along with phone number, address and distance from the location in a map by just specifying a location and selecting a range or radius of search. Admin can add stores/locations to the database.

Requires WordPress 3.6, PHP 5, mySQL.

**Features**
* Add store by entering store name, address and other details.
* Add multiple stores at a time by uploading a CSV file.
* Display the plugin in a page by using a short code.
* Users can search for stores by searching for street, city, postal code, province or country.
* Users can select the radius of the search.
* Displays store address, phone numbers and distance to store from seach area.
* Can use Google Maps Api key in the plugin.

**Coming soon**
* Plugin style customization.
* More store details.
* Current location detection.

== Installation ==

1. Upload the 'akw-store-locator' folder to the '/wp-content/plugins/' folder.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. (Optional) Add the Google API key to the 'storeLocatorConfig.php' page and set the 'USE_GOOGLE_KEY' to 'true'.
4. Add the short-code [akwstorelocator] to the page that you want to display the store locator.
5. Change the short-code to [akwstorelocator maplabel="Some label" mapbutton="Some Button"] for custom label and button names.
6. Plugin is ready for use!

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

1.0 First version released

== Changelog ==

None