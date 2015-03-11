/**
 * @package assets
 */

(function($, Symphony) {
	'use strict';

	/**
	 * Filtering interface for the publish area.
	 */
	Symphony.Interface.Filtering = function() {
		var filtering, duplicator, apply,
			actions = [];

		/**
		 * Initialise filtering interface.
		 */
		var init = function() {
			filtering = Symphony.Elements.context.find('.filtering');
			duplicator = filtering.find('.filters-duplicator');
			apply = duplicator.find('.apply');

			// Add buttons
			createAddButton();
			createClearButton();

			// Set state
			toggleState();
			duplicator.on('constructstop.duplicator destructstart.duplicator', '.instance', toggleState);

			// Handle filtering
			filtering.on('keyup.filtering', '.filter', handleFiltering);
			filtering.on('change.filtering', '.filter', handleFiltering);
			duplicator.on('destructstop.duplicator', filter);

			// Show help
			duplicator.on('constructstop.duplicator', '.instance', handleComparisons);
			filtering.on('change', '.comparison', handleComparisons);
			filtering.find('.instance').each(handleComparisons);

			// Show suggestions
			Symphony.Interface.Suggestions.init(filtering, '.filter');
		};

	/*-------------------------------------------------------------------------
		Event handling
	-------------------------------------------------------------------------*/

		/**
		 * Apply filtering if either the input field is left or
		 * if the enter key was pressed.
		 *
		 * @param Event event
		 *  The keyup or change event
		 */
		var handleFiltering = function(event) {
			var target = $(event.target);

			if(event.keyCode === 13 || target.is('.apply-filters') || target.is('.updated')) {
				event.preventDefault();
				event.stopPropagation();

				filter();
			}
		};

		/**
		 * Clear filters on click.
		 *
		 * @param Event event
		 *  The click event
		 */
		var handleClearing = function(event) {
			event.preventDefault();
			event.stopPropagation();

			clear();
		};

		/**
		 * Update help text, if the user changes the comparison mode or
		 * adds a new filter panel.
		 *
		 * @param Event event
		 *  The keyup or change event
		 */
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

		/**
		 * Apply filters.
		 */
		var filter = function() {
			var filters = build(),
				base, url;

			// Fetch entries
			base = Symphony.Context.get('symphony') + Symphony.Context.get('route');
			url = base + (filters !== '' ? '?' : '') + filters;

			// Redirect
			window.location = url;
		};

		/**
		 * Build filter string to be used in the URL.
		 */
		var build = function() {
			var filters = [];

			filtering.find('.instance:not(.template):visible').each(function() {
				var item = $(this),
					comparison = $.trim(item.find('.comparison').val()),
					query = item.find('.filter'),
					value;

				if (!!comparison) {
					comparison = comparison + ' ';
				}

				value = 'filter[' + query.attr('name') + ']=' + comparison + $.trim(query.val());
				filters.push(value);
			});

			return filters.join('&');
		};

		/**
		 * Clear all filters.
		 */
		var clear = function() {
			window.location = Symphony.Context.get('symphony') + Symphony.Context.get('route');
		};

		/**
		 * Switch help texts.
		 *
		 * @param jQuery item
		 *  The filter instance
		 * @param string comparison
		 *  The selected comparison mode
		 */
		var switchHelp = function(item, comparison) {
			var help = item.find('.suggestions .help');

			if(!comparison) {
				comparison = 'is';
			}

			help.removeClass('active');
			help.filter('[data-comparison="' + comparison + '"]').addClass('active');
		};

		var toggleState = function() {
			if(duplicator.find('.instance:not(.template)').length) {
				activate();
			}
			else {
				deactivate();
			}
		};

		var activate = function() {
			actions.apply.prop('disabled', false);
			actions.apply.removeAttr('disabled');
			actions.clear.show();
		};

		var deactivate = function() {
			actions.apply.prop('disabled', true);
			actions.clear.hide();
		};

	/*-------------------------------------------------------------------------
		Utilities
	-------------------------------------------------------------------------*/

		/**
		 * Create add button.
		 */
		var createAddButton = function() {
			actions.apply = $('<button/>', {
				'text': Symphony.Language.get('Apply filters'),
				'class': 'apply-filters',
				'click': handleFiltering
			}).insertBefore(apply);
		};

		/**
		 * Create clear button.
		 */
		var createClearButton = function() {
			actions.clear = $('<button/>', {
				'text': Symphony.Language.get('Clear filters'),
				'class': 'clear-filters delete',
				'click': handleClearing
			}).insertBefore(apply);
		};

	/*-------------------------------------------------------------------------
		Public API
	-------------------------------------------------------------------------*/

		return {
			init: init
		};
	}();

})(window.jQuery, window.Symphony);
