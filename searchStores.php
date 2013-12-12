<?php
header("Content-type: text/xml");
include_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php' );
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php' );
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-includes/wp-db.php' );

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

// Opens a connection to a mySQL server
/*$connection=mysql_connect (localhost, $username, $password);
if (!$connection) {
  die("Not connected : " . mysql_error());
}

// Set the active mySQL database
($db_selected = mysql_select_db($database, $connection);
if (!$db_selected) {
  die ("Can\'t use db : " . mysql_error());
}*/

// Search the rows in the markers table
$query = sprintf("SELECT FullAddress, Name, Latitude, Longitude, Country, Phone, ( 6371 * acos( cos( radians('%s') ) * cos( radians( Latitude ) ) * cos( radians( Longitude ) - radians('%s') ) + sin( radians('%s') ) * sin( radians( Latitude ) ) ) ) AS distance FROM %s HAVING distance < '%s' ORDER BY distance",
    mysql_real_escape_string($center_lat),
    mysql_real_escape_string($center_lng),
    mysql_real_escape_string($center_lat),
    $table_name,
    mysql_real_escape_string($radius));


$result = $wpdb->get_results($query);
//$result = mysql_query($query, $db);

//$result = mysql_query($query);
if (!$result) {
  die("Invalid query: " . mysql_error());
}

// Start XML file, echo parent node
echo  "<markers>\n";
// Iterate through the rows, printing XML nodes for each
foreach($result AS  $row)
{
  // ADD TO XML DOCUMENT NODE
  echo '<marker ';
  echo 'name="' . parseToXML($row->Name) . '" ';
  echo 'address="' . parseToXML($row->FullAddress) . '" ';
  echo 'lat="' . $row->Latitude. '" ';
  echo 'lng="' . $row->Longitude . '" ';
  echo 'phone="' . parseToXML($row->Phone) . '" ';
  echo 'distance="' . $row->distance. '" ';
  echo "/>\n";
}

// End XML file
echo "</markers>\n";

//echo $response;
?>
    