$(document).ready(function() {
	$(".run").click(function(e) {
		e.preventDefault();
		var id = $(this).data("gazelle-id");
		var className = $(this).data("gazelle-class");
		var methodName = $(this).data("gazelle-method");

		var inputs = $("#method_params_" + id + " input");

		var params = {};
		$.each(inputs, function() {
			params[this.name] = $(this).val();
		});

		$("#method_results_" + id).hide();
		$.ajax({
			type : "POST",
			url : "testing.php?action=ajax_run_method",
			data : {
				"auth": authkey,
				"class": className,
				"method": methodName,
				"params" :  JSON.stringify(params)
			}
		}).done(function(response) {
			$("#method_results_" + id).html(response);
			$("#method_results_" + id).show();
		});

	});
});