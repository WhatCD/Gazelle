
function Vote(amount, requestid) {
	if(typeof amount == 'undefined') {
		amount = parseInt($('#amount').raw().value);
	}
	if(amount == 0) {
		 amount = 20 * 1024 * 1024;
	}
	
	var index;
	var votecount;
	if(!requestid) {
		requestid = $('#requestid').raw().value;
		votecount = $('#votecount').raw();
		index = false;
	} else {
		votecount = $('#vote_count_' + requestid).raw();
		index = true;
	}
	
	ajax.get('requests.php?action=takevote&id=' + requestid + '&auth=' + $('#auth').raw().value + '&amount=' + amount, function (response) {
			if(response == 'bankrupt') {
				error_message("You do not have sufficient upload credit to add " + get_size(amount) + " to this request");
				return;
			}
			if(response == 'dupe') {
				//No increment
			} else {

				votecount.innerHTML = (parseInt(votecount.innerHTML)) + 1;
			}

			if(!index) {
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
	if(($('#amount_box').raw().value * mul) > $('#current_uploaded').raw().value) {
		$('#new_uploaded').raw().innerHTML = "You can't afford that request!";
		$('#new_bounty').raw().innerHTML = "0.00 MB";
		$('#button').raw().disabled = true;
	} else if(isNaN($('#amount_box').raw().value)
			|| (window.location.search.indexOf('action=new') != -1 && $('#amount_box').raw().value*mul < 100*1024*1024)
			|| (window.location.search.indexOf('action=view') != -1 && $('#amount_box').raw().value*mul < 10*1024*1024)) {
		$('#new_uploaded').raw().innerHTML = get_size(($('#current_uploaded').raw().value));
		$('#new_bounty').raw().innerHTML = "0.00 MB";
		$('#button').raw().disabled = true;
	} else {
		$('#button').raw().disabled = false;
		$('#amount').raw().value = $('#amount_box').raw().value * mul;
		$('#new_uploaded').raw().innerHTML = get_size(($('#current_uploaded').raw().value) - (mul * $('#amount_box').raw().value));
		$('#new_ratio').raw().innerHTML = ratio($('#current_uploaded').raw().value - (mul * $('#amount_box').raw().value), $('#current_downloaded').raw().value);
		$('#new_bounty').raw().innerHTML = get_size(mul * $('#amount_box').raw().value);
	}
}

function AddArtistField() {
		var ArtistCount = document.getElementsByName("artists[]").length;
		if (ArtistCount >= 100) { return; }
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
		ImportanceField.options[2] = new Option("Remixer", "3");
		
		var x = $('#artistfields').raw();
		x.appendChild(document.createElement("br"));
		x.appendChild(ArtistField);
		x.appendChild(ImportanceField);
		ArtistCount++;
}

function RemoveArtistField() {
		var ArtistCount = document.getElementsByName("artists[]").length;
		if (ArtistCount == 1) { return; }
		var x = $('#artistfields').raw();
		
		while(x.lastChild.tagName != "INPUT") { 
			x.removeChild(x.lastChild); 
		}
		x.removeChild(x.lastChild); 
		ArtistCount--;
}

function Categories() {
	var cat = $('#categories').raw().options[$('#categories').raw().selectedIndex].value;
	if(cat == "Music") {
		$('#artist_tr').show();
		$('#releasetypes_tr').show();
		$('#formats_tr').show();
		$('#bitrates_tr').show();
		$('#media_tr').show();
		ToggleLogCue();
		$('#year_tr').show();
		$('#cataloguenumber_tr').show();
	} else if(cat == "Audiobooks" || cat == "Comedy") {
		$('#year_tr').show();
		$('#artist_tr').hide();
		$('#releasetypes_tr').hide();
		$('#formats_tr').hide();
		$('#bitrates_tr').hide();
		$('#media_tr').hide();
		$('#logcue_tr').hide();
		$('#cataloguenumber_tr').hide();
	} else {
		$('#artist_tr').hide();
		$('#releasetypes_tr').hide();
		$('#formats_tr').hide();
		$('#bitrates_tr').hide();
		$('#media_tr').hide();
		$('#logcue_tr').hide();
		$('#year_tr').hide();
		$('#cataloguenumber_tr').hide();
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
		if(disable == 1) {
			arr[x].disabled = master;
		}
	}
	
	if(id == "formats") {
		ToggleLogCue();
	}
}

function ToggleLogCue() {
	var formats = document.getElementsByName('formats[]');
	var flac = false;
	
	if(formats[1].checked) {
		flac = true;
	}
	
	if(flac) {
		$('#logcue_tr').show();
	} else {
		$('#logcue_tr').hide();
	}
	ToggleLogScore();
}

function ToggleLogScore() {
	if($('#needlog').raw().checked) {
		$('#minlogscore_span').show();
	} else {
		$('#minlogscore_span').hide();
	}
}
