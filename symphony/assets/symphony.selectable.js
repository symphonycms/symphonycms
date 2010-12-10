/**
 * @package assets
 */

(function($) {

	/**
	 * Selectable plugin
	 */
 	$.fn.symphonySelectable = function(custom_settings) {
		var objects = this;
		var settings = {
			items: 'tbody tr',
			handles: 'td'
		};
		
		$.extend(settings, custom_settings);
		
	/*-----------------------------------------------------------------------*/
		
		// Make selectable
		objects.addClass('selectable');
		
		// Process selections
		objects.delegate(settings.items, 'click', function(event) {
			var item = $(this),
				items = item.siblings().andSelf(),
				selection, first, last;
			
			// Range selection
			if((event.shiftKey) && items.filter('.selected').size() > 0) {
				
				// Select upwards
				if(item.prevAll().filter('.selected').size() > 0) {
					first = items.filter('.selected:first').index();
					last = item.index() + 1;
				}
				
				// Select downwards
				else {
					first = item.index();
					last = items.filter('.selected:last').index() + 1;
				}
				
				// Get selection
				selection = items.slice(first, last);
				
				// Deselect items outside the selection range
				items.filter('.selected').not(selection).removeClass('selected').trigger('deselect');
				
				// Select range
				selection.addClass('selected').trigger('select');
			}
			
			// Single selection
			else {
			
				// Press meta key to adjust current range, otherwise the selection will be removed
				if(!event.metaKey) {
					items.not(item).filter('.selected').removeClass('selected').trigger('deselect');
				}
				
				// Toggle selection
				item.toggleClass('selected');
				
				// Fire event
				if(item.is('.selected')) {
					item.trigger('select');
				}
				else {
					item.trigger('deselect');
				}		
			}

		});
				
		// Handle highlighting conflicts between orderable and selectable items
		objects.find(settings.handles).bind('mousedown', function(event) {
			var object = $(this).parents(objects[0].tagName).addClass('selecting');
			window.setTimeout(function() {
			    object.removeClass('selecting');
			}, 50);
		});	
		
		// Remove all selections by doubleclicking the body
		$('body').bind('dblclick', function() {
			objects.find(settings.items).removeClass('selected');
		});
		
		// Return objects
		return objects;

	};

})(jQuery.noConflict());
