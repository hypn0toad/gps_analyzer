<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <title>GPS Tracker- Analyze Trips</title>
  <?php include_once ("lib_gps.php"); ?>
</head>

<body>
	<h3>GPS Tracker- Trip Connect</h3>
	<p>This will scan and attempt to analyze all trips found in the database. The GPS startup delay makes it annoying because the user can drive for up to 5 minutes without getting a starting location. But wait, this is a car! It does not teleport! The ending location of the previous trip should have had a nice GPS fix. Use it! Take the ending location of the last trip and use it for the starting location of this trip..</p>
    <p>This is based off of the adjusted_start column in the trip_data table.
    	<ul>
        	<li>0  = unanalyzed trip</li>
            <li>-1 = ignore (do NOT try to correct this trip..)</li>
            <li>## = (number > 0) = tripid of the trip I took the ending location from </li>
		</ul>
    </p>

	<?php    
	// open connection to the database
	$db = gps_db_connect();
	
	// TODO: grab list of all trips to be analyzed (adjusted_start >= 0) ; need = tripid, starting datestamp
	$trip_query = "SELECT trip_id, start_time, start_lat, start_lon FROM trip_data WHERE adjusted_start = '0' ORDER BY start_time";
	$trip_result = gps_db_query($trip_query,$db);
	
	while ( $row = mysql_fetch_assoc($trip_result) ) {
		$unadjusted_start = $row['start_time'];
		$cur_trip = $row['trip_id'];
		$cur_lat  = $row['start_lat'];
		$cur_lon  = $row['start_lon'];
		
		// TODO: find the previous trip;  SELECT tripid, starting lat, starting lon WHEN datestamp = (SELECT MAX(gps_timestamp) FROM gps_tripid WHERE timestamp < startingtime)
		$last_query = "SELECT trip_id, start_time, start_lat, start_lon, end_time, end_lat, end_lon FROM trip_data WHERE end_time = (SELECT MAX(end_time) FROM trip_data WHERE end_time < '$unadjusted_start')";
		$last_result = gps_db_query($last_query,$db);		
		$last = mysql_fetch_assoc($last_result);
		
		if($last) {
			$last_tripid = $last['trip_id'];
			$last_lat = $last['end_lat'];
			$last_lon = $last['end_lon'];
		
			echo("<p>Trip $cur_trip was preceeded by trip $last_tripid<br>\n");
			echo("Corrected start from $cur_lat,$cur_lon to $last_lat,$last_lon</p>\n");
			
			// TODO: update tripid row
			//   need to update starting lat, starting lon, adjusted_start
			$update_query = "UPDATE trip_data SET start_lat='$last_lat', start_lon='$last_lon', adjusted_start='$last_tripid' WHERE trip_id='$cur_trip'";
			$result =  gps_db_query ($update_query,$db);
			
			
		} else {
			echo("<p>No match found for $cur_trip</p>\n");
		}
		
		echo("\n<!-- LAST QUERY: ]] $last_query [[-->\n");

	} // END WHILE LOOP THROUGH TRIPIDs
	
	// close the database
	mysql_close($db);
	?>

	</body>
</html>
    
	