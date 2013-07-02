$(document).ready(function() {
	var url = new URL();
	var query = url.query;
	switch (url.path) {
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
