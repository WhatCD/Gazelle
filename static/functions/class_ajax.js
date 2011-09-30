/*
	TODO: Further optimize serialize function
	
	UPDATE: We were forced to create an individual XHR for each request 
	to avoid race conditions on slower browsers where the request would 
	be overwritten before the callback triggered, and leave it hanging. 
	This only happened in FF3.0 that we tested.

	Example usage 1:
	ajax.handle = function () {
		$('#preview' + postid).raw().innerHTML = ajax.response;
		$('#editbox' + postid).hide();	
	}
	ajax.post("ajax.php?action=preview","#form-id" + postid);
	
	Example usage 2:
	ajax.handle = function() {
		$('#quickpost').raw().value = "[quote="+username+"]" + ajax.response + "[/quote]";
	}
	ajax.get("?action=get_post&post=" + postid);
	
*/
"use strict";
var json = {
	encode: function (object) {
		try {
			return JSON.stringify(object);
		} catch (err) {
			return '';
		}
	},
	decode: function (string) {
		if (window.JSON && JSON.parse) {
			return JSON.parse(string);
		} else {
			return eval("(" + string + ")");
			//return (new Function("return " + data))();
		}
	}
};

var ajax = {
	get: function (url, callback) {
		var req = (typeof(window.ActiveXObject) === 'undefined') ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
		if (callback !== undefined) {
			req.onreadystatechange = function () {
				if (req.readyState !== 4 || req.status !== 200) {
					return;
				}
				callback(req.responseText);
			};
		}
		req.open("GET", url, true);
		req.send(null);
	},
	post: function (url, data, callback) {
		var req = isset(window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
		var params = ajax.serialize(data);
		if (callback !== undefined) {
			req.onreadystatechange = function () {
				if (req.readyState !== 4 || req.status !== 200) {
					return;
				}
				callback(req.responseText);
			};
		}
		req.open('POST', url, true);
		req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		req.send(params);
	},
	serialize: function (data) {
		var query = '',
			elements;
		if (is_array(data)) {
			for (var key in data) {
				query += key + '=' + encodeURIComponent(data[key]) + '&';
			}
		} else {
			elements = document.getElementById(data).elements;
			for (var i = 0, il = elements.length; i < il; i++) {
				var element = elements[i];
				if (!isset(element) || element.disabled || element.name === '') {
					continue;
				}
				switch (element.type) {
					case 'text':
					case 'hidden':
					case 'password':
					case 'textarea':
					case 'select-one':
						query += element.name + '=' + encodeURIComponent(element.value) + '&';
						break;
					case 'select-multiple':
						for (var j = 0, jl = element.options.length; j < jl; j++) {
							var current = element.options[j];
							if (current.selected) {
								query += element.name + '=' + encodeURIComponent(current.value) + '&';
							}
						}
						break;
					case 'radio':
						if (element.checked) {
							query += element.name + '=' + encodeURIComponent(element.value) + '&';
						}
						break;
					case 'checkbox':
						if (element.checked) {
							query += element.name + '=' + encodeURIComponent(element.value) + '&';
						}
						break;
				}
			}
		}
		return query.substr(0, query.length - 1);
	}
};
//Bookmarks
function Bookmark(type, id, newName) {
	var lnk = $('#bookmarklink_' + type + '_' + id).raw();
	var oldName = lnk.innerHTML;
	ajax.get("bookmarks.php?action=add&type=" + type + "&auth=" + authkey + "&id=" + id, function() {
		lnk.onclick = function() { Unbookmark(type, id, oldName); return false; };
		lnk.innerHTML = newName;
		lnk.title = 'Remove bookmark';
	});
}

function Unbookmark(type, id, newName) {
	if(window.location.pathname.indexOf('bookmarks.php') != -1) {
		ajax.get("bookmarks.php?action=remove&type=" + type + "&auth=" + authkey + "&id=" + id, function() {
			$('#group_' + id).remove();
			$('.groupid_' + id).remove();
			$('.bookmark_' + id).remove();
		});
	} else {
		var lnk = $('#bookmarklink_' + type + '_' + id).raw();
		var oldName = lnk.innerHTML;
		ajax.get("bookmarks.php?action=remove&type=" + type + "&auth=" + authkey + "&id=" + id, function() {
			lnk.onclick = function() { Bookmark(type, id, oldName); return false; };
			lnk.innerHTML = newName;
			lnk.title = 'Add bookmark';
		});
	}
}
