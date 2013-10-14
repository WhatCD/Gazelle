$(document).ready(function() {
	var open_responses =
	$(".answer_link").click(function(e) {
		e.preventDefault();
		id = this.id;
		$("#answer" + id).gtoggle();
	});

	$(".submit_button").click(function(e) {
		id = this.id;
		$.ajax({
			type : "POST",
			url : "questions.php?action=take_answer_question",
			data : {
				"auth" : authkey,
				"id" : id,
				"answer" : $("#replybox_" + id).val()
			}
		}).done(function() {
			$("#question" + id).remove();
			$("#answer" + id).remove();
			$("#responses_for_" + id).remove();
		});
	});

	$(".view_responses").click(function(e) {
		e.preventDefault();
		id = this.id;
		if ($("#responses_for_" + id).length == 0) {
			$.ajax({
				type : "POST",
				url : "questions.php?action=ajax_get_answers",
				dataType : "html",
				data : {
					"id" : id,
					"userid" : $(this).data("gazelle-userid")
				}
			}).done(function(response) {
				$("#question" + id).after(response);
			});
		} else {
			$("#responses_for_" + id).remove();
		}
	});
});

