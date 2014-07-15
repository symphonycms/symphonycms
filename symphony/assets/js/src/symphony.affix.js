/**
 * @package assets
 */

(function($, Symphony) {
	'use strict';

	// holds up all instances
	var instances = $();
	// last scroll position
	var scrollTop = 0;

	/**
	 * Create affix elements.
	 * Affix elements follow the scroll and are constrain by their container.
	 *
	 * @since Symphony 2.5
	 *
	 * @name $.symphonyAffix
	 * @class
	 *
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {String} [options.container=$(this).parent()] Selector to find affix container
	 *
	 * @example

			var affix = $('#affix').symphonyAffix({
				container:		'.parent',
			});
	 */
	$.fn.symphonyAffix = function(options) {
		var objects = $(this),
			settings = {
				// public
				container: null,
				// private
				top: 0,
				freespace: 0,
				bottom: 0,
				height: 0,
				maxtop: 0
			};

		$.extend(settings, options);


		/*---------------------------------------------------------------------
			Initialisation
		---------------------------------------------------------------------*/

		objects.each(function createOneAffix() {
			var itemSettings = $.extend({}, settings);
			var item = $(this);
			var updateItemSettings = function () {
				itemSettings.top = itemSettings.container.offset().top;
				itemSettings.freespace = itemSettings.container.height();
				itemSettings.bottom = itemSettings.top + itemSettings.freespace;
				itemSettings.height = item.height();
				itemSettings.maxtop = (itemSettings.freespace - itemSettings.height) + 'px';
			};

			// use parent as default container
			if (!itemSettings.container) {
				itemSettings.container = item.parent();
			}

			// resolve jQuery object
			else {
				itemSettings.container = $(itemSettings.container);
			}

			// cache cssom values
			updateItemSettings();
			item.on('updatesettings.affix', updateItemSettings);

			item.addClass('js-affix');

			// save instance settings
			item.data('affix-settings', itemSettings);

			// register instance
			instances = instances.add(item);
		});

		// Init
		$(window).triggerHandler('scroll');

		return objects;
	};

	/*-----------------------------------------------------------------------*/

	// One listener for all instances
	$(window).scroll(function affixScroll(e) {
		scrollTop = $(this).scrollTop();
		Symphony.Utilities.requestAnimationFrame(function affixScrollRaf() {
			instances.each(function affixScrollOne() {
				var item = $(this);
				var settings = item.data('affix-settings');
				var cssClass = 'js-affix-scroll';
				var top = '';
				if (scrollTop < settings.top) {
					cssClass = 'js-affix-top';
				} else if (scrollTop > settings.bottom) {
					cssClass = 'js-affix-bottom';
					top = settings.maxtop;
				}
				// Do changes only if state changes
				if (!item.hasClass(cssClass)) {
					item
						.removeClass('js-affix-scroll js-affix-top js-affix-bottom')
						.addClass(cssClass)
						.css({top: top});
				}
			});
		});
	});

})(window.jQuery, window.Symphony);
