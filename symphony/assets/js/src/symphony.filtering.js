(function($, Symphony) {
	'use strict';

	Symphony.Extensions.Filtering = function() {
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
			filtering.on('keydown.filtering', '.filter', handleFiltering);
		};

	/*-------------------------------------------------------------------------
		Event handling
	-------------------------------------------------------------------------*/

		var handleFiltering = function(event) {
			if(event.keyCode === 13 || event.target.classList.contains('apply-filters')) {
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

			filtering.find('.instance:not(.template)').each(function() {
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

		// API
		return {
			init: init
		};
	}();

})(window.jQuery, window.Symphony);
