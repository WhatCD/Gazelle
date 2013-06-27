(function () {
	var show_all = false;
	var current;
	$(document).ready(function() {
		show_all = $(".show_all_covers").text() == "Hide";
		$(".next_cover").click(function(e) {
			e.preventDefault();
			var next = $(this).data("gazelle-next-cover");
			$("#cover_controls_" + (next - 1)).hide();
			$("#cover_controls_" + next).show();
			$("#cover_div_" + (next - 1)).hide();
			$("#cover_div_" + next).show();
			if ($("#cover_" + next).attr("src").length == 0) {
				$("#cover_" + next).attr("src", $("#cover_" + next).data("gazelle-temp-src"));
			}

		});
		$(".prev_cover").click(function(e) {
			e.preventDefault();
			var prev = $(this).data("gazelle-prev-cover");
			$("#cover_controls_" + (prev + 1)).hide();
			$("#cover_controls_" + prev).show();
			$("#cover_div_" + (prev + 1)).hide();
			$("#cover_div_" + prev).show();
		});

		$(".show_all_covers").click(function(e) {
			e.preventDefault();
			if (!show_all) {
				current = $("#covers div:visible").attr("id");
				show_all = true;
				$(this).text("Hide");
				$("#covers img").each(function() {
					$(this).attr("src", $(this).data("gazelle-temp-src"));
				});
				$("#covers div").each(function() {
					$(this).show();
					$(this).after("<span class=\"cover_seperator\"><br /><hr /><br /></span>");
					$(".next_cover").hide();
					$(".prev_cover").hide();
				});
				$(".cover_seperator:last").remove();
			} else {
				show_all = false;
				$(this).text("Show all");
				$("#covers div").each(function() {
					if ($(this).attr("class") != "head") {
						if ($(this).attr("id") != current) {
							$(this).hide();
						}
						$(".next_cover").show();
						$(".prev_cover").show();
					}
				});
				$(".cover_seperator").remove();
			}

		});
	});
})();
