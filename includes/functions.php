<?
require_once('config.php');

class mymysqli extends mysqli
{
  /* Make errors in query throw an exception */
  function query($query)
  {
    /* Call mysqli's query() method */
    $result = parent::query($query);
    if(mysqli_error($this)){
      throw new exception(mysqli_error($this), mysqli_errno($this));
    }
    return $result;
  }
  
  function prepare($query)
  {
    /* Call mysqli's query() method */
    $result = parent::prepare($query);
    if(mysqli_error($this)){
      throw new exception(mysqli_error($this), mysqli_errno($this));
    }
    return $result;
  }
} 

class flights {
	function __construct() {
		global $db_host, $db_username, $db_password, $db_name, $flickr_key, $flickr_secret;
		$this->mysqli = new mymysqli($db_host, $db_username, $db_password, $db_name);
		
		// Decide which user we're viewing.  ?displayuser= will override everything else.  Assume userid = 1 if nothing is specified.
		session_start();

                if (preg_match('/\/badge.jpg$/', $_REQUEST['user'])) {
                        $split = preg_split('/\//', $_REQUEST['user']);
                        $_REQUEST['user'] = $split[0];
                        require_once('badge.php');
                        exit;
                }

		if (preg_match('/index.php$/', $_SERVER['SCRIPT_FILENAME']) and $_REQUEST['user'] == '') {
			$_SESSION['user'] = "Kyle";
			$this->username = "Kyle";
		} elseif ($_REQUEST['user'] == '' and $_SESSION['user'] != '') {
			$this->username = $_SESSION['user'];
		} elseif ($_SESSION['user'] != '') {
			$_SESSION['user'] = $_REQUEST['user'];
			$this->username = $_REQUEST['user'];
		} else {
			$this->username = "Kyle";
		}
		
		// Get information about the displayed user.
		$sql = "SELECT id, username, password, startlat, startlong, title, foursquare, last_check, flickr_user, flickr_token, keyword FROM users WHERE LOWER(username) = ?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('s', strtolower($this->username));
		$stmt->execute();
		$stmt->bind_result($this->displayuser, $this->username, $this->password, $this->startLat, $this->startLong, $this->title, $this->foursquare, $this->last_check, $this->flickr_user, $this->flickr_token, $this->keyword);
		$stmt->fetch();
		if (!$this->displayuser) {
			showerror("User was not found.");
		}
		
		// Check to see if the user is authorized to make changes
		$this->authenticated = 0;
		if (isset($_COOKIE['auth'])) {
			$this->authuserid = $_COOKIE['auth']['userid'];
			$hash = $_COOKIE['auth']['hash'];
			if ($this->authuserid == $this->displayuser) {
				if ($hash == md5($this->password.$this->authuserid.$_SERVER['HTTP_USER_AGENT'])) $this->authenticated = 1;
			}
		}
	}
	
	function authenticate() {
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		if ($password == $this->password && $username == $this->username) {
			setcookie("auth[userid]", $this->displayuser);
			setcookie("auth[hash]", md5($this->password.$this->displayuser.$_SERVER['HTTP_USER_AGENT']));
			$this->authenticated = 1;
		} else {
			echo "Authentication failed.";
			exit;
		}
	}
	
	function register() {
		$username = stripslashes($_REQUEST['username']);
		$password = $_REQUEST['password'];
		$title = stripslashes($_REQUEST['title']);
		$startlat = $_REQUEST['lat'];
		$startlong = $_REQUEST['lng'];
		
		$sql = "SELECT count(*) from users where username = ?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$stmt->bind_result($count);
		$stmt->fetch();
		
		if ($count > 0) {
			print "Username is already taken; please choose another one.";
			exit;
		} else {
			$stmt->close();
			$sql = "INSERT INTO users (username, password, title, startlat, startlong)
						VALUES (?, ?, ?, ?, ?)";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param('sssdd', $username, $password, $title, $startlat, $startlong);
			$stmt->execute();
			$userid = $stmt->insert_id;
			setcookie("auth[userid]", $userid);
			setcookie("auth[hash]", md5($password.$userid.$_SERVER['HTTP_USER_AGENT']));
		}
	}
	
	function setFourSquare() {
		global $foursquare_clientid, $foursquare_clientsecret;
		$code = $_REQUEST['code'];
		$url = "https://foursquare.com/oauth2/access_token?client_id=$foursquare_clientid&client_secret=$foursquare_clientsecret&grant_type=authorization_code&redirect_uri=https://flightmap.aero/ajax.php&code=$code";
		$response = json_decode(curl_get($url));
		if ($response-> error) {
			print "Error: ".$response->error;
			exit;
		} elseif ($response->access_token) {
			$sql = "UPDATE users SET foursquare = ? WHERE id = ?";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param('si', $response->access_token, $this->displayuser);
			$stmt->execute();
			$this->foursquare = $response->access_token;
			print '<html><body>Foursquare link was successful!</body></html>';
			exit;
		} else {
			print "Something went wrong, but I have no idea what. :(";
			exit;
		}
		
	}
	
