function findRule() {
	var query_string = $('#search_string').val();
	var q = query_string.replace(/\s+/gm, '').split('+');
	var regex = new Array();
	for (var i = 0; i < q.length; i++) {
		regex[i] = new RegExp(q[i], 'mi');
	}
	$('#actual_rules li').each(function() {
		var show = true;
		for (var i = 0; i < regex.length; i++) {
			if (!regex[i].test($(this).html())) {
				show = false;
				break;
			}
		}
		$(this).toggle(show);
	});
	$('.before_rules').toggle(query_string.length == 0);
}

$(document).ready(function() {
		var original_value = $('#search_string').val();
		$('#search_string').keyup(findRule);
		$('#search_string').focus(function() {
			if ($(this).val() == original_value) {
				$(this).val('');
			}
		});
		$('#search_string').blur(function() {
			if ($(this).val() == '') {
				$(this).val(original_value);
				$('.before_rules').show();
			}
		})
});
