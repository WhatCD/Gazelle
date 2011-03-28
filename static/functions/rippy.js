function say() {
		ajax.get("ajax.php?action=rippy", function (message) {
				if(message) {
						$('#rippy-says').raw().innerHTML = message;
						$('#bubble').raw().style.display = "block";
				} else {
			        $('#bubble').raw().style.display = "none";
				}
			}
		);
}

function rippyclick() {
	$('.rippywrap').remove();
}
