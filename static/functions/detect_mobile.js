var ANDROID_COOKIE_NAME = "mobile_checked_android";
var OTHER_COOKIE_NAME = "mobile_checked_other";
var MOBILE_SITE_HOSTNAME = "m.what.cd";
var MOBILE_SITE_URL = "https://m.what.cd/";
var ANDROID_APP_URL = "http://bit.ly/whatandroid";

var isMobile = {
	Android: function() {
		return navigator.userAgent.match(/Android/i) ? true : false;
	},
	BlackBerry: function() {
		return navigator.userAgent.match(/BlackBerry/i) ? true : false;
	},
	iOS: function() {
		return navigator.userAgent.match(/iPhone|iPad|iPod/i) ? true : false;
	},
	Windows: function() {
		return navigator.userAgent.match(/IEMobile/i) ? true : false;
	},
	Any: function() {
		return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Windows());
	},
	NotAndroid: function() {
	return (isMobile.BlackBerry() || isMobile.iOS() || isMobile.Windows());
	}
};

if (window.location.hostname == MOBILE_SITE_HOSTNAME) {
	setCookie(OTHER_COOKIE_NAME, true, 365);
} else {
	if (isMobile.Android()) {
		if (!hasCookie(ANDROID_COOKIE_NAME)) {
			setCookie(ANDROID_COOKIE_NAME, true, 365);
			var result = confirm("An Android app is available for What.CD. Would you like to download it?");
			if (result == true) {
				window.location = ANDROID_APP_URL;
			}
		}
	} else if (isMobile.NotAndroid()) {
		if (!hasCookie(OTHER_COOKIE_NAME)) {
			setCookie(OTHER_COOKIE_NAME, true, 365);
			var result = confirm("A mobile version of What.CD is available. Would you like to use it?");
			if (result == true) {
				window.location = MOBILE_SITE_URL;
			}
		}
	}
}

function setCookie(c_name, value, exdays) {
	var exdate = new Date();
	exdate.setDate(exdate.getDate() + exdays);
	var c_value = escape(value) + ((exdays == null) ? "" : "; expires=" + exdate.toUTCString());
	document.cookie = c_name + "=" + c_value;
}

function getCookie(c_name) {
	var i, x, y, ARRcookies = document.cookie.split(";");
	for (i = 0; i < ARRcookies.length; i++) {
		x = ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
		y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
		x = x.replace(/^\s+|\s+$/g,"");
		if (x == c_name) {
			return unescape(y);
		}
	}
}

function hasCookie(c_name) {
	var checked = getCookie(c_name);
	if (checked != null) {
		return true;
	}
	return false;
}
