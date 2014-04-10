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
			comparison.selectize().on('change', searchEntries);
			search.addClass('init').selectize({
				create: true,
				maxItems: 1,
				sortField: 'text',
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
			rows.not(filter).each(reduceFields);

			// Remove add button
			if(rows.length >= maxRows) {
				Symphony.Elements.context.find('.filtering-add').hide();
			}

			// Highlight filtering
			highlightFiltering();

			// Clear search
			filter.on('mousedown.filtering', '.destructor', clear);

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
					if(contains || !result.filters.length) {
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

		var searchPreview = function(item) {
			return '<div class="create"><em>' + Symphony.Language.get('Search for {$item}', {item: item.input}) + ' …</em></div>';
		};

		var itemPreview = function(item, escape) {
			return '<div class="item">' + escape(item.text) + '<a href="' + location.href.replace(location.search, '') + '" class="destructor">' + Symphony.Language.get('Clear') + '</a></div>';
		};

		var searchEntries = function() {
			if(!search.is('.init')) {
				var filters = buildFilters(),
					base, url;

				// Fetch entries
				if(filters !== '') {
					base = location.href.replace(location.search, '');
					url = base + '?' + filters;

					fetchEntries(url);
					setURL(url);
				}
			}
		};

		var buildFilters = function() {
			var filters = [];

			$('.filtering-row').each(function() {
				var row = $(this),
					fieldVal = row.find('.filtering-fields').val(),
					comparisonVal = row.find('.filtering-comparison').val(),
					searchVal = row.find('.filtering-search').val(),
					filterVal, method;

				if(fieldVal && searchVal) {
					method = (comparisonVal === 'contains') ? 'regexp:' : '';
					filterVal = 'filter[' + encodeURI(fieldVal) + ']=' + method + encodeURI(searchVal);
					filters.push(filterVal);
				}
			});

			return filters.join('&');
		};

		var fetchEntries = function(url) {
			$.ajax({
				url: url,
				type: 'GET',
				dataType: 'html',
				success: appendEntries
			});
		};

		var appendEntries = function(result) {
			var page = $(result),
				entries = page.find('tbody'),
				pagination = page.find('ul.page');

			// Update content
			Symphony.Elements.contents.find('tbody').replaceWith(entries);
			Symphony.Elements.contents.find('ul.page').replaceWith(pagination);

			// Render view
			Symphony.View.render(null, true);
			highlightFiltering();
		};

		var highlightFiltering = function() {
			if(Symphony.Elements.breadcrumbs.find('.inactive').length === 0 && location.search.indexOf('filter') !== -1) {
				Symphony.Elements.breadcrumbs.append('<p class="inactive"><span>– ' + Symphony.Language.get('filtered') + '</span></p>');
			}
		};

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

		// API
		return {
			init: init,
			clear: clear
		};
	};

})(window.jQuery, window.Symphony);
