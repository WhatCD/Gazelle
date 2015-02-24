var username;
var postid;
var url = new gazURL();

function QuoteJump(event, post) {
	var button = event.button;
	var url, pattern;
	if (isNaN(post.charAt(0))) {
		switch (post.charAt(0)) {
			case 'a': // artist comment
				url = 'artist';
				break;
			case 't': // torrent comment
				url = 'torrents';
				break;
			case 'c': // collage comment
				url = 'collages';
				break;
			case 'r': // request comment
				url = 'requests';
				break;
			default:
				return;
		}
		pattern = new RegExp(url + '\.php');
		post = post.substr(1);
		url = 'comments.php?action=jump&postid=' + post;
	} else {
		// forum post
		url = 'forums.php?action=viewthread&postid=' + post;
		pattern = /forums\.php/;
	}
	var hash = "#post" + post;
	if (button == 0) {
		if ($(hash).raw() != null && location.href.match(pattern)) {
			window.location.hash = hash;
		} else {
			window.open(url, '_self');
		}
	} else if (button == 1) {
		window.open(url, '_window');
	}
}

function Quote(post, user) {
	Quote(post, user, false)
}

var original_post;
function Quote(post, user, link) {
	username = user;
	postid = post;

	// check if reply_box element exists and that user is in the forums
	if (!$('#reply_box').length && url.path == "forums") {
		if ($("#quote_" + postid).text() == "Quote") {
			original_post = $("#content" + postid).html();
			$("#quote_" + postid).text("Unquote");
			$.ajax({
				type : "POST",
				url : "ajax.php?action=raw_bbcode",
				dataType : "json",
				data : {
					"postid" : postid
				}
			}).done(function(response) {
				$("#content" + postid).html(response['response']['body']);
				select_all($("#content" + postid).get(0));
			});
		} else {
			document.getSelection().removeAllRanges();
			$("#content" + postid).html(original_post);
			$("#quote_" + postid).text("Quote");
		}
	} else {
		var target = '';
		var requrl = '';
		if (url.path == "inbox") {
			requrl = 'inbox.php?action=get_post&post=' + post;
		} else {
			requrl = 'comments.php?action=get&postid=' + post;
		}
		if (link == true) {
			if (url.path == "artist") {
				// artist comment
				target = 'a';
			} else if (url.path == "torrents") {
				// torrent comment
				target = 't';
			} else if (url.path == "collages") {
				// collage comment
				target = 'c';
			} else if (url.path == "requests") {
				// request comment
				target = 'r';
			} else {
				// forum post
				requrl = 'forums.php?action=get_post&post=' + post;
			}
			target += post;
		}
		ajax.get(requrl, function(response) {
			if ($('#quickpost').raw().value !== '') {
				$('#quickpost').raw().value = $('#quickpost').raw().value + "\n\n";
			}
			$('#quickpost').raw().value = $('#quickpost').raw().value + "[quote=" + username + (link == true ? "|" + target : "") + "]" +
				//response.replace(/(img|aud)(\]|=)/ig,'url$2').replace(/\[url\=(https?:\/\/[^\s\[\]<>"\'()]+?)\]\[url\](.+?)\[\/url\]\[\/url\]/gi, "[url]$1[/url]")
				html_entity_decode(response)
			+ "[/quote]";
			resize('quickpost');
		});
	}
}

