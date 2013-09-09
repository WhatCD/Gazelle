$(document).ready(function() {
	if (jQuery().imagesLoaded && jQuery().wookmark) {
		$('.tiles').imagesLoaded(function() {
			$(".tiles img").each(function() {
				$(this).height(this.height);
			});

			// Prepare layout options.
			var options = {
				container: $('.tiles_container'), // Optional, used for some extra CSS styling
				offset: 5, // Optional, the distance between grid items
				outerOffset: 10, // Optional, the distance to the containers border
				align: 'center',
			};

			// Get a reference to your grid items.
			var handler = $('.tiles li');

			// Call the layout function.
			handler.wookmark(options);
		});
	}
});