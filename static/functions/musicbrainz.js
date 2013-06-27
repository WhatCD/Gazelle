/**
 * Allows the user to populate upload form using MusicBrainz.
 * Popup display code based on example found here http://yensdesign.com/2008/09/how-to-create-a-stunning-and-smooth-popup-using-jquery/
 *
 * @author Gwindow
 *
 * TODO Center dynamically based on scroll position
 */


(function() {
//global variables
var $year_release_group;
var $release_type;
var $release_group_id;
var $tags;
var $year_release;
var $catalog_number;
var $record_label;
var $searched = false;
// controls popup state
var $popup_state = 0;
var $musicbrainz_state = 0;

$(document).ready(function() {
	loadCSS();
	enableMusicBrainzButton();
	controlPopup();

	$("#musicbrainz_button").click(function() {
		var $album = $("#title").val();
		var $artist = $("#artist").val();
		if ($artist.length > 0 || $album.length > 0) {
			jQuery('#results1').empty();
			jQuery('#results2').empty();
			jQuery('#results1').show();
			jQuery('#results2').show();
			$searched = true;
			var $artist_encoded = encodeURIComponent($artist);
			var $album_encoded = encodeURIComponent($album);
			$.ajax({
				type: "GET",
				url : "https://musicbrainz.org/ws/2/release-group/?query=artist:%22" + $artist_encoded + "%22%20AND%20releasegroup:%22" + $album_encoded + "%22",
				dataType: "xml",
				success: showReleaseGroups
			});

		} else {
			alert("Please fill out artist and/or album fields.");
		}
	});

	$("#results1").click(function(event) {
		var $id = event.target.id;
		if ($id != "results1") {
			jQuery('#results1').hide();
			jQuery('#results2').empty();
			jQuery('#results2').show();
			jQuery('#popup_back').empty();
			$.ajax({
				type: "GET",
				url: "https://musicbrainz.org/ws/2/release-group/" + $id + "?inc=artist-credits%2Breleases+tags+media",
				dataType: "xml",
				success: showReleases
			});
		}
	});

	$("#results2").click(function(event) {
		var $id = event.target.id;
		if ($id != "mb" && $id != "results2") {
			jQuery('#results2').empty();
			jQuery('#results2').show();

			$.ajax({
				type: "GET",
				url: "https://musicbrainz.org/ws/2/release/" + $id + "?inc=artist-credits%2Blabels%2Bdiscids%2Brecordings+tags+media+label-rels",
				dataType: "xml",
				success: populateForm
			});
		}
	});

	$("#popup_back").click(function(event) {
		var $id = event.target.id;
		if ($id == "back" ) {
		jQuery('#results2').hide();
		jQuery('#results1').show();
		jQuery('#popup_back').empty();
		jQuery('#popup_title').text("Choose Release Group");
		}
	});

	$("#remaster").click(function(event) {
		if ($("#remaster").attr("checked") && $searched == true) {
			populateEditionsForm();
		} else if ($searched == true) {
			depopulateEditionsForm();
		}

	});

});

/**
 * Shows the release groups
 * @param xml
 */
function showReleaseGroups(xml) {
	var $count = $(xml).find("release-group-list").attr("count");
	if ($count == 0 ) {
		alert("Could not find on MusicBrainz");
	} else {
		jQuery('#popup_title').text("Choose release group");
		openPopup();
	}

	$(xml).find("release-group").each(function() {
		var $title = $(this).find("title:first").text();
		var $artist = $(this).find("name:first").text();
		var $type = $(this).attr("type");
		var $score = $(this).attr("ext:score");
		var $releaseId = $(this).attr("id");
		var $result = $artist + " - " + $title + " [Type: " + $type + ", Score: " + $score + "]"
		$('<a href="#null">' + $result + "<p />").attr("id", $releaseId).appendTo("#results1");
	});
}

/**
 * Shows releases inside a release group
 * @param xml
 */
function showReleases(xml) {
	var $date_release_group = $(xml).find("first-release-date").text();
	$year_original = $date_release_group.substring(0,4);
	$release_type = $(xml).find("release-group").attr("type");
	$release_group_id = $(xml).find("release-group").attr("id");
	jQuery('#popup_title').html("Choose release " + '<a href="https://musicbrainz.org/release-group/'
			+ $release_group_id + '" target="_new" class="brackets">View on MusicBrainz</a>');
	jQuery('#popup_back').html('<a href="#null" id="back" class="brackets">Go back</a>');

	$(xml).find("release").each(function() {
		var $release_id = $(this).attr("id");
		var $title = $(this).find("title").text();
		var $status = $(this).find("status").text();
		var $date = $(this).find("date").text();
		var $year = $date.substring(0,4);
		var $country = $(this).find("country").text();
		var $format; var $tracks;
		$(this).find("medium-list").each(function() {
			$(this).find("medium").each(function() {
				$format = $(this).find("format").text();
				$(this).find("track-list").each(function() {
					$tracks = $(this).attr("count");
				});
			});
		});
		var $result = $title + " [Year: " + $year + ", Format: " + $format + ", Tracks: " + $tracks + ", Country: " + $country + "]";
		$('<a href="#null">' + $result + "</a>").attr("id", $release_id).appendTo("#results2");

		$('<a href="https://musicbrainz.org/release/' + $release_id +'" target="_new" class="brackets">View on MusicBrainz</a>' + "<p />").attr("id", "mb").appendTo("#results2");
	});

	parseTags(xml);
}

/**
 * Parses the tags to the gazelle conventions
 * @param xml
 */
function parseTags(xml) {
	$tags = "";
	$(xml).find("tag").each(function() {
		$tag = cleanTag($(this).find("name").text());
		if (isValidTag($tag)) {
			$tags += "," + $tag;
		}
	});
	if ($tags.charAt(0) == ',') {
		$tags = $tags.substring(1);
	}
}

function cleanTag($t) {
	$t = $t.replace(/ +(?= )/g,',');
	$t = $t.replace('-','.');
	$t = $t.replace(' ','.');
	return $t;
}

/**
 * Populates the upload form
 * @param xml
 */
function populateForm(xml) {
	closePopup();

	var $release_id = $(xml).find("release").attr("id");
	var $release_title = $(xml).find("release").find("title:first").text();
	var $artist = $(xml).find("artist-credit:first").find("name:first").text();
	var $date = $(xml).find("release").find("date").text();
	$year_release = $date.substring(0,4);
	var $country = $(xml).find("country").text();
	var $asin = $(xml).find("asin").text();
	var $barcode = $(xml).find("barcode").text();
	$catalog_number = $(xml).find("catalog-number").text();
	$record_label = $(xml).find("label").find("sort-name").text();
	var $track_count = $(xml).find("track-list").attr("count");
	var $track_titles = new Array();
	$(xml).find("track-list").find("title").each(function() {
		var $title = $(this).text();
		$track_titles.push($title);
	});

	clear();
	$("#artist").val($artist);
	$("#title").val($release_title);
	$("#year").val($year_original);
	$("#record_label").val($record_label);
	$("#catalogue_number").val($catalog_number);
	$("#tags").val($tags);
	$("#releasetype").val(getReleaseType());

	var $amazon_link = "";
	if ($asin.length > 0) {
		$amazon_link = "[url=http://www.amazon.com/exec/obidos/ASIN/" + $asin + "]Amazon[/url]" + "\n";
	}
	var $country_text = "";
	if ($country.length > 0) {
		$country_text = "Country: " + $country + "\n";
	}
	var $barcode_text = "";
	if ($barcode.length > 0) {
		$barcode_text = "Barcode: " + $barcode + "\n";
	}
	var $description = $amazon_link +
		"[url=https://musicbrainz.org/release-group/" + $release_group_id + "]MusicBrainz[/url]" + "\n" + "\n" +
			$country_text +
			$barcode_text +
			"Tracks: " + $track_count + "\n" + "\n" +
			"Track list:" + "\n";
	for (var i = 0; i < $track_titles.length; i++) {
		$description = $description + "[#]" + $track_titles[i] + "\n";
	};
	$("#album_desc").val($description);
}

function populateEditionsForm() {
	$('#remaster_true').show();
	$("#record_label").val("");
	$("#catalogue_number").val("");
	$("#remaster_year").val($year_release);
	$("#remaster_record_label").val($record_label);
	$("#remaster_catalogue_number").val($catalog_number);
}

function depopulateEditionsForm() {
	$("#record_label").val($record_label);
	$("#catalogue_number").val($catalog_number);
	$("#remaster_year").val("");
	$("#remaster_record_label").val("");
	$("#remaster_catalogue_number").val("");
}

function closeEditionsForm() {
	if ($("#remaster").attr("checked")) {
		$('#remaster_true').hide();
	}

	$("#remaster").attr("checked", false);
	$("#remaster_year").val("");
	$("#remaster_record_label").val("");
	$("#remaster_catalogue_number").val("");


}

/**
 * Gets the release type
 * @returns value of type
 */
function getReleaseType() {
	var $value;
	switch ($release_type) {
		case "Album":
			$value = 1;
			break;
		case "Soundtrack":
			$value = 3;
			break;
		case "EP":
			$value = 5;
			break;
		case "Compilation":
			$value = 7;
			break;
		case "Single":
			$value = 9;
			break;
		case "Live":
			$value = 11;
			break;
		case "Remix":
			$value = 13;
			break;
		case "Interview":
			$value = 15;
			break;
		default:
			$value = "---";
			break;
	}
	return $value;
}

/**
 * Enables the musicbrainz button only when the "Music" type is selected and a format isn't being uploaded
 */
function enableMusicBrainzButton() {
	if ($('#categories').is(':disabled') == false) {
		$("#categories").click(function() {
			if ($("#categories").val() != 0 ) {
				$("#musicbrainz_button").attr("disabled", "disabled");
			} else {
				$("#musicbrainz_button").removeAttr("disabled");
			}
		});
	} else {
		$("#musicbrainz_button").attr("disabled", "disabled");
	}
}

/**
 * Clears fields in the upload form
 */
function clear() {
	closeEditionsForm();
	$("#artist").val("");
	$("#title").val("");
	$("#year").val("");
	$("#record_label").val("");
	$("#catalogue_number").val("");
	$("#tags").val("");
	$("#releasetype").val("");
	$("#album_desc").val("");
	$("#remaster_year").val("");
	$("#remaster_record_label").val("");
	$("#remaster_catalogue_number").val("");
}

/**
 * Loads the popup
 * @returns
 */
function openPopup() {
	centerPopup();
	if ($popup_state == 0) {
		$("#popup_background").css({
			"opacity": "0.7"
		});
		$("#popup_background").fadeIn("fast");
		$("#musicbrainz_popup").fadeIn("fast");
		$popup_state = 1;
	}
}

/**
 * Closes the popup
 * @returns
 */
function closePopup() {
	if ($popup_state == 1) {
		$("#popup_background").fadeOut("fast");
		$("#musicbrainz_popup").fadeOut("fast");
		jQuery('#popup_back').html("");
		$popup_state = 0;
	}
}

/**
 * Centers the popup on the screen
 * @returns
 */
function centerPopup() {
	//TODO Center dynamically based on scroll position

	var windowWidth = document.documentElement.clientWidth;
	var windowHeight = document.documentElement.clientHeight;
	var popupHeight = $("#musicbrainz_popup").height();
	var scrollPosition = window.pageYOffset;
	var percentage = (scrollPosition/windowHeight) * 100;
	var popupWidth = $("#musicbrainz_popup").width();
	$("#musicbrainz_popup").css({
		"position": "absolute ! important",
		//"top": windowHeight/2-popupHeight/2,
		"left": windowWidth/2-popupWidth/2 + "! important"
	});

	$("#popup_background").css({
		"height": windowHeight
	});
}

/**
 * Controls the popup state based on user input
 * @returns
 */
function controlPopup() {
	$("#popup_close").click(function() {
		closePopup();
	});

	$(document).keypress(function(e) {
		if (e.keyCode == 27 && $popup_state == 1) {
			closePopup();
		}
	});
}

function loadCSS() {
	var $link = document.createElement('link')
	$link.href = 'static/styles/musicbrainz.css';
	$link.rel = 'stylesheet';
	$link.type = 'text/css';
	document.body.appendChild($link);
	$link = null;
}

})();
