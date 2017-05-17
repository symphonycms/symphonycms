/**
 * Symphony backend initialisation
 *
 * @package assets
 */

(function($, Symphony) {

	// Set environment
	var environment = (function () {
		var env = document.getElementById('environment');
		return env ? JSON.parse(env.textContent) : {};
	})();
	Symphony.Context.add(null, environment);

	// Get translations
	Symphony.Language.add({
		'Are you sure you want to proceed?': false,
		'Reordering was unsuccessful.': false,
		'Change Password': false,
		'Remove File': false,
		'Untitled Field': false,
		'The field “{$title}” ({$type}) has been removed.': false,
		'Undo?': false,
		'untitled': false,
		'Expand all': false,
		'Collapse all': false,
		'drag to reorder': false,
		'Please reset your password': false,
		'required': false,
		'Click to select': false,
		'Type to search': false,
		'Clear': false,
		'Search for {$item}': false,
		'Add filter': false,
		'filtered': false,
		'None': false,
		'Clear filters': false,
		'Apply filters': false,
		'The Symphony calendar widget has been disabled because your system date format is currently not supported. Try one of the following instead or disable the calendar in the field settings:': false,
		'no leading zero': false
	});

	// Initialise backend
	$(document).ready(function() {

		// Cache main elements
		Symphony.Elements.window = $(window);
		Symphony.Elements.html = $('html').addClass('js-active');
		Symphony.Elements.body = $('body');
		Symphony.Elements.wrapper = $('#wrapper');
		Symphony.Elements.header = $('#header');
		Symphony.Elements.nav = $('#nav');
		Symphony.Elements.session = $('#session');
		Symphony.Elements.context = $('#context');
		Symphony.Elements.breadcrumbs = $('#breadcrumbs');
		Symphony.Elements.contents = $('#contents');

		// Create context id
		var path = Symphony.Context.get('path');
		var route = Symphony.Context.get('route');
		if (path && route) {
			var contextId = (path + route).split('/').filter(function(part) {
				return (part != 'edit' && part != 'new' && part != 'created' && part != 'saved' && part != '');
			}).join('.');
			Symphony.Context.add('context-id', contextId);
		}

		// Render view
		Symphony.View.render();

		// Update state to canonical url
		if (window.history.replaceState) {
			// Let extensions read the window.location, delay change on load
			$(window).load(function () {
				$('head > link[rel="canonical"][href]').eq(0).each(function () {
					var href = $(this).attr('href');
					if (href) {
						window.history.replaceState(document.title, null, href);
					}
				});
			});
		}
	});

})(window.jQuery, window.Symphony);
