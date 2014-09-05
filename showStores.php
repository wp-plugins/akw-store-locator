<?php
/*
Plugin Name: AKW Store Locator
Plugin URI: http://www.aroundkwhosting.com
Description: This plugin helps view stores in an area by specifying the radius of search. The admin can add new stores by entering the location, phone number and more. Multiple stores can be uploaded using the csv upload option.
Version: 1.7
Author: Around Kitchener Waterloo
Author URI: http://www.aroundkwhosting.com
License: GPLv2 or later
*/
/*
Copyright 2013  Around Kitchener Waterloo  (email : freelisting@aroundkw.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
require_once('storeLocatorConfig.php');
include_once('storeFunctions.php');

//declare global variables
if (!defined('AKWSTORELOCATOR_VERSION_KEY'))
{
    define('AKWSTORELOCATOR_VERSION_KEY', 'akwstorelocator_version');
}

if (!defined('AKWSTORELOCATOR_VERSION_NUM'))
{
    define('AKWSTORELOCATOR_VERSION_NUM', '1.6.1');
}

add_option(AKWSTORELOCATOR_VERSION_KEY, AKWSTORELOCATOR_VERSION_NUM);

$new_version = '1.7';

if (get_option(AKWSTORELOCATOR_VERSION_KEY) != $new_version) {
    akwstorelocator_update_database_table();
    update_option(AKWSTORELOCATOR_VERSION_KEY, $new_version);
}

function akwstorelocator_update_database_table() {
    global $wpdb;
    $table = $wpdb->prefix."Stores";

    $sql = "CREATE TABLE " . $table . " (
        ID int(10) NOT NULL AUTO_INCREMENT,
        Name varchar(100) DEFAULT NULL,
        Phone varchar(15) DEFAULT NULL,
        Street varchar(100) DEFAULT NULL,
        City varchar(25) DEFAULT NULL,
        Province varchar(25) DEFAULT NULL,
        PostalCode varchar(10) DEFAULT NULL,
        Country varchar(50) DEFAULT NULL,
        Latitude float(10,6) DEFAULT NULL,
        Longitude float(10,6) DEFAULT NULL,
        PreferredStore tinyint(1) DEFAULT 0,
        CustomInfo varchar(255) DEFAULT NULL,
        UNIQUE KEY  (`ID`)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'installStoreTable');
register_deactivation_hook(__FILE__, 'akwUnregisterScripts');
add_action('admin_menu','storeLocator_menu');

//Function to install the tables
function installStoreTable()
{
    global $wpdb;
    $table_name = $wpdb->prefix."Stores";
    
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
    {
        $sql = "CREATE TABLE $table_name (
            ID int(10) NOT NULL AUTO_INCREMENT,
            Name varchar(100) DEFAULT NULL,
            Phone varchar(15) DEFAULT NULL,
            Street varchar(100) DEFAULT NULL,
            City varchar(25) DEFAULT NULL,
            Province varchar(25) DEFAULT NULL,
            PostalCode varchar(10) DEFAULT NULL,
            Country varchar(50) DEFAULT NULL,
            Latitude float(10,6) DEFAULT NULL,
            Longitude float(10,6) DEFAULT NULL,
            PreferredStore tinyint(1) DEFAULT 0,
            CustomInfo varchar(255) DEFAULT NULL,
            PRIMARY KEY  (`ID`)
        );";
        require_once(ABSPATH."wp-admin/includes/upgrade.php");
        dbDelta($sql);
    }
}


//Function to un-register scripts
function akwUnregisterScripts()
{
    wp_dequeue_script('add-akw-store-locator-script');
    wp_dequeue_script('add-akw-google-api');
    wp_dequeue_script('addakwadminjsfunctions');
    wp_dequeue_style('addPaginationStyle1');
    wp_dequeue_style('addPaginationStyle2');
    wp_dequeue_style('addAKWStoreLocatorCss');
}


//Function to display admin stores page
function storeLocator_stores_page ()
{
    global $wpdb;
    
    $table_name = $wpdb->prefix."Stores";
    
    if(isset($_GET['action']))
    {
        switch($_GET['action'])
        {
            //Delete when delete link is clicked
            case 'delete':
                $wpdb->get_results("DELETE FROM $table_name WHERE ID=".$_GET['ID']);
                break;
        } //switch
    }
    
    if(isset($_POST['deleteButton']))
    {
        //Delete when multiple stores are selected using checkboxes
        $checkbox = $_POST['storesCheckbox'];
        $countCheckbox = count($_POST['storesCheckbox']);
        for($i=0; $i<$countCheckbox; $i++)
        {
            $delID = $checkbox[$i];
            
            $wpdb->get_results("DELETE FROM $table_name WHERE ID=$delID"); //, $table_name, $delID);
        }
    }
    
    echo '<div class="wrap">';
    echo '<h2>Stores</h2>';
    echo '<button type="button" onclick="window.location=\''.admin_url('admin.php?page=add_store').'\';">Add a new Store</button>';
    echo '<form method="POST" action="'.admin_url('admin.php?page=storeLocator_admin').'">';
    echo '<p><input type="submit" name="deleteButton" id="deleteButton" value="Delete" />';
    echo '<br /><br /><button type="button" name="check" onclick="checkAllBoxes();">Check All</button>&nbsp;&nbsp;&nbsp;<button type="button" name="uncheck" onclick="unCheckAllBoxes();">Uncheck All</button></p>';
    echo '<table class="storesListTable"><tr><th></th><th>Name</th><th>Street</th><th>City</th><th>Province</th><th>Country</th><th>Postal/ZIP Code</th><th>Coordinates</th><th>Preferred Store</th><th>Action</th></tr>';
    
    //Pagination code
    $pageNo = (int) (!isset($_GET["pageNo"]) ? 1 : $_GET["pageNo"]);
    //Set max number of records to be displayed in a page
    $limit = 30;
    $startpoint = ($pageNo * $limit) - $limit;
    $statement = $table_name;
    
    $sql = "SELECT COUNT(*) FROM $table_name LIMIT $startpoint, $limit";
    $count = $wpdb->get_results($sql);
    
    if($count > 0)
    {
        $count = 0;
        echo pagination($statement, $limit, $pageNo, admin_url('admin.php?page=storeLocator_admin&'));
        $sql = "SELECT * FROM $table_name ORDER BY Country, Province, Name, ID ASC LIMIT $startpoint, $limit";
        $q = $wpdb->get_results($sql);
        foreach($q AS $f)
        {
            echo '<tr '.($count % 2 == 0 ? 'class="evenRow"' : '').'>';
            echo '<td><input type="checkbox" class="storesCheckboxes" name="storesCheckbox[]" id="storesCheckbox[]" value="'.$f->ID.'"/></td>';
            echo '<td>'.$f->Name.'</td>';
            echo '<td>'.$f->Street.'</td>';
            echo '<td>'.$f->City.'</td>';
            echo '<td>'.$f->Province.'</td>';
            echo '<td>'.$f->Country.'</td>';
            echo '<td>'.$f->PostalCode.'</td>';
            if($f->Latitude == 0.000000)
            {
                echo '<td>Empty</td>';    
            }
            else
            {
                echo '<td>Yes</td>';
            }
            if($f->PreferredStore == 0)
            {
                echo '<td>No</td>';    
            }
            else
            {
                echo '<td>Yes</td>';
            }
            echo '<td>';
            echo '<a href="'.admin_url('admin.php?page=add_store&ID='.$f->ID).'" >Edit</a> | ';
            echo '<a href="'.admin_url('admin.php?page=storeLocator_admin&action=delete&ID='.$f->ID).'" onclick="return confirm(\'Are you sure you want to delete this store?\');" > Delete</a>';
            echo '</td>';
            echo '</tr>';
            $count++;
        }
        
    }
    else
    {
        echo '<tr><td colspan="5">No Stores added</td></tr>';
    }
    
    echo '</table>';
    echo '</form>';
    echo '</div>';
    ?>
    <script type="text/javascript">
    function checkAllBoxes()
    {
        var cb = document.getElementsByClassName('storesCheckboxes');
        for(var z=0; z < cb.length; z++)
        {
            cb[z].checked = true;
        }
    }
    function unCheckAllBoxes()
    {
        var cb = document.getElementsByClassName('storesCheckboxes');
        for(var z=0; z < cb.length; z++)
        {
            cb[z].checked = false;
        }
    }
    </script>
    <?php
}

//Function to add stores in admin
function storeLocator_add_stores_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix."Stores";
        
    $errMsgs = array();
    if(isset($_POST['action']))
    {
        if(trim(stripslashes($_POST['name'] == '')))
        {
            $errMsgs[] = "Every store must have a name.";
        }
    
        if(count($errMsgs) == 0)
        {
            $name = trim($_POST['name']);
            $street = trim($_POST['street']);
            $city = trim($_POST['city']);
            $country = trim($_POST['country']);
            $province = trim($_POST['province']);
            $phone = trim($_POST['phone']);
            $latitude = trim($_POST['latitude']);
            $longitude = trim($_POST['longitude']);
            $postalCode = trim($_POST['postalCode']);
            if(isset($_POST['preferredStore']))
            {
                $preferredStore = trim($_POST['preferredStore']);
            }
            else
            {
                $preferredStore = 0;
            }
            $customInfo = trim($_POST['customInfo']);
            
            switch($_POST['action'])
            {
                //Update store
                case 'update':
                    $wpdb->update(
                      $table_name,
                      array(
                        'Name' => $name,
                        'Street' => $street,
                        'City' => $city,
                        'Province' => $province,
                        'Country' => $country,
                        'PostalCode' => $postalCode,
                        'Phone' => $phone,
                        'Latitude' => $latitude,
                        'Longitude' => $longitude,
                        'PreferredStore' => $preferredStore,
                        'CustomInfo' => $customInfo
                        ),
                      array(
                        'ID' => $_POST['ID']
                      ),
                      array(
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%f',
                        '%f',
                        '%d',
                        '%s'
                      ),
                      array(
                       '%d' 
                      )
                    );
                    $errMsgs[] = "Your updates have been saved.";
                    break;
                //Insert new store
                case 'insert':
                    $wpdb->insert(
                      $table_name,
                      array(
                        'Name' => $name,
                        'Street' => $street,
                        'City' => $city,
                        'Province' => $province,
                        'Country' => $country,
                        'PostalCode' => $postalCode,
                        'Phone' => $phone,
                        'Latitude' => $latitude,
                        'Longitude' => $longitude,
                        'PreferredStore' => $preferredStore,
                        'CustomInfo' => $customInfo
                      ),
                       array(
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%f',
                        '%f',
                        '%d',
                        '%s'
                      )
                    );
                    $_REQUEST['ID'] = $wpdb->insert_id;
                    $errMsgs[] = "New store has been added.";
                    break;
            } //switch
        } //if
      } //if

        echo '<div class="wrap">';
        echo '<h2>Add Store</h2>';
        echo '<button type="button" onclick="window.location=\''.admin_url('admin.php?page=storeLocator_admin').'\';">Go Back to Stores</button>';
        echo '<div>
        <h4>Steps to add a store:</h4>
        <ul>
        <li>The Name field is required.</li>
        <li>One of Street, City, Province/State, Country field is required.</li>
        <li>There are two ways to get the coordinates:
        <ol>
        <li>Enter the address Street, City, Province/State, Country, Posta/Zip Code fields and the click on the <strong>Check Address</strong> button</li>
        <li>Drag the marker to the store address</li>
        </ol>
        </li>
        <li>The Latitude and Longitude fields are read-only fields. They are filled out by the plugin</li>
        <li>Cleck the Preferred Store option for the store to have a higher priority when searched.</li>
        <li>Custom information/text can be entered for the store</li>
        </ul>
        </div>';

        if(isset($_REQUEST['ID']) && $_REQUEST['ID'] > 0)
        {
            $sql  = "SELECT ID, Name, Street, City, Country, Phone, Province, PostalCode, Latitude, Longitude, PreferredStore, CustomInfo FROM $table_name\n";
            $sql .= " WHERE ID = ".$_REQUEST['ID']." LIMIT 1";
       
            $f = $wpdb->get_results($sql);          
        }
        
        
        if(isset($_REQUEST['ID']) && $_REQUEST['ID'] > 0)
        {
        echo '<form method="POST" action="'.admin_url('admin.php?page=add_store&ID='.$_REQUEST['ID']).'">';
?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="ID" value="<?php echo $_REQUEST['ID']; ?>">
<?php
        }
        else
        {
            echo '<form method="POST" action="'.admin_url('admin.php?page=add_store').'">';
?>
        <input type="hidden" name="action" value="insert">
<?php
        }
?>
        <fieldset>
        <table>
<?php
        if(count($errMsgs) > 0)
        {
?>
            <tr>
            <td colspan="2">
<?php
            //Display error messages
            foreach($errMsgs as $errMsg)
            {
                echo '<p class="error">'.$errMsg.'</p>';
            }
?>
            </td>
            </tr>
<?php
        }
        //For update
        if(isset($_REQUEST['ID']) && $_REQUEST['ID'] > 0 && $f > 0)
        {
            foreach($f AS $r)
            {
?>
            <tr>
                <td>
                    <label>
                        Name*:
                    </label>
                </td>
                <td>
                    <input id="name" type="text" name="name" value="<?php echo $r->Name; ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        Street:
                    </label>
                </td>
                <td>
                    <input id="street" type="text" name="street" value="<?php echo $r->Street; ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        City:
                    </label>
                </td>
                <td>
                    <input id="city" type="text" name="city" value="<?php echo $r->City; ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        Province/State:
                    </label>
                </td>
                <td>
                    <input id="province" type="text" name="province" value="<?php echo $r->Province; ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        Country:
                    </label>
                </td>
                <td>
                    <input id="country" type="text" name="country" value="<?php echo $r->Country; ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        Postal/ZIP Code:
                    </label>
                </td>
                <td>
                    <input id="postalCode" type="text" name="postalCode" value="<?php echo $r->PostalCode; ?>">
                </td>
            </tr>
            <tr>
                <td style="vertical-align:top;">
                    <label style="display: none;">
                        Address:
                    </label>
                    <button type="button" id="checkAddress" name="checkAddress" onclick="moveToAddress(); return false;">Check Address</button>
                </td>
                <td>
                    <div id="akwAdminMap" style="width:300px; height:300px; float:right; border: 1px solid #444444;">
                    </div>
                    <br />
                  </td>
            </tr>
            <tr>
                <td>
                    <label>
                        Latitude:
                    </label>
                </td>
                <td>
                    <input id="latitude" type="text" name="latitude" value="<?php echo $r->Latitude; ?>" readonly="readonly">
                </td>
            </tr>
            <tr>
                  <td>
                    <label>
                      Longitude:
                    </label>
                  </td>
                  <td>
                    <input id="longitude" type="text" name="longitude" value="<?php echo $r->Longitude; ?>" readonly="readonly">
                  </td>
                </tr>
               <tr>
                  <td>
                    <label>
                      Phone Number:
                    </label>
                  </td>
                  <td>
                    <input id="phone" type="text" name="phone" value="<?php echo $r->Phone; ?>">
                  </td>
                </tr>
                <tr>
                    <td>
                        <label>
                            Is a Preferred Store?:
                        </label>
                    </td>
                    <td>
                        <input type="checkbox" name="preferredStore" id="preferredStore" value="1" <?php echo ($r->PreferredStore == 1 ? 'checked=checked' : ''); ?> />
                    </td>
                </tr>
               <tr>
                  <td>
                    <label>
                      Custom Information:
                    </label>
                  </td>
                  <td>
                    <input id="customInfo" type="text" name="customInfo" value="<?php echo $r->CustomInfo; ?>">
                  </td>
                </tr>
                <tr>
                  <td colspan="100%" align="center">
                    <input type="submit" value="Save Store" onclick="return checkRequiredFields();">
                  </td>
                </tr>
                <script type="text/javascript">
                moveToAddress();
                </script>
                <?php
            }
        }
        else
        {
            //For Insert
            ?>
            <tr>
                  <td>
                    <label>
                      Name:
                    </label>
                  </td>
                  <td>
                    <input id="name" type="text" name="name" value="">
                  </td>
                </tr>
                <tr>
                  <td>
                    <label>
                      Street:
                    </label>
                  </td>
                  <td>
                    <input id="street" type="text" name="street" value="">
                  </td>
                </tr>
                <tr>
                  <td>
                    <label>
                      City:
                    </label>
                  </td>
                  <td>
                    <input id="city" type="text" name="city" value="">
                  </td>
                </tr>
                <tr>
                  <td>
                    <label>
                      Province/State:
                    </label>
                  </td>
                  <td>
                    <input id="province" type="text" name="province" value="">
                  </td>
                </tr>
                <tr>
                  <td>
                    <label>
                      Country:
                    </label>
                  </td>
                  <td>
                    <input id="country" type="text" name="country" value="">
                  </td>
                </tr>
                <tr>
                    <td>
                        <label>
                            Postal/ZIP Code:
                        </label>
                    </td>
                    <td>
                        <input id="postalCode" type="text" name="postalCode" value="">
                    </td>
                </tr>
                <tr>
                  <td style="vertical-align:top;">
                    <label style="display: none;">
                      Address:
                    </label>
                    <button type="button" id="checkAddress" name="checkAddress" onclick="moveToAddress(); return false;">Check Address</button>
                  </td>
                  <td>
                    <div id="akwAdminMap" style="width:300px; height:300px; float:right; border: 1px solid #444444;">
                    </div>
                    <br />
                  </td>
                </tr>
                <tr>
                  <td>
                    <label>
                      Latitude:
                    </label>
                  </td>
                  <td>
                    <input id="latitude" type="text" name="latitude" value="" readonly="readonly">
                  </td>
                </tr>
               <tr>
                  <td>
                    <label>
                      Longitude:
                    </label>
                  </td>
                  <td>
                    <input id="longitude" type="text" name="longitude" value="" readonly="readonly">
                  </td>
                </tr>
               <tr>
                  <td>
                    <label>
                      Phone Number:
                    </label>
                  </td>
                  <td>
                    <input id="phone" type="text" name="phone" value="">
                  </td>
                </tr>
               <tr>
                    <td>
                        <label>
                            Is a Preferred Store?:
                        </label>
                    </td>
                    <td>
                        <input type="checkbox" name="preferredStore" id="preferredStore" value="1" />
                    </td>
                </tr>
               <tr>
                  <td>
                    <label>
                      Custom Information:
                    </label>
                  </td>
                  <td>
                    <input id="customInfo" type="text" name="customInfo" value="">
                  </td>
                </tr>
                <tr>
                  <td colspan="100%" align="center">
                    <input type="submit" value="Save Store" onclick="return checkRequiredFields();">
                  </td>
                </tr>
                <script type="text/javascript">
                    initBlankMap();
                </script>
            <?php
        }
        ?>
              </table>
            </fieldset>
          </form>
        </div>
          
<?php
}


//Function to upload csv file
function storeLocator_upload_csv_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix."Stores";
    
    $errMsgs = array();
    
    if(isset($_POST['uploadCSV']))
    {
        if($_FILES["file"]["error"] > 0)
        {
            $errMsgs[] = $_FILES["file"]["error"];
        }
        if(count($errMsgs) == 0)
        {
            if($_FILES["file"]["type"] == "text/csv")
            {
                $csvFile = plugin_dir_path(__FILE__).'sqlFile.csv';
                move_uploaded_file($_FILES["file"]["tmp_name"], $csvFile);
                $errMsgs[] = "File uploaded successfully.";
                include_once(plugin_dir_path(__FILE__).'simplecsvimport.php');
                
            }
            else
            {
                $errMsgs[] = "The selected file is not a .csv file";
            }
        }
    }
    ?>
    <div>
        <h4>To Create a CSV file:</h4>
        <ul>
            <li>The CSV file must be sorted and arranged by the the following fields:
            <br />
                    <strong>Name : Phone : Street : City : Province : PostalCode : Country : Latitude : Longitude : PreferredStore : Custom Info</strong>
            </li>
            <li>The Name field is mandatory field.</li>
            <li>Any other fields that are not mandatory can be ignored but the column should exist. For eg: Phone field is not required but the field needs to be left empty '::'</li>
            <li>There should be no headings  for the columns and no blank rows.</li>
            <li>The Latitude and Longitude fields should have 0 for the first row at least.</li>
            <li>The List separator need to be ':'. To change the list separator in MS Excel, the following steps need to be followed:
                <ol>
                    <li>In Microsoft Windows, click the Start button, and then click Control Panel.</li>
                    <li>Open the Regional and Language Options dialog box.</li>
                    <li>Do one of the following:
                        <ul>
                            <li>In Window 7, click Regional and Language Options, and then click Customize This Format button.</li>
                            <li>In Windows Vista, click the Formats tab, and then click Customize this format.</li>
                            <li>In Windows XP, click the Regional Options tab, and then click Customize.</li>
                        </ul>
                    </li>
                    <li>Type a new separator ':'  in the List separator box.</li>
                    <li>Click OK twice.</li>
                </ol>
            </li>
            <li>Once its done, click on Save As then choose .csv from the File Type drop-down.</li> 
            <li>For OpenOffice, Check the Edit filter Settings checkbox in the dialog box that appears when Save As is selected. In the Export of Text files dialog box, change the Field Delimiter to ':'.</li>
            <li><strong>For sample CSV file, <a href="<?php echo WP_PLUGIN_URL.'/akw-store-locator/sample.csv'; ?>" target="_blank">Click Here</a></strong> or download sample from <a href="http://aroundkwhosting.com/sample.csv" target="_blank">here</a></li>
        </ul>
        <br />
        <?php
        echo '<form method="post" action="'.admin_url('admin.php?page=upload_csv').'" enctype="multipart/form-data">';
        ?>
            <fieldset>
                <legend><h2>Upload CSV file</h2></legend>
                <?php
                    foreach($errMsgs as $errMsg)
                    {
                        echo '<p class="error">'.$errMsg."</p>\n";
                    }
                ?>
                <label>Upload File:</label>
                <input type="file" name="file" id="file" />
                <br />
                <input type="submit" name="uploadCSV" value="Upload" />
            </fieldset>
        </form>
    </div>
    <?php
}

//Function to get coordinates for stores without one
function storeLocator_get_coordinates_page()
{
 $imageURL = WP_PLUGIN_URL.'/akw-store-locator/images/working.gif';
?>
    <h2>Get Coordinates for Stores</h2>
    <p>Clicking on the <strong>Get Coordinates</strong> button gets the geo-location details for all the stores that do not have a latitude or longitude.</p>
    <button type="button" onclick="getAddresses();">Get Coordinates</button>
    <div id="working">
        <div>Saving coordinates...</div>
        <img src="<?php echo $imageURL; ?>" alt="Working ...">
        <div>Please wait...</div>
    </div>
<?php
}

//Function to add pagination css files to admin using add_action
function storelocator_add_css()
{
    wp_enqueue_style('addAKWStoreLocatorCss', plugins_url('/akw-store-locator/css/akw-store-locator-style.css'));   
    wp_enqueue_style('addPaginationStyle1', plugins_url('/akw-store-locator/css/pagination.css'));
    wp_enqueue_style('addPaginationStyle2', plugins_url('/akw-store-locator/css/pagination_green.css'));   

}

//Function to add admin section actions and hooks
function storeLocator_menu ()
{
    add_menu_page('AKW Store Locator Admin','AKW Store Locator Admin','manage_options','storeLocator_admin', 'storeLocator_stores_page');
    add_submenu_page('storeLocator_admin', 'Add store','Add store','manage_options','add_store', 'storeLocator_add_stores_page');
    add_submenu_page('storeLocator_admin', 'Upload csv file','Upload csv file','manage_options','upload_csv', 'storeLocator_upload_csv_page');
    add_submenu_page('storeLocator_admin', 'Get Coordinates','Get Coordinates','manage_options','get_coordinates', 'storeLocator_get_coordinates_page');
    //Check if Google  api key is set in config
    if(USE_GOOGLE_KEY == true)
    {
        wp_enqueue_script('googleapiurl', 'http://maps.googleapis.com/maps/api/js?key='.GOOGLE_API_KEY.'&sensor=true'); 
    }
    else
    {
        wp_enqueue_script('googleapiurl', 'http://maps.googleapis.com/maps/api/js?sensor=true');
    }
    wp_enqueue_script('addakwadminjsfunctions', plugins_url('/akw-store-locator/js/akwStoreLocatorAdminFunctions.js'));
    wp_enqueue_style('addAKWStoreLocatorCss', plugins_url('/akw-store-locator/css/akw-store-locator-style.css'));   
    wp_enqueue_style('addPaginationStyle1', plugins_url('/akw-store-locator/css/pagination.css'));
    wp_enqueue_style('addPaginationStyle2', plugins_url('/akw-store-locator/css/pagination_green.css'));
    
    //Array to declare object with attributes in js file
    $akwStoreLocatorAdminArray = array(
        'plugin_url' => plugins_url('/akw-store-locator')
    );
    wp_localize_script('addakwadminjsfunctions', 'akwstorelocatoradminobject', $akwStoreLocatorAdminArray);
    //Check current version
    
}

//Function to display store locator in the theme pages
function displayakwstorelocator($attributes)
{
    //Array to declare object with attributes in js file
    $akwStoreLocatorArray = array(
        'plugin_url' => plugins_url('/akw-store-locator')
    );
    
    wp_register_script('add-akw-store-locator-script', plugins_url('/akw-store-locator/js/storeLocatorFunctions.js'));
    wp_enqueue_script('add-akw-store-locator-script');
    wp_localize_script('add-akw-store-locator-script', 'akwstorelocatorobject', $akwStoreLocatorArray);
    wp_enqueue_style('addAKWStoreLocatorCss', plugins_url('/akw-store-locator/css/akw-store-locator-style.css'));   
    
    //short code attributes set or replace defaults
    $shortCodeAttr = shortcode_atts( array(
        'maplabel' => 'Postal/Zip, City/Province/State or Full Address',
        'mapbutton' => 'Search Stores',
        ), $attributes);
    
    $output = '<div style="text-align: center;">';
    $output .= '<label>'.$shortCodeAttr['maplabel'].': </label>';
    $output .= '<input id="addressInput" type="text" />';
    $output .= '<br />';
    $output .= '<br />';
    $output .= '<label>Radius: </label>';
    $output .= '<select id="radiusSelect">';
    $output .= '<option selected="selected" value="5">5</option>';
    $output .= '<option value="10">10</option>';
    $output .= '<option value="25">25</option>';
    $output .= '<option value="50">50</option>';
    $output .= '<option value="100">100</option>';
    $output .= '</select>';
    $output .= '&nbsp;';
    $output .= '<select id="distanceType">';
    $output .= '<option value="kms">Kms</option>';
    $output .= '<option value="miles">Miles</option>';
    $output .= '</select>';
    $output .= '<br />';
    $output .= '<br />';
    $output .= '<input onclick="searchLocations()" type="button" value="'.$shortCodeAttr['mapbutton'].'" />';
    $output .= '</div>';
    $output .= '<p>&nbsp;</p>';
    $output .= '<div style="height: 440px;">';
    $output .= '<div id="akwMap" style="overflow: hidden; width:50%; height:400px; float: left;"></div>';
    $output .= '<div id="akwLocationSidebar" style="overflow: auto; height: 400px; width: 45%; float: right;"></div>';
    $output .= '</div>';
    return $output;
}
//Add shortcode for the display function
add_shortcode('akwstorelocator', 'displayakwstorelocator');

//Function to add google api script to the theme header
function addakwstorelocatorscriptstotheme()
{
    if(!wp_script_is('googleapis', 'enqueued'))
    {
        if(USE_GOOGLE_KEY == true)
        {
            wp_enqueue_script('googleapiurl', 'http://maps.googleapis.com/maps/api/js?key='.GOOGLE_API_KEY.'&sensor=true'); 
        }
        else
        {
            wp_enqueue_script('googleapiurl', 'http://maps.googleapis.com/maps/api/js?sensor=true');
        }
     
        wp_enqueue_script('add-akw-google-api');
    }
}
//Using action to add the google api
add_action('wp_enqueue_scripts', 'addakwstorelocatorscriptstotheme');
?>