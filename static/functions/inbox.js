//Using this instead of comments as comments has pertty damn strict requirements on the variable names required

function Quick_Preview() {
	$('#buttons').raw().innerHTML = "<input type=\"button\" value=\"Editor\" onclick=\"Quick_Edit();\" /><input type=\"submit\" value=\"Send Message\" />";
	ajax.post("ajax.php?action=preview","messageform", function(response) {
		$('#quickpost').ghide();
		$('#preview').raw().innerHTML = response;
		$('#preview').gshow();
	});
}

function Quick_Edit() {
	$('#buttons').raw().innerHTML = "<input type=\"button\" value=\"Preview\" onclick=\"Quick_Preview();\" /><input type=\"submit\" value=\"Send Message\" />";
	$('#preview').ghide();
	$('#quickpost').gshow();
}