	function setFlickr() {
		global $flickr_key, $flickr_secret;
		$frob = $_REQUEST['frob'];
		$method = 'flickr.auth.getToken';
		$parameters = array(
			'api_key' => $flickr_key,
			'frob' => $frob,
			'method' => $method,
			'format' => 'json',
			'nojsoncallback' => 1
		);
		
		ksort($parameters);
		
		$sigstring = $flickr_secret;
		$url = "https://api.flickr.com/services/rest/?";
		
		foreach ($parameters as $key=>$value) {
			$sigstring .= $key;
			$sigstring .= $value;
			$url .= "&$key=$value";
		}
		
		$api_sig = md5($sigstring);

		$url .= "&api_sig=$api_sig";

		$response = json_decode(curl_get($url));
		
		if ($response->stat == 'fail') {
			print "Flickr auth failed! " . $response->code . ": ". $response->message;
		} else {
			$this->flickr_user = $response->auth->user->nsid;
			$this->flickr_token = $response->auth->token->_content;
			$sql = "UPDATE users SET flickr_token = ?, flickr_user = ? WHERE id = ?";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param('ssi', $this->flickr_token, $this->flickr_user, $this->displayuser);
			$stmt->execute();
			$redirect = "https://" . $_SERVER['SERVER_NAME'] . "/" . $this->username;
			header("Location: $redirect");
		}
	}
	
	function getUserAirports() {
		if (isset($this->userAirports)) return $this->userAirports;
		$sql = "SELECT DISTINCT SiteNumber, LocationID, State, ARPLatitudeS, ARPLongitudeS, visited
					FROM airports JOIN userdata ON airports.SiteNumber = userdata.airportid
					WHERE userdata.userid = ? and visited = 1";
		
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('i', $this->displayuser);
		$stmt->execute();
		$stmt->bind_result($id, $identifier, $state, $latitude, $longitude, $status);
		
		while ($stmt->fetch()) {
			$airports[] = array( 'id' => $id,
									'identifier' => $identifier,
									'state' => $state,
									'lat' => $latitude,
									'long' => $longitude,
									'status' => $status);
		}
		
		$this->userAirports = $airports;
		return $this->userAirports;
	}
	
	function getFlickr($parameters, $returnurl=false) {
		global $flickr_key, $flickr_secret;
		$parameters['api_key'] = $flickr_key;
		$parameters['format'] = 'json';
		$parameters['nojsoncallback'] = 1;
		$parameters['auth_token'] = $this->flickr_token;
		
		$url = "https://api.flickr.com/services/rest/?";
		$sigstring = $flickr_secret;
		
		ksort($parameters);
		
		foreach ($parameters as $key => $value) {
			$sigstring .= $key.$value;
			$url .= "&$key=$value";
		}
		
		$api_sig = md5($sigstring);
		
		$url .= "&api_sig=$api_sig";
		
		if ($returnurl) return $url;
		
		$result = curl_get($url);
		return $result;
	}
	
	function getGeoAirports() {
		if (isset($this->geoairports)) return $this->geoairports;
		$minLat = $_REQUEST['minLat'];
		$maxLat = $_REQUEST['maxLat'];
		$minLong = $_REQUEST['minLong'];
		$maxLong = $_REQUEST['maxLong'];
		
		$sql = "select SiteNumber, LocationID, ARPLatitudeS, ARPLongitudeS, 0
					FROM airports
					WHERE ARPLatitudeS between ? and ? AND ARPLongitudeS between ? and ? AND Type = 'AIRPORT' AND Access = 'PU' AND AirportStatusCode = 'O'
					AND SiteNumber NOT IN (SELECT airportid FROM userdata WHERE userid = ? AND visited = 1)";
		
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('ddddi', $minLat, $maxLat, $minLong, $maxLong, $this->displayuser);
		$stmt->execute();
		$stmt->bind_result($id, $identifier, $latitude, $longitude, $status);
		
		while ($stmt->fetch()) {
			$airports[] = array( 'id' => $id,
									'identifier' => $identifier,
									'lat' => $latitude,
									'long' => $longitude,
									'status' => $status);
		}
		
		$this->geoairports = json_encode($airports);
		return json_encode($airports);
	}
	
	function getStateTotals() {
		if (isset($this->stateTotals)) return $this->stateTotals;
		$sql = "SELECT State, count(*)
					FROM airports
					WHERE Type = 'AIRPORT' AND Access = 'PU' AND AirportStatusCode = 'O'
					GROUP BY State";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->execute();
		$stmt->bind_result($state, $count);
		
		while ($stmt->fetch()) {
			$states[$state] = $count;
		}
		
		$this->stateTotals = $states;
		return $this->stateTotals;
	}
	
