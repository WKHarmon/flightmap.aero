  	<form id="login_form" class="modal">
	  	<table id="logindialog" border=0>
			<tr>
				<td class="label"><label for="login_username">Username</label></td>
				<td class="input"><input type="text" name="login_username" id="login_username" /></td>
			</tr>
			<tr>
				<td class="label"><label for="login_password">Password</label>
				<td class="input"><input type="password" name="login_password" id="login_password" /></td>
			</tr>
			<tr>
				<td colspan=2 class="input"><input type="submit" value="Submit" /></td>
			</tr>
	  	</table>
  	</form>
  	<script type="text/javascript">
	$("#login_form").submit(function() {
		var hash = $.md5($("#login_password").val());
		var username = $("#login_username").val();
		var url = "/ajax.php?action=login&username="+username+"&password="+hash;
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
		return false;
  	});
  	</script>