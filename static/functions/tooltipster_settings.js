var tooltip_delay = 500;
$(document).ready(function() {
	if (!$.fn.tooltipster) {
		$('.tooltip_interactive, .tooltip_image, .tooltip, .tooltip_gold').each(function() {
			if ($(this).data('title-plain')) {
				$(this).attr('title', $(this).data('title-plain')).removeData('title-plain');
			}
		});
		return;
	}
	$('.tooltip_interactive').tooltipster({
		interactive: true,
		interactiveTolerance: 500,
		delay: tooltip_delay,
		updateAnimation: false,
		maxWidth: 400
	});

	$('.tooltip').tooltipster({
		delay: tooltip_delay,
		updateAnimation: false,
		maxWidth: 400
	});

	$('.tooltip_image').tooltipster({
		delay: tooltip_delay,
		updateAnimation: false,
		fixedWidth: 252
	});

	$('.tooltip_gold').tooltipster({
		delay: tooltip_delay,
		maxWidth: 400,
		updateAnimation: false,
		theme: '.tooltipster-default gold_theme'
	});
});
