function Vote(amount, requestid) {
	if (typeof amount == 'undefined') {
		amount = parseInt($('#amount').raw().value);
	}
	if (amount == 0) {
		 amount = 20 * 1024 * 1024;
	}

	var index;
	var votecount;
	if (!requestid) {
		requestid = $('#requestid').raw().value;
		votecount = $('#votecount').raw();
		index = false;
	} else {
		votecount = $('#vote_count_' + requestid).raw();
		bounty = $('#bounty_' + requestid).raw();
		index = true;
	}

	if (amount > 20 * 1024 * 1024) {
		upload = $('#current_uploaded').raw().value;
		download = $('#current_downloaded').raw().value;
		rr = $('#current_rr').raw().value;
		if (amount > 0.3 * (upload - rr * download)) {
			if (!confirm('This vote is more than 30% of your buffer. Please confirm that you wish to place this large of a vote.')) {
				return false;
			}
		}
	}

	ajax.get('requests.php?action=takevote&id=' + requestid + '&auth=' + authkey + '&amount=' + amount, function (response) {
			if (response == 'bankrupt') {
				error_message("You do not have sufficient upload credit to add " + get_size(amount) + " to this request");
				return;
			} else if (response == 'dupesuccess') {
				//No increment
			} else if (response == 'success') {
				votecount.innerHTML = (parseInt(votecount.innerHTML)) + 1;
			}

			if ($('#total_bounty').results() > 0) {
				totalBounty = parseInt($('#total_bounty').raw().value);
				totalBounty += (amount * (1 - $('#request_tax').raw().value));
				$('#total_bounty').raw().value = totalBounty;
				$('#formatted_bounty').raw().innerHTML = get_size(totalBounty);

				save_message("Your vote of " + get_size(amount) + ", adding a " + get_size(amount * (1 - $('#request_tax').raw().value)) + " bounty, has been added");
				$('#button').raw().disabled = true;
			} else {
				save_message("Your vote of " + get_size(amount) + " has been added");
			}
		}
	);
}

function Calculate() {
	var mul = (($('#unit').raw().options[$('#unit').raw().selectedIndex].value == 'mb') ? (1024*1024) : (1024*1024*1024));
	var amt = Math.floor($('#amount_box').raw().value * mul);
	if (amt > $('#current_uploaded').raw().value) {
		$('#new_uploaded').raw().innerHTML = "You can't afford that request!";
		$('#new_bounty').raw().innerHTML = "0.00 MB";
		$('#bounty_after_tax').raw().innerHTML = "0.00 MB";
		$('#button').raw().disabled = true;
	} else if (isNaN($('#amount_box').raw().value)
			|| (window.location.search.indexOf('action=new') != -1 && $('#amount_box').raw().value * mul < 100 * 1024 * 1024)
			|| (window.location.search.indexOf('action=view') != -1 && $('#amount_box').raw().value * mul < 20 * 1024 * 1024)) {
		$('#new_uploaded').raw().innerHTML = get_size(($('#current_uploaded').raw().value));
		$('#new_bounty').raw().innerHTML = "0.00 MB";
		$('#bounty_after_tax').raw().innerHTML = "0.00 MB";
		$('#button').raw().disabled = true;
	} else {
		$('#button').raw().disabled = false;
		$('#amount').raw().value = amt;
		$('#new_uploaded').raw().innerHTML = get_size(($('#current_uploaded').raw().value) - amt);
		$('#new_ratio').raw().innerHTML = ratio($('#current_uploaded').raw().value - amt, $('#current_downloaded').raw().value);
		$('#new_bounty').raw().innerHTML = get_size(mul * $('#amount_box').raw().value);
		$('#bounty_after_tax').raw().innerHTML = get_size(mul * 0.9 * $('#amount_box').raw().value);
	}
}

