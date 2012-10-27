var TextareaPreview;
jQuery(document).ready(function ($) {
	TextareaPreview = function (id, textarea_id) {
		if (typeof(id) === 'number') {
			var textarea = document.getElementById(textarea_id || 'quickpost_'+id);
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
	}
	
	TextareaPreview.prototype = {
		constructor: TextareaPreview,
		last : false,
		init : function (textarea) {
			var toggle = $.proxy(this.toggle, this);
			this.elements(textarea);
			
			this.buttons.edit.on('click.preview', toggle);
			this.el.preview
				.on('dblclick.preview', toggle)
				.addClass('text_preview')
				.attr('title', 'Double click to edit.');
				
			this.buttons.preview
				.on('click.preview', $.proxy(this.get, this))
				.toggleClass('hidden');
		},
		elements : function (textarea) {
			this.el = {
				textarea : $(textarea),
				wrap : $('#textarea_wrap_'+this.id),
				preview : $('#preview_'+this.id),
				pwrap : $('#preview_wrap_'+this.id)
			};
			this.buttons = {
				edit : $('.button_edit_'+this.id),
				preview : $('.button_preview_'+this.id)
			};
		},
		toggle : function () {
			this.el.wrap.toggleClass('hidden');
			this.el.pwrap.toggleClass('hidden');
			this.buttons.edit.toggleClass('hidden');
			this.buttons.preview.toggleClass('hidden');
		},
		get : function () {
			if(this.el.textarea.val().length > 0) {
				this.toggle();
				if (this.last !== this.el.textarea.val()) {
					this.el.preview.text('Loading . . .');
					this.last = this.el.textarea.val();
					this.post();
				}
			}
		},
		post : function () {
			$.post('ajax.php?action=preview',
				{ body : this.el.textarea.val() },
				$.proxy(this.html, this),
				'html'
			).fail(function (jqXHR, textStatus) {
				alert('Request failed: ' + textStatus);
			});
		},
		html : function (data) {
			this.el.preview.html(data);
		}
	};
});