	var sortCollageTable;
	jQuery(document).ready(function ($) {
		sortCollageTable = {
			counter: function () {
				var x = 10;
				$('.sort_numbers').each(function () {
						this.value = x;
						x += 10;
				});
				this.serializer();
			},
			color : function () {
				var i = 0, css;
				$('.drag').each(function () {
					css = i % 2 === 0 ? ['rowa', 'rowb'] : ['rowb', 'rowa'];
					$(this).removeClass(css[0]).addClass(css[1]);
					i++;
				});
			},
			serializer : function () {
				var s = this.container.sortable('serialize');
				this.serialInput.val(s);
			},
			save : function () {
				this.form.submit();
			},
			widthFix : function(e, row) {
				row.children('td').each(function () {
					$(this).width($(this).width());
				});
				return row;
			},
			init : function () {
				this.container = $('#manage_collage_table');
				this.form = $('#drag_drop_collage_form');
				this.serialInput = $('#drag_drop_collage_sort_order');
				$('.drag_drop_save').toggleClass('hidden');
				
				this.container.sortable({
					items: '.drag',
					axis: 'y',
					containment: '.thin',
					forcePlaceholderSize: true,
					helper: sortCollageTable.widthFix,
					stop: function () {
						sortCollageTable.postSort();
					}
				});

				$('.save_sortable_collage').click(function () {
					sortCollageTable.save();
				});
				
				this.tableSorter();
			},
			tableSorter : function () {
				this.container.tablesorter({
					cssHeader : 'headerSort',
					textExtraction: sortCollageTable.extractor,
					headers : {
						0: {
							sorter : false
						},
						6: {
							sorter : false
						}
					}
				});
				this.container.bind('sortEnd', function () {
					sortCollageTable.postSort();
				});
			},
			extractor : function (node) {
				return node.textContent || node.innerText;
			},
			postSort : function () {
				this.color();
				this.counter();
			}
		};
		sortCollageTable.init();
	});