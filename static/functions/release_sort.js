//Couldn't use an associative array because JavaScript sorting is stupid http://dev-answers.blogspot.com/2012/03/javascript-object-keys-being-sorted-in.html

$(document).ready(function() {
	var serialize = function () {
		var a = [];
		$('#sortable input').each(function () {
			a.push($(this).attr('id'));
		});
		$('#sorthide').val(JSON.stringify(a));
	};

	serialize();

	$('#sortable')
		.on('click', 'input', function () {
			// the + converts the boolean to either 1 or 0
			var c = +$(this).is(':checked'),
				old_id = $(this).attr('id'),
				new_id = old_id.slice(0, -1) + c;
			$(this).attr('id', new_id);
			serialize();
		})
		.sortable({
			placeholder: 'ui-state-highlight',
			update: serialize
		});

	$('#toggle_sortable').click(function (e) {
		e.preventDefault();
		$('#sortable_container').slideToggle(function () {
			$('#toggle_sortable').text($(this).is(':visible') ? 'Collapse' : 'Expand');
		});
	});

	$('#reset_sortable').click(function (e) {
		e.preventDefault();
		$('#sortable').html(sortable_list_default); // var sortable_list_default is found on edit.php
		serialize();
	});
});
