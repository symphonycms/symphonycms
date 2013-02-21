/**
 * @package assets
 */

(function($) {

	/**
	 * Create orderable elements.
	 *
	 * @name $.symphonyOrderable
	 * @class
	 *
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {String} [options.items='li'] Selector to find items to be orderable
	 * @param {String} [options.handles='*'] Selector to find children that can be grabbed to re-order
	 * @param {String} [options.ignore='input, textarea, select'] Selector to find elements that should not propagate to the handle
	 * @param {String} [options.delay=250] Time used to delay actions
	 *
	 * @example

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
				ignore:				'input, textarea, select, a',
				delay:				250
			};

		$.extend(settings, options);

	/*-------------------------------------------------------------------------
		Events
	-------------------------------------------------------------------------*/

		// Start ordering
		objects.on('mousedown.orderable', settings.items + ' ' + settings.handles, function startOrdering(event) {
			var handle = $(this),
				item = handle.parents(settings.items),
				object = handle.parents('.orderable');

			// Needed to prevent browsers from selecting texts and focusing textinputs
			if(!$(event.target).is('input, textarea')) {
				event.preventDefault();
			}

			if(!handle.is(settings.ignore) && !$(event.target).is(settings.ignore)) {
				object.trigger('orderstart.orderable', [item]);
				object.addClass('ordering');

				// Highlight item
				if(object.is('.selectable, .collapsible')) {

					// Delay ordering to avoid conflicts with scripts bound to the click event
					object.trigger('orderstartlock', [item]);
					setTimeout(function() {
						if(object.is('.ordering')) {
							item.addClass('ordering');
							object.trigger('orderstartunlock', [item]);
						}
					}, settings.delay);
				}
				else {
					item.addClass('ordering');
				}
			}
		});

		// Stop ordering
		objects.on('mouseup.orderable mouseleave.orderable', function stopOrdering(event) {
			var object = $(this),
				item = object.find('.ordering');

			if(object.is('.ordering')) {
				item.removeClass('ordering');
				object.removeClass('ordering');
				object.trigger('orderstop.orderable', [item]);

				// Lock item to avoid conflicts with scripts bound to the click event
				object.trigger('orderstoplock.orderable', [item]);
				item.addClass('locked');
				setTimeout(function() {
					item.removeClass('locked');
					object.trigger('orderstopunlock.orderable', [item]);
				}, settings.delay);
			}
		});

		// Order items
		$(document).on('mousemove.orderable', '.ordering:has(.ordering)', function order(event) {
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

	/*-------------------------------------------------------------------------
		Initialisation
	-------------------------------------------------------------------------*/

		// Make orderable
		objects.addClass('orderable');

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(window.jQuery);
