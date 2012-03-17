<?PHP

function gps_db_connect ( ) {
	include 'config.php';
	$db = mysql_connect($db_host,$db_user,$db_pswd);
	if(!$db) {
		die('Could not connect: ' . mysql_error());
		return -1;
	}
	mysql_select_db($db_name, $db);
	return $db;
}

function gps_db_query ($query, $db) {
	if($db == NULL) { $db = gps_db_connect(); }
	
	$result = mysql_query($query);
			
	if(!$result){
		$message = 'Invalid query: ' . mysql_error() . "\n";
		$message .= 'Whole query: ' . $query;
		die ( $message) ;
		return -1;
	}
	
	return $result;	
}

// This will query the db for a specific trip analysis, and will add in a startup delay if needed
function gps_analyze_trip($tripid, $startup_delay) {
	// open connection to the database
	$db = gps_db_connect();
	
	echo "<p><strong>Analyzing at trip ID $tripid...</strong></p>\n";
	
	/*** check to see if each trip has been analyzed before. if so, skip/update ***/
	$tripid_query = "SELECT timestamp FROM trip_data WHERE trip_id = '$tripid'";
	$tripid_result = gps_db_query($tripid_query, $db);
	
	if (mysql_num_rows($tripid_result) > 0) {
		$prev_timestamp = mysql_result($tripid_result,0,0);
		echo "This trip was already analyzed at $prev_timestamp (skipping).<br>\n";
		return;
	}
	
	// find the start point
	$end_query = "SELECT gps_timestamp, gps_lat, gps_lon FROM gps_raw WHERE trip_id = '$tripid' AND gps_timestamp = (SELECT MIN(gps_timestamp) FROM gps_raw WHERE trip_id = '$tripid')";
	$end_result = gps_db_query($end_query, $db);
	$start_time = mysql_result($end_result,0,0);
	$start_lat  = mysql_result($end_result,0,1);
	$start_lon  = mysql_result($end_result,0,2);	
	
	// correct the start time if needed..
	if($startup_delay!=0) {
		$start_orig = $start_time;
		$start_realtime = strtotime($start_time); 	// gets a unix timestamp (in seconds)
		$sec_offset = round($startup_delay);		// offset in seconds
		$start_new = $start_realtime - $sec_offset;	// subtract
		$start_time = date("Y-m-d H:i:s",$start_new);
		
		echo("Startup was at $start_orig but was delayed $sec_offset and was corrected to be $start_time .<br>\n");
	}
	
	// find the end point
	$end_query = "SELECT gps_timestamp, gps_lat, gps_lon FROM gps_raw WHERE trip_id = '$tripid' AND gps_timestamp = (SELECT MAX(gps_timestamp) FROM gps_raw WHERE trip_id = '$tripid')";
	$end_result = gps_db_query($end_query, $db);
	$end_time = mysql_result($end_result,0,0);
	$end_lat  = mysql_result($end_result,0,1);
	$end_lon  = mysql_result($end_result,0,2);
	
	if($startup_delay == 0) {
		$insert_query = "INSERT INTO trip_data (trip_id, start_time, start_lat, start_lon, end_time, end_lat, end_lon) \n 
			 VALUES ('$tripid', '$start_time', '$start_lat' , '$start_lon', '$end_time', '$end_lat', '$end_lon')";
	} else {
		$insert_query = "INSERT INTO trip_data (trip_id, start_time, start_lat, start_lon, end_time, end_lat, end_lon, startup_delay) \n 
			 VALUES ('$tripid', '$start_time', '$start_lat' , '$start_lon', '$end_time', '$end_lat', '$end_lon', '$startup_delay')";

	}
	
	gps_db_query ($insert_query,$db);
	echo "Added summary information for trip into database!<br>\n";	
}

function check_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
	$data = str_replace('"','',$data);
	$data = str_replace("'","",$data);
    return $data;
}

?>