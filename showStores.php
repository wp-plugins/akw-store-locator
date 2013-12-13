<?php
/*
Plugin Name: AKW Store Locator
Plugin URI: http://www.aroundkwhosting.com
Description: This plugin helps view stores in an area by specifying the radius of search. The admin can add new stores by entering the location, phone number and more. Multiple stores can be uploaded using the csv upload option.
Version: 1.0
Author: Around Kitchener Waterloo
Author URI: http://www.aroundkwhosting.com
License: GPLv2 or later
*/
/*
Copyright 2013  Around Kitchener Waterloo  (email : pradeep@aroundkw.com)

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
            FullAddress varchar(255) DEFAULT NULL,
            PRIMARY KEY (`ID`)
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
    echo '<table><tr><th></th><th>Name</th><th>Street</th><th>City</th><th>Province</th><th>Country</th><th>Coordinates</th><th>Action</th></tr>';
    
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
        echo pagination($statement, $limit, $pageNo, admin_url('admin.php?page=storeLocator_admin&'));
        $sql = "SELECT * FROM $table_name ORDER BY Country, Province, Name, ID ASC LIMIT $startpoint, $limit";
        $q = $wpdb->get_results($sql);
        foreach($q AS $f)
        {
            echo '<tr>';
            echo '<td><input type="checkbox" class="storesCheckboxes" name="storesCheckbox[]" id="storesCheckbox[]" value="'.$f->ID.'"/></td>';
            echo '<td>'.$f->Name.'</td>';
            echo '<td>'.$f->Street.'</td>';
            echo '<td>'.$f->City.'</td>';
            echo '<td>'.$f->Province.'</td>';
            echo '<td>'.$f->Country.'</td>';
            if($f->Latitude == 0.000000)
            {
                echo '<td>Empty</td>';    
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
            $fullAddress = $street.', '.$city.', '.$province.', '.$country;
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
                        'FullAddress' => $fullAddress,
                        'Phone' => $phone,
                        'Latitude' => $latitude,
                        'Longitude' => $longitude
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
                        '%f'
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
                        'FullAddress' => $fullAddress,
                        'Phone' => $phone,
                        'Latitude' => $latitude,
                        'Longitude' => $longitude
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
                        '%f'
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
        

        if(isset($_REQUEST['ID']) && $_REQUEST['ID'] > 0)
        {
            $sql  = "SELECT Name, FullAddress, Street, City, Country, Phone, Province, Latitude, Longitude FROM $table_name\n";
            $sql .= " WHERE ID = ".$_REQUEST['ID']." LIMIT 1";
       
            $f = $wpdb->get_results($sql);          
        }
        
        echo '<form method="POST" action="'.admin_url('admin.php?page=add_store&ID='.$_REQUEST['ID']).'">';
        if(isset($_REQUEST['ID']) && $_REQUEST['ID'] > 0)
        {
?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="ID" value="<?php echo $_REQUEST['ID']; ?>">
<?php
        }
        else
        {
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
                echo '<p class="error">'.$errMsg."</p>\n";
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
                        Name:
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
                <td style="vertical-align:top;">
                    <label style="display: none;">
                        Address:
                    </label>
                    <button type="button" id="checkAddress" name="checkAddress" onclick="moveToAddress(); return false;">Check Address</button>
                </td>
                <td>
                    <textarea id="address" type="text" name="address" style="display: none"><?php echo $r->FullAddress; ?></textarea>
                    <div id="akwAdminMap" style="width:200px; height:200px; float:right; border: 1px solid #444444;">
                    </div>
                    <br />
                    <!--<a onclick="moveToAddress(); return false;">Check Address</a>-->
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
                  <td colspan="100%" align="center">
                    <input type="submit" value="Save Store">
                  </td>
                </tr>
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
                  <td style="vertical-align:top;">
                    <label style="display: none;">
                      Address:
                    </label>
                    <button type="button" id="checkAddress" name="checkAddress" onclick="moveToAddress(); return false;">Check Address</button>
                  </td>
                  <td>
                      <textarea id="address" type="text" name="address" style="display: none"></textarea>
                    <div id="akwAdminMap" style="width:200px; height:200px; float:right; border: 1px solid #444444;">
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
                  <td colspan="100%" align="center">
                    <input type="submit" value="Save Store">
                  </td>
                </tr>
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
            <li>The excel sheet must be sorted and arranged by the the following fields:
            <br />
                    Name : Phone : Street : City : Province : PostalCode : Country : Latitude : Longitude
            </li>
            <li>The Name field is mandatory field.</li>
            <li>Any other fields that are not mandatory can be ignored but the column should exist.</li>
            <li>There should be no headings  for the columns and no no blank rows.</li>
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
    
?>
    <h2>Get Coordinates for Stores</h2>
    <p>Clicking on the <strong>Get Coordinates</strong> button gets the geo-location details for all the stores that do not have a latitude or longitude.</p>
    <button type="button" onclick="getAddresses();">Get Coordinates</button>
<?php
}

//Function to add pagination css files to admin using add_action
function storelocator_add_css()
{
    wp_enqueue_style('addPaginationStyle1', plugins_url('/akw-store-locator/css/pagination.css'));
    wp_enqueue_style('addPaginationStyle2', plugins_url('/akw-store-locator/css/pagination_green.css'));   

}

//Function to add admin section actions and hooks
function storeLocator_menu ()
{
    add_menu_page('Store Locator Admin','Store Locator Admin','manage_options','storeLocator_admin', 'storeLocator_stores_page');
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
    wp_enqueue_style('addPaginationStyle1', plugins_url('/akw-store-locator/css/pagination.css'));
    wp_enqueue_style('addPaginationStyle2', plugins_url('/akw-store-locator/css/pagination_green.css'));
    
    //Array to declare object with attributes in js file
    $akwStoreLocatorAdminArray = array(
        'plugin_url' => plugins_url('/akw-store-locator')
    );
    wp_localize_script('addakwadminjsfunctions', 'akwstorelocatoradminobject', $akwStoreLocatorAdminArray);
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
    
    //short code attributes set or replace defaults
    $shortCodeAttr = shortcode_atts( array(
        'maplabel' => 'Postal/Zip, City/Province/State or Full Address',
        'mapbutton' => 'Search Stores',
        ), $attributes);
    
    $output = '<div style="text-align: center;">';
    $output .= '<label>'.$shortCodeAttr['maplabel'].':</label>';
    $output .= '<input id="addressInput" type="text" />';
    $output .= '<br />';
    $output .= '<label>Radius:</label>';
    $output .= '<select id="radiusSelect">';
    $output .= '<option selected="selected" value="5">5 Kms</option>';
    $output .= '<option value="10">10 Kms</option>';
    $output .= '<option value="25">25 Kms</option>';
    $output .= '<option value="50">50 Kms</option>';
    $output .= '<option value="100">100 Kms</option>';
    $output .= '</select>';
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