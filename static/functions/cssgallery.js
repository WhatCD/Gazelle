(function($) {
	$(document).ready(function () {
		// If the custom stylesheet field is empty, select the current style from the previews
		if (!$('input#styleurl').val()){
			$('input[name="stylesheet_gallery"][value="'+$('select#stylesheet').val()+'"]').click();
		}
		// If an overlay is clicked, select the right item in the dropdown and clear the custom CSS field
		$('div.preview_overlay').click(function() {
			var radiobutton = $(this).parent().find('input');
			radiobutton.prop('checked', true);
			$('select#stylesheet').val(radiobutton.attr('value'));
			$('input#styleurl').val('');
		})
		// If the input is clicked, redirect it to the overlay click event
		$('input[name="stylesheet_gallery"]').change(function() {
			$(this).parent().parent().find('div.preview_overlay').click();
		})
		// If the dropdown is changed, select the appropriate item in gallery, clear the custom CSS field
		$('select#stylesheet').change(function() {
			$('input[name="stylesheet_gallery"][value="'+$(this).val()+'"]').prop('checked', true);
			$('input#styleurl').val('');
		})
		// If the custom CSS field is changed, clear radio buttons
		$('input#styleurl').keydown(function() {
			$('input[name="stylesheet_gallery"]').each(function() {
				$(this).prop('checked', false);
			})
		})
		// If the input is empty, select appropriate gallery item again by the dropdown
		$('input#styleurl').keyup(function() {
			if (!$(this).val()){
				$('select#stylesheet').change();
			}
		})
		// Allow the CSS gallery to be expanded/contracted
		$('#toggle_css_gallery').click(function (e) {
			e.preventDefault();
			$('#css_gallery').slideToggle(function () {
				$('#toggle_css_gallery').text($(this).is(':visible') ? 'Hide gallery' : 'Show gallery');
			});
		});

	});
})(jQuery);
