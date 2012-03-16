/**
 * @package assets
 */

(function($) {

	/**
	 * @todo: documentation
	 */
	$.fn.symphonyDrawer = function(options) {
		var objects = this,
			settings = {};

		$.extend(settings, options);

	/*-----------------------------------------------------------------------*/

		// Toggle drawer
		objects.on('click.drawer', function(event) {
			// Either trigger 'expand.drawer' or ' collapse.drawer'
		});

		// Expand drawer
		objects.on('expand.drawer', function(event, speed) {
			objects.trigger('expandstart.drawer');
			// do stuff, if speed is passed in function call use it for the animation
			objects.trigger('expandstop.drawer');
		});

		// Collapse drawer
		objects.on('collapse.drawer', function(event) {
			objects.trigger('collapsestart.drawer');
			// do stuff
			objects.trigger('collapsestop.drawer');
		});

	/*-----------------------------------------------------------------------*/

		// Build interface
		objects.each(function() {
			// Manipulate the DOM, if needed.
			// Trigger expand for drawers with `data-open="true"`
			// e. g. $(foo).trigger('expand.drawer', [0])
		});

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(jQuery.noConflict());