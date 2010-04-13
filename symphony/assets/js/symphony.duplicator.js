/*-----------------------------------------------------------------------------
	Duplicator plugin
-----------------------------------------------------------------------------*/
	
	jQuery.fn.symphonyDuplicatorNew = function(custom_settings) {
		var objects = this;
		var settings = {
			instances:			'> .instances > *',
			tabs:				'> .tabs > *'
		};
		
		objects = objects.map(function() {
			var object = this;
			
			if (object instanceof jQuery === false) {
				object = jQuery(object);
			}
			
		/*-------------------------------------------------------------------*/
			
			object.find('*').live('tab-initialize', function() {
				var tab = jQuery(this);
				var index = tab.prevAll().length;
				
				tab.data('index', index);
			});
			
			object.find('*').live('tab-select', function() {
				var tab = jQuery(this);
				var index = tab.data('index');
				
				object.find(settings.tabs)
					.removeClass('active')
					.filter(':eq(' + index + ')')
					.addClass('active');
				
				object.find(settings.instances)
					.removeClass('active')
					.filter(':eq(' + index + ')')
					.addClass('active');
			});
			
		/*-------------------------------------------------------------------*/
			
			object.find(settings.tabs)
				.trigger('tab-initialize')
				.filter(':first')
				.trigger('tab-select');
				
			object.find(settings.tabs)
				.bind('click', function() {
					jQuery(this).trigger('tab-select');
				});
				
			object.find(settings.instances).filter(':first').addClass('active');
		});
	};
	
	jQuery(document).ready(function() {
		jQuery('.duplicator').symphonyDuplicatorNew();
	});
	
	jQuery.fn.symphonyDuplicator = function(custom_settings) {
		var objects = this;
		var settings = {
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
		
		jQuery.extend(settings, custom_settings);
		
	/*-------------------------------------------------------------------------
		Language strings
	-------------------------------------------------------------------------*/
		
		Symphony.Language.add({
			'Add item':		false,
			'Remove item':	false,
			'Collapse all':	false,
			'Expand all':	false
		});
		
	/*-------------------------------------------------------------------------
		Collapsible
	-------------------------------------------------------------------------*/
		
		if (settings.collapsible) objects = objects.symphonyCollapsible({
			items:			'.instance',
			handles:		'.header:first'
		});
		
	/*-------------------------------------------------------------------------
		Orderable
	-------------------------------------------------------------------------*/
		
		if (settings.orderable) objects = objects.symphonyOrderable({
			items:			'.instance',
			handles:		'.header:first'
		});
		
	/*-------------------------------------------------------------------------
		Duplicator
	-------------------------------------------------------------------------*/
		
		objects = objects.map(function() {
			var object = this;
			var templates = [];
			var widgets = {
				controls:		null,
				selector:		null,
				constructor:	null
			};
			var silence = function() {
				return false;
			};
			
			// Construct a new instance:
			var construct = function(source) {
				var template = jQuery(source).clone();
				var instance = prepare(template);
				
				widgets.controls.before(instance);
				object.trigger('construct', [instance]);
				refresh(true);
				
				return instance;
			};
			
			var destruct = function(source) {
				var instance = jQuery(source).remove();
				
				object.trigger('destruct', [instance]);
				refresh();
				
				return instance;
			};
			
			// Prepare an instance:
			var prepare = function(source) {
				var instance = jQuery(source)
					.addClass('instance expanded');
				var header = instance.find(settings.headers)
					.addClass('header')
					.wrapInner('<span />');
				var destructor = header
					.append('<a class="destructor" />')
					.find('a.destructor:first')
					.text(Symphony.Language.get('Remove item'));
				
				header.nextAll().wrapAll('<div class="content" />');
				
				destructor.click(function() {
					if (jQuery(this).hasClass('disabled')) return;
					
					destruct(source);
				});
				
				header.bind('selectstart', silence);
				header.mousedown(silence);
				
				return instance;
			};
			
			// Refresh disabled states:
			var refresh = function(input_focus) {
				var constructor = settings.constructable;
				var selector = settings.constructable;
				var destructor = settings.destructable;
				var instances = object.children('.instance');
				var empty = false;
				
				// Update field names:
				instances.each(function(position) {
					jQuery(this).find('*[name]').each(function() {
						var exp = /\[\-?[0-9]+\]/;
						var name = jQuery(this).attr('name');
						
						if (exp.test(name)) {
							jQuery(this).attr('name', name.replace(exp, '[' + position + ']'));
						}
					});
				});

				// Give focus to the first input in the first instance
				if (input_focus) instances.filter(':last').find('input[type!=hidden]:first').focus();
				
				// No templates to add:
				if (templates.length < 1) {
					constructor = false;
				}
				
				// Only one template:
				if (templates.length <= 1) {
					selector = false;
				}
				
				// Maximum reached?
				if (settings.maximum <= instances.length) {
					constructor = false;
					selector = false;
				}
				
				// Minimum reached?
				if (settings.minimum >= instances.length) {
					destructor = false;
				}
				
				if (constructor) widgets.constructor.removeClass('disabled');
				else widgets.constructor.addClass('disabled');
				
				if (selector) widgets.selector.removeClass('disabled');
				else widgets.selector.addClass('disabled');
				
				if (destructor) instances.find(settings.headers).find('.destructor').removeClass('disabled');
				else instances.find(settings.headers).find('.destructor').addClass('disabled');
				
				if (!empty) object.removeClass('empty');
				else object.addClass('empty');
				
				if (settings.collapsible) object.collapsible.initialize();
				if (settings.orderable) object.orderable.initialize();
			};
			
		/*-------------------------------------------------------------------*/
			
			if (object instanceof jQuery === false) {
				object = jQuery(object);
			}
			
			object.duplicator = {
				settings: settings,
				
				refresh: function() {
					refresh();
				},
				
				initialize: function() {
					object.addClass('duplicator');
					
					// Prevent collapsing when ordering stops:
					object.bind('orderstart', function() {
						if (settings.collapsible) {
							object.collapsible.cancel();
						}
					});
					
					// Refresh on reorder:
					object.bind('orderstop', function() {
						refresh();
					});
					
					// Slide up on collapse:
					object.bind('collapsestop', function(event, item) {
						item.find('> .content').show().slideUp(settings.speed);
					});
					
					// Slide down on expand:
					object.bind('expandstop', function(event, item) {
						item.find('> .content').hide().slideDown(settings.speed);
					});
					
					widgets.controls = object
						.append('<div class="controls" />')
						.find('> .controls:last');
					widgets.selector = widgets.controls
						.prepend('<select />')
						.find('> select:first');
					widgets.constructor = widgets.controls
						.append('<a class="constructor" />')
						.find('> a.constructor:first')
						.text(Symphony.Language.get('Add item'));
					
					// Prepare instances:
					object.find(settings.instances).each(function() {
						var instance = prepare(this);
						
						object.trigger('construct', [instance]);
					});
					
					// Store templates:
					object.find(settings.templates).each(function(position) {
						var template = jQuery(this).remove();
						var header = template.find(settings.headers).addClass('header');
						var option = widgets.selector.append('<option />')
							.find('option:last');
						var header_children = header.children();
						
						if (header_children.length) {
							header_text = header.get(0).childNodes[0].nodeValue
							+ ' (' + header_children.filter(':eq(0)').text() + ')';
						}
						
						else {
							header_text = header.text();
						}
						
						option.text(header_text).val(position);
						
						templates.push(template.removeClass('template'));
					});
					
					// Construct new template:
					widgets.constructor.bind('selectstart', silence);
					widgets.constructor.bind('mousedown', silence);
					widgets.constructor.bind('click', function() {
						if (jQuery(this).hasClass('disabled')) return;
						
						var position = widgets.selector.val();
						
						if (position >= 0) construct(templates[position]);
					});
					
					refresh();
				}
			};
			
			if (settings.delay_initialize !== true) {
				object.duplicator.initialize();
			}
			
			return object;
		});
		
		objects.duplicator = {
			settings: settings
		};
		
		return objects;
	};
	
/*-----------------------------------------------------------------------------
	Duplicator With Name plugin
-----------------------------------------------------------------------------*/
	
	jQuery.fn.symphonyDuplicatorWithName = function(custom_settings) {
		var objects = jQuery(this).symphonyDuplicator(jQuery.extend(
			custom_settings, {
				delay_initialize:		true
			}
		));
		
		objects = objects.map(function() {
			var object = this;
			
			object.bind('construct', function(event, instance) {
				var input = instance.find('input:visible:first');
				var header = instance.find('.header:first > span:first');
				var fallback = header.text();
				var refresh = function() {
					var value = input.val();
					
					header.text(value ? value : fallback);
				};
				
				input.bind('change', refresh).bind('keyup', refresh);
				
				refresh();
			});
			
			object.duplicator.initialize();
		});
		
		return objects;
	};
	
/*-----------------------------------------------------------------------------
	Collapsed duplicator
-----------------------------------------------------------------------------*/
	
	jQuery.fn.symphonyCollapsedDuplicator = function(custom_settings) {
		var objects = jQuery(this).symphonyDuplicator(jQuery.extend(
			custom_settings, {
				collapsible:		true,
				delay_initialize:	true
			}
		));
		var settings = objects.duplicator.settings;
		
		objects = objects.map(function() {
			var object = this;
			var collapse_all = null, expand_all = null;
			var cookie_id = '', open = [];
			var construct = function(event, instance, x) {
				// Don't collapse on error:
				if (instance.find('#error').length) return;
				
				// Remember open states:
				if (open && open.indexOf(instance.index().toString()) >= 0) return;
				
				instance.removeClass('expanded').addClass('collapsed');
			};
			var refresh = function() {
				var open = [];
				
				object.find(settings.instances).each(function(index) {
					if (jQuery(this).is('.expanded')) open.push(index);
				});
				
				Symphony.Cookie.set(cookie_id, open.join(','));
				
				// Toggle expand/collape all buttons:
				if (open.length) {
					collapse_all.show();
					expand_all.hide();
				}
				
				else {
					collapse_all.hide();
					expand_all.show();
				}
			};
			
			// Make sure it has an id:
			if (!object.attr('id')) return object;
			
			cookie_id = 'symphony-collapsed-duplicator-' + object.attr('id');
			
			// Read cookie:
			if (Symphony.Cookie.get(cookie_id)) {
				open = Symphony.Cookie.get(cookie_id).split(',');
			}
			
			// Collapse items as they are constructed:
			object.bind('construct', construct);
			object.duplicator.initialize();
			object.unbind('construct', construct);
			
			// Listen for changes:
			object.bind('collapsestop', refresh);
			object.bind('expandstop', refresh);
			object.bind('orderstop', refresh);
			
			// Add collapse/expand all toggle:
			collapse_all = jQuery('<a />')
				.addClass('collapse-all')
				.text('Collapse All')
				.appendTo(object.children('.controls:last'))
				.bind('click', object.collapsible.collapseAll);
			
			expand_all = jQuery('<a />')
				.addClass('collapse-all')
				.text('Expand All')
				.appendTo(object.children('.controls:last'))
				.bind('click', object.collapsible.expandAll);
			
			refresh();
		});
		
		return objects;
	};
	
/*---------------------------------------------------------------------------*/