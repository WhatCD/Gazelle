function toggleNotes(id) {
	var style = $('#notes_div_' + id).raw().style.display;
	if (style == "none") {
		$('#notes_div_' + id).raw().style.display = "block";
	}
	else {
		$('#notes_div_' + id).raw().style.display = "none";
	}
}

function saveNotes(id) {
	var notes = $('#notes_' + id).raw().value;
	notes = notes.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '<br />');
	ajax.get('reports.php?action=add_notes&id=' + id + '&notes=' + notes, function (response) {
		if (JSON.parse(response)['status'] != 'success') {
			alert("Error, could not save notes");
		}
	});

}

function claim(id) {
	ajax.get('reports.php?action=claim&id=' + id, function (response) {
		var json = JSON.parse(response);
		if (json['status'] == 'failure') {
			alert("Error, could not claim.");
		}
		if (json['status'] == 'dupe') {
			alert("Oops, this report has already been claimed.");
		}
		if (json['status'] == 'success') {
			var username = json['username'];
			$('#claim_' + id).raw().innerHTML = '<a href="#" onclick="return false;">Claimed by ' + username + '</a>';
		}
	});
}

function unClaim(id) {
	ajax.get('reports.php?action=unclaim&remove=1&id=' + id, function (response) {
		var json = JSON.parse(response);
		if (json['status'] == 'success') {
			$('#claimed_' + id).raw().innerHTML = '<a href="#" id="claim_' + id + '" onclick="claim(' + id + '); return false;"; return false;">Claim</a>';
		}
	});
}

function resolve(id, claimer) {
	var answer = true;
	if (!claimer) {
		if ($('#claimed_' + id).raw()) {
			var answer = confirm("This is a claimed report, are you sure you want to resolve it?");
			if (answer)
				answer = true;
			else
				answer = false;
		}
	}
	if (answer) {
		ajax.post('reports.php?action=resolve', 'report_form_' + id, function (response) {
				var json = JSON.parse(response);
				if (json['status'] == 'success') {
					$('#report_' + id).remove();
				} else {
					alert(json['status']);
				}
			}
		);
	}
	return false;
}