function Edit_Form(post,key) {
	postid = post;
	var boxWidth, postuserid, pmbox, inputname;
	//If no edit is already going underway or a previous edit was finished, make the necessary dom changes.
	if (!$('#editbox' + postid).results() || $('#editbox' + postid + '.hidden').results()) {
		$('#reply_box').ghide();
		if (location.href.match(/torrents\.php/)
				|| location.href.match(/artist\.php/)) {
			boxWidth = "50";
		} else {
			boxWidth = "80";
		}
		postuserid = $('#post' + postid + ' strong a').attr('href').split('=')[1];
		if (postuserid != userid) {
			pmbox = '<span id="pmbox' + postid + '"><label>PM user on edit? <input type="checkbox" name="pm" value="1" /></label></span>';
		} else {
			pmbox = '';
		};
		if (location.href.match(/forums\.php/)) {
			inputname = "post";
		} else {
			inputname = "postid";
		}
		$('#bar' + postid).raw().cancel = $('#content' + postid).raw().innerHTML;
		$('#bar' + postid).raw().oldbar = $('#bar' + postid).raw().innerHTML;
		$('#content' + postid).raw().innerHTML = "<div id=\"preview" + postid + "\"></div><form id=\"form" + postid + "\" method=\"post\" action=\"\">" + pmbox + "<input type=\"hidden\" name=\"auth\" value=\"" + authkey + "\" /><input type=\"hidden\" name=\"key\" value=\"" + key + "\" /><input type=\"hidden\" name=\"" + inputname + "\" value=\"" + postid + "\" /><textarea id=\"editbox" + postid + "\" onkeyup=\"resize('editbox" + postid + "');\" name=\"body\" cols=\"" + boxWidth + "\" rows=\"10\"></textarea></form>";
		$('#bar' + postid).raw().innerHTML = '<input type="button" value="Preview" onclick="Preview_Edit(' + postid + ');" /><input type="button" value="Post" onclick="Save_Edit(' + postid + ')" /><input type="button" value="Cancel" onclick="Cancel_Edit(' + postid + ');" />';
	}
	/* If it's the initial edit, fetch the post content to be edited.
	 * If editing is already underway and edit is pressed again, reset the post
	 * (keeps current functionality, move into brackets to stop from happening).
	 */
	if (location.href.match(/forums\.php/)) {
		ajax.get("?action=get_post&post=" + postid, function(response) {
			$('#editbox' + postid).raw().value = html_entity_decode(response);
			resize('editbox' + postid);
		});
	} else {
		ajax.get("comments.php?action=get&postid=" + postid, function(response) {
			$('#editbox' + postid).raw().value = html_entity_decode(response);
			resize('editbox' + postid);
		});
	}
}

function Cancel_Edit(postid) {
	var answer = confirm("Are you sure you want to cancel?");
	if (answer) {
		$('#reply_box').gshow();
		$('#bar' + postid).raw().innerHTML = $('#bar' + postid).raw().oldbar;
		$('#content' + postid).raw().innerHTML = $('#bar' + postid).raw().cancel;
	}
}

function Preview_Edit(postid) {
	$('#bar' + postid).raw().innerHTML = "<input type=\"button\" value=\"Editor\" onclick=\"Cancel_Preview(" + postid + ");\" /><input type=\"button\" value=\"Post\" onclick=\"Save_Edit(" + postid + ")\" /><input type=\"button\" value=\"Cancel\" onclick=\"Cancel_Edit(" + postid + ");\" />";
	ajax.post("ajax.php?action=preview","form" + postid, function(response) {
		$('#preview' + postid).raw().innerHTML = response;
		$('#editbox' + postid).ghide();
	});
}

function Cancel_Preview(postid) {
	$('#bar' + postid).raw().innerHTML = "<input type=\"button\" value=\"Preview\" onclick=\"Preview_Edit(" + postid + ");\" /><input type=\"button\" value=\"Post\" onclick=\"Save_Edit(" + postid + ")\" /><input type=\"button\" value=\"Cancel\" onclick=\"Cancel_Edit(" + postid + ");\" />";
	$('#preview' + postid).raw().innerHTML = "";
	$('#editbox' + postid).gshow();
}

function Save_Edit(postid) {
	$('#reply_box').gshow();
	if (location.href.match(/forums\.php/)) {
		ajax.post("forums.php?action=takeedit","form" + postid, function (response) {
			$('#bar' + postid).raw().innerHTML = "<a href=\"reports.php?action=report&amp;type=post&amp;id="+postid+"\" class=\"brackets\">Report</a>&nbsp;<a href=\"#\">&uarr;</a>";
			$('#preview' + postid).raw().innerHTML = response;
			$('#editbox' + postid).ghide();
			$('#pmbox' + postid).ghide();
		});
	} else {
		ajax.post("comments.php?action=take_edit","form" + postid, function (response) {
			$('#bar' + postid).raw().innerHTML = "";
			$('#preview' + postid).raw().innerHTML = response;
			$('#editbox' + postid).ghide();
			$('#pmbox' + postid).ghide();
		});
	}
}

