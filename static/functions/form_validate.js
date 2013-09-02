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
		case "requests":
			if (query['action'] == "new") {
				$("#request_form").preventDoubleSubmission();
			}
			break;
		case "sitehistory":
			if (query['action'] == "edit") {
				$("#event_form").validate();
			}
			break;
		case "tools":
			if (query['action'] == "calendar") {
				$("#event_form").validate();
			}
			if (query['action'] == "mass_pm") {
				$("#messageform").validate();
			}
			break;
		default:
			break;
	}
});
