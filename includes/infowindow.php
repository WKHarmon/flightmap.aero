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

if ($flights->flickr_user) {
		$parameters = array(
			'method' => 'flickr.photos.search',
			'user_id' => $flights->flickr_user,
			'tags' => $identifier,
			'privacy_filter' => 1,
			'safe_search' => 1,
			'sort' => 'date-taken-desc'
		);
		$photos = json_decode($flights->getFlickr($parameters));
}
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

if ($photos->photos->total > 0) {
	$total = $photos->photos->total;
	$count = 0;
	$closed = 0;
	print '<table id="photos"><tr>';
	foreach ($photos->photos->photo as $photo) {
		$image = constructFlickrURL($photo, 'z');
		$title = $photo->title;
		if ($count < 3) {
			$thumbnail = constructFlickrURL($photo, 's');
			print "<td><a href=\"$image\" rel=\"gallery\" title=\"$title\" class=\"gallery_link colorbox\"><img src=\"$thumbnail\" alt=\"$title\" height=\"75\" width=\"75\" /></a></td>\n";
			$count++;
		} else {
			if ($closed == 0) {
				print "</tr><tr><td colspan=\"3\" id=\"total_photos\">$total photos</td></tr>\n";
				print "</table>\n";
				$closed = 1;
			}
			print "<a href=\"$image\" rel=\"gallery\" title=\"$title\" class=\"gallery_link colorbox\" style=\"display:none;\">Image</a>\n";
		}
	}
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