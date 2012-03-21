/**
 * @package assets
 */

(function($) {

	/**
	 * @todo: documentation
	 */
	$.fn.symphonyDrawer = function(options) {
		var objects = this,
			wrapper = $('#wrapper'),
			context = $('#context'),
			contents = $('#contents'),
			settings = {
				verticalWidth: 300,
				speed: 'fast',
				button: null // TODO ?? for custom buttons
			};

		$.extend(settings, options);

	/*-----------------------------------------------------------------------*/

		// Expand drawer
		objects.on('expand.drawer', function(event, speed, stay) {
			var drawer = $(this),
				position = drawer.data('position'),
				top = contents.offset()['top'],
				verticals = $('.drawer.vertical-left, .drawer.vertical-right').filter(function(index) {
					return $(this).data('open');
				});

			drawer.trigger('expandstart.drawer');

			speed = (typeof speed === 'undefined' ? settings.speed : speed);
			stay = (typeof stay === 'undefined' ? false : true);

			// Close opened drawers from same region
			$('.drawer.' + position).filter(function(index) {
				return $(this).data('open');
			}).trigger('collapse.drawer', [speed, true]);

			if (position == 'vertical-left') {
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
					}
				});
			};
			if (position == 'vertical-right') {
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
					}
				});
			};
			if (position == 'horizontal') {
				drawer.animate({
					height: 'show'
				}, {
					duration: speed,
					step: function(now, fx) {
						verticals.trigger('update.drawer');
					},
					complete: function() {
						verticals.trigger('update.drawer');
					}
				});
			};

			wrapper.addClass('drawer-' + position);
			drawer.data('open', true);

			drawer.trigger('expandstop.drawer');
		});

		// Collapse drawer
		objects.on('collapse.drawer', function(event, speed, stay) {
			var drawer = $(this),
				position = drawer.data('position'),
				top = contents.offset()['top'],
				verticals = $('.drawer.vertical-left, .drawer.vertical-right').filter(function(index) {
					return $(this).data('open');
				});

			drawer.trigger('collapsestart.drawer');

			speed = (typeof speed === 'undefined' ? settings.speed : speed);
			stay = (typeof stay === 'undefined' ? false : true);

			if (position == 'vertical-left') {
				drawer.animate({
					width: 0
				}, {
					duration: speed,
					step: function(now, fx){
						if (!stay) {
							contents.css('margin-left', now);
						};
					},
					complete: function() {
						drawer.css({
							display: 'none'
						});
					}
				});
			};
			if (position == 'vertical-right') {
				drawer.animate({
					width: 0
				}, {
					duration: speed,
					step: function(now, fx){
						if (!stay) {
							contents.css('margin-right', now);
						};
					},
					complete: function() {
						drawer.css({
							display: 'none'
						});
					}
				});
			};
			if (position == 'horizontal') {
				drawer.animate({
					height: 'hide'
				}, {
					duration: speed,
					step: function(now, fx) {
						verticals.trigger('update.drawer');
					},
					complete: function() {
						verticals.trigger('update.drawer');
					}
				});
			};
			wrapper.removeClass('drawer-' + position);
			drawer.data('open', false);

			drawer.trigger('collapsestop.drawer');
		});

		// Update drawer
		objects.on('update.drawer', function(event) {
			var drawer = $(this),
			position = drawer.data('position');

			if (position == 'vertical-left' || position == 'vertical-left') {
				drawer.css({
					top: contents.offset()['top']
				});
			};
		});

	/*-----------------------------------------------------------------------*/

		objects.each(function() {
			var drawer = $(this),
				position = drawer.data('position'),
				button = context.find('.actions').find('.drawer[href="#' + drawer.attr('id') + '"]');

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