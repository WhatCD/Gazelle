var TextareaPreview;
$(document).ready(function () {
	'use strict';
	TextareaPreview = function (id, textarea_id) {
		if (!isNaN(+id)) {
			var textarea = document.getElementById(textarea_id || 'quickpost_' + id);
			if (textarea) {
				this.id = id;
				this.init(textarea);
			}
		}
	};

	TextareaPreview.factory = function (arrays) {
		var i = 0, j = arrays.length, t;
		for (i; i < j; i++) {
			t = arrays[i];
			t = new TextareaPreview(t[0], t[1]);
		}
	};

	TextareaPreview.prototype = {
		constructor: TextareaPreview,
		last : false,
		text : [['Edit', 'Edit text'], ['Preview', 'Preview text']],
		init : function (textarea) {
			this.elements(textarea);

			this.el.preview
				.on('dblclick.preview', $.proxy(this.toggle, this))
				.addClass('text_preview')
				.addClass('tooltip')
				.attr('title', 'Double-click to edit');

			this.buttons.preview
				.on('click.preview', $.proxy(this.get, this))
				.toggleClass('hidden');
		},
		elements : function (textarea) {
			this.el = {
				textarea : $(textarea),
				wrap : $('#textarea_wrap_' + this.id),
				preview : $('#preview_' + this.id),
				pwrap : $('#preview_wrap_' + this.id)
			};
			this.buttons = {
				preview : $('.button_preview_' + this.id)
			};
		},
		toggle : function () {
			var t = this.text[+(this.buttons.preview.val() === 'Edit')];
			this.el.wrap.toggleClass('hidden');
			this.el.pwrap.toggleClass('hidden');
			this.buttons.preview.val(t[0]);
			this.buttons.preview.attr('title', t[1]);
		},
		get : function () {
			if (this.buttons.preview.val() === 'Edit') {
				return this.toggle();
			}
			if (this.buttons.preview.val() === 'Preview'
					&& this.el.textarea.val().length > 0) {
				this.toggle();
				if (this.last !== this.el.textarea.val()) {
					this.el.preview.text('Loading...');
					this.last = this.el.textarea.val();
					this.post();
				}
				return;
			}
		},
		post : function () {
			$.post('ajax.php?action=preview',
					{ body : this.el.textarea.val() },
					$.proxy(this.html, this),
					'html'
				);
				// .fail(function (jqXHR, textStatus) {
					// alert('Request failed: ' + textStatus);
				// });
		},
		html : function (data) {
			this.el.preview.html(data);
		}
	};
});
