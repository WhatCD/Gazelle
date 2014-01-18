/**
 * Check or uncheck checkboxes in formElem
 * If masterElem is false, toggle each box, otherwise use masterElem's status on all boxes
 * If elemSelector is false, act on all checkboxes in formElem
 */
function toggleChecks(formElem, masterElem, elemSelector) {
	elemSelector = elemSelector || 'input:checkbox';
	if (masterElem) {
		$('#' + formElem + ' ' + elemSelector).prop('checked', masterElem.checked);
	} else {
		$('#' + formElem + ' ' + elemSelector).each(function() {
			this.checked = !this.checked;
		})
	}
}

//Lightbox stuff

/*
 * If loading from a thumbnail, the lightbox is shown first with a "loading" screen
 * while the full size image loads, then the HTML of the lightbox is replaced with the image.
 */

var lightbox = {
	init: function (image, size) {
		if (typeof(image) == 'string') {
			$('#lightbox').gshow().listen('click', lightbox.unbox).raw().innerHTML =
				'<p size="7" style="color: gray; font-size: 50px;">Loading...<p>';
			$('#curtain').gshow().listen('click', lightbox.unbox);
			var src = image;
			image = new Image();
			image.onload = function() {
				lightbox.box_async(image);
			}
			image.src = src;
		}
		if (image.naturalWidth === undefined) {
			var tmp = document.createElement('img');
			tmp.style.visibility = 'hidden';
			tmp.src = image.src;
			image.naturalWidth = tmp.width;
			delete tmp;
		}
		if (image.naturalWidth > size) {
			lightbox.box(image);
		}
	},
	box: function (image) {
		var hasA = false;
		if (image.parentNode != null && image.parentNode.tagName.toUpperCase() == 'A') {
			hasA = true;
		}
		if (!hasA) {
			$('#lightbox').gshow().listen('click', lightbox.unbox).raw().innerHTML = '<img src="' + image.src + '" alt="" />';
			$('#curtain').gshow().listen('click', lightbox.unbox);
		}
	},
	box_async: function (image) {
		var hasA = false;
		if (image.parentNode != null && image.parentNode.tagName.toUpperCase() == 'A') {
			hasA = true;
		}
		if (!hasA) {
			$('#lightbox').raw().innerHTML = '<img src="' + image.src + '" alt="" />';
		}
	},
	unbox: function (data) {
		$('#curtain').ghide();
		$('#lightbox').ghide().raw().innerHTML = '';
	}
};

/* Still some issues
function caps_check(e) {
	if (e === undefined) {
		e = window.event;
	}
	if (e.which === undefined) {
		e.which = e.keyCode;
	}
	if (e.which > 47 && e.which < 58) {
		return;
	}
	if ((e.which > 64 && e.which < 91 && !e.shiftKey) || (e.which > 96 && e.which < 123 && e.shiftKey)) {
		$('#capslock').gshow();
	}
}
*/

function hexify(str) {
	str = str.replace(/rgb\(|\)/g, "").split(",");
	str[0] = parseInt(str[0], 10).toString(16).toLowerCase();
	str[1] = parseInt(str[1], 10).toString(16).toLowerCase();
	str[2] = parseInt(str[2], 10).toString(16).toLowerCase();
	str[0] = (str[0].length == 1) ? '0' + str[0] : str[0];
	str[1] = (str[1].length == 1) ? '0' + str[1] : str[1];
	str[2] = (str[2].length == 1) ? '0' + str[2] : str[2];
	return (str.join(""));
}

function resize(id) {
	var textarea = document.getElementById(id);
	if (textarea.scrollHeight > textarea.clientHeight) {
		//textarea.style.overflowY = 'hidden';
		textarea.style.height = Math.min(1000, textarea.scrollHeight + textarea.style.fontSize) + 'px';
	}
}

//ZIP downloader stuff
function add_selection() {
	var selected = $('#formats').raw().options[$('#formats').raw().selectedIndex];
	if (selected.disabled === false) {
		var listitem = document.createElement("li");
		listitem.id = 'list' + selected.value;
		listitem.innerHTML = '						<input type="hidden" name="list[]" value="' + selected.value + '" /> ' +
'						<span style="float: left;">' + selected.innerHTML + '</span>' +
'						<a href="#" onclick="remove_selection(\'' + selected.value + '\'); return false;" style="float: right;" class="brackets">X</a>' +
'						<br style="clear: all;" />';
		$('#list').raw().appendChild(listitem);
		$('#opt' + selected.value).raw().disabled = true;
	}
}

function remove_selection(index) {
	$('#list' + index).remove();
	$('#opt' + index).raw().disabled = '';
}

// Thank you http://stackoverflow.com/questions/4578398/selecting-all-text-within-a-div-on-a-single-left-click-with-javascript
function select_all(el) {
	if (typeof window.getSelection != "undefined" && typeof document.createRange != "undefined") {
		var range = document.createRange();
		range.selectNodeContents(el);
		var sel = window.getSelection();
		sel.removeAllRanges();
		sel.addRange(range);
	} else if (typeof document.selection != "undefined" && typeof document.body.createTextRange != "undefined") {
		var textRange = document.body.createTextRange();
		textRange.moveToElementText(el);
		textRange.select();
	}
}