function Delete(post) {
	postid = post;
	if (confirm('Are you sure you wish to delete this post?') == true) {
		if (location.href.match(/forums\.php/)) {
			ajax.get("forums.php?action=delete&auth=" + authkey + "&postid=" + postid, function () {
				$('#post' + postid).ghide();
			});
		} else {
			ajax.get("comments.php?action=take_delete&auth=" + authkey + "&postid=" + postid, function () {
				$('#post' + postid).ghide();
			});
		}
	}
}

function Quick_Preview() {
	var quickreplybuttons;
	$('#post_preview').raw().value = "Make changes";
	$('#post_preview').raw().preview = true;
	ajax.post("ajax.php?action=preview", "quickpostform", function(response) {
		$('#quickreplypreview').gshow();
		$('#contentpreview').raw().innerHTML = response;
		$('#quickreplytext').ghide();
	});
}

function Quick_Edit() {
	var quickreplybuttons;
	$('#post_preview').raw().value = "Preview";
	$('#post_preview').raw().preview = false;
	$('#quickreplypreview').ghide();
	$('#quickreplytext').gshow();
}

function Newthread_Preview(mode) {
	$('#newthreadpreviewbutton').gtoggle();
	$('#newthreadeditbutton').gtoggle();
	if (mode) { // Preview
		ajax.post("ajax.php?action=preview", "newthreadform", function(response) {
			$('#contentpreview').raw().innerHTML = response;
		});
		$('#newthreadtitle').raw().innerHTML = $('#title').raw().value;
		var pollanswers = $('#answer_block').raw();
		if (pollanswers && pollanswers.children.length > 4) {
			pollanswers = pollanswers.children;
			$('#pollquestion').raw().innerHTML = $('#pollquestionfield').raw().value;
			for (var i = 0; i < pollanswers.length; i += 2) {
				if (!pollanswers[i].value) {
					continue;
				}
				var el = document.createElement('input');
				el.id = 'answer_' + (i + 1);
				el.type = 'radio';
				el.name = 'vote';
				$('#pollanswers').raw().appendChild(el);
				$('#pollanswers').raw().appendChild(document.createTextNode(' '));
				el = document.createElement('label');
				el.htmlFor = 'answer_' + (i + 1);
				el.innerHTML = pollanswers[i].value;
				$('#pollanswers').raw().appendChild(el);
				$('#pollanswers').raw().appendChild(document.createElement('br'));
			}
			if ($('#pollanswers').raw().children.length > 4) {
				$('#pollpreview').gshow();
			}
		}
	} else { // Back to editor
		$('#pollpreview').ghide();
		$('#newthreadtitle').raw().innerHTML = 'New Topic';
		var pollanswers = $('#pollanswers').raw();
		if (pollanswers) {
			var el = document.createElement('div');
			el.id = 'pollanswers';
			pollanswers.parentNode.replaceChild(el, pollanswers);
		}
	}
	$('#newthreadtext').gtoggle();
	$('#newthreadpreview').gtoggle();
	$('#subscribediv').gtoggle();
}

function LoadEdit(type, post, depth) {
	ajax.get("forums.php?action=ajax_get_edit&postid=" + post + "&depth=" + depth + "&type=" + type, function(response) {
			$('#content' + post).raw().innerHTML = response;
		}
	);
}

