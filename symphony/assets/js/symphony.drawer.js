/**
 * @package assets
 */

(function($) {

	/**
	 * Drawers are hidden areas in the backend that are used to
	 * display additional content on request. There are three different
	 * types of drawers: horizontal, vertical left and vertical right.
	 *
	 * @name $.symphonyDrawer
	 * @class
	 *
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {Integer} [options.verticalWidth=300] Width of the vertical drawers
	 * @param {String} [options.speed='fast'] Animation speed
	 *
	 * @example

			$('.drawer').symphonyDrawer();
	 */
	$.fn.symphonyDrawer = function(options) {
		var objects = this,
			wrapper = $('#wrapper'),
			context = $('#context'),
			contents = $('#contents'),
			settings = {
				verticalWidth: 300,
				speed: 'fast'
			};

		$.extend(settings, options);

	/*-------------------------------------------------------------------------
		Events
	-------------------------------------------------------------------------*/

		// Expand drawer
		objects.on('expand.drawer', function expand(event, speed, stay) {
			var drawer = $(this),
				position = drawer.data('position'),
				buttons = $('.button.drawer'),
				button = buttons.filter('[href="#' + drawer.attr('id') + '"]'),
				samePositionButtons = buttons.filter('.' + position),
				context = drawer.data('context') ? '.' + drawer.data('context') : '',
				top = contents.offset().top,
				verticals = $('div.drawer.vertical-left, div.drawer.vertical-right').filter(function(index) {
					return $(this).data('open');
				});

			drawer.trigger('expandstart.drawer');

			speed = (typeof speed === 'undefined' ? settings.speed : speed);
			stay = (typeof stay === 'undefined' ? false : true);

			// update button state
			samePositionButtons.removeClass('selected');

			// Close opened drawers from same region
			$('.drawer.' + position).filter(function(index) {
				return $(this).data('open');
			}).trigger('collapse.drawer', [speed, true]);

			if (position === 'vertical-left') {
				drawer.css({
					top: top,
					width: 0,
					display: 'block'
				})
				.animate({
					width: settings.verticalWidth
				}, {
					duration: speed,
					step: function(now, fx){
						contents.css('margin-left', now + 1); // +1px right border
					},
					complete: function() {
						contents.css('margin-left', settings.verticalWidth + 1); // +1px right border
						drawer.trigger('expandstop.drawer');
					}
				});
			}
			else if (position === 'vertical-right') {
				drawer.css({
					top: top,
					width: 0,
					display: 'block'
				})
				.animate({
					width: settings.verticalWidth
				}, {
					duration: speed,
					step: function(now, fx){
						contents.css('margin-right', now + 1); // +1px left border
					},
					complete: function() {
						contents.css('margin-right', settings.verticalWidth + 1); // +1px right border
						drawer.trigger('expandstop.drawer');
					}
				});
			}
			else if (position === 'horizontal') {
				drawer.animate({
					height: 'show'
				}, {
					duration: speed,
					step: function(now, fx) {
						verticals.trigger('update.drawer');
					},
					complete: function() {
						verticals.trigger('update.drawer');
						drawer.trigger('expandstop.drawer');
					}
				});
			}

			button.addClass('selected');

			// store state
			if(Symphony.Support.localStorage === true) {
				window.localStorage['symphony.drawer.' + drawer.attr('id') + context] = 'opened';
			}

			wrapper.addClass('drawer-' + position);
			drawer.data('open', true);
		});

		// Collapse drawer
		objects.on('collapse.drawer', function collapse(event, speed, stay) {
			var drawer = $(this),
				position = drawer.data('position'),
				buttons = $('.button.drawer'),
				button = buttons.filter('[href="#' + drawer.attr('id') + '"]'),
				context = drawer.data('context') ? '.' + drawer.data('context') : '',
				top = contents.offset().top,
				verticals = $('div.drawer.vertical-left, div.drawer.vertical-right').filter(function(index) {
					return $(this).data('open');
				});

			drawer.trigger('collapsestart.drawer');

			speed = (typeof speed === 'undefined' ? settings.speed : speed);
			stay = (typeof stay === 'undefined' ? false : true);

			// update button state
			button.removeClass('selected');

			if (position === 'vertical-left') {
				drawer.animate({
					width: 0
				}, {
					duration: speed,
					step: function(now, fx){
						if (!stay) {
							contents.css('margin-left', now);
						}
					},
					complete: function() {
						drawer.css({
							display: 'none'
						})
						.trigger('collapsestop.drawer');
					}
				});
			}
			else if (position === 'vertical-right') {
				drawer.animate({
					width: 0
				}, {
					duration: speed,
					step: function(now, fx){
						if (!stay) {
							contents.css('margin-right', now);
						}
					},
					complete: function() {
						drawer.css({
							display: 'none'
						})
						.trigger('collapsestop.drawer');
					}
				});
			}
			else if (position === 'horizontal') {
				drawer.animate({
					height: 'hide'
				}, {
					duration: speed,
					step: function(now, fx) {
						verticals.trigger('update.drawer');
					},
					complete: function() {
						verticals.trigger('update.drawer');
						drawer.trigger('collapsestop.drawer');
					}
				});
			}

			// store state
			if(Symphony.Support.localStorage === true) {
				window.localStorage['symphony.drawer.' + drawer.attr('id') + context] = 'closed';
			}

			wrapper.removeClass('drawer-' + position);
			drawer.data('open', false);
		});

		// Update drawer
		objects.on('update.drawer', function update(event) {
			var drawer = $(this),
			position = drawer.data('position');

			if (position === 'vertical-left' || position === 'vertical-right') {
				drawer.css({
					top: contents.offset().top
				});
			}
		});

	/*-------------------------------------------------------------------------
		Initialisation
	-------------------------------------------------------------------------*/

		objects.each(function drawers() {
			var drawer = $(this),
				position = drawer.data('position'),
				button = $('.button.drawer[href="#' + drawer.attr('id') + '"]'),
				context = drawer.data('context') ? '.' + drawer.data('context') : '',
				storedState;

			// Initial state
			if (drawer.data('default-state') === 'opened') {
				drawer.data('open', true);
			}
			// Restore state
			if (Symphony.Support.localStorage === true) {
				storedState = window.localStorage['symphony.drawer.' + drawer.attr('id') + context];
				if (storedState === 'opened') {
					drawer.data('open', true);
				}
				else if (storedState === 'closed') {
					drawer.data('open', false);
				}
			}

			// Click event for the related button
			button.on('click.drawer', function(event) {
				event.preventDefault();
				!drawer.data('open') ? drawer.trigger('expand.drawer') : drawer.trigger('collapse.drawer');
			});

			// Initially opened drawers
			drawer.data('open') ? drawer.trigger('expand.drawer', [0]) : drawer.trigger('collapse.drawer', [0, true]);
		});

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(jQuery.noConflict());