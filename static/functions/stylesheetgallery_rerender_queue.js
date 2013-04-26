(function ($){
	
	// Work through the queue of stylesheets
	function recursivelyProcessQueue(queue){
		// If our work here is done, call it a day.
		if(queue.length < 1) return 0;
		var nextTarget = queue.pop();
		var originalSrc = nextTarget.attr('src');
		nextTarget.attr('src', nextTarget.attr('data-src'));
		nextTarget.load(function() {
			var targetHtml = $(this).contents().find("html");
			targetHtml.on('click', function() {
				targetHtml.unbind();
				recursivelyProcessQueue(queue);
				// Avoid unnecessary caching, cahce might lead to undefined behavior.
				nextTarget.attr('src', '#');
				nextTarget.addClass('finished');
				return 0;
			})
		});
		// Be sure to close off.
		return 0;
	}
		
	$(document).ready(function (){
		// Build a queue of stylesheets to be rendered
		var queue = [];
		$('.statusbutton').children('iframe').each(function() {
			var targetDiv = $(this);
			queue.push(targetDiv);
		});
		// We'd prefer to work from top-down, not bottom-up.
		queue = queue.reverse();
		$('#start_rerendering').click(function(event) {
			event.preventDefault();
			recursivelyProcessQueue(queue);
			$(this).css('text-decoration', 'line-through');
			$(this).unbind();
			return false;
		});
	});
})(jQuery);
