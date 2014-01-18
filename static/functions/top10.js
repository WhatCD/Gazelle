$(document).ready(function() {
	if (jQuery().imagesLoaded && jQuery().wookmark) {
		$('.tiles').imagesLoaded(function() {
			$(".tiles img").each(function() {
				var size = getResized(this.width, this.height, 252, 400)
				$(this).width(size[0]);
				$(this).height(size[1]);
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

	function getResized(srcWidth, srcHeight, maxWidth, maxHeight) {
		var ratio = [maxWidth / srcWidth, maxHeight / srcHeight ];
		ratio = Math.min(ratio[0], ratio[1]);

		return {
			width: srcWidth * ratio,
			height: srcHeight * ratio
		};
	 }
});
