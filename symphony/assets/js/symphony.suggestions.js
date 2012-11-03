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
	
		// Build suggestion list
		var suggestions = $('<ul class="suggestions" />').hide();

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
		Events
	-------------------------------------------------------------------------*/

		// Show suggestions
		objects.on('keyup.suggestions click.suggestions', 'input', function suggest(event) {
			var input = $(this),
				value = input.val();
				selectionStart = input[0].selectionStart || 0,
				selectionEnd = input[0].selectionEnd || 0,
				before = value.substring(0, selectionStart).split(' '),
				token = before[before.length - 1];

			// Token found
			if(token.indexOf(settings.trigger) == 0) {
			
				// Relocate suggestions
				if(input.nextAll('ul.suggestions').length == 0) {
					input.after(suggestions);
				}
	
				// Find suggestions
				var suggested = suggestions.find('li').hide().filter('[data-name^="' + token + '"]').show();
				
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
			setTimeout(function hideSuggestions() {
				$(this).next('ul.suggestions').hide();
			}, 200)
		});
		
		// Select
		suggestions.on('click.suggestions', 'li', function addSuggestion() {
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
			input[0].selectionStart = before.length + suggestion.length;
			input[0].selectionEnd = before.length + suggestion.length;
		});

	/*-----------------------------------------------------------------------*/

		return objects;
	};
	
	$(document).on('ready', function() {
		$('.filters-duplicator').symphonySuggestions();
	});
	
})(jQuery.noConflict());
