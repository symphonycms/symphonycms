/**
 * @package assets
 */

(function($) {

	/**
	 * Show and hide elements based on the value of a select box.
	 *
	 * Pickable knows two display modes for the select box it is applied to: 
	 * `always` and `multiple`. If the mode is set to `always`, the select box 
	 * will appear independent of the option count. If the mode is set to `multiple`, 
	 * the select box will only display, if more than one option exists. The modes 
	 * can be set via the `data-display` attribute, it defaults to `multiple` for 
	 * compatiblity reasons.
	 *
	 * Relations between the select box and the content instances are mapped 
	 * automatically using the `data-relation` attribute or – if it does not exist – 
	 * the select box name. 
	 *
	 * Options are linked to their content areas using their values: 
	 * the `id` of a pickable content area has to match the option's text. 
	 * If no content area of the given `id` is found, Pickable checks for a `data-request` 
	 * attribute containing an URL to fetch the needed content remotely. In order to do so, 
	 * the given URL has to return the content `div` without any additional wrapping markup.
	 *
	 * @name $.symphonyPickable
	 * @class
	 *
	 * @param {Object} options An object containing the elements specified below
	 * @param {String} [options.content='#contents'] Selector to find the container that wrapps all pickable areas
	 * @param {String} [options.pickables='.pickable'] Selector to find items to be pickable
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
				relation = object.attr('data-relation') || object.attr('name'),
				related = pickables.filter('[data-relation="' + relation + '"]'),
				selection = pickables.filter('#' + choice),
				request;

			// Hide all choices
			object.trigger('pickstart.pickable');
			related.hide();

			// Selection found
			if(selection.length == 1) {
				selection.show().trigger('pick.pickable');
				object.trigger('pickstop.pickable');
			}

			// Selection not found
			else {
				request = object.data('request');

				// Fetch selection
				if(request) {
					$.ajax({
						type: 'GET',
						url: request,
						data: { 'choice': choice },
						dataType: 'html',
						success: function(selection) {
							content.append(selection);
							selection.trigger('pick.pickable');
							object.trigger('pickstop.pickable');
						}
					});
				}
			}
		});

	/*-------------------------------------------------------------------------
		Initialisation
	-------------------------------------------------------------------------*/

		var content = $(settings.content),
			pickables = $(settings.pickables);

		// Prepare picking and show content of initially selected option
		objects.each(function init() {
			var object = $(this),
				choices = object.find('option'),
				relation = object.attr('name') || object.attr('data-relation'),
				display = object.attr('data-display') || 'multiple';

			// Set up relationships
			choices.each(function() {
				pickables.filter('#' + $(this).val()).attr('data-relation', relation).hide();
			});

			// Hide select boxes with single option
			if(choices.length == 1 && display == 'multiple') {
				object.hide();
			}
		});

		// Initialise content
		objects.trigger('change.pickable');

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(window.jQuery);
