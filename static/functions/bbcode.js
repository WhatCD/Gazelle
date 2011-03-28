var BBCode = {
	spoiler: function(link) {
		if($(link.nextSibling).has_class('hidden')) {
			$(link.nextSibling).show();
			$(link).html('Hide');
		} else {
			$(link.nextSibling).hide();
			$(link).html('Show');
		}
	}
};
