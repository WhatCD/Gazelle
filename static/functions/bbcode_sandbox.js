$(document).ready(function() {
	$("#sandbox").keyup(function() {
		$.ajax({
			type : "POST",
			dataType : "html",
			url : "ajax.php?action=preview",
			data : {
				"body" : $(this).val()
			}
		}).done(function(response) {
			$("#preview").html(response);
		});
	});
});