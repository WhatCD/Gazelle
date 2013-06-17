function SetMessage() {
	var id = document.getElementById('common_answers_select').value;

	ajax.get("?action=get_response&plain=1&id=" + id, function (data) {
		$('#quickpost').raw().value = data;
		$('#common_answers').ghide();
	});
}

function UpdateMessage() {
	var id = document.getElementById('common_answers_select').value;

	ajax.get("?action=get_response&plain=0&id=" + id, function (data) {
		$('#common_answers_body').raw().innerHTML = data;
		$('#first_common_response').remove()
	});
}

function SaveMessage(id) {
	var ajax_message = 'ajax_message_' + id;
	var ToPost = [];

	ToPost['id'] = id;
	ToPost['name'] = document.getElementById('response_name_' + id).value;
	ToPost['message'] = document.getElementById('response_message_' + id).value;

	ajax.post("?action=edit_response", ToPost, function (data) {
			if (data == '1') {
				document.getElementById(ajax_message).textContent = 'Response successfully created.';
			} else if (data == '2') {
				document.getElementById(ajax_message).textContent = 'Response successfully edited.';
			} else {
				document.getElementById(ajax_message).textContent = 'Something went wrong.';
			}
			$('#' + ajax_message).gshow();
			var t = setTimeout("$('#" + ajax_message + "').ghide()", 2000);
		}
	);
}

function DeleteMessage(id) {
	var div = '#response_' + id;
	var ajax_message = 'ajax_message_' + id;

	var ToPost = [];
	ToPost['id'] = id;

	ajax.post("?action=delete_response", ToPost, function (data) {
		$(div).ghide();
		if (data == '1') {
			document.getElementById(ajax_message).textContent = 'Response successfully deleted.';
		} else {
			document.getElementById(ajax_message).textContent = 'Something went wrong.';
		}
		$('#'+ajax_message).gshow();
		var t = setTimeout("$('#" + ajax_message + "').ghide()", 2000);
	});
}

function Assign() {
	var ToPost = [];
	ToPost['assign'] = document.getElementById('assign_to').value;
	ToPost['convid'] = document.getElementById('convid').value;

	ajax.post("?action=assign", ToPost, function (data) {
		if (data == '1') {
			document.getElementById('ajax_message').textContent = 'Conversation successfully assigned.';
		} else {
			document.getElementById('ajax_message').textContent = 'Something went wrong.';
		}
		$('#ajax_message').gshow();
		var t = setTimeout("$('#ajax_message').ghide()", 2000);
	});
}

function PreviewResponse(id) {
	var div = '#response_div_'+id;
	if ($(div).has_class('hidden')) {
		var ToPost = [];
		ToPost['message'] = document.getElementById('response_message_'+id).value;
		ajax.post('?action=preview', ToPost, function (data) {
			document.getElementById('response_div_'+id).innerHTML = data;
			$(div).gtoggle();
			$('#response_message_'+id).gtoggle();
		});
	} else {
		$(div).gtoggle();
		$('#response_message_'+id).gtoggle();
	}
}

function PreviewMessage() {
	if ($('#preview').has_class('hidden')) {
		var ToPost = [];
		ToPost['message'] = document.getElementById('quickpost').value;
		ajax.post('?action=preview', ToPost, function (data) {
			document.getElementById('preview').innerHTML = data;
			$('#preview').gtoggle();
			$('#quickpost').gtoggle();
			$('#previewbtn').raw().value = "Edit";
		});
	} else {
		$('#preview').gtoggle();
		$('#quickpost').gtoggle();
		$('#previewbtn').raw().value = "Preview";
	}
}

function Quote(post, user) {
	username = user;
	postid = post;
	ajax.get("?action=get_post&post=" + postid, function(response) {
		if ($('#quickpost').raw().value !== '') {
			$('#quickpost').raw().value = $('#quickpost').raw().value + "\n\n";
		}
		$('#quickpost').raw().value = $('#quickpost').raw().value + "[quote=" + username + "]" +
			//response.replace(/(img|aud)(\]|=)/ig,'url$2').replace(/\[url\=(https?:\/\/[^\s\[\]<>"\'()]+?)\]\[url\](.+?)\[\/url\]\[\/url\]/gi, "[url]$1[/url]")
			html_entity_decode(response)
		+ "[/quote]";
		resize('quickpost');
	});
}
