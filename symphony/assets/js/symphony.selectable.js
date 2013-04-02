/**
 * @package assets
 */

(function($) {

	/**
	 * Create selectable elements. Clicking an item will select it
	 * by adding the class <code>.selected</code>. Holding down the shift key
	 * while clicking multiple items creates a selection range. Holding the meta key
	 * (which is <code>cmd</code> on a Mac or <code>ctrl</code> on Windows) allows
	 * the selection of multiple items or the modification of an already selected
	 * range of items. Doubleclicking outside the selection list will
	 * remove the selection.
	 *
	 * @name $.symphonySelectable
	 * @class
	 *
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {String} [options.items='tbody tr:has(input)'] Selector to find items that are selectable
	 * item. Needed to properly handle item highlighting when used in connection with the orderable plugin
	 * @param {String} [options.ignore='a'] Selector to find elements that should not propagate to the handle
	 * @param {String} [optinos.mode='single'] Either 'default' (click removes other selections) or 'additive' (click adds to exisiting selection)
	 *
	 * @example

			var selectable = $('table').symphonySelectable();
			selectable.find('a').mousedown(function(event) {
				event.stopPropagation();
			});
	 */
	$.fn.symphonySelectable = function(options) {
		var objects = this,
			settings = {
				items: 'tbody tr:has(input)',
				ignore: 'a',
				mode: 'single'
			};

		$.extend(settings, options);

	/*-------------------------------------------------------------------------
		Events
	-------------------------------------------------------------------------*/

		// Select
		objects.on('click.selectable', settings.items, function select(event) {
			var item = $(this),
				items = item.siblings().addBack(),
				object = $(event.liveFired),
				target = $(event.target),
				selection, deselection, first, last;

			// Ignored elements
			if(target.is(settings.ignore)) {
				return true;
			}

			// Remove text ranges
			if(window.getSelection) {
				window.getSelection().removeAllRanges();
			}

			// Range selection
			if((event.shiftKey) && items.filter('.selected').length > 0 && !object.is('.single')) {

				// Select upwards
				if(item.prevAll().filter('.selected').length > 0) {
					first = items.filter('.selected:first').index();
					last = item.index() + 1;
				}

				// Select downwards
				else {
					first = item.index();
					last = items.filter('.selected:last').index() + 1;
				}

				// Get selection
				selection = items.slice(first, last);

				// Deselect items outside the selection range
				deselection = items.filter('.selected').not(selection).removeClass('selected').trigger('deselect.selectable');
				deselection.find('input[type="checkbox"]').prop('checked', false);

				// Select range
				selection.addClass('selected').trigger('select.selectable');
				selection.find('input[type="checkbox"]').prop('checked', true);
			}

			// Single selection
			else {

				// Press meta or ctrl key to adjust current range, otherwise the selection will be removed
				if((!event.metaKey && !event.ctrlKey && settings.mode != 'additive' &&  !target.is('input')) || object.is('.single')) {
					deselection = items.not(item).filter('.selected').removeClass('selected').trigger('deselect.selectable');
					deselection.find('input[type="checkbox"]').prop('checked', false);
				}

				// Toggle selection
				if(item.is('.selected')) {
					item.removeClass('selected').trigger('deselect.selectable');
					item.find('input[type="checkbox"]').prop('checked', false);
				}
				else {
					item.addClass('selected').trigger('select.selectable');
					item.find('input[type="checkbox"]').prop('checked', true);
				}
			}

		});

		// Remove all selections by doubleclicking the body
		$('body').bind('dblclick.selectable', function removeAllSelection() {
			objects.find(settings.items).removeClass('selected').trigger('deselect.selectable');
		});

	/*-------------------------------------------------------------------------
		Initialisation
	-------------------------------------------------------------------------*/

		// Make selectable
		objects.addClass('selectable');

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(window.jQuery);
