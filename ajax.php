<?php
require_once("includes/functions.php");
$flights = new flights();

if ($argv[1] == "foursquare") {
	$flights->getFoursquare();
}

if (isset($_REQUEST['frob'])) {
	if ($flights->authenticated) {
		$flights->setFlickr();
	}
	exit;
}

if (isset($_REQUEST['code'])) {
	if ($flights->authenticated) {
		$flights->setFoursquare();
	}
	exit;
}
//$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);
//if (mysqli_connect_errno()) {
//   printf("Can't connect to MySQL Server. Errorcode: %s\n", mysqli_connect_error());
//   exit;
//} 

$action = $_REQUEST['action'];
switch ($action) {
	case "airports":
		echo $flights->getGeoAirports();
		break;
	case "toggle":
		if ($flights->authenticated == 1) $flights->toggleAirport();
		break;
	case "login":
		$flights->authenticate();
		break;
	case "foursquare_redirect":
		global $foursquare_clientid;
		if ($flights->foursquare) {
			echo "Foursquare already linked.";
			exit;
		}
		if ($flights->authenticated) {
			$url = "https://foursquare.com/oauth2/authenticate?client_id=$foursquare_clientid&response_type=code&redirect_uri=https://flightmap.aero/ajax.php";
			header("Location: $url");
		} else {
			echo "User not authenticated.";
			exit;
		}
		break;
	case "flickr_redirect":
		if ($flights->flickr_user) {
			echo "Flickr already linked.";
			exit;
		}
		if ($flights->authenticated) {
			//$url = $flights->flickr->getAuthUrl('read');
			$api_sig = md5($flickr_secret.'api_key'.$flickr_key.'permsread');
			$url = "https://flickr.com/services/auth/?api_key=$flickr_key&perms=read&api_sig=$api_sig";
			header("Location: $url");
		} else {
			echo "User not authenticated.";
			exit;
		}
		break;
	case "get_foursquare":
		$flights->getFoursquare();
		break;
	case "get_flickr":
		$parameters = array(
			'method' => 'flickr.photos.search',
			'user_id' => $flights->flickr_user,
			'tags' => $_REQUEST['airport'],
			'privacy_filter' => 1,
			'safe_search' => 1
		);
		print $flights->getFlickr($parameters);
		break;
	case 'airport_ajax':
		require_once('includes/infowindow.php');
		break;
	case 'login_form':
		require_once('includes/login.php');
		break;
	case 'registration_form':
		require_once('includes/register.php');
		break;
	case "register":
		$flights->register();
		break;
	case 'update_location':
		$flights->updateLocation();
		break;
	case 'update_title':
		$flights->updateTitle();
		break;
}
?>
