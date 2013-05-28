/*
	TODO: Move to more efficient structure used in ajax.class.

	Example Usage:
	<script type="text/javascript">
		window.onload = freeleech;
		function freeleech() {
			count.end = 'The freeleech has ended!';
			count.event = 'The freeleech will end in';
			count.element = 'freeleech';
			count.update(<?=(time(3,0,0,9,22)-time());?>);
		}
	</script>
*/
"use strict";
var count = {
	update: function (Offset) {
		if (Offset < 0) {
			document.getElementById(this.element).innerHTML = this.end;
		} else {
			var Seconds = Offset % 60;
			if (Seconds < 10) {
				Seconds = '0' + Seconds;
			}
			var Remainder = (Offset - Seconds) / 60;
			var Minutes = Remainder % 60;
			if (Minutes < 10) {
				Minutes = '0' + Minutes;
			}
			Remainder = (Remainder - Minutes) / 60;
			var Hours = Remainder % 24;
			var Days = (Remainder - Hours) / 24;
			$(this.element).raw().innerHTML = this.event + ' ' + (Days > 0 ? Days + 'd, ' : '') + (Hours > 0 ? Hours + 'h, ' : '') + Minutes + 'm, ' + Seconds + 's.';
			setTimeout(function (object) {
				object.update(Offset - 1);
			}, 999, this);
		}
	}
};