function AddPollOption(id) {
	var list = $('#poll_options').raw();
	var item = document.createElement("li");
		var form = document.createElement("form");
		form.method = "POST";
			var auth = document.createElement("input");
			auth.type = "hidden";
			auth.name = "auth";
			auth.value = authkey;
			form.appendChild(auth);

			var action = document.createElement("input");
			action.type = "hidden";
			action.name = "action";
			action.value = "add_poll_option";
			form.appendChild(action);

			var threadid = document.createElement("input");
			threadid.type = "hidden";
			threadid.name = "threadid";
			threadid.value = id;
			form.appendChild(threadid);

			var input = document.createElement("input");
			input.type = "text";
			input.name = "new_option";
			input.size = "50";
			form.appendChild(input);

			var submit = document.createElement("input");
			submit.type = "submit";
			submit.id = "new_submit";
			submit.value = "Add";
			form.appendChild(submit);
		item.appendChild(form);
	list.appendChild(item);
}

/**
 * HTML5-compatible storage system
 * Tries to use 'oninput' event to detect text changes and sessionStorage to save it.
 *
 * new StoreText('some_textarea_id', 'some_form_id', 'some_topic_id')
 * The form is required to remove the stored text once it is submitted.
 *
 * Topic ID is required to retrieve the right text on the right topic
 **/
function StoreText (field, form, topic) {
	this.field = document.getElementById(field);
	this.form = document.getElementById(form);
	this.key = 'auto_save_temp';
	this.keyID = 'auto_save_temp_id';
	this.topic = +topic;
	this.load();
}
StoreText.prototype = {
	constructor : StoreText,
	load : function () {
		if (this.enabled() && this.valid()) {
			this.retrieve();
			this.autosave();
			this.clearForm();
		}
	},
	valid : function () {
		return this.field && this.form && !isNaN(this.topic);
	},
	enabled : function () {
		return window.sessionStorage && typeof window.sessionStorage === 'object';
	},
	retrieve : function () {
		var r = sessionStorage.getItem(this.key);
		if (this.topic === +sessionStorage.getItem(this.keyID) && r) {
			this.field.value = r;
		}
	},
	remove : function () {
		sessionStorage.removeItem(this.keyID);
		sessionStorage.removeItem(this.key);
	},
	save : function () {
		sessionStorage.setItem(this.keyID, this.topic);
		sessionStorage.setItem(this.key, this.field.value);
	},
	autosave : function () {
		$(this.field).on(this.getInputEvent(), $.proxy(this.save, this));
	},
	getInputEvent : function () {
		var e;
		if ('oninput' in this.field) {
			e = 'input';
		} else if (document.body.addEventListener) {
			e = 'change keyup paste cut';
		} else {
			e = 'propertychange';
		}
		return e;
	},
	clearForm : function () {
		$(this.form).submit($.proxy(this.remove, this));
	}
};

$(document).ready(function() {
	var fadeSpeed = 300;
	var avatars = new Array();
	$(".double_avatar").each(function() {
		if ($(this).data("gazelle-second-avatar")) {
			$(this).data("fading", "");
			var secondAvatar = $(this).data("gazelle-second-avatar");
			var originalAvatar = $(this).attr("src");
			if ($.inArray(secondAvatar, avatars) == -1) {
				// Preload second image
				avatars.push(secondAvatar);
				var image = new Image();
				image.src = secondAvatar;
			}
			$(this).mouseover(function() {
				if ($(this).data("fading") == "") {
					var originalHeight = $(this).parent().height();
					$(this).data("fading", "in");
					$(this).fadeTo(fadeSpeed, 0, function() {
						$(this).attr("src", secondAvatar);
						if (!this.parentNode.style.height) {
							$(this).parent().css("height", Math.max($(this).parent().height(), originalHeight) + 'px');
						}
					}).fadeTo(fadeSpeed, 1);
				}
			});
			$(this).mouseout(function() {
				if ($(this).data("fading") != "out" && ($(this).data("fading") != "" || $(this).attr("src") != originalAvatar)) {
					$(this).data("fading", "out");
					$(this).fadeOut(fadeSpeed, function() {
						$(this).attr("src", originalAvatar);
					}).fadeIn(fadeSpeed, function() {
						$(this).data("fading", "");
					});
				}
			});
		}

	});
});
