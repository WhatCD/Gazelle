/*
	TODO: Document.
*/
"use strict";
var cookie = {
	get: function (cookie_name) {
		var value = document.cookie.match('(^|;)?' + cookie_name + '=([^;]*)(;|$)');
		return (value) ? value[2] : null;
	},
	set: function (cookie_name, value, days) {
		var date = new Date();

		if (days === undefined) {
			days = 365;
		}

		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		document.cookie = cookie_name + "=" + value + "; expires=" + date.toGMTString() + "; path=/";
	},
	del: function (cookie_name) {
		cookie.set(cookie_name, '', -1);
	}
};
