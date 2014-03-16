var sortableTable;
$(function () {

	// tips/notes:
	// In HTML add data-sorter="false" to table headings (TH) that should not be sorted
	// or add data-sorter="myParser" to THs that require a custom parser

	// sorts dates placed in the title attribute of a td
	$.tablesorter.addParser({
		id: 'relativeTime',
		is: function (s) {
			return false;
		},
		format: function (str, table, td) {
			return td.title;
		},
		type: 'text'
	});

	// sort to ignore (English) articles
	// add data-sorter="ignoreArticles" to THs
	$.tablesorter.addParser({
		id: 'ignoreArticles',
		$format: $.tablesorter.getParserById('text').format,
		articlesRegEx: /^(?:the\s|a\s|an\s)/i,
		is: function () {
			return false;
		},
		format: function (s, table) {
			return this.$format((s || '').replace(this.articlesRegEx, ''), table);
		},
		type: 'text'
	});

	sortableTable = {
		container: $('#manage_collage_table'),
		form: $('#drag_drop_collage_form'),
		serialInput: $('#drag_drop_collage_sort_order'),
		check: $('#check_all'),
		counter: function () {
			var x = 10;
			$('input.sort_numbers').each(function () {
				this.value = x;
				x += 10;
			});
			this.serializer();
		},
		color: function () {
			var i = 0, css;
			$('tr.drag').each(function () {
				css = i % 2 === 0 ? ['rowa', 'rowb'] : ['rowb', 'rowa'];
				$(this).removeClass(css[0]).addClass(css[1]);
				i++;
			});
		},
		serializer: function () {
			this.serialInput.val(this.container.sortable('serialize'));
		},
		save: function () {
			sortableTable.form.submit();
		},
		widthFix: function (e, row) {
			row.children('td').each(function () {
				$(this).width($(this).width());
			});
			return row;
		},
		init: function () {
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
		draggable: function () {
			this.container.sortable({
				items: '.drag',
				axis: 'y',
				containment: '.thin',
				forcePlaceholderSize: true,
				helper: sortableTable.widthFix,
				stop: sortableTable.postSort
			});
		},
		tableSorter: function () {
			this.container.tablesorter({
				cssHeader: 'headerSort',
				cssDesc: 'headerSortUp',
				cssAsc: 'headerSortDown',
				textExtraction: sortableTable.extractor
			}).on('sortEnd', sortableTable.postSort);
		},
		extractor: function (node) {
			return node.textContent || node.innerText;
		},
		postSort: function () {
			sortableTable.color();
			sortableTable.counter();
		},
		noteToggle: function () {
			var span = $('<a href="#" class="brackets tooltip" title="Toggle note">Hide</a>').click(function (e) {
				e.preventDefault();
				$('#drag_drop_textnote > :first-child').toggle();
				var $this = $(this);
				$this.text($this.text() === 'Hide' ? 'Show' : 'Hide');
			});
			$('#sorting_head').append(' ', span);
		},
		checks: function () {
			this.check.on('click', 'input', function () {
				var s = this.checked ?
						'td.center :checkbox:not(:checked)' :
						'td.center :checked';
				$(s).click();
			}).find('span').html('<input type="checkbox" />');

			this.container.on('click', 'td > :checkbox', function () {
				$(this).parents('tr').toggleClass('row_checked');
			}).on('dblclick', 'tr.drag', function () {
				$(this).find(':checkbox').click();
			});
		}
	};
	sortableTable.init();
});
