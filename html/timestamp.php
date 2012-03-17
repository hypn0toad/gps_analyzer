<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <title>Timestamp Correction Test</title>
  <?php include_once ("lib_gps.php"); ?>
</head>

<body>
	<h3>Timestamp Correction Test</h3>

	<?php    

		/// TRY TWO, a late date (2012 01 05)
		
		$line = "$GPRMC,125630.000,A,3917.2510,N,07637.4146,W,0.04,92.45,030112,,*25";
		
		$data = explode(",",$line);
		$t_h = substr($data[1],0,2);
		$t_s = substr($data[1],4,2);			
		$t_m = substr($data[1],2,2);
		$d_d = substr($data[9],0,2);
		$d_m = substr($data[9],2,2);
		$d_y = substr($data[9],4,2);
				
		$offset = date("O",mktime($t_h, $t_m, $t_s, $d_m, $d_d, $d_y)) / 100 * 60 * 60;
		$time = date("Y-m-d H:i:s",mktime($t_h, $t_m, $t_s, $d_m, $d_d, $d_y)+$offset);
		
		echo("Late Offset $offset, time $time <br>\n");
		
		/// TRY ONE, an early date (2011 06 18)
		
		$line = "$GPRMC,111633.198,A,3917.5144,N,07636.8256,W,0.53,150.42,130611,,*13";
		
		$data = explode(",",$line);
		$t_h = substr($data[1],0,2);
		$t_s = substr($data[1],4,2);			
		$t_m = substr($data[1],2,2);
		$d_d = substr($data[9],0,2);
		$d_m = substr($data[9],2,2);
		$d_y = substr($data[9],4,2);
				
		$offset = date("O",mktime($t_h, $t_m, $t_s, $d_m, $d_d, $d_y)) / 100 * 60 * 60;
		$time = date("Y-m-d H:i:s",mktime($t_h, $t_m, $t_s, $d_m, $d_d, $d_y)+$offset);
		
		echo("Early Offset $offset, time $time <br>\n");
		
		/// TRY THREE (today)
			
		$offset = date("O") / 100 * 60 * 60;
		$time = date("Y-m-d H:i:s",mktime($t_h, $t_m, $t_s, $d_m, $d_d, $d_y)+$offset);

		echo("Current Offset $offset, time $time <br>\n");		
		


	?>

	</body>
</html>
    
	