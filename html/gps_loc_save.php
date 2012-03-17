<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <title>GPS Tracker- Save New Location</title>
    <?php 
	include_once ("lib_gps.php"); 
	?>
</head>

<body>
<?
	$loc_desc = check_input($_POST['poi_name']);
	$loc_lat = check_input($_POST['lat']);
	$loc_lon = check_input($_POST['lon']);

	echo("<p><strong>Desc </strong>".$loc_desc."</p>\n");
	echo("<p><strong>Lat </strong>".$loc_lat."</p>\n");
	echo("<p><strong>Lon </strong>".$loc_lon."</p>\n");
	
	/* Hookay. Time to find all locations and pull their information! */
	$poi_query = "SELECT MAX(poi_id) FROM gps_poi";
	$poi_result = gps_db_query($poi_query,$db);
	$last_id = mysql_result($poi_result,0,0);
	$poi_id = $last_id + 1;
	
	echo('<p>Last POI in database was '.strval($last_id).' this will be <strong> #'.strval($poi_id).'</strong></p>');
	
	/* Build the insertion string for the database! */
	$insert_poi = "INSERT INTO gps_poi (poi_id, poi_desc, poi_lat, poi_lon) \n 
			 VALUES ('$poi_id', '$loc_desc', '$loc_lat' , '$loc_lon')";	
	gps_db_query ($insert_poi,$db);
	echo("<p>Added into database! <strong>Remember to <a href=\"gps_match_loc.php\">re-analyze your trips</a>! Don't save multiple points</strong></p><br>\n");	
?>
</body>
</html>