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
	 * @param {Object} custom_settings An object specifying containing the attributes specified below
	 * @param {String} [custom_settings.instances='> li:not(.template)'] Selector to find children to use as instances
	 * @param {String} [custom_settings.templates='> li.template'] Selector to find children to use as templates
	 * @param {String} [custom_settings.headers='> :first-child'] Selector to find the header part of each instance
	 * @param {Boolean} [custom_settings.orderable=false] Can instances be ordered
	 * @param {Boolean} [custom_settings.collapsible=false] Can instances be collapsed
	 * @param {Boolean} [custom_settings.constructable=true] Allow construction of new instances
	 * @param {Boolean} [custom_settings.destructable=true] Allow destruction of instances
	 * @param {Integer} [custom_settings.minimum=0] Do not allow instances to be removed below this limit
	 * @param {Integer} [custom_settings.maximum=1000] Do not allow instances to be added above this limit
	 * @param {String} [custom_settings.speed='fast'] Animation speed
	 * @param {Boolean} [custom_settings.delay_initialize=false] Initialise plugin extensions before the duplicator itself is initialised
	 *
	 *	@example

			$('.duplicator').symphonyDuplicator({
				orderable: true,
				collapsible: true
			});
	 */
	$.fn.symphonyDuplicator = function(custom_settings) {
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
				speed:				'fast',					// Control the speed of any animations
				delay_initialize:	false
			};

		$.extend(settings, custom_settings);

	/*-----------------------------------------------------------------------*/

		// Language strings
		Symphony.Language.add({
			'Add item': false,
			'Remove item': false,
			'Expand all': false,
			'Collapse all': false,
			'All selected': false
		});

		// Collapsible
		if(settings.collapsible) {
			objects = objects.symphonyCollapsible({
				items:			'.instance',
				handles:		'.header span'
			});
		}

		// Orderable
		if(settings.orderable) {
			objects = objects.symphonyOrderable({
				items:			'.instance',
				handles:		'.header'
			});
		}

		// Duplicator
		objects = objects.map(function() {
			var object = this,
				templates = [],
				widgets = {
					controls:		null,
					selector:		null,
					constructor:	null,
					topcontrols:	null,
					collapser:		null
				},
				silence = function() {
					return false;
				};

			// Construct a new instance
			var construct = function(source) {
				var template = $(source).clone(true),
					instance = prepare(template);

				widgets.controls.before(instance);
				object.trigger('construct', [instance]);
				refresh(true);
				updateUniqueness();

				return instance;
			};

			var destruct = function(source) {
				var instance = $(source).remove();

				object.trigger('destruct', [instance]);
				refresh();
				updateUniqueness();

				return instance;
			};

			// Prepare an instance
			var prepare = function(source) {
				var instance = $(source).addClass('instance expanded'),
					header = instance.find(settings.headers).addClass('header').wrapInner('<span />'),
					destructor = header.append('<a class="destructor" />').find('a.destructor:first').text(Symphony.Language.get('Remove item'));

				header.nextAll().wrapAll('<div class="content" />');

				destructor.bind('click.duplicator', function() {
					if($(this).hasClass('disabled')) {
						return;
					}
					destruct(source);
				});

				header.bind('selectstart.duplicator', silence);

				return instance;
			};

			// Refresh disabled states
			var refresh = function(input_focus) {
				var constructor = settings.constructable,
					selector = settings.constructable,
					destructor = settings.destructable,
					instances = object.children('.instance'),
					empty = false;

				// Update field names
				instances.each(function(position) {
					$(this).find('*[name]').each(function() {
						var exp = /\[\-?[0-9]+\]/,
							name = $(this).attr('name');

						if (exp.test(name)) {
							$(this).attr('name', name.replace(exp, '[' + position + ']'));
						}
					});
				});

				// Give focus to the first input in the first instance
				if(input_focus) {
					instances.filter(':last').find('input[type!="hidden"]:first').focus();
				}

				// No templates to add
				if(templates.length < 1) {
					constructor = false;
				}

				// Only one template
				if(templates.length <= 1) {
					selector = false;
				}

				// Maximum reached?
				if(settings.maximum <= instances.length) {
					constructor = false;
					selector = false;
				}

				// Minimum reached?
				if(settings.minimum >= instances.length) {
					destructor = false;
				}

				// Constructor?
				if(constructor) {
					widgets.constructor.removeClass('disabled');
				}
				else {
					widgets.constructor.addClass('disabled');
				}

				// Selector?
				if(selector) {
					widgets.selector.removeClass('disabled');
				}
				else {
					widgets.selector.addClass('disabled');
				}

				// Destructor?
				if(destructor) {
					instances.find(settings.headers).find('.destructor').removeClass('disabled');
				}
				else {
					instances.find(settings.headers).find('.destructor').addClass('disabled');
				}

				// Empty?
				if(!empty) {
					object.removeClass('empty');
				}
				else {
					object.addClass('empty');
				}

				// Collapsible?
				if(settings.collapsible) {
					object.collapsible.initialize();
				}

				// Orderable?
				if(settings.orderable) {
					object.orderable.initialize();
					object.bind('orderstop', function(event) {
						object.trigger('savestate');
					});
				}
			};

			// Update uniqueness
			var updateUniqueness = function() {
				var instances = object.children('.instance'),
					options = widgets.selector.find('option');

				options.attr('disabled', false);

				instances.each(function(position) {
					var instance = $(this);

					if (instance.hasClass('unique')) {
						options.filter('[data-type=' + instance.attr('data-type') + ']').attr('disabled', 'disabled');

						if (options.not(':disabled').length === 0) {
							widgets.selector.prepend('<option class="all-selected">' + Symphony.Language.get('All selected') + '</option>');
							widgets.selector.attr('disabled', 'disabled');
							widgets.constructor.addClass('disabled');
						} else {
							widgets.selector.attr('disabled', false);
							options.filter('.all-selected').remove();
						};

						widgets.selector.find('option').not(':disabled').first().attr('selected', 'selected');
					};
				});
			};

			var collapsingEnabled = function() {
				widgets.topcontrols.removeClass('hidden');
				widgets.collapser.removeClass('disabled');
			};

			var collapsingDisabled = function() {
				widgets.topcontrols.addClass('hidden');
				widgets.collapser.addClass('disabled');
			};

			var toCollapseAll = function() {
				widgets.collapser.removeClass('compact').text(Symphony.Language.get('Collapse all'));
			};

			var toExpandAll = function() {
				widgets.collapser.addClass('compact').text(Symphony.Language.get('Expand all'));
			};

		/*-------------------------------------------------------------------*/

			if (object instanceof $ === false) {
				object = $(object);
			}

			object.duplicator = {
				refresh: function() {
					refresh();
				},

				initialize: function() {
					object.addClass('duplicator');

					// Prevent collapsing when ordering stops:
					object.bind('orderstart.duplicator', function() {
						if (settings.collapsible) {
							object.collapsible.cancel();
						}
					});

					// Refresh on reorder:
					object.bind('orderstop.duplicator', function() {
						refresh();
					});

					// Slide up on collapse:
					object.bind('collapsestop.duplicator', function(event, item, instantly) {
						if (instantly) {
							item.find('> .content').hide();
						} else {
							item.find('> .content').show().slideUp(settings.speed);
						}
					});

					// Slide down on expand:
					object.bind('expandstop.duplicator', function(event, item, instantly) {
						if (instantly) {
							item.find('> .content').show();
						} else {
							item.find('> .content').hide().slideDown(settings.speed);
						};
					});

					widgets.controls = object.append('<div class="controls" />').find('> .controls:last');
					widgets.selector = widgets.controls.prepend('<select />').find('> select:first');
					widgets.constructor = widgets.controls.append('<a class="constructor" />').find('> a.constructor:first').text(Symphony.Language.get('Add item'));

					// Prepare instances:
					object.find(settings.instances).each(function() {
						var instance = prepare(this);

						object.trigger('construct', [instance]);
					});

					// Store templates:
					object.find(settings.templates).each(function(position) {
						var template = $(this).clone(true),
							header = template.find(settings.headers).addClass('header'),
							option = widgets.selector.append('<option />').find('option:last'),
							header_children = header.children(),
							header_text = header.text();

						if(header_children.length) {
							header_text = header.get(0).childNodes[0].nodeValue + ' (' + header_children.filter(':eq(0)').text() + ')';
						}
						option.text(header_text).val(position).attr('data-type', template.attr('data-type'));

						// HACK: preselect Text Input for Section editor
						if (header_text == 'Text Input') {
							option.attr('selected', 'selected');
						}

						templates.push(template.removeClass('template'));

						// Remove template source
						$(this).remove();
					});

					// Construct new template:
					widgets.constructor.bind('selectstart.duplicator', silence);
					widgets.constructor.bind('mousedown.duplicator', silence);
					widgets.constructor.bind('click.duplicator', function() {
						if($(this).hasClass('disabled')) {
							return;
						}

						var position = widgets.selector.val();

						if(position >= 0) {
							construct(templates[position]);
						}
					});

					if(settings.collapsible) {
						widgets.topcontrols = object
							.prepend('<div class="controls top hidden" />')
							.find('> .controls:first')
							.append(widgets.controls
								.prepend('<a class="collapser disabled" />')
								.find('> a.collapser:first')
								.text(Symphony.Language.get('Collapse all'))
								.clone()
							);
						widgets.collapser = object.find('.controls > .collapser');

						if(object.children('.instance').length > 0) {
							collapsingEnabled();
						}

						object.bind('construct.duplicator', function() {
							var instances = object.children('.instance');

							if(instances.length > 0) {
								collapsingEnabled();
							}
						});

						object.bind('destruct.duplicator', function() {
							var instances = object.children('.instance');

							if(instances.length < 1) {
								collapsingDisabled();
								toCollapseAll();
							}
						});

						object.bind('collapsestop.duplicator destruct.duplicator', function() {
							if(object.has('.expanded').length == 0) {
								toExpandAll();
							}
						});

						object.bind('expandstop.duplicator destruct.duplicator', function() {
							if(object.has('.collapsed').length == 0) {
								toCollapseAll();
							}
						});

						widgets.collapser.bind('click.duplicator', function() {
							var item = $(this);

							if(item.is('.disabled')) return;

							object.duplicator[item.is('.compact') ? 'expandAll' : 'collapseAll']();
						});
					}

					refresh();
					updateUniqueness();
				},
				
				/**
				 * Expand all closed items
				 *
				 * @name $.symphonyDuplicator#expandAll
				 * @function
				 * @requires $.symphonyCollapsible plugin
				 * @requires constructor { collapsible: true }
				 * @see $.symphonyCollapsible#expandAll
				 */
				expandAll: function() {
					object.collapsible.expandAll();
					toCollapseAll();
				},
				
				/**
				 * Collapse all open items
				 *
				 * @name $.symphonyDuplicator#collapseAll
				 * @function
				 * @requires $.symphonyCollapsible plugin
				 * @requires constructor { collapsible: true }
				 * @see $.symphonyCollapsible#collapseAll
				 */
				collapseAll: function() {
					object.collapsible.collapseAll();
					toExpandAll();
				}
			};

			if (settings.delay_initialize !== true) {
				object.duplicator.initialize();
			}

			return object;
		});

		return objects;
	};


	/**
	 * This plugin creates a Symphony duplicator with name.
	 *
	 * @param {Object} custom_settings
	 *  An object with custom duplicator settings
	 */
	$.fn.symphonyDuplicatorWithName = function(custom_settings) {
		var objects = $(this).symphonyDuplicator($.extend(
			custom_settings, {
				delay_initialize:		true
			}
		));

		objects = objects.map(function() {
			var object = this;

			object.bind('construct.duplicator', function(event, instance) {
				var input = instance.find('input:visible:first'),
					header = instance.find('.header:first > span:first'),
					fallback = header.text(),
					refresh = function() {
						var value = input.val();
						header.text(value ? value : fallback);
					};

				input.bind('change.duplicator', refresh).bind('keyup', refresh);

				refresh();
			});

			object.duplicator.initialize();
		});

		return objects;
	};

})(jQuery.noConflict());
