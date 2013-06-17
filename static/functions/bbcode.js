var BBCode = {
	spoiler: function(link) {
		if ($(link.nextSibling).has_class('hidden')) {
			$(link.nextSibling).gshow();
			$(link).html('Hide');
		} else {
			$(link.nextSibling).ghide();
			$(link).html('Show');
		}
	}
};
