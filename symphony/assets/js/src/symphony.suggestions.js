/**
 * @package assets
 */

(function($, Symphony) {
	'use strict';

	Symphony.Interface.Suggestions = function() {

		var context;

		var init = function(element, selector) {
			context = $(element);

			context.on('input.suggestions', selector, handleChange);
			context.on('focus.suggestions', selector, handleChange);
			context.on('mouseover.suggestions', '.suggestions li:not(.help)', handleOver);
			context.on('mouseout.suggestions', '.suggestions li:not(.help)', handleOut);
			context.on('mousedown.suggestions', '.suggestions li:not(.help)', handleSelect);
			context.on('keydown.suggestions', selector, handleNavigation);
		};

	/*-------------------------------------------------------------------------
		Event handling
	-------------------------------------------------------------------------*/

		var handleChange = function() {
			var input = $(this);

			if(input.val()) {
				load(input);
			}
			else {
				clear(input.next('.suggestions'));
			}
		};

		var handleOver = function(event) {
			var suggestion = $(event.target);

			suggestion.siblings('li:not(.help)').removeClass('active');
			suggestion.addClass('active');
		};

		var handleOut = function(event) {
			var suggestion = $(event.target);

			suggestion.removeClass('active');
		};

		var handleNavigation = function(event) {
			var input = $(this),
				suggestions, active, next, prev;

			// Down
			if(event.which == 40) {
				suggestions = input.next('.suggestions');
				active = suggestions.find('li:not(.help).active').removeClass('active');
				next = active.next('li:not(.help):visible');

				// First
				if(active.length === 0 || next.length === 0) {
					suggestions.find('li:not(.help)').first().addClass('active');
				}

				// Next
				else {
					next.addClass('active');
				}
			}

			// Up
			else if(event.which == 38) {
				suggestions = input.next('.suggestions');
				active = suggestions.find('li:not(.help).active').removeClass('active');
				prev = active.prev('li:not(.help):visible');

				// First
				if(active.length === 0 || prev.length === 0) {
					suggestions.find('li:not(.help)').last().addClass('active');
				}

				// Next
				else {
					prev.addClass('active');
				}
			}

			// Enter
			else if(event.which == 13) {
				active = input.next('.suggestions').find('li:not(.help).active').text();

				select(active, input);
			}
		};

		var handleSelect = function(event) {
			var input = $(event.target).parent('.suggestions').prev('input');

			select(event.target.textContent, input);
		};

	/*-------------------------------------------------------------------------
		Suggestions
	-------------------------------------------------------------------------*/

		var load = function(input) {
			var suggestions = input.next('.suggestions');

			// Load suggestions
			$.ajax({
				type: 'GET',
				url: Symphony.Context.get('symphony') + '/ajax/query/',
				data: {
					'field_id': suggestions.attr('data-search-id'),
					'query': input.val()
				},
				success: function(result) {
					var help = suggestions.find('.help:first');

					// Clear existing suggestions
					clear(suggestions);

					// Add suggestions
					if(result.entries) {
						$.each(result.entries, function(index, data) {
							var suggestion = $('<li />', {
								text: data.value
							});

							if(help) {
								suggestion.insertBefore(help);
							}
							else {
								suggestions.append(suggestion);
							}
						});
					}
				}
			});
		};

		var select = function(value, input) {
			input.val(value);
			input.addClass('updated');
		};

		var clear = function(suggestions) {
			suggestions.find('li:not(.help)').remove();
		};

		// API
		return {
			init: init
		};
	}();

	$.fn.symphonySuggestions = function(options) {
		var objects = this,
			settings = {
				trigger: '{$',
				source: Symphony.Context.get('path') + '/ajax/parameters/'
			};

		$.extend(settings, options);

	/*-------------------------------------------------------------------------
		Initialisation
	-------------------------------------------------------------------------*/

		// Suggestions
		objects.addClass('suggestions');

		// Build suggestion list
		var suggestions = $('<ul class="suggestionlist" />').hide();

		// Disable autocomplete
		objects.find('input[type="text"]').attr('autocomplete', 'off');

		// Add suggestion
		$.ajax({
			type: 'GET',
			url: Symphony.Context.get('root') + settings.source,
			success: function(result) {
				$.each(result, function addSuggestions(index, name) {
					$('<li data-name="' + name + '">' + name + '</li>').appendTo(suggestions);
				});
			}
		});

	/*-------------------------------------------------------------------------
		Functions
	-------------------------------------------------------------------------*/

		function stayInFocus() {
			var active = suggestions.find('li.active'),
				distance;

			// Get distance
			if(!active.is(':visible:first')) {
				distance = ((active.prevAll(':visible').length + 1) * active.outerHeight()) - 180;
			}
			else {
				distance = 0;
			}

			// Focus
			suggestions.animate({
				'scrollTop': distance
			}, 150);
		}

	/*-------------------------------------------------------------------------
		Events
	-------------------------------------------------------------------------*/

		// Show suggestions
		objects.on('keyup.suggestions click.suggestions', 'input', function suggest() {
			var input = $(this),
				value = input.val(),
				selectionStart = input[0].selectionStart || 0,
				before = value.substring(0, selectionStart).split(' '),
				after = value.substr(selectionStart).split(' '),
				token = before[before.length - 1],
				param = before[before.length - 1] + after[0];

			// Token found
			if(token.indexOf(settings.trigger) === 0) {

				// Relocate suggestions
				if(input.nextAll('ul.suggestionlist').length === 0) {
					input.after(suggestions);
					suggestions.width(input.innerWidth());
					suggestions.find('.active').removeClass();
				}

				// Find suggestions
				var suggested = suggestions.find('li').hide().filter('[data-name^="' + token + '"]').filter('[data-name!="' + param + '"]').show();

				// Show suggestion list
				if(suggested.length > 0) {
					suggestions.show();
				}

				// Hide suggestion list
				else {
					suggestions.hide();
				}
			}

			// No token found
			else {
				suggestions.hide();
			}
		});

		// Hide suggestions
		objects.on('blur.suggestions', 'input', function suggest() {
			var current = $(this).next('ul.suggestionlist');

			setTimeout(function hideSuggestions() {
				current.hide();
			}, 200);
		});

		// Keyboard interactions
		objects.on('keydown.suggestions', 'input', function keyboardSuggestion(event) {
			if(suggestions.is(':visible')) {
				var active = suggestions.find('li.active');

				// Down
				if(event.which == 40) {
					event.preventDefault();
					var next = active.nextAll('li:visible:first');
					active.removeClass('active');

					// First
					if(active.length === 0 || next.length === 0) {
						suggestions.find('li:visible:first').addClass('active');
					}

					// Next
					else {
						next.addClass('active');
					}

					stayInFocus();
				}

				// Up
				if(event.which == 38) {
					event.preventDefault();
					var prev = active.prevAll('li:visible:first');
					active.removeClass('active');

					// last
					if(active.length === 0 || prev.length === 0) {
						suggestions.find('li:visible:last').addClass('active');
					}

					// Next
					else {
						prev.addClass('active');
					}

					stayInFocus();
				}

				// Enter
				if(event.which == 13) {
					event.preventDefault();
					active.trigger('click.suggestions');
				}
			}
		});

		// Highlight active suggestions
		suggestions.on('mouseover.suggestions', 'li', function hoverSuggestion() {
			suggestions.find('li').removeClass('active');
			$(this).addClass('active');
		});

		// Select
		suggestions.on('click.suggestions', 'li', function addSuggestion() {
			var suggestion = $(this).text(),
				input = suggestions.prev('input'),
				value = input.val(),
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
			input.trigger('focus');
		});

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(window.jQuery, window.Symphony);
