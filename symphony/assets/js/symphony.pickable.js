/**
 * @package assets
 */

(function($) {

	/**
	 * Pickable allows to show and hide elements based on the value of a select box. 
	 * 
	 * Each option is mapped to its associated content by matching the option `value` 
	 * with the content `id`. If the option value is numeric, Pickable prefices it 
	 * with `choice`. Only the content of the currently selected option is 
	 * shown, all other elements associated with the given select box are hidden. 
	 *
	 * If no content element of the given `id` is found, Pickable checks for a 
	 * `data-request` attribute on the selected option. If a `data-request` URL is set, 
	 * Pickable tries to fetch the content remotely and expects an content element with 
	 * no additional markup in return.
	 *
	 * @name $.symphonyPickable
	 * @class
	 *
	 * @param {Object} options An object containing the element selectors specified below
	 * @param {String} [options.content='#contents'] Selector to find the container that wraps all pickable elements
	 * @param {String} [options.pickables='.pickable'] Selector used to find pickable elements
	 *
	 * @example

			$('.picker').symphonyPickable();
	 */
	$.fn.symphonyPickable = function(options) {
		var objects = $(this),
			settings = {
				content: '#contents',
				pickables: '.pickable'
			};

		$.extend(settings, options);

	/*-------------------------------------------------------------------------
		Events
	-------------------------------------------------------------------------*/

		// Switch content
		objects.on('change.pickable', function pick(event) {
			var object = $(this),
				choice = object.val(),
				relation = object.attr('id') || object.attr('name'),
				related = $(settings.pickables + '[data-relation="' + relation + '"]'),
				picked, request;

			// Handle numeric values
			if($.isNumeric(choice) === true) {
				choice = 'choice' + choice;
			}

			// Hide all choices
			object.trigger('pickstart.pickable');
			related.hide();

			// Selection found
			picked = $('#' + choice);
			if(picked.length > 0) {
				picked.show().trigger('pick.pickable');
				object.trigger('pickstop.pickable');
			}

			// Selection not found
			else {
				request = object.data('request');

				// Fetch picked element
				if(request) {
					$.ajax({
						type: 'GET',
						url: request,
						data: { 'choice': choice },
						dataType: 'html',
						success: function(remote) {
							content.append(remote);
							remote.trigger('pick.pickable');
							object.trigger('pickstop.pickable');
						}
					});
				}
			}
		});

	/*-------------------------------------------------------------------------
		Initialisation
	-------------------------------------------------------------------------*/

		var content = $(settings.content);

		// Set up relationships
		objects.each(function init() {
			var object = $(this),
				relation = object.attr('id') || object.attr('name');

			object.find('option').each(function() {
				$('#' + $(this).val()).attr('data-relation', relation);
			});
		});

		// Show picked content
		objects.trigger('change.pickable');

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(window.jQuery);
