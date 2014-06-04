function Categories() {
	ajax.get('ajax.php?action=upload_section&categoryid=' + $('#categories').raw().value, function (response) {
		$('#dynamic_form').raw().innerHTML = response;
		initMultiButtons();
		// Evaluate the code that generates previews.
		eval($('#dynamic_form script.preview_code').html());
	});
}

function Remaster() {
	if ($('#remaster').raw().checked) {
		$('#remaster_true').gshow();
	} else {
		$('#remaster_true').ghide();
	}

}

function Format() {
	if ($('#format').raw().options[$('#format').raw().selectedIndex].value == 'FLAC') {
		for (var i = 0; i < $('#bitrate').raw().options.length; i++) {
			if ($('#bitrate').raw().options[i].value == 'Lossless') {
				$('#bitrate').raw()[i].selected = true;
			}
		}
		$('#upload_logs').gshow();
		$('#other_bitrate_span').ghide();
	} else {
		$('#bitrate').raw()[0].selected = true;
		$('#upload_logs').ghide();
	}

 	if ($('#format').raw().options[$('#format').raw().selectedIndex].value == 'AAC') {
		$('#format_warning').raw().innerHTML = 'AAC torrents may only be uploaded if they represent editions unavailable on What.CD in any other format sourced from the same medium and edition <a href="rules.php?p=upload#r2.1.24">(2.1.24)</a>';
	} else {
		$('#format_warning').raw().innerHTML = '';
	}
}

function Bitrate() {
	$('#other_bitrate').raw().value = '';
	if ($('#bitrate').raw().options[$('#bitrate').raw().selectedIndex].value == 'Other') {
		$('#other_bitrate_span').gshow();
	} else {
		$('#other_bitrate_span').ghide();
	}
}

function AltBitrate() {
	if ($('#other_bitrate').raw().value >= 320) {
		$('#vbr').raw().disabled = true;
		$('#vbr').raw().checked = false;
	} else {
		$('#vbr').raw().disabled = false;
	}
}

function add_tag() {
	if ($('#tags').raw().value == "") {
		$('#tags').raw().value = $('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value;
	} else if ($('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value == '---') {
	} else {
		$('#tags').raw().value = $('#tags').raw().value + ', ' + $('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value;
	}
}

var LogCount = 1;

function AddLogField() {
	if (LogCount >= 200) {
		return;
	}
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
	if (LogCount == 1) {
		return;
	}
	var x = $('#logfields').raw();
	for (i = 0; i < 2; i++) {
		x.removeChild(x.lastChild);
	}
	LogCount--;
}

var ExtraLogCount = 1;

function AddExtraLogField(id) {
	if (LogCount >= 200) {
		return;
	}
	var LogField = document.createElement("input");
	LogField.type = "file";
	LogField.id = "file_" + id;
	LogField.name = "logfiles_" + id + "[]";
	LogField.size = 50;
	var x = $('#logfields_' + id).raw();
	x.appendChild(document.createElement("br"));
	x.appendChild(LogField);
	LogCount++;
}

function RemoveLogField() {
	if (LogCount == 1) {
		return;
	}
	var x = $('#logfields').raw();
	for (i = 0; i < 2; i++) {
		x.removeChild(x.lastChild);
	}
	LogCount--;
}

var FormatCount = 0;

function AddFormat() {
	if (FormatCount >= 10) {
		return;
	}
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

	NewRow = document.createElement("tr");
	NewRow.id = "new_format_row"+FormatCount;
	NewRow.setAttribute("style","border-left-width: 5px; border-right-width: 5px;");
	NewCell1 = document.createElement("td");
	NewCell1.setAttribute("class","label");
	NewCell1.innerHTML = "Extra Format / Bitrate";

	NewCell2 = document.createElement("td");
	tmp = '<select id="releasetype" name="extra_formats[]"><option value="">---</option>';
	var formats=["Saab","Volvo","BMW"];
	for (var i in formats) {
		tmp += '<option value="'+formats[i]+'">'+formats[i]+"</option>\n";
	}
	tmp += "</select>";
	var bitrates=["1","2","3"];
	tmp += '<select id="releasetype" name="extra_bitrates[]"><option value="">---</option>';
	for (var i in bitrates) {
		tmp += '<option value="'+bitrates[i]+'">'+bitrates[i]+"</option>\n";
	}
	tmp += "</select>";

	NewCell2.innerHTML = tmp;
	NewRow.appendChild(NewCell1);
	NewRow.appendChild(NewCell2);


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
}

function RemoveFormat() {
	if (FormatCount == 0) {
		return;
	}
	$('#extras').raw().value = FormatCount;

	var x = $('#new_torrent_row'+FormatCount).raw();
	x.parentNode.removeChild(x);

	x = $('#new_format_row'+FormatCount).raw();
	x.parentNode.removeChild(x);

	x = $('#new_description_row'+FormatCount).raw();
	x.parentNode.removeChild(x);

	FormatCount--;
}


var ArtistCount = 1;

function AddArtistField() {
	if (ArtistCount >= 200) {
		return;
	}
	var ArtistField = document.createElement("input");
	ArtistField.type = "text";
	ArtistField.id = "artist_" + ArtistCount;
	ArtistField.name = "artists[]";
	ArtistField.size = 45;

	var ImportanceField = document.createElement("select");
	ImportanceField.id = "importance";
	ImportanceField.name = "importance[]";
	ImportanceField.options[0] = new Option("Main", "1");
	ImportanceField.options[1] = new Option("Guest", "2");
	ImportanceField.options[2] = new Option("Composer	", "4");
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
	if (ArtistCount == 1) {
		return;
	}
	var x = $('#artistfields').raw();
	for (i = 0; i < 4; i++) {
		x.removeChild(x.lastChild);
	}
	ArtistCount--;
}


function CheckVA () {
	if ($('#artist').raw().value.toLowerCase().trim().match(/^(va|various(\sa|a)rtis(t|ts)|various)$/)) {
		$('#vawarning').gshow();
	} else {
		$('#vawarning').ghide();
	}
}

function CheckYear() {
	var media = $('#media').raw().options[$('#media').raw().selectedIndex].text;
	if (media == "---" || media == "Vinyl" || media == "Soundboard" || media == "Cassette") {
		media = "old";
	}
	var year = $('#year').val();
	var unknown = $('#unknown').prop('checked');
	if (year < 1982 && year != '' && media != "old" && !unknown) {
		$('#yearwarning').gshow();
		$('#remaster').raw().checked = true;
		$('#remaster_true').gshow();
	} else if (unknown) {
		$('#remaster').raw().checked = true;
		$('#yearwarning').ghide();
		$('#remaster_true').gshow();
	} else {
		$('#yearwarning').ghide();
	}
}

function ToggleUnknown() {
	if ($('#unknown').raw().checked) {
		$('#remaster_year').raw().value = "";
		$('#remaster_title').raw().value = "";
		$('#remaster_record_label').raw().value = "";
		$('#remaster_catalogue_number').raw().value = "";

		if ($('#groupremasters').raw()) {
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

		if ($('#groupremasters').raw()) {
			$('#groupremasters').raw().disabled = false;
		}
	}
}

function GroupRemaster() {
	var remasters = json.decode($('#json_remasters').raw().value);
	var index = $('#groupremasters').raw().options[$('#groupremasters').raw().selectedIndex].value;
	if (index != "") {
		$('#remaster_year').raw().value = remasters[index][1];
		$('#remaster_title').raw().value = remasters[index][2];
		$('#remaster_record_label').raw().value = remasters[index][3];
		$('#remaster_catalogue_number').raw().value = remasters[index][4];
	}
}
