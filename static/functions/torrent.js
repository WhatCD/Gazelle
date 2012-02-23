function ChangeCategory(catid) {
	if(catid == 1) {
		$('#split_releasetype').show();
		$('#split_artist').show();
		$('#split_year').show();
	} else if(catid == 4 || catid == 6) {
		$('#split_releasetype').hide();
		$('#split_year').show();
		$('#split_artist').hide();
	} else {
		$('#split_releasetype').hide();
		$('#split_artist').hide();
		$('#split_year').hide();
	}
}

function ArtistManager() {
	var GroupID = window.location.search.match(/[?&]id=(\d+)/);
	if(typeof GroupID == 'undefined') {
		return;
	} else {
		GroupID = GroupID[1];
	}
	var ArtistList;
	if(!(ArtistList = $('#artist_list').raw())) {
		return false;
	} else if($('#artistmanager').raw()) {
		$('#artistmanager').toggle();
		$('#artist_list').toggle();
	} else {
		MainArtistCount = 0;
		var elArtistManager = document.createElement('div');
		elArtistManager.id = 'artistmanager';

		var elArtistList = ArtistList.cloneNode(true);
		elArtistList.id = 'artistmanager_list';
		for(var i=0; i<elArtistList.children.length; i++) {
			switch (elArtistList.children[i].className) {
				case 'artist_main':
					importance = 1;
					break;
				case 'artists_with':
					importance = 2;
					break;
				case 'artists_remix':
					importance = 3;
					break;
				case 'artists_composers':
					importance = 4;
					break;
				case 'artists_conductors':
					importance = 5;
					break;
				case 'artists_dj':
					importance = 6;
					break;
				case 'artists_producer':
					importance = 7;
					break;
			}
			if(elArtistList.children[i].children[0].tagName.toUpperCase() == 'A') {
				var ArtistID = elArtistList.children[i].children[0].href.match(/[?&]id=(\d+)/)[1];
				var elBox = document.createElement('input');
				elBox.type = 'checkbox';
				elBox.id = 'artistmanager_box'+(i-importance+1);
				elBox.name = 'artistmanager_box';
				elBox.value = importance+';'+ArtistID;
				elBox.onclick = function(e) { SelectArtist(e,this); };
				elArtistList.children[i].insertBefore(elBox, elArtistList.children[i].children[0]);
				if(importance == 1 || importance == 4 || importance == 6) {
					MainArtistCount++;
				}
			}
		}
		elArtistManager.appendChild(elArtistList);

		var elArtistForm = document.createElement('form');
		elArtistForm.id = 'artistmanager_form';
		elArtistForm.method = 'post';
		var elGroupID = document.createElement('input');
		elGroupID.type = 'hidden';
		elGroupID.name = 'groupid';
		elGroupID.value = GroupID;
		elArtistForm.appendChild(elGroupID);
		var elAction = document.createElement('input');
		elAction.type = 'hidden';
		elAction.name = 'manager_action';
		elAction.id = 'manager_action';
		elAction.value = 'manage';
		elArtistForm.appendChild(elAction);
		var elAction = document.createElement('input');
		elAction.type = 'hidden';
		elAction.name = 'action';
		elAction.value = 'manage_artists';
		elArtistForm.appendChild(elAction);
		var elAuth = document.createElement('input');
		elAuth.type = 'hidden';
		elAuth.name = 'auth';
		elAuth.value = authkey;
		elArtistForm.appendChild(elAuth);
		var elSelection = document.createElement('input');
		elSelection.type = 'hidden';
		elSelection.id = 'artists_selection';
		elSelection.name = 'artists';
		elArtistForm.appendChild(elSelection);

		var elSubmitDiv = document.createElement('div');
		var elImportance = document.createElement('select');
		elImportance.name = 'importance';
		elImportance.id = 'artists_importance';
		var elOpt = document.createElement('option');
		elOpt.value = 1;
		elOpt.innerHTML = 'Main artist';
		elImportance.appendChild(elOpt);
		elOpt = document.createElement('option');
		elOpt.value = 2;
		elOpt.innerHTML = 'Guest artist';
		elImportance.appendChild(elOpt);
		elOpt = document.createElement('option');
		elOpt.value = 4;
		elOpt.innerHTML = 'Composer';
		elImportance.appendChild(elOpt);
		elOpt = document.createElement('option');
		elOpt.value = 5;
		elOpt.innerHTML = 'Conductor';
		elImportance.appendChild(elOpt);
		elOpt = document.createElement('option');
		elOpt.value = 6;
		elOpt.innerHTML = 'DJ / Compiler';
		elImportance.appendChild(elOpt);
		elOpt = document.createElement('option');
		elOpt.value = 3;
		elOpt.innerHTML = 'Remixer';
		elImportance.appendChild(elOpt);
		elOpt = document.createElement('option');
		elOpt.value = 7;
		elOpt.innerHTML = 'Producer';
		elImportance.appendChild(elOpt);
		elSubmitDiv.appendChild(elImportance);
		elSubmitDiv.appendChild(document.createTextNode(' '));

		elSubmitDiv.className = 'body';
		var elSubmit = document.createElement('input');
		elSubmit.type = 'button';
		elSubmit.value = 'Update';
		elSubmit.onclick = ArtistManagerSubmit;
		elSubmitDiv.appendChild(elSubmit);
		elSubmitDiv.appendChild(document.createTextNode(' '));

		var elDelButton = document.createElement('input');
		elDelButton.type = 'button';
		elDelButton.value = 'Delete';
		elDelButton.onclick = ArtistManagerDelete;
		elSubmitDiv.appendChild(elDelButton);

		elArtistForm.appendChild(elSubmitDiv);
		elArtistManager.appendChild(elArtistForm);
		ArtistList.parentNode.appendChild(elArtistManager);
		$('#artist_list').hide();
	}
}

