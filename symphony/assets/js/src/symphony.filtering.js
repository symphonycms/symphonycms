(function($, Symphony) {
	'use strict';

	Symphony.Language.add({
		'Click to select': false,
		'Type to search': false,
		'Clear': false,
		'Search for {$item}': false,
		'Add filter': false,
		'filtered': false
	});

	$(document).on('ready.publishfiltering', function() {
		var contents = $('#contents'),
			rows = $('.publishfiltering-row'),
			options = Symphony.Context.get('publishfiltering'),
			maxRows = rows.filter('.template').find('.publishfiltering-fields option').length,
			addRow;

		var Publishfiltering = function() {
			var filter, fields, comparison, search,
				comparisonSelectize, searchSelectize, fieldsSelectize;

			var init = function(context) {
				filter = $(context);
				fields = filter.find('.publishfiltering-fields');
				comparison = filter.find('.publishfiltering-comparison');
				search = filter.find('.publishfiltering-search');

				// Setup interface
				fields.selectize().on('change', switchField);
				comparison.selectize().on('change', searchEntries);
				search.addClass('init').selectize({
					create: true,
					maxItems: 1,
					render: {
						item: itemPreview,
						option_create: searchPreview
					}
				}).on('change', searchEntries);

				// Store Selectize instances
				fieldsSelectize = fields[0].selectize;
				comparisonSelectize = comparison[0].selectize;
				searchSelectize = search[0].selectize;

				// Reduce field options
				rows.not(filter).not('.template').each(reduceFields);

				// Remove add button
				if(rows.filter(':not(.template)').length >= maxRows) {
					addRow.hide();
				}

				// Highlight filtering
				highlightFiltering();

				// Clear search
				filter.on('mousedown.publishfiltering', '.destructor', clear);

				// Finish initialisation
				search.removeClass('init');
			};

			var reduceFields = function() {
				var row = $(this),
					value = row.find('.publishfiltering-fields').val();

				fieldsSelectize.removeOption(value);
				fieldsSelectize.addItem(Object.keys(fieldsSelectize.options)[0]);
			}

			var switchField = function() {
				var field = fieldsSelectize.getValue();

				// Clear
				searchSelectize.clearOptions();

				// Default options
				if(hasOptions(field)) {
					comparisonSelectize.setValue('is');
					searchSelectize.$control_input.attr('placeholder', Symphony.Language.get('Click to select') + '…');
					$.each(options[field], function(key, option) {
						var value = (typeof key === 'string') ? key : option;

						searchSelectize.addOption({
							value: value,
							text: option
						});
					});
				}

				// Text search
				else {
					comparisonSelectize.setValue('contains');
					searchSelectize.$control_input.attr('placeholder', Symphony.Language.get('Type to search') + '…');
				}		
			};

			var searchPreview = function(item) {
				return '<div class="create"><em>' + Symphony.Language.get('Search for {$item}', {item: item.input}) + ' …</em></div>';
			};

			var itemPreview = function(item, escape) {
				return '<div class="item">' + escape(item.text) + '<a href="' + location.href.replace(location.search, '') + '" class="destructor">' + Symphony.Language.get('Clear') + '</a></div>';
			};

			var searchEntries = function(event) {
				if(!search.is('.init')) {
					var filters = buildFilters(),
						base, url;

					// Fetch entries
					if(filters != '') {
						base = location.href.replace(location.search, '');
						url = base + '?' + filters;

						fetchEntries(url);
						setURL(url);
					}
				}
			};

			var buildFilters = function() {
				var filters = [];

				$('.publishfiltering-row').each(function() {
					var row = $(this),
						fieldVal = row.find('.publishfiltering-fields').val(),
						comparisonVal = row.find('.publishfiltering-comparison').val(),
						searchVal = row.find('.publishfiltering-search').val(),
						filterVal, method;

					if(fieldVal && searchVal) {
						method = (comparisonVal === 'contains') ? 'regexp:' : '';
						filterVal = 'filter[' + encodeURI(fieldVal) + ']=' + method + encodeURI(searchVal);
						filters.push(filterVal);
					}
				});

				return filters.join('&');
			}

			var fetchEntries = function(url) {
				$.ajax({
					url: url,
					dataType: 'html',
					success: appendEntries
				});
			};

			var appendEntries = function(result) {
				var page = $(result),
					entries = page.find('tbody'),
					pagination = page.find('ul.page');

				// Update content
				contents.find('tbody').replaceWith(entries);
				contents.find('ul.page').replaceWith(pagination);

				// Render view
				Symphony.View.render(null, true);
				highlightFiltering();
			};

			var highlightFiltering = function() {
				if(Symphony.Elements.breadcrumbs.find('.inactive').length === 0 && location.search.indexOf('filter') !== -1) {
					Symphony.Elements.breadcrumbs.append('<p class="inactive"><span>' + Symphony.Language.get('filtered') + '</span></p>');
				}
			}

			var setURL = function(url) {
				if(!!(window.history && history.pushState)) {
					history.pushState(null, null, url);
				}
			};

			var clear = function(event) {
				event.preventDefault();
				if(searchSelectize.isLocked) return;

				searchSelectize.clear();
			};

			var hasOptions = function(name) {
				return name in options;
			};

			// API
			return {
				init: init,
				clear: clear
			};
		};

		// Init filter interface
		rows.filter(':not(.template)').each(function() {
			var filtering = new Publishfiltering();
			filtering.init(this);
		});

		// Add filters
		addRow = $('<a />', {
			class: 'button publishfiltering-add',
			text: Symphony.Language.get('Add filter'),
			on: {
				click: function() {
					var filtering = new Publishfiltering(),
						template = rows.filter('.template').clone().removeClass('template');

					template.insertBefore(this).css('display', 'block');
					rows = rows.add(template);
					filtering.init(template);
				}
			}
		}).appendTo('.publishfiltering');
	});

})(window.jQuery, window.Symphony);