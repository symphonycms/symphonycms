(function($, Symphony) {
	'use strict';

	Symphony.Interface.Filtering = function() {
		var filtering, duplicator, apply;

		var init = function() {
			filtering = Symphony.Elements.context.find('.filtering');
			duplicator = filtering.find('.filters-duplicator');
			apply = duplicator.find('.apply');

			// Add apply button
			$('<button/>', {
				'text': 'Apply filters',
				'class': 'apply-filters',
				'click': handleFiltering
			}).insertBefore(apply);

			// Add clear button
			$('<button/>', {
				'text': 'Clear filters',
				'class': 'clear-filters delete',
				'click': handleClearing
			}).insertBefore(apply);

			// Apply filtering
			filtering.on('keyup.filtering', '.filter', handleFiltering);
			filtering.on('change.filtering', '.filter', handleFiltering);

			// Clear single filter
			duplicator.on('destructstop.duplicator', filter);

			// Show suggestions
			Symphony.Interface.Suggestions.init(filtering, '.filter');

			// Show help
			duplicator.on('constructstop.duplicator', '.instance', handleComparisons);
			filtering.on('change', '.comparison', handleComparisons);
			filtering.find('.instance').each(handleComparisons);
		};

	/*-------------------------------------------------------------------------
		Event handling
	-------------------------------------------------------------------------*/

		var handleFiltering = function(event) {
			var target = $(event.target);

			if(event.keyCode === 13 || target.is('.apply-filters') || target.is('.updated')) {
				event.preventDefault();
				event.stopPropagation();

				filter();
			}
		};

		var handleClearing = function(event) {
			event.preventDefault();
			event.stopPropagation();

			clear();
		};

		var handleComparisons = function() {
			var item = $(this),
				comparison;

			// Show help contextually
			if(item.is('.instance')) {
				comparison = item.find('.comparison').val();
			}
			else {
				comparison = item.val();
				item = item.parents('.instance');
			}

			switchHelp(item, comparison);
		};

	/*-------------------------------------------------------------------------
		Filtering
	-------------------------------------------------------------------------*/

		var filter = function() {
			var filters = build(),
				base, url;

			// Fetch entries
			base = Symphony.Context.get('symphony') + Symphony.Context.get('route');
			url = base + (filters !== '' ? '?' : '') + filters;

			// Redirect
			window.location = url;
		};

		var build = function() {
			var filters = [];

			filtering.find('.instance:not(.template):visible').each(function() {
				var item = $(this),
					comparison = item.find('.comparison'),
					query = item.find('.filter'),
					value = 'filter[' + query.attr('name') + ']=' + comparison.val() + query.val();

				filters.push(value);
			});

			return filters.join('&');
		};

		var clear = function() {
			window.location = Symphony.Context.get('symphony') + Symphony.Context.get('route');
		};

		var switchHelp = function(item, comparison) {
			var help = item.find('.suggestions .help');

			if(!comparison) {
				comparison = 'is';
			}

			help.removeClass('active');
			help.filter('[data-comparison="' + comparison + '"]').addClass('active');
		};

		// API
		return {
			init: init
		};
	}();

})(window.jQuery, window.Symphony);
