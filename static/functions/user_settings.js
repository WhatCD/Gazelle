var PUSHOVER = 5;
var TOASTY = 4;

$(document).ready(function() {
	var top = $('#settings_sections').offset().top - parseFloat($('#settings_sections').css('marginTop').replace(/auto/, 0));
	$(window).scroll(function (event) {
		var y = $(this).scrollTop();
		if (y >= top) {
			$('#settings_sections').addClass('fixed');
		} else {
			$('#settings_sections').removeClass('fixed');
		}
	});

	$("#settings_sections li").each(function(index) {
		$(this).click(function(e) {
			var id = $(this).data("gazelle-section-id");
			if (id) {
				e.preventDefault();
				if (id == "all_settings" || id == "live_search") {
					$("#userform table").show();
				} else {
					$("#userform table").hide();
					$("#" + id).show();
				}
			}
		});
	});

	$("#settings_search").on("keyup", function() {
		var search = $(this).val().toLowerCase();
		if ($.trim(search).length > 0) {
			$("#userform tr").not(".colhead_dark").each(function(index) {
				var text = $(this).find("td:first").text().toLowerCase();
				if (text.length > 0 && search.length > 0 && fuzzyMatch(text, search)) {
					$(this).show();
				}
				else {
					$(this).hide();
				}
			});
		} else {
			$("#userform tr").show();
		}
	});

	// I'm sure there is a better way to do this but this will do for now.
	$("#notifications_Inbox_traditional").click(function() {
		$("#notifications_Inbox_popup").prop('checked', false);
	});
	$("#notifications_Inbox_popup").click(function() {
		$("#notifications_Inbox_traditional").prop('checked', false);
	});
	$("#notifications_Torrents_traditional").click(function() {
		$("#notifications_Torrents_popup").prop('checked', false);
	});
	$("#notifications_Torrents_popup").click(function() {
		$("#notifications_Torrents_traditional").prop('checked', false);
	});

	if ($("#pushservice").val() > 0) {
		$('#pushsettings').show();
	}
	$("#pushservice").change(function() {
		if ($(this).val() > 0) {
			$('#pushsettings').show(500);

			if ($(this).val() == TOASTY) {
				$('#pushservice_title').text("Device ID");
			} else if ($(this).val() == PUSHOVER) {
				$('#pushservice_title').text("User Key");
			} else {
				$('#pushservice_title').text("API Key");
			}
		} else {
			$('#pushsettings').hide(500);
		}
	});
});

function fuzzyMatch(str, pattern){
	pattern = pattern.split("").reduce(function(a,b){ return a+".*"+b; });
	return new RegExp(pattern).test(str);
};
