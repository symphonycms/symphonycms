/**
 * @package assets
 */

(function($, Symphony) {

	/**
	 * Duplicators are advanced lists used throughout the
	 * Symphony backend to manage repeatable content.
	 *
	 * @name $.symphonyDuplicator
	 * @class
	 *
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {String} [options.instances='> li:not(.template)'] Selector to find children to use as instances
	 * @param {String} [options.templates='> li.template'] Selector to find children to use as templates
	 * @param {String} [options.headers='> :first-child'] Selector to find the header part of each instance
	 * @param {String} [options.perselect=false] Default option for the selector
	 * @param {Boolean} [options.orderable=false] Can instances be ordered
	 * @param {Boolean} [options.collapsible=false] Can instances be collapsed
	 * @param {Boolean} [options.constructable=true] Allow construction of new instances
	 * @param {Boolean} [options.destructable=true] Allow destruction of instances
	 * @param {Integer} [optionss.minimum=0] Do not allow instances to be removed below this limit
	 * @param {Integer} [options.maximum=1000] Do not allow instances to be added above this limit,
	 * @param {Integer} [options.delay=250'] Time delay for animations
	 *
	 * @example

			$('.duplicator').symphonyDuplicator({
				orderable: true,
				collapsible: true
			});
	 */
	$.fn.symphonyDuplicator = function(options) {
		var objects = this,
			settings = {
				instances: '> li:not(.template)',
				templates: '> li.template',
				headers: '> :first-child',
				preselect: false,
				orderable: false,
				collapsible: false,
				constructable: true,
				destructable: true,
				save_state: true,
				minimum: 0,
				maximum: 1000,
				delay: 250
			};

		$.extend(settings, options);

	/*-----------------------------------------------------------------------*/

		// Language strings
		Symphony.Language.add({
			'Add item': false,
			'Remove item': false
		});

	/*-----------------------------------------------------------------------*/

		objects.each(function duplicators() {
			var duplicator = $(this),
				list = duplicator.find('> ol'),
				apply = $('<fieldset class="apply" />'),
				selector = $('<select />'),
				constructor = $('<button type="button" class="constructor" />'),
				instances, templates, items, headers;

			// Initialise duplicator components
			duplicator.addClass('duplicator').addClass('empty');
			instances = list.find(settings.instances).addClass('instance');
			templates = list.find(settings.templates).addClass('template');
			items = instances.add(templates);
			headers = items.find(settings.headers).addClass('frame-header');
			constructor.text(list.attr('data-add') || Symphony.Language.get('Add item'));

		/*---------------------------------------------------------------------
			Events
		---------------------------------------------------------------------*/

			// Construct instances
			apply.on('click.duplicator', '.constructor:not(.disabled)', function construct(event, speed) {
				var instance = templates.filter('[data-type="' + $(this).parent().find('select').val() + '"]').clone(true),
					heightMin, heightMax;

				event.preventDefault();

				// Prepare instance
				instance
					.trigger('constructstart.duplicator')
					.appendTo(list);

				// Duplicator is not empty
				duplicator.removeClass('empty');

				// Show instance
				instance
					.trigger('constructshow.duplicator');
					
				// Update collapsible sizes
				instance.trigger('updatesize.collapsible');
				instance.trigger('setsize.collapsible');

				setTimeout(function() {
					instance.trigger('animationend.duplicator');
				}, settings.delay);
			});

			// Destruct instances
			duplicator.on('click.duplicator', '.destructor:not(.disabled)', function destruct(event) {
				var instance = $(this).closest('.instance');

				// Remove instance
				instance
					.trigger('collapse.collapsible')
					.trigger('destructstart.duplicator')
					.addClass('destructed');

				setTimeout(function() {
					instance.trigger('animationend.duplicator');
				}, settings.delay);
			});

			// Finish animations
			duplicator.on('animationend.duplicator', '.instance', function finish() {
				var instance = $(this).removeClass('js-animate');

				// Trigger events
				if(instance.is('.destructed')) {
					instance.remove();

					// Check if duplicator is empty
					if(duplicator.find('.instance').length == 0) {
						duplicator.addClass('empty');
					}

					instance.trigger('destructstop.duplicator');
					duplicator.trigger('destructstop.duplicator', [instance]);
				}
				else {
					instance.trigger('constructstop.duplicator');
				}

				// Update collapsible states
				if(settings.collapsible) {
					instance.trigger('store.collapsible');
				}
			});

			// Lock constructor
			duplicator.on('constructstop.duplicator', '.instance', function lockConstructor() {
				if(duplicator.find('.instance').length >= settings.maximum) {
					constructor.addClass('disabled');
				}
			});

			// Unlock constructor
			duplicator.on('destructstart.duplicator', '.instance', function unlockConstructor() {
				if(duplicator.find('.instance').length <= settings.maximum) {
					constructor.removeClass('disabled');
				}
			});

			// Lock destructor
			duplicator.on('destructstart.duplicator', '.instance', function lockDestructor() {
				if(duplicator.find('.instance').length - 1 == settings.minimum) {
					duplicator.find('a.destructor').addClass('disabled');
				}
			});

			// Unlock destructor
			duplicator.on('constructstop.duplicator', '.instance', function unlockDestructor() {
				if(duplicator.find('.instance').length > settings.minimum) {
					duplicator.find('a.destructor').removeClass('disabled');
				}
			});

			// Lock unique instances
			duplicator.on('constructstop.duplicator', '.instance', function lockUnique(event) {
				var instance = $(this);

				if(instance.is('.unique')) {
					selector.find('option[value="' + instance.attr('data-type') + '"]').attr('disabled', true);

					// Preselect first available instance
					selector.find('option').prop('selected', false).filter(':not(:disabled):first').prop('selected', true);

					// All selected
					if(selector.find('option:not(:disabled)').length == 0) {
						selector.attr('disabled', 'disabled');
					}
				}
			});

			// Unlock unique instances
			duplicator.on('destructstart.duplicator', '.instance', function unlockUnique(event) {
				var instance = $(this),
					option;

				if(instance.is('.unique')) {
					option = selector.attr('disabled', false).find('option[value="' + instance.attr('data-type') + '"]').attr('disabled', false);

					// Preselect instance if it's the only active one
					if(selector.find('option:not(:disabled)').length == 1) {
						option.prop('selected', true);
					}
				}
			});

			// Build field indexes
			duplicator.on('constructstop.duplicator refresh.duplicator', '.instance', function buildIndexes(event) {
				var instance = $(this),
					position = duplicator.find('.instance').index(instance);

				// Loop over named fields
				instance.find('*[name]').each(function() {
					var field = $(this),
						exp = /\[\-?[0-9]+\]/,
						name = field.attr('name');

					// Set index
					if(exp.test(name)) {
						field.attr('name', name.replace(exp, '[' + position + ']'));
					}
				});
			});

			// Refresh field indexes
			duplicator.on('orderstop.orderable', function refreshIndexes(event) {
				duplicator.find('.instance').trigger('refresh.duplicator');
			});

		/*---------------------------------------------------------------------
			Initialisation
		---------------------------------------------------------------------*/

			// Wrap content, if needed
			headers.each(function wrapContent() {
				var header = $(this);

				if (!header.next('.content').length) {
					header.nextAll().wrapAll( $('<div />').attr('class','content') );
				}
			});

			// Constructable interface
			if(settings.constructable === true) {
				duplicator.addClass('constructable');
				apply.append($('<div />').append(selector)).append(constructor);
				apply.appendTo(duplicator);

				// Populate selector
				templates.detach().each(function createTemplates() {
					var template = $(this),
						title = $.trim(template.find(settings.headers).attr('data-name'))
								|| $.trim(template.find(settings.headers).text()),
						value = $.trim(template.attr('data-type'));

					template.trigger('constructstart.duplicator');

					// Check type connection
					if(!value) {
						value = title;
						template.attr('data-type', value);
					}

					// Append options
					$('<option />', {
						text: title,
						value: value
					}).appendTo(selector);

					// Check uniqueness
					template.trigger('constructstop.duplicator');
				}).removeClass('template').addClass('instance');
			}

			// Select default
			if(settings.preselect != false) {
				selector.find('option[value="' + settings.preselect + '"]').prop('selected', true);
			}

			// Single template
			if(templates.length <= 1) {
				apply.addClass('single');

				// Single unique template
				if(templates.is('.unique')) {
					if(instances.length == 0) {
						constructor.trigger('click.duplicator', [0]);
					}
					
					apply.hide();
				}
			}

			// Destructable interface
			if(settings.destructable === true) {
				duplicator.addClass('destructable');
				headers.append(
						$('<a />')
							.attr('class', 'destructor')
							.text(list.attr('data-remove') || Symphony.Language.get('Remove item'))
						);
			}

			// Collapsible interface
			if(settings.collapsible) {
				duplicator.symphonyCollapsible({
					items: '.instance',
					handles: '.frame-header',
					ignore: '.destructor',
					save_state: settings.save_state,
					delay: settings.delay
				});
			}

			// Orderable interface
			if(settings.orderable) {
				duplicator.symphonyOrderable({
					items: '.instance',
					handles: '.frame-header'
				});
			}

			// Catch errors
			instances.filter(':has(.invalid)').addClass('conflict');

			// Initialise existing instances
			instances.trigger('constructstop.duplicator');
			instances.find('input[name*="[label]"]').trigger('keyup.duplicator');

			// Check for existing instances
			if(instances.length > 0) {
				duplicator.removeClass('empty');
			}
		});

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(window.jQuery, window.Symphony);
