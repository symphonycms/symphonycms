/**
 * @package assets
 */

(function($) {

	/**
	 * This plugin allows items to be orderable.
	 *
	 * @name $.symphonyOrderable
	 * @class
	 *
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {String} [options.items='li'] Selector to find items to be orderable
	 * @param {String} [options.handles='*'] Selector to find children that can be grabbed to re-order
	 * @param {String} [options.ignore='input, textarea, select'] Selector to find elements that should not propagate to the handle
	 *
	 *	@example

			$('table').symphonyOrderable({
				items: 'tr',
				handles: 'td'
			});
	 */
	$.fn.symphonyOrderable = function(options) {
		var objects = this,
			settings = {
				items:				'li',
				handles:			'*',
				ignore:				'input, textarea, select'
			};

		$.extend(settings, options);

	/*-----------------------------------------------------------------------*/

		// Start ordering
		objects.on('mousedown.orderable', settings.items + ' ' + settings.handles, function(event) {
			var handle = $(this),
				item = handle.parents(settings.items),
				object = handle.parents('.orderable');

			// Needed to prevent browsers from selecting texts and focusing textinputs
			event.preventDefault();
			
			if(!handle.is(settings.ignore)) {
				object.trigger('orderstart.orderable', [item]);
				object.addClass('ordering');
				item.addClass('ordering');
			}
		});
		
		// Stop ordering
		objects.on('mouseup.orderable mouseleave.orderable', function(event) {
			var object = $(this),
				item = object.find('.ordering');
		
			if(object.is('.ordering')) {
				item.removeClass('ordering');
				object.removeClass('ordering');
				object.trigger('orderstop.orderable', [item]);
			}
		});
		
		// Reorder items
		$(document).on('mousemove.orderable', '.ordering:has(.ordering)', function(event) {
			var object = $(this),
				item = object.find('.ordering'),
				top = item.offset().top,
				bottom = top + item.outerHeight(),
				position = event.pageY,
				prev, next;

			// Remove text ranges
			if(window.getSelection) {
				window.getSelection().removeAllRanges();
			}
			
			// Move item up
			if(position < top) {
				prev = item.prev(settings.items);
				if(prev.length > 0) {
					item.insertBefore(prev);
					object.trigger('orderchange', [item]);
				}
			}
			
			// Move item down
			else if(position > bottom) {
				next = item.next(settings.items);
				if(next.length > 0) {
					item.insertAfter(next);
					object.trigger('orderchange', [item]);
				}
			}
		});

	/*-----------------------------------------------------------------------*/

		// Make orderable
		objects.addClass('orderable');

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(jQuery.noConflict());
