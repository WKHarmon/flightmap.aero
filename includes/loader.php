<?
	require_once 'functions.php';
	$flights = new flights();
	if (!$flights->authenticated) {
		throw new exception("User is not authenticated.");
		exit;
	}
	
	$f = fopen('airports.csv', 'w+');
	
	// create curl resource 
	$ch = curl_init(); 
	// set url 
	curl_setopt($ch, CURLOPT_URL, "http://www.faa.gov/airports/airport_safety/airportdata_5010/menu/nfdcfacilitiesexport.cfm?Region=&District=&State=&County=&City=&Use=PU&Certification="); 
	curl_setopt($ch, CURLOPT_FILE, $f);
	//return the transfer as a string 
	// $output contains the output string 
	curl_exec($ch); 
	// close curl resource to free up system resources 
	curl_close($ch);
	
	rewind($f);
	
	// Parse output to an array
	$header = fgetcsv($f, 0, "\t"); // throw away the headder row
	
	// truncate the existing table
	$sql = 'TRUNCATE airports_simplified';
	$stmt = $flights->mysqli->prepare($sql);
	$stmt->execute();
	
	$sql = 'INSERT INTO airports_simplified (SiteNumber, AirportType, LocationID, State, ARPLatitudeS, ARPLongitudeS, AirportUse, AirportStatusCode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $flights->mysqli->prepare($sql);

	while (($row = fgetcsv($f, 0, "\t")) !== FALSE) {
	    $lat = (substr($row[23], -1) = "N" ? substr($row[23], 0, -1), substr($row[23], 0, -1) * -1);
	    $lon = (substr($row[25], -1) = "E" ? substr($row[25], 0, -1), substr($row[25], 0, -1) * -1);
	    $stmt->bind_param('ssssddss', $row[0], $row[1], $row[2], $row[6], $lat, $lon, $row[13], $row[53]);
	    $stmt->execute();
	/*    $result[$number]['SiteNumber'] = $row[0];
	    $result[$number]['LocationID'] = $row[2];
	    $result[$number]['ARPLatitudeS'] = $row[23];
	    $result[$number]['ARPLongitudeS'] = $row[25];
	    $result[$number]['Type'] = $row[1];
	    $result[$number]['Use'] = $row[13];
	    $result[$number]['AirportStatusCode'] = $row[53];
	    $number++;*/
	}
	
	fclose($f);
	unlink('airports.csv');
	echo ("Success!");
	exit;s
?>