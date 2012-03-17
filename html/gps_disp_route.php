<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>GPS Tracker- Display Route</title>
  <?php 
	include_once ("lib_gps.php"); 
	$startid = 1;
	$usr_override = $_GET["start"];
	$usr_clean = ereg_replace("/[^0-9]/", "", $usr_override );
	if(strlen($usr_clean)>0) { $startid = $usr_clean; }
	
	$endid = 2;
	$usr_override = $_GET["end"];
	$usr_clean = ereg_replace("/[^0-9]/", "", $usr_override );
	if(strlen($usr_clean)>0) { $endid = $usr_clean; }
  
  
  ?>
</head>

<body>


			<h1>GPS Tracker- Display Route</h1>
			<?php
			$startstr = (string)($startid+$startlim);
			$limstr = (string)$startlim;
			?>

    
	<?php

	$db = gps_db_connect();
	
	$poi_query = "SELECT poi_id, poi_desc, poi_lat, poi_lon FROM gps_poi WHERE poi_id = '$startid'";
	$poi_result = gps_db_query($poi_query,$db);
	$row = mysql_fetch_assoc($poi_result);
	$start_desc = $row['poi_desc'];
	$start_lat = $row['poi_lat'];
	$start_lon = $row['poi_lon'];	
	
	$poi_query = "SELECT poi_id, poi_desc, poi_lat, poi_lon FROM gps_poi WHERE poi_id = '$endid'";
	$poi_result = gps_db_query($poi_query,$db);
	$row = mysql_fetch_assoc($poi_result);
	$end_desc = $row['poi_desc'];
	$end_lat = $row['poi_lat'];
	$end_lon = $row['poi_lon'];		

	$url = "http://maps.googleapis.com/maps/api/staticmap?size=600x200&markers=color:blue|label:S|".$start_lat.",".$start_lon."&markers=color:red|label:E|".$end_lat.",".$end_lon."&sensor=false";
	echo("<img src=\"$url\"><br>\n");
	echo("<strong>From $start_desc</strong>\n");
	echo(" ($start_lat,$start_lon)<br>\n");
	echo("<strong>to $end_desc</strong>\n");
	echo("($end_lat,$end_lon)<br>\n");
	echo("<h3>CSV Data</h3>\n");
	
	
		


	/* Hookay. Time to find all trips and pull their information! */
	$trip_query = "SELECT trip_id, start_time, end_time FROM trip_data WHERE start_poi = '$startid' AND end_poi = '$endid' ORDER BY start_time";
	$trip_result = gps_db_query($trip_query,$db);
	
	$count = 0;
	
	echo("tripid,start_date,start_time,end_date,end_time,elapsed_time<br>\n");
	
	while ( $row = mysql_fetch_assoc($trip_result) ) {
		$tripid = $row['trip_id'];
		$end_time = strtotime($row['end_time']);
		$start_time = strtotime($row['start_time']);
		$trip_time = $end_time - $start_time;
		
		//$start_str = $row['start_time'];
		//$end_str   = $row['end_time'];
		
		/*** calculate elapsed time ***/		
		//$trip_hr  = floor($trip_time/3600);
		//$trip_min = floor($trip_time / 60) - $trip_hr * 60;
		//$trip_sec =  $trip_time % 60;
		//
		//$elapsed_str = "";
		//if($trip_hr > 0) 
		//	$elapsed_str .= $trip_hr."h ";
		//if($trip_min > 0) 
		//	$elapsed_str .= $trip_min."m ";
		//if($trip_sec > 0) 
		//	$elapsed_str .= $trip_sec."s ";		
		//echo "Elapsed time of $elapsed_str.<br>\n";
		
		echo("<a href=\"gps_display_trip.php?tripid=$tripid\">$tripid</a>,".date("Y-m-d",$start_time).",".date("H:i:s",$start_time).",".date("Y-m-d",$end_time).",".date("H:i:s",$end_time).",".$trip_time."<br>\n");
		
	} //while FETCH ROW

	// close the database
	mysql_close($db);
	?>
</body>
</html>