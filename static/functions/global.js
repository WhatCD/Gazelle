function toggleChecks(formElem,masterElem) {
	if (masterElem.checked) { checked=true; } else { checked=false; }
	for(s=0; s<$('#'+formElem).raw().elements.length; s++) {
		if ($('#'+formElem).raw().elements[s].type=="checkbox") {
			$('#'+formElem).raw().elements[s].checked=checked;
		}
	}
}

//Lightbox stuff
var lightbox = {
	init: function (image, size) {
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
		if(image.parentNode.tagName.toUpperCase() != 'A') {
			$('#lightbox').show().listen('click',lightbox.unbox).raw().innerHTML = '<img src="' + image.src + '" />';
			$('#curtain').show().listen('click',lightbox.unbox);
		}
	},
	unbox: function (data) {
		$('#curtain').hide();
		$('#lightbox').hide().raw().innerHTML = '';
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
	if ((e.which > 64 && e.which <  91 && !e.shiftKey) || (e.which > 96 && e.which < 123 && e.shiftKey)) {
		$('#capslock').show();
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
		textarea.style.overflowY = 'hidden';
		textarea.style.height = textarea.scrollHeight + textarea.style.fontSize + 'px';
	}
}

//ZIP downloader stuff
function add_selection() {
	var selected = $('#formats').raw().options[$('#formats').raw().selectedIndex];
	if (selected.disabled === false) {
		var listitem = document.createElement("li");
		listitem.id = 'list' + selected.value;
		listitem.innerHTML = '						<input type="hidden" name="list[]" value="'+selected.value+'" /> ' +
'						<span style="float:left;">'+selected.innerHTML+'</span>' +
'						<a href="#" onclick="remove_selection(\''+selected.value+'\');return false;" style="float:right;">[X]</a>' +
'						<br style="clear:all;" />';
		$('#list').raw().appendChild(listitem);
		$('#opt' + selected.value).raw().disabled = true;
	}
}

function remove_selection(index) {
	$('#list' + index).remove();
	$('#opt' + index).raw().disabled='';
}

function Stats(stat) {
	ajax.get("ajax.php?action=stats&stat=" + stat);
}
