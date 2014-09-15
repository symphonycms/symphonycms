/**
 * @package assets
 */

(function($, Symphony) {

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
	 * @param {Integer} [options.delay=250] Time used to delay actions
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
				items: 'li',
				handles: '*',
				ignore: 'input, textarea, select, a',
				delay: 250
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
				object.data('ordering', 1);

				// Highlight item
				if(object.is('.selectable, .collapsible')) {

					// Delay ordering to avoid conflicts with scripts bound to the click event
					setTimeout(function() {
						if(object.data('ordering') == 1) {
							object.trigger('orderstart.orderable', [item]);
							item.addClass('ordering');
						}
					}, settings.delay);
				}
				else {
					object.trigger('orderstart.orderable', [item]);
					item.addClass('ordering');
				}
			}
		});

		// Stop ordering
		objects.on('mouseup.orderable mouseleave.orderable', function stopOrdering(event) {
			var object = $(this),
				item;

			if(object.data('ordering') == 1) {
				item = object.find('.ordering');
				item.removeClass('ordering');
				object.data('ordering', 0);
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
		$(document).on('mousemove.orderable', '.orderable:has(.ordering)', function order(event) {
			var object = $(this);
			if (object.data('ordering') != 1) {
				return;
			}
			// Only keep what we need from event object
			var pageY = event.pageY;
			Symphony.Utilities.requestAnimationFrame(function () {
				var item = object.find('.ordering');

				// If there is still an ordering item in DOM
				if (!item.length) {
					return;
				}

				var top = item.offset().top,
					bottom = top + item.outerHeight(),
					prev, next;

				// Remove text ranges
				if(window.getSelection) {
					window.getSelection().removeAllRanges();
				}

				// Move item up
				if(pageY < top) {
					prev = item.prev(settings.items);
					if(prev.length > 0) {
						item.insertBefore(prev);
						object.trigger('orderchange', [item]);
					}
				}

				// Move item down
				else if(pageY > bottom) {
					next = item.next(settings.items);
					if(next.length > 0) {
						item.insertAfter(next);
						object.trigger('orderchange', [item]);
					}
				}
			});
		});

	/*-------------------------------------------------------------------------
		Initialisation
	-------------------------------------------------------------------------*/

		// Make orderable
		objects.addClass('orderable');

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(window.jQuery, window.Symphony);
