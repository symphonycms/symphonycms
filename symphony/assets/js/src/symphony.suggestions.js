/**
 * @package assets
 */

(function($, Symphony) {
	'use strict';

	Symphony.Interface.Suggestions = function() {

		var context;
		var options;

		/**
		 * Initialise suggestions
		 */
		var init = function(element, selector, opts) {
			context = $(element);
			options = $.extend({}, options, opts);

			// Disable autocomplete
			context.find(selector).each(function() {
				this.autocomplete = 'off';
			});

			// Create suggestion lists
			createSuggestions(selector);

			// Interactions
			context.on('input.suggestions', selector, handleChange);
			context.on('click.suggestions', selector, handleChange);
			context.on('focus.suggestions', selector, handleChange);
			context.on('keyup.suggestions', selector, handleChange);
			context.on('mouseover.suggestions', '.suggestions li:not(.help):not(.calendar)', handleOver);
			context.on('mouseout.suggestions', '.suggestions li:not(.help):not(.calendar)', handleOut);
			context.on('mousedown.suggestions', '.suggestions li:not(.help):not(.calendar)', handleSelect);
			context.on('keydown.suggestions', selector, handleNavigation);
		};

	/*-------------------------------------------------------------------------
		Event handling
	-------------------------------------------------------------------------*/

		/**
		 * Load suggestions based on type while the user types.
		 */
		var handleChange = function(event) {
			var input = $(this),
				value = input.val(),
				suggestions = input.next('.suggestions'),
				types = suggestions.attr('data-search-types'),
				trigger = input.attr('data-trigger');

			// Stop when navigating the suggestion list
			if(jQuery.inArray(event.which, [13, 27, 38, 40]) !== -1) {
				return;
			}

			// Dates
			if(types && types.indexOf('date') !== -1) {
				schedule(input);
			}

			// Tokens
			else if(value && trigger) {
				tokenize(input, suggestions, value, trigger);
			}

			// Entries
			else if(value || (types && types.indexOf('static') !== -1)) {
				load(input, value);
			}

			// No input
			else {
				clear(suggestions);
			}
		};

		/**
		 * Handle mouse interactions on the suggestion list.
		 * In order to make this work with the keyboard as well, set the class
		 * `.active` to the current target.
		 *
		 * @param Event event
		 *  The mouseover event
		 */
		var handleOver = function(event) {
			var suggestion = $(event.target);

			suggestion.siblings('li:not(.help)').removeClass('active');
			suggestion.addClass('active');
		};

		/**
		 * Handle finished mouse interactions on the suggestion list and
		 * remove `.active` class set by `handleOver`.
		 *
		 * @param Event event
		 *  The mouseout event
		 */
		var handleOut = function(event) {
			var suggestion = $(event.target);

			suggestion.removeClass('active');
		};

		/**
		 * Handle keyboard navigation in the suggestion list.
		 *
		 * @param Event event
		 *  The keydown event
		 */
		var handleNavigation = function(event) {
			var input = $(this),
				active;

			// Down
			if(event.which == 40) {
				event.preventDefault();
				down(input);
			}

			// Up
			else if(event.which == 38) {
				event.preventDefault();
				up(input);
			}

			// Exit
			else if(event.which == 27) {
				event.preventDefault();
				input.blur();
			}

			// Enter
			else if(event.which == 13) {
				event.preventDefault();
				active = input.next('.suggestions').find('li:not(.help).active');

				if(active.length) {
					select(active, input);
				}
			}
		};

		/**
		 * Handle suggestion selection by click.
		 *
		 * @param Event event
		 *  The mousedown event
		 */
		var handleSelect = function(event) {
			var input = $(event.target).parent('.suggestions').prev('input');

			select($(event.target), input);
		};

	/*-------------------------------------------------------------------------
		Suggestions
	-------------------------------------------------------------------------*/

		var tokenize = function(input, suggestions, value, trigger) {
			var selectionStart = input[0].selectionStart || 0,
				before = value.substring(0, selectionStart).split(' '),
				after = value.substr(selectionStart).split(' '),
				token = before[before.length - 1],
				param = before[before.length - 1] + after[0];

			// Token found
			if(token && token.indexOf(trigger) === 0) {
				load(input, param);
			}
			else {
				clear(suggestions);
			}
		};

		var load = function(input, value) {
			var suggestions = input.next('.suggestions'),
				types = suggestions.attr('data-search-types'),
				trigger = input.attr('data-trigger'),
				query = value,
				prefix, data, url;

			// Prefix
			if(trigger) {
				prefix = trigger.substr(0, 1);
			}

			// Get value
			if(!query) {
				query = input.val();
			}

			if(prefix === '{') {
				query = query.substr(1);
			}

			// Get data
			if(types && types.indexOf('parameters') !== -1) {
				url = Symphony.Context.get('symphony') + '/ajax/parameters/';
				data = {
					'query': query
				};
			}
			else {
				url = Symphony.Context.get('symphony') + '/ajax/query/';
				data = {
					'field_id': suggestions.attr('data-field-id'),
					'query': query,
					'types': types
				};
			}

			// Get custom url
			if(input.attr('data-url')) {
				url = input.attr('data-url');
			}

			// Load suggestions
			if(query !== suggestions.attr('data-last-query')) {
				suggestions.attr('data-last-query', query);

				$.ajax({
					type: 'GET',
					url: url,
					data: data,
					success: function(result) {
						if(types && types.indexOf('parameters') !== -1) {
							listtoken(input, suggestions, result);
						}
						else {
							list(suggestions, result);
						}
					}
				});
			}
		};

		var listtoken = function(input, suggestions, result) {
			var clone = suggestions.clone(),
				help = clone.find('.help:first'),
				trigger = input.attr('data-trigger'),
				prefix;

			// Prefix
			if(trigger) {
				prefix = trigger.substr(0, 1);
			}

			// Clear existing suggestions
			clear(clone);

			// Add suggestions
			$.each(result, function(index, value) {
				if (index === 'status') {
					return;
				}

				if (prefix === '{') {
					value = '{' + value + '}';
				}

				var suggestion = $('<li />', {
					text: value
				});

				if ($.isFunction(options.editSuggestion)) {
					options.editSuggestion(suggestion, index, value, result);
				}

				if (help.length) {
					suggestion.insertBefore(help);
				}
				else {
					clone.append(suggestion);
				}
			});

			suggestions.replaceWith(clone);
		};

		var list = function(suggestions, result) {
			var clone = suggestions.clone(),
				help = clone.find('.help:first');

			// Clear existing suggestions
			clear(clone);

			// Add suggestions
			if(result.entries) {
				$.each(result.entries, function(index, data) {
					var suggestion = $('<li />', {
						text: data.value
					});

					if ($.isFunction(options.editSuggestion)) {
						options.editSuggestion(suggestion, index, data, result);
					}

					if (help.length) {
						suggestion.insertBefore(help);
					}
					else {
						clone.append(suggestion);
					}
				});

				suggestions.replaceWith(clone);
			}
		};

		var schedule = function(input) {
			var suggestions = input.next('.suggestions'),
				calendar = suggestions.find('.calendar');

			if(!calendar.length) {
				createCalendar(suggestions);
			}
		};

		var select = function(value, input) {
			var types = input.attr('data-search-types');
			var text = value.text();

			if(types && types.indexOf('parameters') !== -1) {
				insert(text, input);
			}
			else {
				text = $.trim(text.replace(/,/g, '\\,').replace(/&/g, '%26'));
				input.attr('data-value', value.attr('data-value'));
				input.val(text);
				input.addClass('updated');
				input.change();
			}

			if ($.isFunction(options.valueSelected)) {
				options.valueSelected(value, input);
			}

			clear(input.next('.suggestions'));
		};

		var insert = function(suggestion, input) {
			var value = input.val(),
				selectionStart = input[0].selectionStart || 0,
				beforeSelection = value.substring(0, selectionStart).split(' '),
				afterSelection = value.substr(selectionStart).split(' '),
				before = '',
				after = '';

			// Get text before parameter
			if(beforeSelection.length > 1) {
				beforeSelection.pop();
				before = beforeSelection.join(' ') + ' ';
			}

			// Get text after parameter
			if(afterSelection.length > 1) {
				afterSelection.shift();
				after = ' ' + afterSelection.join(' ');
			}

			// Insert suggestion
			input.val(before + suggestion + after);

			// Set cursor
			var length = before.length + suggestion.length;
			input[0].selectionStart = length;
			input[0].selectionEnd = length;
			input.focus();
		};

		var clear = function(suggestions) {
			suggestions.removeAttr('data-last-query');
			suggestions.find('li:not(.help)').remove();
		};

		var up = function(input) {
			var suggestions = input.next('.suggestions'),
				active = suggestions.find('li:not(.help).active').removeClass('active'),
				prev = active.prev('li:not(.help):visible');

			// First
			if(active.length === 0 || prev.length === 0) {
				suggestions.find('li:not(.help)').last().addClass('active');
			}

			// Next
			else {
				prev.addClass('active');
			}
			
			stayInFocus(suggestions);
		};

		var down = function(input) {
			var suggestions = input.next('.suggestions'),
				active = suggestions.find('li:not(.help).active').removeClass('active'),
				next = active.next('li:not(.help):visible');

			// First
			if(active.length === 0 || next.length === 0) {
				suggestions.find('li:not(.help)').first().addClass('active');
			}

			// Next
			else {
				next.addClass('active');
			}
			
			stayInFocus(suggestions);
		};

	/*-------------------------------------------------------------------------
		Utilities
	-------------------------------------------------------------------------*/

		var createSuggestions = function(selector) {
			var inputs = context.find(selector);

			inputs.each(function() {
				var input = $(this),
					suggestions = input.next('.suggestions'),
					list, types;

				if(!suggestions.length) {
					list = $('<ul class="suggestions" />');

					types = input.attr('data-search-types');
					if(types) {
						list.attr('data-search-types', types);
					}

					list.insertAfter(input);
				}
			});
		};

		var createCalendar = function(suggestions) {
			var calendar = new Symphony.Interface.Calendar();

			suggestions.prepend('<li class="calendar" data-format="YYYY-MM-DD" />');
			calendar.init(suggestions.parents('label'));
		};

		var stayInFocus = function(suggestions) {
			var active = suggestions.find('li.active'),
				distance;

			// Get distance
			if(!active.is(':visible:first')) {
				distance = ((active.prevAll().length + 1) * active.outerHeight()) - 180;
			}
			else {
				distance = 0;
			}

			// Focus
			suggestions.animate({
				'scrollTop': distance
			}, 150);
		};

	/*-------------------------------------------------------------------------
		API
	-------------------------------------------------------------------------*/

		return {
			init: init
		};
	}();

})(window.jQuery, window.Symphony);
