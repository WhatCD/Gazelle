/*
	TODO: Document.
*/
"use strict";

var cookie = {
	get: function (key_name) {
		var value = document.cookie.match('(^|;)?' + key_name + '=([^;]*)(;|$)');
		return (value) ? value[2] : null;
	},
	set: function (key_name, value, days) {
		var date = new Date();

		if (days === undefined) {
			days = 365;
		}

		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		document.cookie = key_name + "=" + value + "; expires=" + date.toGMTString() + "; path=/";
	},
	del: function (key_name) {
		cookie.set(key_name, '', -1);
	},
	flush: function () {
		document.cookie = '';
	}
};

/*
var database = {
	link: false,
	database: 'what',
	connect: function (db_name) {
		if (db_name === undefined) {
			db_name = this.database;
		}
		window.openDatabase(db_name);
	}
};
*/

var local = {
	get: function (key_name) {
		return localStorage.getItem(key_name);
	},
	set: function (key_name, value) {
		localStorage.setItem(key_name, value);
	},
	del: function (key_name) {
		localStorage.removeItem(key_name);
	},
	flush: function () {
		localStorage.clear();
	}
};

var session = {
	get: function (key_name) {
		sessionStorage.getItem(key_name);
	},
	set: function (key_name, value) {
		sessionStorage.setItem(key_name, value);
	},
	del: function (key_name) {
		sessionStorage.removeItem(key_name);
	},
	flush: function () {
		sessionStorage.clear();
	}
};
