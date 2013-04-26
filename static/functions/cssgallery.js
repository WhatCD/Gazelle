(function($) {
	/*! jQuery Ajax Queue v0.1.2pre | (c) 2013 Corey Frang | Licensed MIT */
	(function(e){var r=e({});e.ajaxQueue=function(n){function t(r){u=e.ajax(n),u.done(a.resolve).fail(a.reject).then(r,r)}var u,a=e.Deferred(),i=a.promise();return r.queue(t),i.abort=function(o){if(u)return u.abort(o);var c=r.queue(),f=e.inArray(t,c);return f>-1&&c.splice(f,1),a.rejectWith(n.context||n,[i,o,""]),i},i}})(jQuery);
	//@ sourceMappingURL=dist/jquery.ajaxQueue.min.map

	$(document).ready(function () {
		// If the custom stylesheet field is empty, select the current style from the previews
		if(!$('input#styleurl').val()){
			var radiobutton = $('input[name="stylesheet_gallery"][value="'+$('select#stylesheet').val()+'"]');
			radiobutton.click();
			$('.preview_wrapper').removeClass('selected');
			radiobutton.parent().parent().addClass('selected');
		}
		// If an overlay is clicked, select the right item in the dropdown and clear the custom css field
		$('div.preview_image').click(function() {
			$('.preview_wrapper').removeClass('selected');
			var parent = $(this).parent();
			parent.addClass('selected');
			var radiobutton = parent.find('input');
			radiobutton.prop('checked', true);
			$('select#stylesheet').val(radiobutton.attr('value'));
			$('input#styleurl').val('');
		})
		// If the input is clicked, redirect it to the overlay click event
		$('input[name="stylesheet_gallery"]').change(function() {
			$(this).parent().parent().find('div.preview_image').click();
		})
		// If the dropdown is changed, select the appropriate item in gallery, clear the custom CSS field
		$('select#stylesheet').change(function() {
			var radiobutton = $('input[name="stylesheet_gallery"][value="'+$(this).val()+'"]');
			radiobutton.prop('checked', true);
			$('.preview_wrapper').removeClass('selected');
			radiobutton.parent().parent().addClass('selected');
			$('input#styleurl').val('');
		})
		// If the custom CSS field is changed, clear radio buttons
		$('input#styleurl').keydown(function() {
			$('input[name="stylesheet_gallery"]').each(function() {
				$(this).prop('checked', false);
			})
			$('.preview_wrapper').removeClass('selected');
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
