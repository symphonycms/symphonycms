/**
 * @package assets
 */

(function($) {

	/**
	 * This plugin makes items orderable.
	 *
	 * @param {Object} custom_settings
	 *  An object specifying the item to be ordered, their handles and 
	 *  a initialization delay
	 */
	$.fn.symphonyOrderable = function(custom_settings) {
		var objects = this,
			settings = {
				items:				'li',
				handles:			'*',
				delay_initialize:	false
			};

		$.extend(settings, custom_settings);

	/*-----------------------------------------------------------------------*/

		objects = objects.map(function() {
			var object = this,
				state = null;

			var start = function(item) {		
				state = {
					item:		item,
					min:		null,
					max:		null,
					delta:		0
				};

				$(document).bind('mousemove.orderable', change);
				$(document).bind('mouseup.orderable', stop);

				$(document).mousemove();
			};

			var change = function(event) {
				var item = state.item,
					target, next, top = event.pageY,
					a = item.height(),
					b = item.offset().top,
					prev = item.prev();

				state.min = Math.min(b, a + (prev.size() > 0 ? prev.offset().top : -Infinity));
				state.max = Math.max(a + b, b + (item.next().height() ||  Infinity));

				if(!object.is('.ordering')) {
					object.addClass('ordering');
					item.addClass('ordering');
					object.trigger('orderstart', [state.item]);
				}

				if(top < state.min) {
					target = item.prev(settings.items);

					while (true) {
						state.delta--;
						next = target.prev(settings.items);

						if(next.length === 0 || top >= (state.min -= next.height())) {
							item.insertBefore(target); break;
						}

						target = next;
					}
				}

				else if(top > state.max) {
					target = item.next(settings.items);

					while (true) {
						state.delta++;
						next = target.next(settings.items);

						if(next.length === 0 || top <= (state.max += next.height())) {
							item.insertAfter(target); 
							break;
						}

						target = next;
					}
				}

				object.trigger('orderchange', [state.item]);

				return false;
			};

			var stop = function() {
				$(document).unbind('.orderable');

				if(state != null) {
					object.removeClass('ordering');
					state.item.removeClass('ordering');
					object.trigger('orderstop', [state.item]);
					state = null;
				}

				return false;
			};

		/*-------------------------------------------------------------------*/

			if(object instanceof $ === false) {
				object = $(object);
			}

			object.orderable = {
				cancel: function() {
					$(document).unbind('.orderable');

					if(state != null) {
						object.removeClass('ordering');
						state.item.removeClass('ordering');
						object.trigger('ordercancel', [state.item]);
						state = null;
					}
				},

				initialize: function() {
					object.addClass('orderable');
					object.delegate(settings.items + ' ' + settings.handles, 'mousedown.orderable', function(event) {
						var target = $(event.target),
							item = $(this).parents(settings.items);

						// Keep fields accessible inside orderable list
						if(!target.is('input, textarea, select')) {
							start(item);
							return false;
						}
					});
				}
			};

			if(settings.delay_initialize !== true) {
				object.orderable.initialize();
			}

			return object;
		});

		return objects;
	};

})(jQuery.noConflict());
