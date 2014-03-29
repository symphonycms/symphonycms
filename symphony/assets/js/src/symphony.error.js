/**
 * @package assets
 */

(function($, Symphony) {

	$(document).ready(function() {

		// Init collapsibles
		var collapsible = $('.frame').symphonyCollapsible({
			items: '> li',
			handles: '.frame-header',
			content: '.content',
			save_state: true,
			storage: 'symphony.collapsible.error'
		});

		// Hide backtrace and query log by default
		if(!('symphony.collapsible.error.0.collapsed' in window.localStorage)) {
			collapsible.trigger('collapse.collapsible', [0]);
		}
	});

})(window.jQuery, window.Symphony);
