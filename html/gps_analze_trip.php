<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <title>GPS Tracker- Analyze Trips</title>
  <?php include_once ("lib_gps.php"); ?>
</head>

<body>
	<h3>GPS Tracker- Analyze Trips</h3>
	<p>This will scan and attempt to analyze all trips found in the database.</p>
    <p>Fancy hooks... ?id= gives starting point ?lim= gives amount to pull</p>

	<?php    
    $startid = 1;
	$usr_override = $_GET["id"];
	$usr_clean = ereg_replace("/[^0-9]/", "", $usr_override );
	if(strlen($usr_clean)>0) { $startid = $usr_clean; }
    
    $startlim = 20;
	$usr_override = $_GET["lim"];
	$usr_clean = ereg_replace("/[^0-9]/", "", $usr_override );
	if(strlen($usr_clean)>0) { $startlim = $usr_clean; }
	

	// open connection to the database
	$db = gps_db_connect();
	
	// look for all trips in the database
	$query = "SELECT trip_id, gps_timestamp, gps_lat, gps_lon FROM gps_raw WHERE point_type = '1' AND trip_id >= '$startid' LIMIT $startlim";
	$result = gps_db_query ($query, $db);
		
	while ( $row = mysql_fetch_assoc($result) ) {
		$tripid = $row['trip_id'];
		gps_analyze_trip($tripid,0);
	} // end of trips
	
	// close the database
	mysql_close($db);
	?>

	</body>
</html>
    
	