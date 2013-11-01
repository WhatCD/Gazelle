function ChangeTo(to) {
	if (to == "text") {
		$('#admincommentlinks').ghide();
		$('#admincomment').gshow();
		resize('admincomment');
		var buttons = document.getElementsByName('admincommentbutton');
		for (var i = 0; i < buttons.length; i++) {
			buttons[i].setAttribute('onclick',"ChangeTo('links'); return false;");
		}
	} else if (to == "links") {
		ajax.post("ajax.php?action=preview","form", function(response) {
			$('#admincommentlinks').raw().innerHTML = response;
			$('#admincomment').ghide();
			$('#admincommentlinks').gshow();
			var buttons = document.getElementsByName('admincommentbutton');
			for (var i = 0; i < buttons.length; i++) {
				buttons[i].setAttribute('onclick',"ChangeTo('text'); return false;");
			}
		})
	}
}

function UncheckIfDisabled(checkbox) {
	if (checkbox.disabled) {
		checkbox.checked = false;
	}
}

function AlterParanoia() {
	// Required Ratio is almost deducible from downloaded, the count of seeding and the count of snatched
	// we will "warn" the user by automatically checking the required ratio box when they are
	// revealing that information elsewhere
	if (!$('input[name=p_ratio]').raw()) {
		return;
	}
	var showDownload = $('input[name=p_downloaded]').raw().checked || ($('input[name=p_uploaded]').raw().checked && $('input[name=p_ratio]').raw().checked);
	if (($('input[name=p_seeding_c]').raw().checked) && ($('input[name=p_snatched_c]').raw().checked) && showDownload) {
		$('input[type=checkbox][name=p_requiredratio]').raw().checked = true;
		$('input[type=checkbox][name=p_requiredratio]').raw().disabled = true;
	} else {
		$('input[type=checkbox][name=p_requiredratio]').raw().disabled = false;
	}
	$('input[name=p_torrentcomments_l]').raw().disabled = !$('input[name=p_torrentcomments_c]').raw().checked;
	$('input[name=p_collagecontribs_l]').raw().disabled = !$('input[name=p_collagecontribs_c]').raw().checked;
	$('input[name=p_requestsfilled_list]').raw().disabled = !($('input[name=p_requestsfilled_count]').raw().checked && $('input[name=p_requestsfilled_bounty]').raw().checked);
	$('input[name=p_requestsvoted_list]').raw().disabled = !($('input[name=p_requestsvoted_count]').raw().checked && $('input[name=p_requestsvoted_bounty]').raw().checked);
	$('input[name=p_uploads_l]').raw().disabled = !$('input[name=p_uploads_c]').raw().checked;
	$('input[name=p_uniquegroups_l]').raw().disabled = !$('input[name=p_uniquegroups_c]').raw().checked;
	$('input[name=p_perfectflacs_l]').raw().disabled = !$('input[name=p_perfectflacs_c]').raw().checked;
	$('input[name=p_seeding_l]').raw().disabled = !$('input[name=p_seeding_c]').raw().checked;
	$('input[name=p_leeching_l]').raw().disabled = !$('input[name=p_leeching_c]').raw().checked;
	$('input[name=p_snatched_l]').raw().disabled = !$('input[name=p_snatched_c]').raw().checked;
	UncheckIfDisabled($('input[name=p_torrentcomments_l]').raw());
	UncheckIfDisabled($('input[name=p_collagecontribs_l]').raw());
	UncheckIfDisabled($('input[name=p_requestsfilled_list]').raw());
	UncheckIfDisabled($('input[name=p_requestsvoted_list]').raw());
	UncheckIfDisabled($('input[name=p_uploads_l]').raw());
	UncheckIfDisabled($('input[name=p_uniquegroups_l]').raw());
	UncheckIfDisabled($('input[name=p_perfectflacs_l]').raw());
	UncheckIfDisabled($('input[name=p_seeding_l]').raw());
	UncheckIfDisabled($('input[name=p_leeching_l]').raw());
	UncheckIfDisabled($('input[name=p_snatched_l]').raw());

	// unique groups, "Perfect" FLACs and artists added are deducible from the list of uploads
	if ($('input[name=p_uploads_l]').raw().checked) {
		$('input[name=p_uniquegroups_c]').raw().checked = true;
		$('input[name=p_uniquegroups_l]').raw().checked = true;
		$('input[name=p_uniquegroups_c]').raw().disabled = true;
		$('input[name=p_uniquegroups_l]').raw().disabled = true;
		$('input[name=p_perfectflacs_c]').raw().checked = true;
		$('input[name=p_perfectflacs_l]').raw().checked = true;
		$('input[name=p_perfectflacs_c]').raw().disabled = true;
		$('input[name=p_perfectflacs_l]').raw().disabled = true;
		$('input[type=checkbox][name=p_artistsadded]').raw().checked = true;
		$('input[type=checkbox][name=p_artistsadded]').raw().disabled = true;
	} else {
		$('input[name=p_uniquegroups_c]').raw().disabled = false;
		$('input[name=p_uniquegroups_l]').raw().checked = false;
		$('input[name=p_uniquegroups_l]').raw().disabled = true;
		$('input[name=p_perfectflacs_c]').raw().disabled = false;
		$('input[type=checkbox][name=p_artistsadded]').raw().disabled = false;
	}
	if ($('input[name=p_collagecontribs_l]').raw().checked) {
		$('input[name=p_collages_c]').raw().disabled = true;
		$('input[name=p_collages_l]').raw().disabled = true;
		$('input[name=p_collages_c]').raw().checked = true;
		$('input[name=p_collages_l]').raw().checked = true;
	} else {
		$('input[name=p_collages_c]').raw().disabled = false;
		$('input[name=p_collages_l]').raw().disabled = !$('input[name=p_collages_c]').raw().checked;
		UncheckIfDisabled($('input[name=p_collages_l]').raw());
	}
}

