<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <title>GPS Tracker- Import Raw Files</title>
  <?php include_once ("lib_gps.php"); ?>
</head>

<body>
	<h3>GPS Tracker- Import Raw Files</h3>
	<p>This will scan and attempt to import all raw GPS files found in the RawGps subfolder.</p>
	
	<?php
	    // loop through all input and see if there are any files.
		$file_array = array();
		$index = 0;
		echo "<p>Scanning for files:<br>";
		
		if ($handle = opendir('raw_input')) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					// save to an index for later processing
					$file_array[$index] = $file;
					$index ++;
					// print out the file
					echo "  $file\n";
					
				}
			}
			closedir($handle);
		}
		echo "<br>Scanning complete!</p>";
		
		// for some reason it scans the files in a random order, sort the list
		// this ensures that trips are sequential
		sort($file_array);
		
		// so the timestamps for the GPS are at GMT
		// we need to convert them to our current timezone
		// WARNING: if the log is on the other side of a daylight savings time divider they will be skewed
		$offset = date("O") / 100 * 60 * 60;
		
		// open connection to the database
		$db = gps_db_connect();

		foreach ( $file_array as $fname ) {
			echo "<p> <strong>Processing: ".$fname."</strong></p>";
			$f = fopen("raw_input/$fname","r") or exit ("Unable to open file!");
			
			/****************** find new tripID ******************************/
			$query = "SELECT MAX(trip_id) FROM gps_raw";
			$result = gps_db_query ($query, $db);
			
			$sql_data = mysql_result($result,0);
			$tripid = $sql_data+1;
			echo ("Highest trip-id in database is $sql_data , this trip ID will be $tripid .<br>\n");
			
			/***************** read the file *********************************/
			$time_start = 0;
			$time_end   = 0;			
			$pt_first = 0;			
			$count = 0;
			$startup_delay = 0;
			
			// figure out what type fo data we're working with
			if ($data[0] == "$GPRMC" ) {
				$in_val = 2;
				$in_time = 1;
				$in_date = 9;
				$in_lat = 3;
				$in_lat_dir = 4;
				$in_lon = 5;
				$in_lon_dir = 6;
			} 
				
			
			$query = "INSERT INTO gps_raw (trip_id, gps_timestamp, gps_lat, gps_lon, gps_angle, gps_speed, point_type)\n";
			
			// set the timezone to GMT since that is what its read as
			while (!feof($f)){
				// grab the line and print it
				$line = fgets($f);
				
				// split up the line and check if valid
				$data = explode(",",$line);
				if($data[2]=="A") {
					//"[1] 134016.8, [9] 170611"
					//            mktime hour, min, second, month, day, year
					$t_h = substr($data[1],0,2);
					$t_s = substr($data[1],4,2);			
					$t_m = substr($data[1],2,2);
					$d_d = substr($data[9],0,2);
					$d_m = substr($data[9],2,2);
					$d_y = substr($data[9],4,2);
					
					// Time of driving in GMT. Use the current offset...
					$offset = date("O",mktime($t_h, $t_m, $t_s, $d_m, $d_d, $d_y)) / 100 * 60 * 60;
					$time = date("Y-m-d H:i:s",mktime($t_h, $t_m, $t_s, $d_m, $d_d, $d_y)+$offset);
					
					// convert the GPS to actual lat long
					$pos = strpos($data[3],".");
					$gps_lat = substr($data[3],0,$pos-2) + substr($data[3],$pos-2)/60;
					if(strcmp($data[4],"N")==0) {$gps_lat = "+".$gps_lat;} 
					else                     {$gps_lat = "-".$gps_lat;}
									
					$pos = strpos($data[5],".");
					$gps_lon = substr($data[5],0,$pos-2) + substr($data[5],$pos-2)/60;
					if(strcmp($data[6],"E")==0) {$gps_lon = "+".$gps_lon;} 
					else                     {$gps_lon = "-".$gps_lon;}

					
					if(!$pt_first) {		
						// this is the start time, save it
						$time_start = $time;
						echo "Started at ".$time_start."<br>\n";
						$pt_first = 1;
						
						// check for another trip in the database
						// TODO query database for existing track
						$q_chk = "SELECT trip_id, timestamp FROM gps_raw WHERE (point_type = '1' and gps_timestamp = '$time')";
						$result =  gps_db_query ($q_chk, $db);
			
						if (mysql_num_rows($result) > 0) {
							$prev_tripid = mysql_result($result,0,0);
							$prev_timestamp = mysql_result($result,0,1);
							echo "This trip was already entered into the database on $prev_timestamp as trip ID $prev_tripid (skipping).<br>\n";
							break;
						}
						
						
						// never added this trip before. continue
						$query .= "VALUES ('$tripid', '$time', '$gps_lat', '$gps_lon', '$data[8]' , '$data[7]', 1)\n" ;
					} else {
						$query .= ",('$tripid', '$time', '$gps_lat', '$gps_lon', '$data[8]' , '$data[7]', 0)\n" ;
					}
					
					// save the end point if this will be the end
					$time_end = $time;					
					$count++;
				} // IF valid GPS data	(if($data[2]=="A"))
				else if ($data[0] == "startup_delay") { 
					// Found startup delay! Use it..
					$startup_delay = $data[1] / 1000.0; // in file as miliseconds
					echo "Found startup delay of $startup_delay seconds<br>\n";
				}
			} // WHILE !eof
			
			fclose($f);
			
			// Clean it up..
			if($count > 0 ) {
				echo "Ended at ".$time_end."<br>\n";
			
				$query .= ";\n";
				$result =  gps_db_query ($query,$db);
				echo "Added $count gps points!<br>\n";
				
				echo "Analyzing tripid $tripid<br>\n";
				gps_analyze_trip($tripid,$startup_delay);
			}		
			
		} // FOREACH file
		
		// close the database
		mysql_close($db);
	?>

	</body>
</html>