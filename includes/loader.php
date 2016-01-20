<?
	require_once 'functions.php';
	$flights = new flights();
	if (!$flights->authenticated) {
		throw new exception("User is not authenticated.");
		exit;
	}
	
	// Create virtual file pointer
	$f = fopen('php://temp', 'w+');
	
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
	if (($headers = fgetcsv($f, 0, "\t")) !== FALSE)
	  if ($headers)
	    while (($line = fgetcsv($f, 0, "\t")) !== FALSE) 
	      if ($line)
	        if (sizeof($line)==sizeof($headers))
	          $result[] = array_combine($headers,$line);
	print_r($result);
?>