function ParanoiaReset(checkbox, drops) {
	var selects = $('select');
	for (var i = 0; i < selects.results(); i++) {
		if (selects.raw(i).name.match(/^p_/)) {
			if (drops == 0) {
				selects.raw(i).selectedIndex = 0;
			} else if (drops == 1) {
				selects.raw(i).selectedIndex = selects.raw(i).options.length - 2;
			} else if (drops == 2) {
				selects.raw(i).selectedIndex = selects.raw(i).options.length - 1;
			}
			AlterParanoia();
		}
	}
	var checkboxes = $(':checkbox');
	for (var i = 0; i < checkboxes.results(); i++) {
		if (checkboxes.raw(i).name.match(/^p_/) && (checkboxes.raw(i).name != 'p_lastseen')) {
			if (checkbox == 3) {
				checkboxes.raw(i).checked = !(checkboxes.raw(i).name.match(/_list$/) || checkboxes.raw(i).name.match(/_l$/));
			} else {
				checkboxes.raw(i).checked = checkbox;
			}
			AlterParanoia();
		}
	}
}

function ParanoiaResetOff() {
	ParanoiaReset(true, 0);
}

function ParanoiaResetStats() {
	ParanoiaReset(3, 0);
	$('input[name=p_collages_l]').raw().checked = false;
}

function ParanoiaResetOn() {
	ParanoiaReset(false, 0);
	$('input[name=p_collages_c]').raw().checked = false;
	$('input[name=p_collages_l]').raw().checked = false;
}

addDOMLoadEvent(AlterParanoia);

function ToggleWarningAdjust(selector) {
	if (selector.options[selector.selectedIndex].value == '---') {
		$('#ReduceWarningTR').gshow();
		$('#ReduceWarning').raw().disabled = false;
	} else {
		$('#ReduceWarningTR').ghide();
		$('#ReduceWarning').raw().disabled = true;
	}
}

addDOMLoadEvent(ToggleIdenticons);
function ToggleIdenticons() {
	var disableAvatars = $('#disableavatars');
	if (disableAvatars.size()) {
		var selected = disableAvatars[0].selectedIndex;
		if (selected == 2 || selected == 3) {
			$('#identicons').gshow();
		} else {
			$('#identicons').ghide();
		}
	}
}

function userform_submit() {
	if ($('#resetpasskey').is(':checked')) {
		if (!confirm('Are you sure you want to reset your passkey?')) {
			return false;
		}
	}
	return formVal();
}

function togglePassKey(key) {
	if ($('#passkey').raw().innerHTML == 'View') {
		$('#passkey').raw().innerHTML = key;
	} else {
		$('#passkey').raw().innerHTML = 'View';
	}

}

function commStats(userid) {
	$('.user_commstats').html('Loading...');
	ajax.get('ajax.php?action=community_stats&userid=' + userid, function(JSONresponse) {
		var response = JSON.parse(JSONresponse) || false;
		if (!response || response.status == 'failure') {
			$('.user_commstats').html('An error occurred');
			return;
		}
		displayCommStats(response.response);
	});
}

function displayCommStats(stats) {
	var baseid = '#user_commstats_';
	for (x in stats) {
		if (stats[x] === false) {
			continue;
		}
		switch (x) {
			case 'leeching':
				$(baseid + x).html(stats[x]);
				break;
			case 'seeding':
				$(baseid + x).html(stats[x]);
				break;
			case 'downloaded':
				$(baseid + x).html(stats[x]);
				break;
			case 'snatched':
				$(baseid + x).html(stats[x]);
				break;
			case 'usnatched':
				$(baseid + x).html('(' + stats[x] + ')');
				break;
			case 'udownloaded':
				$(baseid + x).html('(' + stats[x] + ')');
				break;
			case 'seedingperc':
				$(baseid + x).html('(' + stats[x] + '%)');
				break;
		}
	}
}

$(document).ready(function() {
	$("#random_password").click(function() {
		var length = 15,
			charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=<>?",
			password = "";
		for (var i = 0, n = charset.length; i < length; ++i) {
			password += charset.charAt(Math.floor(Math.random() * n));
		}
		$('#change_password').val(password);
	});
});
