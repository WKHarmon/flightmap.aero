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
	
	function getUserAirports() {
		if (isset($this->userAirports)) return $this->userAirports;
		$sql = "SELECT DISTINCT SiteNumber, LocationID, State, ARPLatitudeS, ARPLongitudeS, visited
					FROM airports_simplfiied JOIN userdata ON airports_simplified.SiteNumber = userdata.airportid
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
	
	function getGeoAirports() {
		if (isset($this->geoairports)) return $this->geoairports;
		$minLat = $_REQUEST['minLat'];
		$maxLat = $_REQUEST['maxLat'];
		$minLong = $_REQUEST['minLong'];
		$maxLong = $_REQUEST['maxLong'];
		
		$sql = "select SiteNumber, LocationID, ARPLatitudeS, ARPLongitudeS, 0
					FROM airports_simplified
					WHERE ARPLatitudeS between ? and ? AND ARPLongitudeS between ? and ? AND AirportType = 'AIRPORT' AND AirportUse = 'PU' AND AirportStatusCode = 'O'
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
					FROM airports_simplified
					WHERE AirportType = 'AIRPORT' AND AirportUse = 'PU' AND AirportStatusCode = 'O'
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
?>
