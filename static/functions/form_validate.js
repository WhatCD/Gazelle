$(document).ready(function() {
	var parts = window.location.pathname.split('/');
	var page = parts[parts.length - 1].split(".")[0];
	var splitted = window.location.search.substr(1).split("&");
	var query = {};
	for (var i = 0; i < splitted.length; i++) {
		var q = splitted[i].split("=");
		query[q[0]] = q[1];
	};

	switch (page) {
		case "forums":
			if (query['action'] == "new") {
				$("#newthreadform").validate();
			}
			break;
		case "reports":
			if (query['action'] == "report") {
				$("#report_form").validate();
			}
			break;
		case "inbox":
			if (query['action'] == "viewconv" || query['action'] == "compose") {
				$("#messageform").validate();
			}
			break;
		case "user":
			if (query['action'] == "notify") {
				$("#filter_form").validate();
			}
			break;
		default:
			break;
	}
});
