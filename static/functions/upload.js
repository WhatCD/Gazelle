function Categories() {
	ajax.get('ajax.php?action=upload_section&categoryid=' + $('#categories').raw().value, function (response) {
		$('#dynamic_form').raw().innerHTML = response;
	});
}

function Remaster() {
	$('#remaster_true').toggle();
}

function Format() {
	if($('#format').raw().options[$('#format').raw().selectedIndex].value == 'FLAC') {
		for (var i = 0; i<$('#bitrate').raw().options.length; i++) {
			if($('#bitrate').raw().options[i].value == 'Lossless') {
				$('#bitrate').raw()[i].selected = true;
			}
		}
		$('#upload_logs').show();
		$('#other_bitrate_span').hide();
	} else {
		$('#bitrate').raw()[0].selected = true;
		$('#upload_logs').hide();
	}
}

function Bitrate() {
	$('#other_bitrate').raw().value = '';
	if($('#bitrate').raw().options[$('#bitrate').raw().selectedIndex].value == 'Other') {
		$('#other_bitrate_span').show();
	} else {
		$('#other_bitrate_span').hide();
	}
}

function AltBitrate() {
	if($('#other_bitrate').raw().value >= 320) {
		$('#vbr').raw().disabled = true;
		$('#vbr').raw().checked = false;
	} else {
		$('#vbr').raw().disabled = false;
	}
}

