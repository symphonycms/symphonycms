/**
 * @package assets
 */

(function($, Symphony) {
	'use strict';

	// holds up all instances
	var instances = $();
	// raf ID
	var scrollRequestId = 0;
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
				container: null
			};

		$.extend(settings, options);


		/*---------------------------------------------------------------------
			Initialisation
		---------------------------------------------------------------------*/

		objects.each(function createOneAffix() {
			var item = $(this);
			// use parent as default container
			if (!settings.container) {
				settings.container = item.parent();
			}
			// resolve jQuery object
			else {
				settings.container = $(settings.container);
			}

			item.addClass('js-affix');

			// save settings
			item.data('affix-settings', settings);

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
		//Symphony.Utilities.craf(scrollRequestId);
		scrollRequestId = Symphony.Utilities.raf(function affixScrollRaf() {
			instances.each(function affixScrollOne() {
				var item = $(this);
				var settings = item.data('affix-settings');
				var ctnOffset = settings.container.offset().top;
				var ctnHeight = settings.container.height();
				var cssClass = 'js-affix-scroll';
				var top = '';
				if (scrollTop < ctnOffset) {
					cssClass = 'js-affix-top';
				} else if (scrollTop > ctnOffset + ctnHeight) {
					cssClass = 'js-affix-bottom';
					top = (scrollTop - ctnOffset) + 'px';
				}
				item
					.removeClass('js-affix-scroll js-affix-top js-affix-bottom')
					.addClass(cssClass)
					item.css({top: top});
			});
		});
	});

})(window.jQuery, window.Symphony);
