/**
 * @package assets
 */

(function($) {

	/**
	 * This plugin shows and hides elements based on the value of a select box. 
	 * If there is only one option, the select box will be hidden and 
	 * the single element will be shown.
	 *
	 * @name $.symphonyPickable
	 * @class
	 *
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {String} [options.pickables='.pickable'] Selector to find items to be pickable
	 *
	 *	@example

			$('.picker').symphonyPickable();
	 */
	$.fn.symphonyPickable = function(options) {
		var objects = $(this),
			settings = {
				container: '#contents',
				pickables: '.pickable'
			};

		$.extend(settings, options);

	/*-----------------------------------------------------------------------*/

		// Switch content
		objects.on('change.pickable', function(event) {
			var object = $(this),
				choice = object.val(),
				relation = object.attr('name') || object.attr('data-relation'),
				related = pickables.filter('[data-relation="' + relation + '"]'),
				selection = pickables.filter('#' + choice),
				request;

			// Hide all choices
			object.trigger('pickstart.pickable');
			related.hide();
			
			// Selection found
			if(selection.length == 1) {
				selection.show().trigger('pick.pickable');
				object.trigger('pickstop.pickable');
			}
			
			// Selection not found
			else {
				request = choice.data('request');
				
				// Fetch selection
				if(request) {
					$.ajax({
						type: 'GET',
						url: request,
						data: { 'choice': choice },
						dataType: 'html',
						success: function(selection) {
							content.append(selection);
							selection.trigger('pick.pickable');
							object.trigger('pickstop.pickable');
						}
					});
				}
			}
		});

	/*-----------------------------------------------------------------------*/
	
		var content = $(settings.content),
			pickables = $(settings.pickables);

		// Make pickable
		objects.addClass('pickable');

		// Prepare content picking
		objects.each(function() {
			var object = $(this),
				choices = object.find('option'),
				relation = object.attr('name') || object.attr('data-relation');

			// Multiple items
			if(choices.length > 1) {
				choices.each(function() {
					pickables.filter('#' + $(this).val()).attr('data-relation', relation).hide();
				});
			}

			// Single item
			else {
				object.hide();
			}
		});
		
		// Initialise content
		objects.trigger('change.pickable');
		
	/*-----------------------------------------------------------------------*/
		
		return objects;
	};

})(jQuery.noConflict());
