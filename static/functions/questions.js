$(document).ready(function() {
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
		});
	});
});

