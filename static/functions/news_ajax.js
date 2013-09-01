function news_ajax(event, count, offset, privileged) {
	/*
	 * event - The click event, passed to hide the element when necessary.
	 * count - Number of news items to fetch.
	 * offset - Database offset for fetching news.
	 * privilege - Gotta check your privilege (used to show/hide [Edit] on news).
	 */
	// Unbind onclick to avoid spamclicks.
	$(event.target).attr('onclick', 'return false;');
	// Fetch news data, check for errors etc.
	$.get("ajax.php", {
		action: "news_ajax",
		count: count,
		offset: offset
	})
	.done(function(data) {
		var response = $.parseJSON(data.response);
		if (typeof data == 'undefined' || data == null || data.status != "success" || typeof response == 'undefined' || response == null) {
			console.log("ERR ajax_news(" + (new Error).lineNumber + "): Unknown data or failure returned.");
			// Return to original paremeters, no news were added.
			$(event.target).attr('onclick', 'news_ajax(event, ' + count + ', ' + offset + ', ' + privileged + '); return false;');
		} else {
			if (response.length == 0) {
				$(event.target).parent().remove();
			} else {
				var targetClass = $('#more_news').prev().attr('class');
				$.each(response, function() {
					// Create a new element, insert the news.
					$('#more_news').before($('<div/>', {
						id: 'news' + this[0],
						Class: targetClass
					}));
					// I'm so happy with this condition statement.
					if (privileged) {
						$('#news' + this[0]).append('<div class="head"><strong>' + this[1] + '</strong> ' + this[2] + ' - <a href="tools.php?action=editnews&amp;id=' + this[0] + '" class="brackets">Edit</a><span style="float: right;"><a class="brackets" onclick="$(\'#newsbody' + this[0] + '\').gtoggle(); this.innerHTML=(this.innerHTML == \'Hide\' ? \'Show\' : \'Hide\'); return false;" href="#">Hide</a></span></div>');
					} else {
						$('#news' + this[0]).append('<div class="head"><strong>' + this[1] + '</strong> ' + this[2] + '<span style="float: right;"><a class="brackets" onclick="$(\'#newsbody' + this[0] + '\').gtoggle(); this.innerHTML=(this.innerHTML == \'Hide\' ? \'Show\' : \'Hide\'); return false;" href="#">Hide</a></span></div>');
					}
					$('#news' + this[0]).append('<div class="pad" id="newsbody'+this[0]+'">' + this[3] + '</div>');
				});
				// Update the onclick parameters to appropriate offset.
				$(event.target).attr('onclick', 'news_ajax(event, ' + count + ', ' + (count + offset) + ', ' + privileged + '); return false;');
			}
		}
	})
	.fail(function() {
		console.log("WARN ajax_news(" + (new Error).lineNumber + "): AJAX get failed.");
		// Return to original paremeters, no news were added.
		$(event.target).attr('onclick', 'news_ajax(event, ' + count + ', ' + offset + ', ' + privileged + '); return false;');
	});
}
