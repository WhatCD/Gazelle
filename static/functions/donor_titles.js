$(document).ready(function() {
	if ($('#donor_title_prefix_preview').size() === 0) {
		return;
	}
	$('#donor_title_prefix_preview').text($('#donor_title_prefix').val().trim() + ' ');
	$('#donor_title_suffix_preview').text(' ' + $('#donor_title_suffix').val().trim());

	if ($('#donor_title_comma').attr('checked')) {
		$('#donor_title_comma_preview').text('');
	} else {
		$('#donor_title_comma_preview').text(', ');
	}

	$('#donor_title_prefix').keyup(function() {
		if ($(this).val().length <= 30) {
			$('#donor_title_prefix_preview').text($(this).val().trim() + ' ');
		}
	});

	$('#donor_title_suffix').keyup(function() {
		if ($(this).val().length <= 30) {
			$('#donor_title_suffix_preview').text(' ' + $(this).val().trim());
		}
	});

	$('#donor_title_comma').change(function() {
		if ($(this).attr('checked')) {
			$('#donor_title_comma_preview').text('');
		} else {
			$('#donor_title_comma_preview').text(', ');
		}
	});
});
