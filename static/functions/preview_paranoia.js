(function() {
	// Used to get user ID from URL.
	function getURLParameter(name) {
		return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null;
	}
	// Attach click event on document ready.
	$(function(){
		$('a#preview_paranoia').click(function(event) {
			event.preventDefault();
			var paranoia = {};
			// Build an object of unchecked (hidden, not allowed for others to see) paranoias.
			// We "abuse" object keys to implement sets in JavaScript. This is simpler and
			// more memory efficient than building a string and working through that each time.
			$('input[name^="p_"]').each(function() {
				if (!$(this).prop('checked')) {
					var attribute = $(this).attr('name').replace(/^p_/,'');
					if (/_c$/.test(attribute)) {
						paranoia[attribute.replace(/_.$/,'') + '+'] = 1;
					} else if (/_l$/.test(attribute)) {
						if (typeof paranoia[attribute.replace(/_.$/,'') + '+'] == "undefined") {
							paranoia[attribute.replace(/_.$/,'')] = 1;
						}
					} else {
						paranoia[attribute] = 1;
					}
				}
			});
			// Build into a comma-delimited string.
			var paranoiaString = "";
			for (var key in paranoia) {
				if (key === 'length' || !paranoia.hasOwnProperty(key)) {
					continue;
				}
				paranoiaString += key+',';
			}
			// Get rid of trailing comma.
			paranoiaString = paranoiaString.substring(0, paranoiaString.length - 1);
			// Get user ID from URL parameter.
			var userId = getURLParameter("userid");
			// Open a new tab with specified paranoia settings.
			window.open('user.php?id=' + encodeURIComponent(userId) + '&preview=1&paranoia=' + encodeURIComponent(paranoiaString), '_blank');
		});
	});
})();
