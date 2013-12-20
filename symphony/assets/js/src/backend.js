/**
 * Symphony backend initialisation
 *
 * @package assets
 */

(function($) {
	$(document).ready(function() {

		// Get main elements
		Symphony.Elements.window = $(window);
		Symphony.Elements.html = $('html').addClass('js-active');
		Symphony.Elements.body = $('body');
		Symphony.Elements.wrapper = $('#wrapper');
		Symphony.Elements.header = $('#header');
		Symphony.Elements.nav = $('#nav');
		Symphony.Elements.session = $('#session');
		Symphony.Elements.context = $('#context');
		Symphony.Elements.contents = $('#contents');

		// Initialise core language strings
		Symphony.Language.add({
			'Are you sure you want to proceed?': false,
			'Reordering was unsuccessful.': false,
			'Change Password': false,
			'Remove File': false,
			'Untitled Field': false,
			'The field “{$title}” ({$type}) has been removed.': false,
			'Undo?': false,
			'untitled': false,
			'Expand all fields': false,
			'Collapse all fields': false
		});

		// Set basic context information
		var user = Symphony.Elements.session.find('li:first a');
		Symphony.Context.add('user', {
			fullname: user.text(),
			name: user.data('name'),
			type: user.data('type'),
			id: user.data('id')
		});
		Symphony.Context.add('lang', Symphony.Elements.html.attr('lang'));

		// Render view
		Symphony.View.render();

	});
})(window.jQuery);
