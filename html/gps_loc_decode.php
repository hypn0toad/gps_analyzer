<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />

    <title>GPS Tracker- Location Decoder</title>
    <?php 
	include_once ("lib_gps.php"); 
	$tripid = preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET["tripid"]);
	?>

	<style type="text/css">
      html { height: 100% }
      body { height: 100%; margin: 0px; padding: 0px }

    </style>
	<script type="text/javascript"
    	src="http://maps.googleapis.com/maps/api/js?sensor=false">
	</script>
    <script type="text/javascript" src="scripts/flot/jquery.js" > </script>
    <script type="text/javascript" src="scripts/flot/jquery.flot.js" > </script>    

    
    <?php
		$db = gps_db_connect ( );
		// Get start and end points
		$pts_query = "SELECT start_time, start_lat, start_lon, end_time, end_lat, end_lon, start_poi, end_poi FROM trip_data WHERE trip_id='$tripid'";
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
		
		$start_poi = mysql_result($pts_result,0,6);
		$end_poi = mysql_result($pts_result,0,7);		
		
		$query = "SELECT gps_lat,gps_lon, gps_speed, gps_timestamp FROM gps_raw WHERE trip_id = '$tripid'";
		$result = gps_db_query($query,$db);
				
		$first = 0;
		$points = "";
		$flot_data = "";
		
		
		$offset = date("O",strtotime($start_time)) / 100 * 60 * 60;		
		while ( $row = mysql_fetch_assoc($result) ) {
			if($first == 0){$points .= "new google.maps.LatLng(".$row['gps_lat'].", ".$row['gps_lon'].")"; $first++;}
			else{$points .= ",\n new google.maps.LatLng(".$row['gps_lat'].", ".$row['gps_lon'].")";}
			
			$iFlotTime = strtotime($row['gps_timestamp'])*1000+($offset*1000); // this needs to be in MS since 1970, this is 1000x unix time
			$iFlotSpeed = round($row['gps_speed']*1.15077945); // convert knots to MPH
			$flot_data = $flot_data."[$iFlotTime,$iFlotSpeed],";
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
			
			var map = new google.maps.Map(document.getElementById("map_canvas"),myOptions);
				
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
						
			// START LOCATION MAP	
			var start_options = {
				center: latlng_start,
				zoom: 14,
				mapTypeId: google.maps.MapTypeId.HYBRID
			};
			var start_map = new google.maps.Map(document.getElementById("start_map_canvas"), start_options);
			var marker_start2 = new google.maps.Marker({
				position: latlng_start,
				map: start_map,
				icon: 'img/icon_start.png',
				title: "Trip started at <?php echo $start_time ;?>"
			});

			// END LOCATION MAP				
			var end_options = {
				center: latlng_end,
				zoom: 14,				
				mapTypeId: google.maps.MapTypeId.HYBRID
			};
			var end_map = new google.maps.Map(document.getElementById("end_map_canvas"), end_options);
			var marker_end2 = new google.maps.Marker({
				position: latlng_end,
				map: end_map,
				icon: 'img/icon_end.png',
				title: "Trip finished at <?php echo $end_time ;?>"
			});		}

</script>
</head>
<body onload="initialize()">
<?
		/*** calculate elapsed time ***/		
		$trip_time = strtotime($end_time) - strtotime($start_time);

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
?>
  <div id="map_canvas" style="width:100%; height:200px"></div>
  <h2>Trip was on <? echo($start_time); ?> (elapsed time <? echo($elapsed_str); ?>)</h2>
  
  <h3>Starting Information</h3>
  <? 
    if($start_poi != 0) { 	
		$poi_query = "SELECT (poi_desc) FROM gps_poi WHERE poi_id='$start_poi'";
		$poi_result = gps_db_query($poi_query,$db);	
		$poistr = mysql_result($poi_result,0,0);
	} else {$poistr = "unknown location";} 
	$lat = $start_lat; $lon = $start_lon;
	$imgsrc = "http://maps.googleapis.com/maps/api/staticmap?size=200x200&markers=color:green|".$lat.",".$lon."&sensor=true&maptype=hybrid";
	$streetsrc = "http://maps.googleapis.com/maps/api/streetview?size=400x200&location=".$lat.",".$lon."&sensor=true";
	
  ?>
  <table>
  	<tr>
  		<!-- <td><img src="<? echo($imgsrc); ?>"/></td> -->
  		<!--<td><img src="<? echo($streetsrc); ?>"/></td>-->
        <td><div id="start_map_canvas" style="width:400px; height:200px"></div></td>
        <td>
        	<form action="gps_loc_save.php" method="post">
        	<p>Started at <? echo($poistr); ?> </p>
            <? 
			if($start_poi==0){
            	echo('What is this location? <input type="text" name="poi_name">');
            	echo('<input type="hidden" name="lat" value="'.$lat.'">');
            	echo('<input type="hidden" name="lon" value="'.$lon.'">');
            }
			?>
            </form>
		</td>
    </tr>
  </table>
  <h3>Ending Information</h3>
  <? 
    if($end_poi != 0) { 	
		$poi_query = "SELECT (poi_desc) FROM gps_poi WHERE poi_id='$end_poi'";
		$poi_result = gps_db_query($poi_query,$db);	
		$poistr = mysql_result($poi_result,0,0);
	} else {$poistr = "unknown location";} 
	$lat = $end_lat; $lon = $end_lon; 
	$imgsrc = "http://maps.googleapis.com/maps/api/staticmap?size=200x200&markers=color:red|".$lat.",".$lon."&sensor=true&maptype=hybrid";	
	$streetsrc = "http://maps.googleapis.com/maps/api/streetview?size=400x200&location=".$lat.",".$lon."&sensor=true";	
  ?>
  <table>
  	<tr>
  		<!-- <td><img src="<? echo($imgsrc); ?>"/></td> -->
  		<!--<td><img src="<? echo($streetsrc); ?>"/></td>-->
        <td><div id="end_map_canvas" style="width:400px; height:200px"></div></td>
        <td>
        	<form action="gps_loc_save.php" method="post">       
        	<p>Ended at <? echo($poistr); ?> </p>
            <? 
			if($end_poi==0){
            	echo('What is this location? <input type="text" name="poi_name">');
            	echo('<input type="hidden" name="lat" value="'.$lat.'">');
            	echo('<input type="hidden" name="lon" value="'.$lon.'">');
            }
			?>
            </form>            
		</td>
    </tr>
  </table>  
  <h3>Speed</h3>
  <div id="flotdata" style="width:600px;height:300px"></div>
  
  <script id="source">
$(function () {
    var d = [<? echo($flot_data); ?>]; 

    $.plot($("#flotdata"), [{data: d, label: "speed (mph)"}], { 
		series: {
			lines: { show: true }
		},
		crosshair: {mode: "x" },
		grid: {hoverable: true, autoHighlight: false },
		xaxis: { mode: "time" } 
		
		});

});
</script>
  
  
</body>
</html>