(function() {
	var sent = new Array();
	var loaded = false;
	var type;
	var id;
	$(document).ready(function() {
		type = $("#recommendation_div").data('type');
		id = $("#recommendation_div").data('id');
		$("#recommend").click(function() {
			$("#recommendation_div").slideToggle(150);
			if (!loaded) {
				$("#recommendation_status").html("Loading...");
				$.ajax({
					type : "POST",
					url : "ajax.php?action=get_friends",
					dataType : "json",
					success : function(response) {
						$.each(response, function(key, value) {
							var id = value['FriendID'];
							var friend = value['Username'];
							$("#friend").append($("<option></option>").attr("value", id).text(friend));
						});
						loaded = true;
						$("#recommendation_status").html("<br />");
					}
				});
			}
		});
		$("#friend").change(function() {
			var friend = $("select#friend").val();
			if (friend == 0) {
				$("#send_recommendation").attr("disabled", "disabled");
			} else if ($.inArray(friend, sent) == -1) {
				$("#send_recommendation").removeAttr("disabled");
			}
			$("#recommendation_status").html("<br />");
		});

		$("#send_recommendation").click(function() {
			send_recommendation();
		});
		$("#recommendation_note").keypress(function(e) {
			state = $("#send_recommendation").attr("disabled");
			if (typeof state === 'undefined' && e.keyCode == 13) {
				e.preventDefault();
				send_recommendation();
			}
		});
	});
	function send_recommendation() {
		var friend = $("select#friend").val();
		var note = $("#recommendation_note").val();
		if (friend != 0) {
			$.ajax({
				type : "POST",
				dataType : "json",
				url : "ajax.php?action=send_recommendation",
				data : {
					"friend" : friend,
					"note" : note,
					"type" : type,
					"id" : id
				}
			}).done(function(response) {
				$("#recommendation_status").html("<strong>" + response['response'] + "</strong>");
				$("#send_recommendation").attr("disabled", "disabled");
				$("#recommendation_note").val("");
				sent.push(friend);
			});
		}
	}
})();
