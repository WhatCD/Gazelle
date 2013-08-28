// This a global variable because other scripts need to use it
var notifications;

$(document).ready(function() {
	var url = new URL();

	$.ajax({
		type: "GET",
		url: "ajax.php?action=get_user_notifications" + (url.query.clearcache ? "&clearcache=" + url.query.clearcache : ""),
		dataType: "json",
		data: {
			"skip" : getSkippedPage(url)
		}
	}).done(function(results) {
		notifications = results;
		if (results['status'] == 'success') {
			results = results['response'];
			if (results) {
				$.each(results, function(type, notification) {
					if (type != "Rippy") {
						createNoty(type, notification['contents']['id'], notification['contents']['message'], notification['contents']['url'], notification['contents']['importance']);
						if (type == "Subscriptions") {
							$("#userinfo_minor").addClass("highlite");
							$("#nav_subscriptions").addClass("new-subscriptions");
						}
					}
					else {
						$.getScript("static/functions/rippy.js");
					}
				});
			}
		}
	});
});

function getSkippedPage(url) {
	var skip = "";
	switch(url.path) {
		case "inbox":
			if (url.query.length == 0) {
				skip = "Inbox";
			}
			break;
		case "userhistory":
			if (url.query['action'] == "subscriptions") {
				skip = "Subscriptions";
			}
			if (url.query['action'] == "quote_notifications") {
				skip = "Quotes";
			}
			if (url.query['action'] == "subscribed_collages") {
				skip = "Collages";
			}
			break;
		case "user":
			if (url.query['action'] == "notify") {
				skip = "Torrents";
			}
			break;
		case "blog":
			if (url.query.length == 0) {
				skip = "Blog";
			}
			break;
		case "index":
			if (url.query.length == 0) {
				skip = "News";
			}
			break;
		case "staffpm":
			if (url.query.length == 0) {
				skip = "StaffPM";
			}
			break;
		default:
			break;
	}
	return skip;
}

function createNoty(type, id, message, url, importance) {
	var hidden = !url ? "hidden" : "";
	noty({
		text: message,
		type: importance,
		layout: 'bottomRight',
		closeWith: ['click'],
		animation: {
			open: {height: 'toggle'},
			close: {height: 'toggle'},
			easing: 'swing',
			speed: 250
		},
		buttonElement : 'a',
		buttons: [
		{
			addClass: 'brackets noty_button_view ' + hidden, text: 'View', href: url
		},
		{
			addClass: 'brackets noty_button_clear', text: 'Clear', onClick: function($noty) {
				$noty.close();
				clear(type, id);
			}
		},
		{
			addClass: 'brackets noty_button_close ', text: 'Dismiss', onClick: function($noty) {
				$noty.close();
			}
		},
		]
	});
}

function clear(type, id) {
	$.ajax({
		type : "POST",
		url: "ajax.php?action=clear_user_notification",
		dataType: "json",
		data : {
			"type" : type,
			"id" : id
		}
	});
}
