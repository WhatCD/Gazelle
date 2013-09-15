$(document).ready(function() {
	$(".forum_category").click(function(e) {
		var id = this.id;
		var isChecked = $(this).text() != "Check all";
		isChecked ? $(this).text("Check all") : $(this).text("Uncheck all");
		$("input[data-category='" + id + "']").attr("checked", !isChecked);
		e.preventDefault();
	});

	$("#type_body").click(function() {
		$("#post_created_row").gshow();
	});
	$("#type_title").click(function() {
		$("#post_created_row").ghide();
	});
});