	function toggleAirport() {
		$sitenumber = $_REQUEST['sitenumber'];
		$enabled = $_REQUEST['enabled'];
		$userid = $this->displayuser;
		
		if ($enabled == 0) {
			$sql = "UPDATE userdata SET visited = 0 WHERE userid = ? AND airportid = ?";
		} else {
			$sql = "REPLACE INTO userdata (checkin, userid, airportid, visited) values (0, ?, ?, 1)";
		}
		
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('is', $userid, $sitenumber);
		$stmt->execute();
	}
	
	function updateLocation() {
		if (!$this->authenticated) {
			print "User not authenticated.";
			exit;
		}
		$lat = $_REQUEST['lat'];
		$lng = $_REQUEST['lng'];
		$sql = "UPDATE users SET startlat = ?, startlong = ?
					WHERE id = ?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('ddi', $lat, $lng, $this->displayuser);
		$stmt->execute();
	}
	
	function updateTitle() {
		if (!$this->authenticated) {
			print "User not authenticated.";
			exit;
		}
		$title = stripslashes($_REQUEST['title']);
		$sql = "UPDATE users SET title = ?
					WHERE id = ?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('si', $title, $this->displayuser);
		$stmt->execute();
	}
	
	function getFoursquare() {
		global $db_host, $db_username, $db_password, $db_name;
		$sql = "SELECT id, foursquare, last_check
					FROM users
					WHERE foursquare != ''";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->execute();
		$stmt->bind_result($userid, $foursquare, $last_checked);
		
		while ($stmt->fetch()) {
			$url = "https://api.foursquare.com/v2/users/self/checkins?oauth_token=$foursquare&afterTimestamp=$last_checked&v=20131204&limit=250";
			$result = json_decode(curl_get($url));
			if ($result->meta->errorType) {
				mail('kyle@kyleharmon.com', 'Flightmap.aero Foursquare Error', $result->meta->errorDetail);
				$updatesql = new mymysqli($db_host, $db_username, $db_password, $db_name);
				$sql = "UPDATE users SET foursquare = null WHERE id = ?";
				$update = $updatesql->prepare($sql);
				$update->bind_param('i', $userid);
				$update->execute();
				$update->close();
			} else {
				$last_timestamp = $result->response->checkins->items[0]->createdAt;
				foreach ($result->response->checkins->items as $item) {
					$shout = $item->shout;
					if (strpos(strtolower($shout), strtolower($this->keyword)) !== false) {
						if ($item->location->lat) {
							$lat = $item->location->lat;
							$lng = $item->location->lng;
						} elseif ($item->venue->location->lat) {
							$lat = $item->venue->location->lat;
							$lng = $item->venue->location->lng;
						} else {
							continue;
						}
						
						if ($item->photos->count > 0) {
							$item->photo = $item->photos->items[0]->url;
							$item->thumbnail = $item->photos->items[0]->sizes->items[3]->url;
						}
						
						$item->minLat = $lat - .02;
						$item->maxLat = $lat + .02;
						$item->minLng = $lng - .02;
						$item->maxLng = $lng + .02;
						
						$updatesql = new mymysqli($db_host, $db_username, $db_password, $db_name);
						$sql = "REPLACE INTO userdata (checkin, userid, airportid, time, visited, notes, photo, thumbnail)
									SELECT ?, ?, SiteNumber, ?, 1, ?, ?, ? FROM airports
									WHERE ARPLatitudeS BETWEEN ? and ?
									AND ARPLongitudeS BETWEEN ? and ?
									AND Type = 'AIRPORT'
									AND Access = 'PU'
									AND AirportStatusCode = 'O'
									LIMIT 1";
						$update = $updatesql->prepare($sql);
						$update->bind_param('siisssdddd', $item->id, $userid, $item->createdAt, $item->shout, $item->photo, $item->thumbnail, $item->minLat, $item->maxLat, $item->minLng, $item->maxLng);
						$update->execute();
						$update->close();
					}
				}
				
				if ($last_timestamp > 0) {
					$updatesql = new mymysqli($db_host, $db_username, $db_password, $db_name);
					$sql = "UPDATE users SET last_check = ? WHERE id = ?";
					$update = $updatesql->prepare($sql);
					$update->bind_param('ii', $last_timestamp, $userid);
					$update->execute();
					$update->close();
				}
			}
		}
	}
}

function showerror($string) {
	print $string;
	exit;
}

function curl_get($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch);
	curl_close($ch);
	return $response;
}

function constructFlickrURL($object, $size='z') {
	$farm = $object->farm;
	$server = $object->server;
	$id = $object->id;
	$secret = $object->secret;
	return "https://farm".$farm.".static.flickr.com/".$server."/".$id."_".$secret."_".$size.".jpg";
}
?>
