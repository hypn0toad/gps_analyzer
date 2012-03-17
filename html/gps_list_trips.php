<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>GPS Tracker- List Trips</title>
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
	
	$decoded = -1;
	$usr_override = $_GET["decoded"];
	$usr_clean = ereg_replace("/[^0-9]/", "", $usr_override );
	if(strlen($usr_clean)>0) { $decoded = $usr_clean; }
  
  
  ?>
  
  
  <!-- The 1140px Grid - http://cssgrid.net/ -->
  <link rel="stylesheet" href="css/1140.css" type="text/css" media="screen" />
	
  <!-- Your styles -->
  <link rel="stylesheet" href="css/styles.css" type="text/css" media="screen" />
</head>

<body>

<div class="container">
	<div class="row">
		<div class="twelvecol">
			<h1>GPS Tracker- List Trips</h1>
            <p>Address arguments --> id (starting trip_id); lim (limit per page); decoded (1 = at least one end of trip decoded; 0 = at least one end of trip not-decoded)</p>
			<p>&nbsp;</p>
            <p><?php
			$startstr = (string)($startid+$startlim);
			$limstr = (string)$startlim;
			echo("<a href=\"gps_list_trips.php?id=".$startstr."&lim=".$limstr."\">Next Page >>></a>"); ?>
</p>
		</div>
	</div>
</div>
<div class="container">
    <div class="row">
    
	<?php

	$db = gps_db_connect();

	/* Hookay. Time to find all trips and pull their information! */
	if($decoded == 1) {
	  $trip_query = "SELECT trip_id, start_time, start_lat, start_lon, end_time, end_lat, end_lon FROM trip_data WHERE trip_id >= '$startid' AND (start_poi > '0' OR end_poi > '0') ORDER BY start_time  LIMIT $startlim";
	} else if ($decoded == 0) {
	  $trip_query = "SELECT trip_id, start_time, start_lat, start_lon, end_time, end_lat, end_lon FROM trip_data WHERE trip_id >= '$startid' AND (start_poi = '0' OR end_poi = '0') ORDER BY start_time  LIMIT $startlim";
	} else {
	  $trip_query = "SELECT trip_id, start_time, start_lat, start_lon, end_time, end_lat, end_lon FROM trip_data WHERE trip_id >= '$startid' ORDER BY start_time  LIMIT $startlim";
	}
	$trip_result = gps_db_query($trip_query,$db);
	
	$count = 0;
	
	while ( $row = mysql_fetch_assoc($trip_result) ) {
		$tripid = $row['trip_id'];
		$end_time = strtotime($row['end_time']);
		$start_time = strtotime($row['start_time']);
		$trip_time = $end_time - $start_time;
		
		if($count%4 == 3){
			echo "<div class=\"threecol last\">\n<h3><a href=\"gps_display_trip.php?tripid=$tripid\">Trip #$tripid</a> (<a href=\"gps_loc_decode.php?tripid=$tripid\">d</a>)</h3>\n";
		} else {
			echo "<div class=\"threecol\">\n<h3><a href=\"gps_display_trip.php?tripid=$tripid\">Trip #$tripid</a> (<a href=\"gps_loc_decode.php?tripid=$tripid\">d</a>)</h3>\n";
		}
		echo "On ".$row['start_time']."<br>\n";
		
		/*** calculate elapsed time ***/		
		$trip_hr  = floor($trip_time/3600);
		$trip_min = floor($trip_time / 60) - $trip_hr * 60;
		$trip_sec =  $trip_time % 60;
		
		$elapsed_str = "";
		if($trip_hr > 0) 
			$elapsed_str .= $trip_hr."h ";
		if($trip_min > 0) 
			$elapsed_str .= $trip_min."m ";
		if($trip_sec > 0) 
			$elapsed_str .= $trip_sec."s ";		
		echo "Elapsed time of $elapsed_str.<br>\n";
		
		// Check if we already have a tripimage
		$imgname = "GenImg/tripmap_".$tripid.".png";
		if(!file_exists($imgname)){
			echo "Img did not exist, fetching.<br>\n";
			
			$url = "http://maps.googleapis.com/maps/api/staticmap?size=200x200&markers=color:blue|label:S|".$row['start_lat'].",".$row['start_lon']."&markers=color:red|label:E|".$row['end_lat'].",".$row['end_lon']."&sensor=false";
			
			$ch = curl_init($url);
			$fp = fopen($imgname,'wb');
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
			
		}
		if(!file_exists($imgname)){
			echo "Img fetch failed!<br>\n";
			break;
		}
		echo "<img src=\"$imgname\"><br></div>\n";
					
		// if that was the 12th column in a row, create a new row
		if($count%4 == 3){
			echo "</div><br><br>\n<div class=\"row\">\n";
		}		
		
		$count ++;
		//if ($count > 20) {break;};		
	} //while FETCH ROW

	// close the database
	mysql_close($db);
	?>
</div>
</body>
</html>