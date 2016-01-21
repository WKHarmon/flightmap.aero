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
	$result = array();
	$number = 0;
	while (($row = fgetcsv($f, 0, "\t")) !== FALSE) {
	    // Example insert - obviously use prepared statements/escaping/another DAL
	    $result[$number]['SiteNumber'] = $row[0];
	    $result[$number]['LocationID'] = $row[2];
	    $result[$number]['ARPLatitudeS'] = $row[23];
	    $result[$number]['ARPLongitudeS'] = $row[25];
	    $result[$number]['Type'] = $row[1];
	    $result[$number]['Use'] = $row[13];
	    $result[$number]['AirportStatusCode'] = $row[53];
	    $number++;
	}
	
	fclose($f);
	unlink('airports.csv');
	
	print_r($result);
?>