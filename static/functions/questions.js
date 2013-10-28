$(document).ready(function() {
	$(".answer_link").click(function(e) {
		e.preventDefault();
		var id = this.id;
		$("#answer" + id).gtoggle();
	});

	$(".submit_button").click(function(e) {
		var id = this.id;
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
		var id = this.id;
		var respDiv = $("#responses_for_" + id);
		if (respDiv.length == 0) {
			respDiv = $('<div id="responses_for_' + id + '" style="display: none; margin-left: 20px;"></div>');
			$("#question" + id).after(respDiv);
			$.ajax({
				type : "GET",
				url : "questions.php?action=ajax_get_answers",
				dataType : "html",
				data : {
					"id" : id,
					"userid" : $(this).data("gazelle-userid")
				}
			}).done(function(response) {
				respDiv.html(response).show();
			});
		} else {
			respDiv.toggle();
		}
	});

	$(".ignore_link").click(function(e) {
		e.preventDefault();
		var id = this.id;
		$.ajax({
			type : "POST",
			url : "questions.php?action=take_ignore_question",
			data : {
				"auth" : authkey,
				"id" : id
			}
		}).done(function() {
			$("#question" + id).remove();
			$("#answer" + id).remove();
			$("#responses_for_" + id).remove();
		});
	});
});