function SelectArtist(e,obj) {
	if(window.event) {
		e = window.event;
	}
	EndBox = Number(obj.id.substr(17));
	if(!e.shiftKey || typeof StartBox == 'undefined') {
		StartBox = Number(obj.id.substr(17));
	}
	Dir = (EndBox > StartBox ? 1 : -1);
	var checked = obj.checked;
	for(var i = StartBox; i != EndBox; i += Dir) {
		var key, importance = obj.value.substr(0,1), id = obj.value.substr(2);
		$('#artistmanager_box'+i).raw().checked = checked;
	}
	StartBox = Number(obj.id.substr(17));
}

function ArtistManagerSubmit() {
	var Selection = new Array();
	var MainSelectionCount = 0;
	for(var i = 0, boxes = $('[name="artistmanager_box"]'); boxes.raw(i); i++) {
		if(boxes.raw(i).checked) {
			Selection.push(boxes.raw(i).value);
			if(boxes.raw(i).value.substr(0,1) == '1') {
				MainSelectionCount++;
			}
		}
	}
	if(Selection.length == 0 || ($('#manager_action').raw().value == 'delete' && !confirm('Are you sure you want to delete '+Selection.length+' artists from this group?'))) {
		return;
	}
	$('#artists_selection').raw().value = Selection.join(',');
	if((($('#artists_importance').raw().value != 1 && $('#artists_importance').raw().value != 4 && $('#artists_importance').raw().value != 6) || $('#manager_action').raw().value == 'delete') && MainSelectionCount == MainArtistCount) {
		if(!$('.error_message').raw()) {
			error_message('All groups need to have at least one main artist, composer, or DJ.');
		}
		$('.error_message').raw().scrollIntoView();
		return;
	}
	$('#artistmanager_form').raw().submit();
}

function ArtistManagerDelete() {
	$('#manager_action').raw().value = 'delete';
	ArtistManagerSubmit();
	$('#manager_action').raw().value = 'manage';
}

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
		bounty = $('#bounty_' + requestid).raw();
		index = true;
	}
	
	ajax.get('requests.php?action=takevote&id=' + requestid + '&auth=' + $('#auth').raw().value + '&amount=' + amount, function (response) {
			if(response == 'bankrupt') {
				error_message("You do not have sufficient upload credit to add " + get_size(amount) + " to this request");
				return;
			} else if (response == 'dupesuccess') {
				//No increment
			} else if (response == 'success') {
				votecount.innerHTML = (parseInt(votecount.innerHTML)) + 1;
			}

			if($('#total_bounty').results() > 0) {
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