function AddArtistField() {
		var ArtistCount = document.getElementsByName("artists[]").length;
		if (ArtistCount >= 200) {
			return;
		}
		var ArtistField = document.createElement("input");
		ArtistField.type = "text";
		ArtistField.id = "artist";
		ArtistField.name = "artists[]";
		ArtistField.size = 45;

		var ImportanceField = document.createElement("select");
		ImportanceField.id = "importance";
		ImportanceField.name = "importance[]";
		ImportanceField.options[0] = new Option("Main", "1");
		ImportanceField.options[1] = new Option("Guest", "2");
		ImportanceField.options[2] = new Option("Composer", "4");
		ImportanceField.options[3] = new Option("Conductor", "5");
		ImportanceField.options[4] = new Option("DJ / Compiler", "6");
		ImportanceField.options[5] = new Option("Remixer", "3");
		ImportanceField.options[6] = new Option("Producer", "7");

		var x = $('#artistfields').raw();
		x.appendChild(document.createElement("br"));
		x.appendChild(ArtistField);
		x.appendChild(document.createTextNode('\n'));
		x.appendChild(ImportanceField);

		if ($("#artist").data("gazelle-autocomplete")) {
			$(ArtistField).live('focus', function() {
				$(ArtistField).autocomplete({
					serviceUrl : 'artist.php?action=autocomplete'
				});
			});
		}

		ArtistCount++;
}

function RemoveArtistField() {
		var ArtistCount = document.getElementsByName("artists[]").length;
		if (ArtistCount == 1) {
			return;
		}
		var x = $('#artistfields').raw();

		while (x.lastChild.tagName != "INPUT") {
			x.removeChild(x.lastChild);
		}
		x.removeChild(x.lastChild);
		x.removeChild(x.lastChild); //Remove trailing new line.
		ArtistCount--;
}

function Categories() {
	var cat = $('#categories').raw().options[$('#categories').raw().selectedIndex].value;
	if (cat == "Music") {
		$('#artist_tr').gshow();
		$('#releasetypes_tr').gshow();
		$('#formats_tr').gshow();
		$('#bitrates_tr').gshow();
		$('#media_tr').gshow();
		ToggleLogCue();
		$('#year_tr').gshow();
		$('#cataloguenumber_tr').gshow();
	} else if (cat == "Audiobooks" || cat == "Comedy") {
		$('#year_tr').gshow();
		$('#artist_tr').ghide();
		$('#releasetypes_tr').ghide();
		$('#formats_tr').ghide();
		$('#bitrates_tr').ghide();
		$('#media_tr').ghide();
		$('#logcue_tr').ghide();
		$('#cataloguenumber_tr').ghide();
	} else {
		$('#artist_tr').ghide();
		$('#releasetypes_tr').ghide();
		$('#formats_tr').ghide();
		$('#bitrates_tr').ghide();
		$('#media_tr').ghide();
		$('#logcue_tr').ghide();
		$('#year_tr').ghide();
		$('#cataloguenumber_tr').ghide();
	}
}

function add_tag() {
	if ($('#tags').raw().value == "") {
		$('#tags').raw().value = $('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value;
	} else if ($('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value == "---") {
	} else {
		$('#tags').raw().value = $('#tags').raw().value + ", " + $('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value;
	}
}

function Toggle(id, disable) {
	var arr = document.getElementsByName(id + '[]');
	var master = $('#toggle_' + id).raw().checked;
	for (var x in arr) {
		arr[x].checked = master;
		if (disable == 1) {
			arr[x].disabled = master;
		}
	}

	if (id == "formats") {
		ToggleLogCue();
	}
}

function ToggleLogCue() {
	var formats = document.getElementsByName('formats[]');
	var flac = false;

	if (formats[1].checked) {
		flac = true;
	}

	if (flac) {
		$('#logcue_tr').gshow();
	} else {
		$('#logcue_tr').ghide();
	}
	ToggleLogScore();
}

function ToggleLogScore() {
	if ($('#needlog').raw().checked) {
		$('#minlogscore_span').gshow();
	} else {
		$('#minlogscore_span').ghide();
	}
}
