(function($, Symphony) {
	'use strict';

	Symphony.Extensions.Filtering = function() {
		var filter, fields, comparison, search, rows, maxRows,
			comparisonSelectize, searchSelectize, fieldsSelectize;

		var init = function(context) {
			filter = $(context);
			fields = filter.find('.filtering-fields');
			comparison = filter.find('.filtering-comparison');
			search = filter.find('.filtering-search');
			rows = Symphony.Elements.context.find('.filtering-row:not(.template)');
			maxRows = Symphony.Elements.context.find('.filtering-row.template .filtering-fields option').length;

			// Setup interface
			fields.selectize().on('change', switchField);
			comparison.selectize().on('change', switchComparison);
			search.addClass('init').selectize({
				create: true,
				maxItems: 1,
				sortField: 'text',
				render: {
					item: itemPreview,
					option_create: searchPreview
				},
				onItemAdd: searchEntries
			});

			// Store Selectize instances
			fieldsSelectize = fields[0].selectize;
			comparisonSelectize = comparison[0].selectize;
			searchSelectize = search[0].selectize;

			// Reduce field options
			rows.not(filter).each(reduceFields);

			// Remove add button
			if(rows.length >= maxRows) {
				Symphony.Elements.context.find('.filtering-add').hide();
			}

			// Highlight filtering
			highlightFiltering();

			// Clear search
			filter.find('.destructor').on('click', clear).on('mouseover mouseout', prepareClear);

			// Finish initialisation
			search.removeClass('init');
		};

		var reduceFields = function() {
			var row = $(this),
				value = row.find('.filtering-fields').val();

			fieldsSelectize.removeOption(value);
			fieldsSelectize.addItem(Object.keys(fieldsSelectize.options)[0]);
		};

		var switchField = function() {

			// Clear
			searchSelectize.clearOptions();
			searchSelectize.$control_input.attr('placeholder', Symphony.Language.get('Type to search') + '…');

			// Add suggestions
			$.ajax({
				url: Symphony.Context.get('symphony') + '/ajax/filters/',
				type: 'GET',
				dataType: 'json',
				data: {
					handle: fields.val(),
					section: Symphony.Context.get('env')['section_handle']
				},
				success: function(result) {
					var contains = false;

					// Add options
					$.each(result.filters, function(index, data) {
						searchSelectize.addOption(data);

						if(isNaN(data.value)) {
							contains = true;
						}
					});

					// Set comparison mode
					if(contains || !result.filters.length) {
						comparisonSelectize.setValue('contains');
					}
					else {
						comparisonSelectize.setValue('is');
					}

					// Refresh suggestions
					searchSelectize.refreshOptions(false);
				}
			});
		};

		var switchComparison = function() {
			if(searchSelectize.getValue() !== '') {
				searchEntries();
			}
		};

		var searchPreview = function(item) {
			return '<div class="create"><em>' + Symphony.Language.get('Search for {$item}', {item: item.input}) + ' …</em></div>';
		};

		var itemPreview = function(item, escape) {
			return '<div class="item">' + escape(item.text) + '<a href="' + location.href.replace(location.search, '') + '" class="destructor">' + Symphony.Language.get('Clear') + '</a></div>';
		};

		var searchEntries = function(value, item, exclude) {
			if(!search.is('.init')) {
				var filters = buildFilters(exclude),
					base, url;

				// Fetch entries
				base = location.href.replace(location.search, '');
				url = base + (filters !== '' ? '?' : '') + filters;

				// Redirect
				window.location.href = url;
			}
		};

		var buildFilters = function(exclude) {
			var filters = [];

			$('.filtering-row:not(.template)').each(function() {
				var row = $(this);

				if(row[0] != exclude) {
					var fieldVal = row.find('.filtering-fields')[0].selectize.getValue(),
						comparisonVal = row.find('.filtering-comparison')[0].selectize.getValue(),
						searchVal = row.find('.filtering-search')[0].selectize.getValue(),
						filterVal, method;

					if(fieldVal && searchVal) {
						method = (comparisonVal === 'contains') ? 'regexp:' : '';
						filterVal = 'filter[' + encodeURI(fieldVal) + ']=' + method + encodeURI(searchVal);
						filters.push(filterVal);
					}
				}
			});

			return filters.join('&');
		};

		var highlightFiltering = function() {
			if(Symphony.Elements.breadcrumbs.find('.inactive').length === 0 && location.search.indexOf('filter') !== -1) {
				Symphony.Elements.breadcrumbs.append('<p class="inactive"><span>– ' + Symphony.Language.get('filtered') + '</span></p>');
			}
		};

		var prepareClear = function(event) {
			if(searchSelectize.$dropdown.is(':hidden')) {
				searchSelectize.$dropdown.css('opacity', (event.type === 'mouseover' ? 0 : 1));
			}
		};

		var clear = function(event) {
			event.preventDefault();
			event.stopPropagation();

			var destructor = $(this),
				exclude = destructor.parents('.filtering-row')[0];

			searchEntries(null, null, exclude);
		};

		// API
		return {
			init: init,
			clear: clear
		};
	};

})(window.jQuery, window.Symphony);
