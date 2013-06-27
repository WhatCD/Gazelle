(function () {
	$(document).ready(function () {
		// Upload button is clicked
		$("#post").click(function(e) {
			// Make sure "Music" category is selected.
			if ($("#categories").find(":selected").val() == 0) {
				checkHasMainArtist(e);
			}
		});

		/**
		 * Make sure a main artist is selected.
		 */
		function checkHasMainArtist(e) {
			var has_main = false;
			$("select[id^=importance]").each(function() {
				if ($(this).find(":selected").val() == 1) {
					has_main = true;
				}
			});
			if (!has_main) {
				alert('A "Main" artist is required');
				// Don't POST the form.
				e.preventDefault();
			}
		}
	});
})();
