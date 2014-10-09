<?php
//akw-store-locator search store functionalities

header("Content-type: text/xml");
$parse_uri = explode('wp-content', __FILE__);
$wploadAKW = $parse_uri[0].'wp-load.php';
include_once($wploadAKW);
//include_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php' );
//require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php' );
//require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-includes/wp-db.php' );

global $wpdb;
$table_name = $wpdb->prefix."Stores";
$response = '';
function parseToXML($htmlStr) 
{ 
    $xmlStr=str_replace('<','&lt;',$htmlStr); 
    $xmlStr=str_replace('>','&gt;',$xmlStr); 
    $xmlStr=str_replace('"','&quot;',$xmlStr); 
    $xmlStr=str_replace("'",'&#39;',$xmlStr); 
    $xmlStr=str_replace("&",'&amp;',$xmlStr); 
    return $xmlStr; 
} 

// Get parameters from URL
$center_lat = $_GET["lat"];
$center_lng = $_GET["lng"];
$radius = $_GET["radius"];
$distType = $_GET["distType"];
$newRadius = 0;
$storeName = $_GET['storeName'];

if($distType == 'miles')
{
    $newRadius = intval($radius) * 1.6;
}
else
{
    $newRadius = intval($radius);
}

// Search the rows in the markers table
if($storeName == '' || $storeName == 'false')
{
    $query = sprintf("SELECT Street, City, Province, PostalCode, Name, Latitude, Longitude, Country, Phone, PreferredStore, CustomInfo,
                 ( 6371 * acos( cos( radians('%s') ) * cos( radians( Latitude ) ) * cos( radians( Longitude ) - radians('%s') ) + sin( radians('%s') ) * sin( radians( Latitude ) ) ) ) AS distance
                 FROM %s HAVING distance < '%s' ORDER BY PreferredStore DESC, distance ASC",
    mysql_real_escape_string($center_lat),
    mysql_real_escape_string($center_lng),
    mysql_real_escape_string($center_lat),
    $table_name,
    mysql_real_escape_string($newRadius));
}
else
{
    $query = sprintf("SELECT Street, City, Province, PostalCode, Name, Latitude, Longitude, Country, Phone, PreferredStore, CustomInfo,
                 ( 6371 * acos( cos( radians('%s') ) * cos( radians( Latitude ) ) * cos( radians( Longitude ) - radians('%s') ) + sin( radians('%s') ) * sin( radians( Latitude ) ) ) ) AS distance
                 FROM %s WHERE Name LIKE '%%%s%%' HAVING distance < '%s' ORDER BY PreferredStore DESC, distance ASC",
    mysql_real_escape_string($center_lat),
    mysql_real_escape_string($center_lng),
    mysql_real_escape_string($center_lat),
    $table_name,
    mysql_real_escape_string($storeName),
    mysql_real_escape_string($newRadius));
}

$result = $wpdb->get_results($query);

if (!$result) {
  die("Invalid query: " . mysql_error());
}

// Start XML file, echo parent node
echo  "<markers>\n";
// Iterate through the rows, printing XML nodes for each
foreach($result AS  $row)
{
    $distance = 0;
    if($distType == 'miles')
    {
        $distance = $row->distance * 0.6;
    }
    else
    {
        $distance = $row->distance;
    }
    
    $fullAddress = $row->Street.', '.$row->City.', '.$row->Province.', '.$row->PostalCode.', '.$row->Country;
    
  // ADD TO XML DOCUMENT NODE
  echo '<marker ';
  echo 'name="' . parseToXML($row->Name) . '" ';
  echo 'address="' . parseToXML($fullAddress) . '" ';
  echo 'lat="' . $row->Latitude. '" ';
  echo 'lng="' . $row->Longitude . '" ';
  echo 'phone="' . parseToXML($row->Phone) . '" ';
  echo 'distance="' .$distance. '" ';
  echo 'preferredStore="' .$row->PreferredStore. '" ';
  echo 'customInfo="' . parseToXML($row->CustomInfo) . '" ';
  echo "/>\n";
}

// End XML file
echo "</markers>\n";
?>
    