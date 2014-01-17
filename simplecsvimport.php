<?php

/********************************/
/* Code at http://legend.ws/blog/tips-tricks/csv-php-mysql-import/
/* Edit the entries below to reflect the appropriate values
/********************************/
$fieldseparator = ":";
$lineseparator = "\n";
$csvfile = plugins_url('/akw-store-locator/sqlFile.csv');
global $wpdb;
$table_name = $wpdb->prefix."Stores";
/********************************/
/* Would you like to add an ampty field at the beginning of these records?
/* This is useful if you have a table with the first field being an auto_increment integer
/* and the csv file does not have such as empty field before the records.
/* Set 1 for yes and 0 for no. ATTENTION: don't set to 1 if you are not sure.
/* This can dump data in the wrong fields if this extra field does not exist in the table
/********************************/
$addauto = 1;
/********************************/

$cp = curl_init();
curl_setopt($cp, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($cp, CURLOPT_URL, $csvfile);
//curl_setopt($cp, CURLLOPT_TIMEOUT, 60);

if(curl_exec($cp) === false)
{
	echo 'cURL error: '.curl_error($cp).' - error no.: '.curl_errno($cp)."\n";	
}
else
{
	$csvcontent = curl_exec($cp);
}

curl_close($cp);

$lines = 0;
$queries = "";
$linearray = array();

foreach(split($lineseparator,$csvcontent) as $line) {

	$lines++;
	
	$line = trim($line," \t");
	
	$line = str_replace("\r","",$line);
	
	/************************************
	This line escapes the special character. remove it if entries are already escaped in the csv file
	************************************/
	$line = str_replace("'","\'",$line);
	/*************************************/
	
	$linearray = explode($fieldseparator,$line);
	
	$linemysql = implode("','",$linearray);
	
	if($addauto)
	{
		$query = "insert into $table_name values('','$linemysql');";
	}
	else
	{
		$query = "insert into $table_name values('$linemysql');";
	}
	
	$wpdb->query($query);
}

echo "<p>Found a total of $lines records in this csv file.\n</p>";
?>
