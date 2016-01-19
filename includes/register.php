<form id="registration" class="modal">
	<table border=0>
		<tr>
			<td class="label"><label for="username">Username</label></td>
			<td class="input"><input type="text" name="username" id="username" /></td>
		</tr>
		<tr>
			<td class="label"><label for="password">Password</label>
			<td class="input"><input type="password" name="password" id="password" /></td>
		<tr>
			<td class="label"><label for="title">Log Title</label>
			<td class="input"><input type="text" name="title" id="title" /></td>
		</tr>
		<tr>
			<td class="label"><label for="address">Starting Address</label></label>
			<td class="input"><input type="text" name="address" id="address" /></td>
		</tr>
		<tr>
			<td colspan=2 class="input"><input type="submit" value="Submit" /></td>
		</tr>
	</table>
</form>
<script type="text/javascript">
	$("#registration").submit(function() {
		var problem = 0;
		$("#registration").find("input").each(function() {
			if ($(this).val() == '') {
				problem = 1;
			}
		});
		if (problem == 1) {
			alert("Please fill in all fields.");
			return false;
		}
		var lat = 0;
		var lng = 0;
		var hash = $.md5($("#password").val());
		var username = $("#username").val();
		var title = $("#title").val();
		var geocoder = new google.maps.Geocoder();
		address = $("#address").val();
		geocoder.geocode({
			'address': address,
			'region': 'us'
		}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				lat = results[0].geometry.location.lat();
				lng = results[0].geometry.location.lng();
				var url = "/ajax.php?action=register&username="+username+"&password="+hash+"&title="+encodeURIComponent(title)+"&lat="+lat+"&lng="+lng;
				$.ajax({
					url: url,
					dataType: 'text',
					success: function(data) {
						if (data.length > 0) {
							alert("Error: "+data);
						} else {
							window.location = "/"+username;
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