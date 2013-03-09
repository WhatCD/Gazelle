function say() {
	ajax.get("ajax.php?action=rippy", function(response) {
		response = JSON.parse(response);
		if (response['Message']) {
			var say = response['Message'];
			if(response['From']) {
				say += "<br/><a href='user.php?action=rippy&amp;to=" + response['From'] + "' class='brackets'>Ripback</a>";
			}
			$('#rippywrap').raw().style.display = "";
			$('#rippy-says').raw().innerHTML = say;
			$('#bubble').raw().style.display = "block";
		}
	});
}

function rippyclick() {
	$('.rippywrap').remove();
}