function add_tag() {
	if($('#tags').raw().value == "") {
		$('#tags').raw().value = $('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value;
	} else if($('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value == '---') {
	} else {
		$('#tags').raw().value = $('#tags').raw().value + ', ' + $('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value;
	}
}

var LogCount = 1;

function AddLogField() {
		if(LogCount >= 200) { return; }
		var LogField = document.createElement("input");
		LogField.type = "file";
		LogField.id = "file";
		LogField.name = "logfiles[]";
		LogField.size = 50;
		var x = $('#logfields').raw();
		x.appendChild(document.createElement("br"));
		x.appendChild(LogField);
		LogCount++;
}

function RemoveLogField() {
		if(LogCount == 1) { return; }
		var x = $('#logfields').raw();
		for (i=0; i<2; i++) { x.removeChild(x.lastChild); }
		LogCount--;
}

var FormatCount = 0;

function AddFormat() {
	if(FormatCount >= 10) { return; }
	FormatCount++;
	$('#extras').raw().value = FormatCount;
	
	var NewRow = document.createElement("tr");
	NewRow.id = "new_torrent_row"+FormatCount;
	NewRow.setAttribute("style","border-top-width: 5px; border-left-width: 5px; border-right-width: 5px;");
	
	var NewCell1 = document.createElement("td");
	NewCell1.setAttribute("class","label");
	NewCell1.innerHTML = "Extra Torrent File";
	
	var NewCell2 = document.createElement("td");
	var TorrentField = document.createElement("input");
	TorrentField.type = "file";
	TorrentField.id = "extra_torrent_file"+FormatCount;
	TorrentField.name = "extra_torrent_files[]";
	TorrentField.size = 50;
	NewCell2.appendChild(TorrentField);
	
	NewRow.appendChild(NewCell1);
	NewRow.appendChild(NewCell2);	

	var x = $('#tags_row').raw();
	x.parentNode.insertBefore(NewRow, x);
	
	NewRow = document.createElement("tr");
	NewRow.id = "new_format_row"+FormatCount;
	NewRow.setAttribute("style","border-left-width: 5px; border-right-width: 5px;");
	NewCell1 = document.createElement("td");
	NewCell1.setAttribute("class","label");
	NewCell1.innerHTML = "Extra Format / Bitrate";
	
	NewCell2 = document.createElement("td");
	tmp = '<select id="releasetype" name="extra_formats[]"><option value="">---</option>';
	
	for(var i in formats) {
		tmp += "<option value='"+formats[i]+"'>"+formats[i]+"</option>\n";
	}
	tmp += "</select>";
	
	tmp += '<select id="releasetype" name="extra_bitrates[]"><option value="">---</option>';
	for(var i in bitrates) {
		tmp += "<option value='"+bitrates[i]+"'>"+bitrates[i]+"</option>\n";
	}
	tmp += "</select>";
	
	NewCell2.innerHTML = tmp;
	NewRow.appendChild(NewCell1);
	NewRow.appendChild(NewCell2);	

	x = $('#tags_row').raw();
	x.parentNode.insertBefore(NewRow, x);
	
	NewRow = document.createElement("tr");
	NewRow.id = "new_description_row"+FormatCount;
	NewRow.setAttribute("style","border-bottom-width: 5px; border-left-width: 5px; border-right-width: 5px;");
	NewCell1 = document.createElement("td");
	NewCell1.setAttribute("class","label");
	NewCell1.innerHTML = "Extra Release Description";
	
	NewCell2 = document.createElement("td");
	NewCell2.innerHTML = '<textarea name="extra_release_desc[]" id="release_desc" cols="60" rows="4"></textarea>';
	
	NewRow.appendChild(NewCell1);
	NewRow.appendChild(NewCell2);	

	x = $('#tags_row').raw();
	x.parentNode.insertBefore(NewRow, x);
}

function RemoveFormat() {
	if(FormatCount == 0) { return; }
	$('#extras').raw().value = FormatCount;
	
	var x = $('#new_torrent_row'+FormatCount).raw();
	x.parentNode.removeChild(x);
	
	x = $('#new_format_row'+FormatCount).raw();
	x.parentNode.removeChild(x);
	
	x = $('#new_description_row'+FormatCount).raw();
	x.parentNode.removeChild(x);
	
	FormatCount--;
	
}

function Media() {
	if($('#media').raw().options[$('#media').raw().selectedIndex].text == 'Cassette') {
		$('#cassette_true').show();
	} else {
		$('#cassette_true').hide();
	}
}


var ArtistCount = 1;

function AddArtistField() {
	if(ArtistCount >= 100) { return; }
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
	if(ArtistCount == 1) { return; }
	var x = $('#artistfields').raw();
	for (i=0; i<3; i++) { x.removeChild(x.lastChild); }
	ArtistCount--;
}

function CheckVA() {
	var x = $('#artist').raw();
	if(x.value.toLowerCase() == 'various artists' || x.value.toLowerCase() == 'va' || x.value.toLowerCase() == 'various') {
		$('#vawarning').show();
	} else {
		$('#vawarning').hide();
	}
}

function CheckYear() {
	var media = $('#media').raw().options[$('#media').raw().selectedIndex].text;
	if(media == "Vinyl" || media == "Soundboard" || media == "Cassette") {
		media = "old";
	}
	var x = $('#year').raw();
	if(x.value < 1982 && x.value != '' && media != "old" && !$('#unknown').raw().checked) {
		$('#yearwarning').show();
		$('#remaster').raw().checked = true;
		$('#remaster_true').show();
	} else if($('#unknown').raw().checked) {
		$('#remaster').raw().checked = true;
		$('#yearwarning').hide();
		$('#remaster_true').show();
	} else {
		$('#yearwarning').hide();
	}
}

function ToggleUnknown() {
	if($('#unknown').raw().checked) {
		$('#remaster_year').raw().value = "";
		$('#remaster_title').raw().value = "";
		$('#remaster_record_label').raw().value = "";
		$('#remaster_catalogue_number').raw().value = "";
		
		if($('#groupremasters').raw()) {
			$('#groupremasters').raw().selectedIndex = 0;
			$('#groupremasters').raw().disabled = true;
		}
		
		$('#remaster_year').raw().disabled = true;
		$('#remaster_title').raw().disabled = true;
		$('#remaster_record_label').raw().disabled = true;
		$('#remaster_catalogue_number').raw().disabled = true;
	} else {
		$('#remaster_year').raw().disabled = false;
		$('#remaster_title').raw().disabled = false;
		$('#remaster_record_label').raw().disabled = false;
		$('#remaster_catalogue_number').raw().disabled = false;
		
		if($('#groupremasters').raw()) {
			$('#groupremasters').raw().disabled = false;
		}
	}
}

function GroupRemaster() {
	var remasters = json.decode($('#json_remasters').raw().value);
	
	var index = $('#groupremasters').raw().options[$('#groupremasters').raw().selectedIndex].value;
	if(index != "") {
		$('#remaster_year').raw().value = remasters[index][1];
		$('#remaster_title').raw().value = remasters[index][2];
		$('#remaster_record_label').raw().value = remasters[index][3];
		$('#remaster_catalogue_number').raw().value = remasters[index][4];
	}
}
