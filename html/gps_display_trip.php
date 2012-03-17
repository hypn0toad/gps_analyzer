<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <title>GPS Tracker- Display Trip</title>
    <?php 
	include_once ("lib_gps.php"); 
	$tripid = preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET["tripid"]);
	?>

	<style type="text/css">
      html { height: 100% }
      body { height: 100%; margin: 0px; padding: 0px }
      #map_canvas { height: 100% }
    </style>
	<script type="text/javascript"
    	src="http://maps.googleapis.com/maps/api/js?sensor=false">
	</script>
    <?php
		$db = gps_db_connect ( );
		// Get start and end points
		$pts_query = "SELECT start_time, start_lat, start_lon, end_time, end_lat, end_lon FROM trip_data WHERE trip_id='$tripid'";
		$pts_result = gps_db_query($pts_query,$db);
		
		if (mysql_num_rows($pts_result) <= 0) {
			echo "Could not find trip with that TripID in the database..\n";
			return -1;
		}
		
		$start_time = mysql_result($pts_result,0,0);
		$start_lat = mysql_result($pts_result,0,1);
		$start_lon = mysql_result($pts_result,0,2);
		$end_time = mysql_result($pts_result,0,3);
		$end_lat = mysql_result($pts_result,0,4);
		$end_lon = mysql_result($pts_result,0,5);	
		
		
		
		
		$query = "SELECT gps_lat,gps_lon FROM gps_raw WHERE trip_id = '$tripid'";
		$result = gps_db_query($query,$db);
				
		$first = 0;
		$points = "";
		while ( $row = mysql_fetch_assoc($result) ) {
			if($first == 0){$points .= "new google.maps.LatLng(".$row['gps_lat'].", ".$row['gps_lon'].")"; $first++;}
			else{$points .= ",\n new google.maps.LatLng(".$row['gps_lat'].", ".$row['gps_lon'].")";}
		} 
	?>
    
	<script type="text/javascript">
  		function initialize() {
			var latlng_start = new google.maps.LatLng(<?php echo "$start_lat,$start_lon"; ?>);
			var latlng_end = new google.maps.LatLng(<?php echo "$end_lat,$end_lon"; ?>);
			
			var latlngbounds = new google.maps.LatLngBounds();
			latlngbounds.extend(latlng_start);
			latlngbounds.extend(latlng_end);
			
			var myOptions = {
    			center: latlngbounds.getCenter(),
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			
			var map = new google.maps.Map(document.getElementById("map_canvas"),
				myOptions);
				
			map.fitBounds(latlngbounds);
				
			var marker_start = new google.maps.Marker({
				position: latlng_start,
				map: map,
				icon: 'img/icon_start.png',
				title: "Trip started at <?php echo $start_time ;?>"
			});
			
			var marker_end = new google.maps.Marker({
				position: latlng_end,
				map: map,
				icon: 'img/icon_end.png',
				title: "Trip finished at <?php echo $end_time ;?>"
			});	
			
			var trip_coordinates = [				
				<?php echo $points ; ?>
			];
			
			var trip_path = new google.maps.Polyline({
				path: trip_coordinates,
				strokeColor: "#FF0000",
				strokeOpacity: 1.0,
				strokeWeight: 2
			});
			
			trip_path.setMap(map);
		}

</script>
</head>
<body onload="initialize()">
  <div id="map_canvas" style="width:100%; height:100%"></div>
</body>
</html>