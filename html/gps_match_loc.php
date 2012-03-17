<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>GPS Tracker- Match Locations</title>
  <?php 
	include_once ("lib_gps.php"); 
	$startid = 1;
	$usr_override = $_GET["id"];
	$usr_clean = ereg_replace("/[^0-9]/", "", $usr_override );
	if(strlen($usr_clean)>0) { $startid = $usr_clean; }
	
	$startlim = 20;
	$usr_override = $_GET["lim"];
	$usr_clean = ereg_replace("/[^0-9]/", "", $usr_override );
	if(strlen($usr_clean)>0) { $startlim = $usr_clean; }
  
  
  ?>
  
  
  <!-- The 1140px Grid - http://cssgrid.net/ -->
  <link rel="stylesheet" href="css/1140.css" type="text/css" media="screen" />
	
  <!-- Your styles -->
  <link rel="stylesheet" href="css/styles.css" type="text/css" media="screen" />
</head>

<body>
    
	<?php

	$db = gps_db_connect();

	/* Hookay. Time to find all locations and pull their information! */
	$poi_query = "SELECT poi_id, poi_desc, poi_lat, poi_lon FROM gps_poi ORDER BY poi_id";
	$poi_result = gps_db_query($poi_query,$db);
	
	$count = 0;
	
	while ( $row = mysql_fetch_assoc($poi_result) ) {
		$poi_id = $row['poi_id'];
		$poi_desc = $row['poi_desc'];
		$poi_lat = $row['poi_lat'];
		$poi_lon = $row['poi_lon'];
		
		echo("<h3>$poi_desc</h3>\n$poi_lat,$poi_lon<br>");
		
		// about 2000 feet in degrees latitude and longitude... trust me
		// http://www.meridianworlddata.com/Distance-Calculation.asp
		
		/*
			Because of the near-spherical shape of the Earth (technically an oblate spheroid) , calculating an accurate distance between two points requires the use of spherical geometry and trigonometric math functions. However, you can calculate an approximate distance using much simpler math functions. For many applications the approximate distance calculation provides sufficient accuracy with much less complexity. 
			
			The following approximate distance calculations are relatively simple, but can produce distance errors of 10 percent of more. These approximate calculations are performed using latitude and longitude values in degrees. The first approximation requires only simple math functions: 
			
			Approximate distance in miles:
			
			sqrt(x * x + y * y)
			
			where x = 69.1 * (lat2 - lat1) 
			and y = 53.0 * (lon2 - lon1) 
			
			You can improve the accuracy of this approximate distance calculation by adding the cosine math function: 
			
			Improved approximate distance in miles:
			
			sqrt(x * x + y * y)
			
			where x = 69.1 * (lat2 - lat1) 
			and y = 69.1 * (lon2 - lon1) * cos(lat1/57.3) 
			
			If you need greater accuracy, you can use the Great Circle Distance Formula. This formula requires use of spherical geometry and a high level of floating point mathematical accuracy - about 15 digits of accuracy (sometimes called "double-precision"). In order to use this formula properly make sure your software application or programming language is capable of double-precision floating point calculations. In addition, the trig math functions used in this formula require conversion of the latitude and longitude values from decimal degrees to radians. To convert latitude or longitude from decimal degrees to radians, divide the latitude and longitude values in this database by 180/pi, or approximately 57.29577951. The radius of the Earth is assumed to be 6,378.8 kilometers, or 3,963.0 miles.
			
			If you convert all latitude and longitude values in the database to radians before the calculation, use this equation: 
			
			Great Circle Distance Formula using radians:
			
			3963.0 * arccos[sin(lat1) *  sin(lat2) + cos(lat1) * cos(lat2) * cos(lon2 - lon1)]
			
			If you do NOT first convert the latitude and longitude values in the database to radians, you must include the degrees-to-radians conversion in the calculation. Substituting degrees for radians, the formula becomes: 
			
			Great Circle Distance Formula using decimal degrees:
			
			3963.0 * arccos[sin(lat1/57.2958) * sin(lat2/57.2958) + cos(lat1/57.2958) * cos(lat2/57.2958) *  cos(lon2/57.2958 -lon1/57.2958)]
			
			OR
			
			r * acos[sin(lat1) * sin(lat2) + cos(lat1) * cos(lat2) * cos(lon2 - lon1)]
			
			Where r is the radius of the earth in whatever units you desire. 
			r=3437.74677 (nautical miles) 
			r=6378.7 (kilometers) 
			r=3963.0 (statute miles) 
			
			If the software application or programming language you are using has no arccosine function, you can calculate the same result using the arctangent function, which most applications and languages do support. Use the following equation:
			
			3963.0 * arctan[sqrt(1-x^2)/x]
			
			where
			
			x = [sin(lat1/57.2958) * sin(lat2/57.2958)] + [cos(lat1/57.2958) * cos(lat2/57.2958) * cos(lon2/57.2958 - lon1/57.2958)]
		
		*/
		$del_lat = 0.0054817;
		$del_lon = 0.0071469;
		
		// find the box
		$lat_max = $poi_lat + $del_lat;
		$lat_min = $poi_lat - $del_lat;
		
		$lon_max = $poi_lon + $del_lon;
		$lon_min = $poi_lon - $del_lon;
		
		/////////////////////////////
		// find STARTING point in box		
		$trip_query = "SELECT trip_id, start_time, start_lat, start_lon FROM trip_data WHERE start_poi = '0' AND start_lat > '$lat_min' AND start_lat < '$lat_max' AND start_lon > '$lon_min' AND start_lon < '$lon_max' ORDER BY start_time";
		$trip_result = gps_db_query($trip_query,$db);
		echo ("<!-- $trip_query -->\n\n");
		
		while ( $t_row = mysql_fetch_assoc($trip_result) ) {
		
			$tripid = $t_row['trip_id'];
			$start_time = strtotime($t_row['start_time']);			
				
			echo("<p>Start of <a href=\"gps_display_trip.php?tripid=$tripid\">trip_id $tripid </a>at ".$t_row['start_time']."</p>\n");
			
			$update_query = "UPDATE trip_data SET start_poi='$poi_id' WHERE trip_id='$tripid'";
			$result =  gps_db_query ($update_query,$db); // don't even check because i'm a badass
		} // fetch row of trip results
		
		///////////////////////////
		// find ENDING point in box		
		$trip_query = "SELECT trip_id, end_time, end_lat, end_lon FROM trip_data WHERE end_poi = '0' AND end_lat > '$lat_min' AND end_lat < '$lat_max' AND end_lon > '$lon_min' AND end_lon < '$lon_max' ORDER BY end_time";
		$trip_result = gps_db_query($trip_query,$db);
		echo ("<!-- $trip_query -->\n\n");
		
		while ( $t_row = mysql_fetch_assoc($trip_result) ) {
		
			$tripid = $t_row['trip_id'];
			$end_time = strtotime($t_row['end_time']);			
				
			echo("<p>End of <a href=\"gps_display_trip.php?tripid=$tripid\">trip_id $tripid </a>at ".$t_row['end_time']."</p>\n");
			
			$update_query = "UPDATE trip_data SET end_poi='$poi_id' WHERE trip_id='$tripid'";
			$result =  gps_db_query ($update_query,$db); // don't even check because i'm a badass
		} // fetch row of trip results
								
	} // fetch row of POIs

	// close the database
	mysql_close($db);
	?>
</div>
</body>
</html>