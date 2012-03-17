<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>GPS Tracker</title>
</head>

<body>
<h1>GPS Tracker</h1>
<p>Hello! And welcome to my GPS analyzer. What do you want to do?</p>
<p><img src="img/hardware.jpg" width="355" height="293" /></p>

<h3>Data Display</h3>
<ul>
  <li><a href="gps_date_select.php">Date Select </a>(new 3/13/2012)</li>
  <li><a href="gps_list_trips.php">List Trips</a> (updated 3/7/2012)</li>
  <li><a href="gps_disp_route.php?start=1&amp;end=2">Display Route (from home to work)</a></li>
  <li><a href="gps_disp_route.php?start=2&amp;end=1">Display Route (from work to home)</a></li>
</ul>
<h3>Data Import</h3>
<ul>
  <li><a href="gps_import.php">Import Raw GPS Files</a></li>
  <li><a href="gps_analze_trip.php">Analyze Tracks</a> (deprecated, now performed automatically on import)</li>
  <li><a href="gps_trip_connect.php">Retrofix starting positions</a> (new 1/22/2012), run after every import</li>
  <li><a href="gps_match_loc.php">Match POI in database to start/end locations</a> (new 1/25/2012), run after every retrofix</li>
  <li><a href="gps_loc_decode.php">Decode Start / End Points for a Trip</a> (new 3/7/2012), best run through list trips</li>
</ul>
<h3>Changelog</h3>
<p>3/12/2012</p>
<ul>
  <li>Added very basic date selection page. Need to refine this a lot. It will be my next main interface (to replace list trips).</li>
</ul>
<p>3/11/2012</p>
<ul>
  <li>Added the graphing of speed to the main display page (gps_loc_decode.php). This is becoming the main information page for everything. I'd like to have this graph have hover-data. Maybe in the future.</li>
</ul>
<p>3/7/2012</p>
<ul>
  <li>Enhancement: Added ability to filter trip list by decoded/non-decoded trips. Now can easily find unknown POI</li>
  <li>Added graphical ability to store POI. now its e-z. Just don't add double points, or make mistakes (there is no delete, and it could get ugly).</li>
</ul>
<p>1/25/2012</p>
<ul>
  <li>Added ability to match points of interest in the database to routes. (Points of interest still must be manually entered)</li>
  <li>Added ability to display summary of route. Basically just shows CSV data of when trips were made (e.g. home to work)</li>
</ul>
1/22/2012
<ul>
  <li>Linked import to analyze. Now will analyze as import tracks. Seperate analyze still works but should not be needed any more. Moved analyze contents into seperate function.</li>
  <li>Added ability to limit content with browser arguments. Works on List trip page ?id=#&limit=# will pick a starting point and quantity to be displayed.</li>
  <li>Startup delay, stored in files since 7/26/2011, now is handled at import. Will make trip start time based off of the delay. Note: this requires a re-import of all old data. I could make a seperate page to readd the data but meh ill just reimport it later.</li>
  <li>Added starting location correction. Will analyze the previous trip and grab the ending point, and will save it as the proper starting point for th enext track. With the change above we now have accurate starting GPS and times with absolutely no delay. Huzzah!</li>
</ul>

<h3>&nbsp;</h3>
<p>&nbsp;</p>
<p>More to come..</p>
</body>
</html>