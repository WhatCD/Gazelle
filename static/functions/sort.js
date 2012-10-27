var sortableTable;
jQuery(document).ready(function ($) {
	sortableTable = {
		container : $('#manage_collage_table'),
		form : $('#drag_drop_collage_form'),
		serialInput : $('#drag_drop_collage_sort_order'),
		check : $('#check_all'),
		counter : function () {
			var x = 10;
			$('input.sort_numbers').each(function () {
				this.value = x;
				x += 10;
			});
			this.serializer();
		},
		color : function () {
			var i = 0, css;
			$('tr.drag').each(function () {
				css = i % 2 === 0 ? ['rowa', 'rowb'] : ['rowb', 'rowa'];
				$(this).removeClass(css[0]).addClass(css[1]);
				i++;
			});
		},
		serializer : function () {
			this.serialInput.val(this.container.sortable('serialize'));
		},
		save : function () {
			sortableTable.form.submit();
		},
		widthFix : function(e, row) {
			row.children('td').each(function () {
				$(this).width($(this).width());
			});
			return row;
		},
		init : function () {
			$('.drag_drop_save').removeClass('hidden');

			this.noteToggle();
			this.draggable();
			this.tableSorter();

			if (this.check.length !== 0) {
				this.checks();
			} else {
				$('.save_sortable_collage').click(sortableTable.save);
			}
		},
		draggable : function () {
			this.container.sortable({
				items: '.drag',
				axis: 'y',
				containment: '.thin',
				forcePlaceholderSize: true,
				helper: sortableTable.widthFix,
				stop: sortableTable.postSort
			});
		},
		tableSorter : function () {
			this.container.tablesorter({
				cssHeader : 'headerSort',
				textExtraction: sortableTable.extractor,
				headers : {
					0: {
						sorter : false
					},
					6: {
						sorter : false
					}
				}
			}).on('sortEnd', sortableTable.postSort);
		},
		extractor : function (node) {
			return node.textContent || node.innerText;
		},
		postSort : function () {
			sortableTable.color();
			sortableTable.counter();
		},
		noteToggle : function () {
			var span = $('<a href="#" title="Toggle Note">(Hide)</a>').click(function (e) {
				e.preventDefault();
				$('#drag_drop_textnote > :first-child').toggle();
				$this = $(this);
				$this.text($this.text() === '(Hide)' ? '(Show)' : '(Hide)');
			});
			$('#sorting_head').append(' ', span);
		},
		checks : function () {
			this.check.on('click', 'input', function () {
				var s = this.checked ?
					'td.center :checkbox:not(:checked)' :
					'td.center :checked';
				$(s).click();
			}).find('span').html('<input type="checkbox" />');

			this.container.on('click', 'td > :checkbox', function() {
				$(this).parents('tr').toggleClass('row_checked');
			}).on('dblclick', 'tr.drag', function () {
				$(this).find(':checkbox').click();
			});
		}
	};
	sortableTable.init();
});