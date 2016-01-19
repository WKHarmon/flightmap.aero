<?php
if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == ""){
    $redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: $redirect");
}
require_once('includes/functions.php');
$flights = new flights();
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<link href="images/default.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="images/colorbox.css" type="text/css" media="screen" />
<title><?=$flights->title?></title>
<script type="text/javascript" src="https://www.google.com/jsapi?key=<?=$google_key?>"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?sensor=false&key=<?=$google_key?>"></script>
<script type="text/javascript" src="images/markermanager/markermanager_packed.js"></script>
<script type="text/javascript" src="images/jquery.colorbox-min.js"></script>
<script type="text/javascript" src="images/md5.js"></script>
<script type="text/javascript">
<?
$output = "var myFlights = new Array(";
$airportlist = $flights->getUserAirports();
if (count($airportlist) > 0) {
	foreach ($airportlist as $airports) {
		$output .= '{';
		foreach ($airports as $key=>$value) {
			if ($key == 'state') {
				$states[$value]++;
			} else {
				$output .= "'$key':'" . addslashes($value) . "',";
			}
		}
		$output = rtrim($output, ",");
		$output .= "},";
	}
}
$output = rtrim($output, ",");
$output .= ");\n";
$output .= "var myLatlng = new google.maps.LatLng(".$flights->startLat.",".$flights->startLong.");\n";
echo $output;

?>
</script>
<script type="text/javascript" src="images/flightlog.js"></script>
</head>
<body onload="initialize()">
  <div id="map_canvas"></div>
  <div style="display:none">
  	<div id="states" class="overlay">
<?
if (count($states) > 0) {
	arsort($states);
	$count = 0;
	$stateTotals = $flights->getStateTotals();
	foreach ($states as $state => $value) {
		if ($count > 3) break;
		$percentage = floor(($value/$stateTotals[$state])*100) . '%';
		echo "			<li class=\"state\"><span class=\"statename\">$state:</span> $value/$stateTotals[$state] ($percentage)</li>\n";
		$count++;
	}
}
?>
  	</div>
  	<div id="key" class="overlay">
  		<li class="keyitem"><img src="images/mm_20_blue.png" /> Visited</li>
  		<li class="keyitem"><img src="images/mm_20_red.png" /> Unvisited</li>
  	</div>
<?
  	if ($flights->authenticated != 1) {
  	echo <<<EOT
	<div id="control" class="overlay">  	
		<a class="ajax" href="/ajax.php?action=registration_form">Create your own</a> | <a class="ajax" href="/ajax.php?action=login_form">Login</a>
	</div>
	
EOT;
  	} else {
  	echo "<div id=\"control\" class=\"overlay\">\n";
  		if (!$flights->flickr_user) {
  			print '<li><a href="/ajax.php?action=flickr_redirect">Link Flickr</a></li>';
  		}
  		if (!$flights->foursquare) {
  			print '<li><a href="/ajax.php?action=foursquare_redirect">Link Foursquare</a></li>';
  		}
  		print '<li><a class="inline" href="#settitle">Set Title</a></li>';
  		print '<li><a class="inline" href="#setaddress">Set Default Location</a></li>';
  		print <<<EOT
<div style="display:none"><form class="modal" id="setaddress"><label for="newaddress">Address: </label><input type="text" name="newaddress" id="newaddress" /></form><form class="modal" id="settitle"><label for="newtitle">Title: </label><input type="text" name="newtitle" id="newtitle" /></form></div>
<script type="text/javascript">
$('.iframe').colorbox({
	iframe: true,
	overlayClose: false,
	width: 965,
	height: 580
});

$('.inline').colorbox({
	inline: true,
	overlayClose: false
});

$('#settitle').live('submit', function() {
	var title = $('#newtitle').val();
	var url = "/ajax.php?action=update_title&title="+encodeURIComponent(title);
	$.ajax({
		url: url,
		dataType: 'text',
		success: function(data) {
			if (data.length > 0) {
				alert("Error: "+data);
			} else {
				location.reload();
			}
		},
		error: function(xml, error, status) {
			alert("Error: "+error+" ("+status+")");
		}
	});
	return false;
});

$('#setaddress').live('submit', function() {
	var geocoder = new google.maps.Geocoder();
	address = $("#newaddress").val();
	geocoder.geocode({
		'address': address,
		'region': 'us'
	}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			lat = results[0].geometry.location.lat();
			lng = results[0].geometry.location.lng();
			var url = "/ajax.php?action=update_location&lat="+lat+"&lng="+lng;
			$.ajax({
				url: url,
				dataType: 'text',
				success: function(data) {
					if (data.length > 0) {
						alert("Error: "+data);
					} else {
						$.colorbox.close();
						map.panTo(results[0].geometry.location);
					}
				},
				error: function(xml, error, status) {
					alert("Error: "+error+" ("+status+")");
				}
			});
		} else {
			alert("Unable to find starting location: "+status);
		}
	});
	return false;
});
</script>
EOT;
  	}
?>
  </div>
</body>
</html>
