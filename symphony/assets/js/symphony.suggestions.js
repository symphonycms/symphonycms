/**
 * @package assets
 */

(function($) {

	$.fn.symphonySuggestions = function(options) {
		var objects = this,
			settings = {
				trigger: '{$',
				source: '/symphony/ajax/parameters/'
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
			type: 'POST',
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
		objects.on('keyup.suggestions click.suggestions', 'input', function suggest(event) {
			var input = $(this),
				value = input.val();
				selectionStart = input[0].selectionStart || 0,
				selectionEnd = input[0].selectionEnd || 0,
				before = value.substring(0, selectionStart).split(' '),
				after = value.substr(selectionStart).split(' '),
				token = before[before.length - 1],
				param = before[before.length - 1] + after[0];

			// Token found
			if(token.indexOf(settings.trigger) == 0) {
			
				// Relocate suggestions
				if(input.nextAll('ul.suggestionlist').length == 0) {
					input.after(suggestions);
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
		objects.on('blur.suggestions', 'input', function suggest(event) {
			var current = $(this).next('ul.suggestionlist');
			
			setTimeout(function hideSuggestions() {
				current.hide();
			}, 200)
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
					if(active.length == 0 || next.length == 0) {
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
					if(active.length == 0 || prev.length == 0) {
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
		suggestions.on('mouseover.suggestions', 'li', function hoverSuggestion(event) {
			suggestions.find('li').removeClass('active');
			$(this).addClass('active');
		});
		
		// Select
		suggestions.on('click.suggestions', 'li', function addSuggestion(event) {
			var suggestion = $(this).text(),
				input = suggestions.prev('input'),
				value = input.val(),
				selectionStart = input[0].selectionStart || 0,
				selectionEnd = input[0].selectionEnd || 0,
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
	
})(jQuery.noConflict());
