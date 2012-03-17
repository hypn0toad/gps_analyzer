<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>GPS Tracker- Date Select</title>
  
  <link href="scripts/calendar/calendar.css" rel="stylesheet" type="text/css" />
  <script language="javascript" src="scripts/calendar/calendar.js"></script>  
  
  <?php 
	include_once("lib_gps.php"); 
	require_once('scripts/calendar/classes/tc_calendar.php');

	$start_date_query = "2011-06-01";
	$usr_override = $_GET["start"];
	$usr_clean = ereg_replace("/[^0-9]/", "", $usr_override );
	if(strlen($usr_clean)>0) { $start_date_query = $usr_clean; }
	
	$today = date('Y-m-d');
	$end_date_query = $today;
	$usr_override = $_GET["end"];
	$usr_clean = ereg_replace("/[^0-9]/", "", $usr_override );
	if(strlen($usr_clean)>0) { $end_date_query = $usr_clean; }
  
  
  echo("<h1>$start_date_query to $end_date_query</h1>\n");
  	$startid = 1;$startlim = 20;$decoded = -1;
  ?>
  
  
</head>

<body>
<?php					
      $date3_default = $start_date_query;
      $date4_default = $end_date_query;

	  $myCalendar = new tc_calendar("date3", true, false);
	  $myCalendar->setIcon("scripts/calendar/images/iconCalendar.gif");
	  $myCalendar->setDate(date('d', strtotime($date3_default))
            , date('m', strtotime($date3_default))
            , date('Y', strtotime($date3_default)));
	  $myCalendar->setPath("scripts/calendar/");
	  $myCalendar->setYearInterval(1970, 2020);
	  $myCalendar->setAlignment('left', 'bottom');
	  $myCalendar->setDatePair('date3', 'date4', $date4_default);
	  $myCalendar->setOnChange("date_change()");
	  $myCalendar->writeScript();	  
	  
	  $myCalendar = new tc_calendar("date4", true, false);
	  $myCalendar->setIcon("scripts/calendar/images/iconCalendar.gif");
	  $myCalendar->setDate(date('d', strtotime($date4_default))
           , date('m', strtotime($date4_default))
           , date('Y', strtotime($date4_default)));
	  $myCalendar->setPath("scripts/calendar/");
	  $myCalendar->setYearInterval(1970, 2020);
	  $myCalendar->setAlignment('left', 'bottom');
	  $myCalendar->setDatePair('date3', 'date4', $date3_default);
	  $myCalendar->setOnChange("date_change()");
	  $myCalendar->writeScript();	
	  
	  ?>
      
<script language="javascript">
<!--
function date_change(){
	//alert("Hello, value has been changed : "+document.getElementById("date3").value");
	var url_string = "http://natemcbean.com/gps/gps_date_select.php?start="+document.getElementById("date3").value+"&end="+document.getElementById("date4").value;
	window.open(url_string,"_self");
}
//-->
</script>      
    
	<?php
	echo("\n\n<br><br>\n\n");
	$db = gps_db_connect();
	
	if($decoded == 1) {
		$poi_str = "AND (start_poi > '0' OR end_poi > '0')";
	} else if ($decoded == 0) {
		$poi_str = "AND (start_poi = '0' OR end_poi = '0')";
	} else {
		$poi_str = "";	
	}

	/* Hookay. Time to find all trips and pull their information! */
	$trip_query = "SELECT trip_id, start_time, start_lat, start_lon, end_time, end_lat, end_lon FROM trip_data WHERE trip_id >= '$startid' $poi_str AND start_time > '$start_date_query' AND start_time < '$end_date_query' ORDER BY start_time  LIMIT $startlim";
	
	$trip_result = gps_db_query($trip_query,$db);
		
	while ( $row = mysql_fetch_assoc($trip_result) ) {
		$tripid = $row['trip_id'];
		$end_time = strtotime($row['end_time']);
		$start_time = strtotime($row['start_time']);
		$trip_time = $end_time - $start_time;
		
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
		echo "Elapsed time of $elapsed_str.<br><br>\n";		
	} //while FETCH ROW

	// close the database
	mysql_close($db);
	?>

</body>
</html>