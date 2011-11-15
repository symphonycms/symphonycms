/**
 * @package assets
 */

(function($) {

	/**
	 * This plugin creates a Symphony duplicator.
	 *
	 * @name $.symphonyDuplicator
	 * @class
	 *
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {String} [options.instances='> li:not(.template)'] Selector to find children to use as instances
	 * @param {String} [options.templates='> li.template'] Selector to find children to use as templates
	 * @param {String} [options.headers='> :first-child'] Selector to find the header part of each instance
	 * @param {Boolean} [options.orderable=false] Can instances be ordered
	 * @param {Boolean} [options.collapsible=false] Can instances be collapsed
	 * @param {Boolean} [options.constructable=true] Allow construction of new instances
	 * @param {Boolean} [options.destructable=true] Allow destruction of instances
	 * @param {Integer} [optionss.minimum=0] Do not allow instances to be removed below this limit
	 * @param {Integer} [options.maximum=1000] Do not allow instances to be added above this limit
	 * @param {String} [options.speed='fast'] Animation speed
	 *
	 *	@example

			$('.duplicator').symphonyDuplicator({
				orderable: true,
				collapsible: true
			});
	 */
	$.fn.symphonyDuplicator = function(options) {
		var objects = this,
			settings = {
				instances:			'> li:not(.template)',	// What children do we use as instances?
				templates:			'> li.template',		// What children do we use as templates?
				headers:			'> :first-child',		// What part of an instance is the header?
				orderable:			false,					// Can instances be ordered?
				collapsible:		false,					// Can instances be collapsed?
				constructable:		true,					// Allow construction of new instances?
				destructable:		true,					// Allow destruction of instances?
				minimum:			0,						// Do not allow instances to be removed below this limit.
				maximum:			1000,					// Do not allow instances to be added above this limit.
				speed:				'fast'					// Control the speed of any animations
			};

		$.extend(settings, options);

	/*-----------------------------------------------------------------------*/

		// Language strings
		Symphony.Language.add({
			'Add item': false,
			'Remove item': false
		});

	/*-----------------------------------------------------------------------*/
	
		objects.each(function() {
			var object = $(this),
				instances = object.find(settings.instances).addClass('instance'),
				templates = object.find(settings.templates).addClass('template'),
				items = instances.add(templates),
				headers = items.find(settings.headers).addClass('header'),
				duplicator = $('<div class="duplicator" />'),
				controls = $('<div class="controls" />'),
				selector = $('<select />'),
				constructor = $('<a class="constructor">' + Symphony.Language.get('Add item') + '</a>');
			
		/*-------------------------------------------------------------------*/

			// Construct instances
			controls.on('click.duplicator', 'a.constructor:not(.disabled)', function(event) {
				var instance = templates.filter('[data-type="' + selector.val() + '"]').clone();

				instance.trigger('constructstart.duplicator');
				instance.trigger('construct.duplicator'); /* deprecated */
				instance.hide().appendTo(object).slideDown(settings.speed, function() {

					// Focus first input
					instance.find('input[type!="hidden"]:first').focus();
					instance.trigger('constructstop.duplicator');
				});
			});
			
			// Destruct instances
			duplicator.on('click.duplicator', 'a.destructor:not(.disabled)', function(event) {
				var instance = $(this).parents('.instance:first');

				instance.trigger('destructstart.duplicator');
				instance.trigger('destruct.duplicator'); /* deprecated */
				instance.slideUp(settings.speed, function() {
					$(this).remove();
					instance.trigger('destructstop.duplicator');				
				});
			});
			
			// Lock constructor
			duplicator.on('constructstop.duplicator', '.instance', function() {
				if(duplicator.find('.instance').size() >= settings.maximum) {
					constructor.addClass('disabled');
				}
			});
			
			// Unlock constructor
			duplicator.on('destructstart.duplicator', '.instance', function() {
				if(duplicator.find('.instance').size() <= settings.maximum) {
					constructor.removeClass('disabled');
				}
			});
			
			// Lock destructor
			duplicator.on('destructstart.duplicator', '.instance', function() {
				if(duplicator.find('.instance').size() - 1 == settings.minimum) {
					duplicator.find('a.destructor').addClass('disabled');
				}
			});

			// Unlock destructor
			duplicator.on('constructstop.duplicator', '.instance', function() {
				if(duplicator.find('.instance').size() > settings.minimum) {
					duplicator.find('a.destructor').removeClass('disabled');
				}
			});
			
			// Lock unique instances
			duplicator.on('constructstop.duplicator', '.instance', function(event) {
				var instance = $(this);

				if(instance.is('.unique')) {
					selector.find('option[value="' + instance.attr('data-type') + '"]').attr('disabled', true);
					
					// Preselect first available instance
					selector.find('option').attr('selected', false).filter(':not(:disabled):first').attr('selected', true);
					
					// All selected
					if(selector.find('option:not(:disabled)').size() == 0) {
						selector.attr('disabled', 'disabled');
					}
				}
			});
			
			// Unlock unique instances
			duplicator.on('destructstart.duplicator', '.instance', function(event) {
				var instance = $(this),
					option;

				if(instance.is('.unique')) {
					option = selector.attr('disabled', false).find('option[value="' + instance.attr('data-type') + '"]').attr('disabled', false);
					
					// Preselect instance if it's the only active one
					if(selector.find('option:not(:disabled)').size() == 1) {
						option.attr('selected', true);
					}
				}
			});
			
			// Update title descriptions in header
			duplicator.on('keyup.duplicator', '.instance input[name*="[label]"]', function(event) {
				var input = $(this),
					instance = input.parents('.instance:first'),
					title = instance.find(settings.headers).find('span:first'),
					description = title.find('i');
					
				// Create description
				if(description.size() == 0) {
					description = $('<i />').appendTo(title);
				}
				
				// Update description
				description.text($.trim(input.val()));
			});

			// Build field indexes
			items.on('constructstop.duplicator refresh.duplicator', function(event) {
				var instance = $(this),
					position = instances.index(instance);
			
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
			duplicator.on('orderchange', function(event) {
				items.trigger('refresh.duplicator');
			});
						
		/*-------------------------------------------------------------------*/

			// Build interface			
			duplicator.insertBefore(object).prepend(object);
			headers.wrapInner('<span />').each(function() {
				$(this).nextAll().wrapAll('<div class="content" />');
			});

			// Constructable interface
			if(settings.constructable === true) {
				duplicator.addClass('constructable');
				controls.append(selector).append(constructor).appendTo(duplicator);

				// Populate selector
				templates.each(function() {
					var template = $(this);
					template.trigger('constructstart.duplicator');

					// Append options
					$('<option />', {
						text: template.find(settings.headers).text(),
						value: template.attr('data-type')
					}).appendTo(selector);
					
					// Check uniqueness
					template.trigger('constructstop.duplicator');
				}).removeClass('template').addClass('instance').remove();
			}
			
			// Destructable interface
			if(settings.destructable === true) {
				duplicator.addClass('destructable');
				headers.append('<a class="destructor">' + Symphony.Language.get('Remove item') + '</a>')
			}
				
			// Collapsible interface
			if(settings.collapsible) {
				duplicator.symphonyCollapsible({
					items: '.instance',
					handles: '.header span'
				});
			}
	
			// Orderable interface
			if(settings.orderable) {
				duplicator.symphonyOrderable({
					items: '.instance',
					handles: '.header',
					ignore: 'span'
				});
			}			
			
			// Initialise existing instances
			instances.trigger('constructstop.duplicator');
			instances.find('input[name*="[label]"]').trigger('keyup.duplicator');
		});				
		
	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(jQuery.noConflict());
