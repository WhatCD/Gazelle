(function() {
	var PUSHOVER = 5;
	var TOASTY = 4;
	$(document).ready(function() {
		if ($("#pushservice").val() > 0) {
			$('#pushsettings').show();
		}
		$("#pushservice").change(function() {
			if ($(this).val() > 0) {
				$('#pushsettings').show(500);

				if ($(this).val() == TOASTY) {
					$('#pushservice_title').text("Device ID");
				} else if ($(this).val() == PUSHOVER) {
					$('#pushservice_title').text("User Key");
				} else {
					$('#pushservice_title').text("API Key");
				}
			} else {
				$('#pushsettings').hide(500);
			}
		});
	});
})();
