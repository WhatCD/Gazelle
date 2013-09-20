$(document).ready(function() {
	var month = $("#month").val();
	var year = $("#year").val();
	$(".event_day, .day-number").click(function(e) {
		e.preventDefault();
		var id = $(this).data("gazelle-id");
		if ($(this).hasClass("day-number")) {
			var day = $(this).text().trim();
		} else {
			var day = $(this).parent().next().text().trim();
		}
		$.ajax({
			type : "GET",
			dataType : "html",
			url : "tools.php?action=get_calendar_event",
			data : {
				"id" : id,
				"day" : day,
				"month": month,
				"year": year
			}
		}).done(function(response) {
			$("#event_div").html(response);
			$("#event_form").validate();
		});
	});
});