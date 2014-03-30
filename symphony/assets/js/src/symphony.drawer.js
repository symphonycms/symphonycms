/**
 * @package assets
 */

(function($, Symphony) {

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
			contents = $('#contents'),
			form = contents.find('> form'),
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
				height = getHeight();

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
					width: 0,
					height: height,
					display: 'block'
				})
				.animate({
					width: settings.verticalWidth
				}, {
					duration: speed,
					step: function(now, fx){
						form.css('margin-left', now + 1); // +1px right border
					},
					complete: function() {
						form.css('margin-left', settings.verticalWidth + 1); // +1px right border
						drawer.trigger('expandstop.drawer');
					}
				});
			}
			else if (position === 'vertical-right') {
				drawer.css({
					width: 0,
					height: height,
					display: 'block'
				})
				.animate({
					width: settings.verticalWidth
				}, {
					duration: speed,
					step: function(now, fx){
						form.css('margin-right', now + 1); // +1px left border
					},
					complete: function() {
						form.css('margin-right', settings.verticalWidth + 1); // +1px right border
						drawer.trigger('expandstop.drawer');
					}
				});
			}
			else if (position === 'horizontal') {
				drawer.animate({
					height: 'show'
				}, {
					duration: speed,
					complete: function() {
						drawer.trigger('expandstop.drawer');
					}
				});
			}

			button.addClass('selected');

			// store state
			if(Symphony.Support.localStorage === true) {
				// Put in a try/catch incase we exceed storage space
				try {
					window.localStorage['symphony.drawer.' + drawer.attr('id') + context] = 'opened';
				}
				catch(e) {
					window.onerror(e.message);
				}
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
				context = drawer.data('context') ? '.' + drawer.data('context') : '';

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
							form.css('margin-left', now);
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
							form.css('margin-right', now);
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
					complete: function() {
						drawer.trigger('collapsestop.drawer');
					}
				});
			}

			// store state
			if(Symphony.Support.localStorage === true) {
				// Put in a try/catch incase we exceed storage space
				try {
					window.localStorage['symphony.drawer.' + drawer.attr('id') + context] = 'closed';
				}
				catch(e) {
					window.onerror(e.message);
				}
			}

			wrapper.removeClass('drawer-' + position);
			drawer.data('open', false);
		});

		// Resize drawers
		$(window).on('resize.drawer load.drawer', function() {
			var height = getHeight();
			objects.filter('.vertical-left, .vertical-right').css('height', height);
		});

	/*-------------------------------------------------------------------------
		Utilities
	-------------------------------------------------------------------------*/

		var getHeight = function() {
			var height = Math.max(window.innerHeight - contents[0].offsetTop - 1, contents[0].clientHeight);

			return height;
		};

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

})(window.jQuery, window.Symphony);
