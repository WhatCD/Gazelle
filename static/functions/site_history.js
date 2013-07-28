$(document).ready(function() {
	var trimmed = false;
	var tags = $("#tags");
	$("#tag_list").change(function() {
		if (tags.val().length == 0) {
			trimmed = false;
		} else {
			trimmed = true;
		}
		if ($(this).prop("selectedIndex")) {
			tags.val(tags.val() + "," + $(this).val());
			if (!trimmed) {
				tags.val(tags.val().substr(1, tags.val().length));
				trimmed = true;
			}
		}
	});
});
