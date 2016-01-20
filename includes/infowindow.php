<?
$sitenumber = $_REQUEST['airportid'];
$sql = "SELECT LocationID, City, State, FacilityName
			FROM airports
			WHERE SiteNumber = ?";
$stmt = $flights->mysqli->prepare($sql);
$stmt->bind_param('s', $_REQUEST['airportid']);
$stmt->execute();
$stmt->bind_result($identifier, $city, $state, $name);
$stmt->fetch();
$stmt->close();

$sql = "SELECT time, photo, thumbnail, notes, visited
			FROM userdata
			WHERE userid = ? AND airportid = ? AND visited = 1
			ORDER BY time DESC
			LIMIT 1";
$stmt = $flights->mysqli->prepare($sql);
$stmt->bind_param('is', $flights->displayuser, $_REQUEST['airportid']);
$stmt->execute();
$stmt->bind_result($timestamp, $photo, $checkin_thumbnail, $notes, $visited);
$stmt->fetch();
$stmt->close();
?>
<div id="content">
	<div id="titleblock">
		<div id="identifier"><?=$identifier?></div>
		<div id="name"><a href="http://www.airnav.com/airport/<?=$identifier?>"><?=$name?></a></div>
	</div>
	<div id="city"><?=$city?>, <?=$state?></div>
<?
if ($timestamp > 1) {
	$date = date('l, F j, Y', $timestamp);
	print "<div id=\"timestamp\">Last Visited: $date</div>\n";
	print "<div id=\"shout\">";
	if ($checkin_thumbnail) {
		print "<a id=\"checkin_thumbnail\" href=\"$photo\" class=\"colorbox\"><img src=\"$checkin_thumbnail\" height=\"36\" width=\"36\" alt=\"Thumbnail\"/></a>\n";
	}
	print "$notes</div>\n";
}

if ($flights->authenticated) {
	if ($visited == 1) {
		print "<div id=\"location_admin\">Admin: <a href=\"#\" id=\"toggle\">Remove checkins to $identifier</a></div>\n";
	} else {
		print "<div id=\"location_admin\">Admin: <a href=\"#\" id=\"toggle\">I've been to $identifier!</a></div>\n";
	}
}
?>
</div>