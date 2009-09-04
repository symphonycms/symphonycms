/*-----------------------------------------------------------------------------
	Duplicator plugin
-----------------------------------------------------------------------------*/
	
	jQuery.fn.symphonyDuplicator = function(custom_settings, custom_strings, delay_initialize) {
	/*-------------------------------------------------------------------------
		Initialise
	-------------------------------------------------------------------------*/
		
		var objects = jQuery(this);
		var settings = {
			contents:		'> ol:first',
			instances:		'> li:not(.template)',	// What children do we use as instances?
			templates:		'> li.template',		// What children do we use as templates?
			headers:		'> :first-child',		// What part of an instance is the header?
			orderable:		false,					// Can instances be ordered?
			collapsible:	false,					// Can instances be collapsed?
			collapsed:		false,					// Collapse constructed instances?
			constructable:	true,					// Allow construction of new instances?
			destructable:	true,					// Allow destruction of instances?
			minimum:		0,						// Do not allow instances to be removed below this limit.
			maximum:		1000					// Do not allow instances to be added above this limit.
		};
		var strings = {
			constructor:	'Add item',
			destructor:		'Remove item'
		};
		
		jQuery.extend(settings, custom_settings);
		jQuery.extend(strings, custom_strings);
		
	/*-------------------------------------------------------------------------
		Objects
	-------------------------------------------------------------------------*/
		
		objects = objects.map(function() {
			var object = jQuery(this);
			var storage = {
				collapsing:		{},					// Collapsing state, see methods.collapse_state
				ordering:		{},					// Ordering state, see methods.order_state
				templates:		[],					// Array of templates
				instances:		[]					// Array of instances
			};
			var widgets = {
				contents: 		null,
				wrapper: 		null,
				controls:		null,
				selector:		null,
				constructor:	null
			};
			var methods = {
				// Construct a new instance:
				construct:		function(source) {
					var template = jQuery(source).clone();
					var instance = methods.prepare(template);
					
					widgets.controls.before(instance);
					object.trigger('construct', [instance]);
					methods.refresh();
					
					return instance;
				},
				
				destruct:		function(source) {
					var instance = jQuery(source).remove();
					
					object.trigger('destruct', [instance]);
					methods.refresh();
					
					return instance;
				},
				
				// Silent action:
				silence:		function() { return false; },
				
				// Prepare an instance:
				prepare:		function(source) {
					var instance = jQuery(source)
						.addClass('instance expanded');
					var header = instance.find(settings.headers)
						.addClass('header')
						.wrapInner('<span />');
					var destructor = header
						.append('<a class="destructor" />')
						.find('a.destructor:first')
						.text(strings.destructor);
						
					destructor.click(function() {
						if (jQuery(this).hasClass('disabled')) return;
						
						methods.destruct(source);
					});
					
					if (settings.collapsible) {
						header.mousedown(function() {
							if (instance.hasClass('collapsed')) {
								methods.expand(instance);
								
							} else {
								methods.collapse(instance);
							}
							
							return false;
						});
					}
					
					header.bind('selectstart', methods.silence);
					
					if (settings.orderable) {
						header.mousedown(function() {
							methods.order(instance); return false;
						});
						
					} else {
						header.mousedown(methods.silence);
					}
					
					return instance;
				},
				
				// Collapse:
				collapse_state:	function() {
					if (storage.collapsing.instance) {
						storage.collapsing.instance
							.removeClass('expanding collapsing');
					}
					
					storage.collapsing = {
						instance:	null,
						min:		null,
						max:		null,
						delta:		0,
						started:	false
					};
				},
				collapse:		function(instance) {
					methods.collapse_state();
					storage.collapsing.started = true;
					storage.collapsing.instance = instance
						.addClass('collapsing');
					
					jQuery(document).mouseup(methods.collapsed);
				},
				collapsed:		function() {
					jQuery(document).unbind('mouseup', methods.collapsed);
					
					if (storage.collapsing.started) {
						storage.collapsing.instance
							.removeClass('collapsing expanded')
							.addClass('collapsed')
							.find('input[name $= "[collapsed]"]')
							.val('yes');
						
						methods.collapse_state();
						methods.refresh();
					}
					
					return false;
				},
				expand:		function(instance) {
					methods.collapse_state();
					storage.collapsing.started = true;
					storage.collapsing.instance = instance
						.addClass('expanding');
					
					jQuery(document).mouseup(methods.expanded);
				},
				expanded:		function() {
					jQuery(document).unbind('mouseup', methods.expanded);
					
					if (storage.collapsing.started) {
						storage.collapsing.instance
							.removeClass('expanding collapsed')
							.addClass('expanded')
							.find('input[name $= "[collapsed]"]')
							.val('no');
						
						methods.collapse_state();
						methods.refresh();
					}
					
					return false;
				},
				
				// Order instances:
				order_state:	function() {
					if (storage.ordering.instance) {
						widgets.wrapper
							.removeClass('ordering');
						storage.ordering.instance
							.removeClass('ordering');
					}
					
					storage.ordering = {
						instance:	null,
						min:		null,
						max:		null,
						delta:		0,
						started:	false
					};
				},
				order:			function(instance) {
					methods.order_state();
					storage.ordering.instance = instance;
					
					jQuery(document).mousemove(methods.ordering);
					jQuery(document).mouseup(methods.ordered);
				},
				ordering:		function(event) {
					var instance = storage.ordering.instance;
					var target, next, top = event.pageY;
					var a = instance.height();
					var b = instance.offset().top;
					
					storage.ordering.min = Math.min(b, a + (instance.prev().offset().top || -Infinity));
					storage.ordering.max = Math.max(a + b, b + (instance.next().height() ||  Infinity));
					widgets.wrapper
						.addClass('ordering');
					storage.ordering.instance
						.addClass('ordering');
					storage.ordering.started = true;
					methods.collapse_state();
					
					if (top < storage.ordering.min) {
						target = instance.prev('.instance');
						
						while (true) {
							storage.ordering.delta--;
							next = target.prev('.instance');
							
							if (next.length === 0 || top >= (storage.ordering.min -= next.height())) {
								instance.insertBefore(target); break;
							}
							
							target = next;
						}
						
					} else if (top > storage.ordering.max) {
						target = instance.next('.instance');
						
						while (true) {
							storage.ordering.delta++;
							next = target.next('.instance');
							
							if (next.length === 0 || top <= (storage.ordering.max += next.height())) {
								instance.insertAfter(target); break;
							}
							
							target = next;
						}
						
					} else {
						return;
					}
					
					return false;
				},
				ordered:		function(event) {
					jQuery(document).unbind('mousemove', methods.ordering);
					jQuery(document).unbind('mouseup', methods.ordered);
					
					if (storage.ordering.started) {
						widgets.wrapper
							.removeClass('ordering');
						storage.ordering.instance
							.removeClass('ordering');
						methods.order_state();
						methods.refresh();
					}
					
					return false;
				},
				
				// Refresh disabled states:
				refresh:		function() {
					var constructor = settings.constructable;
					var selector = settings.constructable;
					var destructor = settings.destructable;
					var empty = false;
					
					storage.instances = widgets.wrapper.children('.instance');
					
					// Update field names:
					storage.instances.each(function(position) {
						jQuery(this).find('*[name]').each(function() {
							var exp = /\[\-?[0-9]+\]/;
							var name = jQuery(this).attr('name');
							
							if (exp.test(name)) {
								jQuery(this).attr('name', name.replace(exp, '[' + position + ']'));
							}
						});
					});
					
					// No templates to add:
					if (storage.templates.length < 1) {
						constructor = false;
					}
					
					// Only one template:
					if (storage.templates.length <= 1) {
						selector = false;
					}
					
					// Maximum reached?
					if (settings.maximum <= storage.instances.length) {
						constructor = false;
						selector = false;
					}
					
					// Minimum reached?
					if (settings.minimum >= storage.instances.length) {
						destructor = false;
					}
					
					if (constructor) {
						widgets.constructor.removeClass('disabled');
						
					} else {
						widgets.constructor.addClass('disabled');
					}
					
					if (selector) {
						widgets.selector.removeClass('disabled');
						
					} else {
						widgets.selector.addClass('disabled');
					}
					
					if (destructor) {
						storage.instances.find(settings.headers).find('.destructor').removeClass('disabled');
						
					} else {
						storage.instances.find(settings.headers).find('.destructor').addClass('disabled');
					}
					
					if (!empty) {
						widgets.wrapper.removeClass('empty');
						
					} else {
						widgets.wrapper.addClass('empty');
					}
				},
				
				initialize:		function() {
					methods.collapse_state();
					methods.order_state();
					
					// Initialize objects:
					widgets.wrapper = jQuery(object)
						.addClass('duplicator');
					widgets.controls = widgets.wrapper
						.append('<div class="controls" />')
						.find('> .controls:last');
					widgets.selector = widgets.controls
						.prepend('<select />')
						.find('> select:first');
					widgets.constructor = widgets.controls
						.append('<a class="constructor" />')
						.find('> a.constructor:first')
						.text(strings.constructor);
					
					if (settings.orderable) widgets.wrapper.addClass('orderable');
					if (settings.collapsible) widgets.wrapper.addClass('collapsible');
					
					// Prepare instances:
					widgets.wrapper.find(settings.instances).each(function() {
						var instance = methods.prepare(this);
						var collapsed = instance.find('input[name $= "[collapsed]"]').val();
						var errors = instance.find('.invalid:first');
						
						object.trigger('construct', [instance]);
						
						if (!errors.length && collapsed != 'no') {
							if (settings.collapsed || collapsed == 'yes') {
								methods.collapse(instance);
								methods.collapsed();
							}
						}
					});
					
					// Store templates:
					widgets.wrapper.find(settings.templates).each(function(position) {
						var template = jQuery(this).remove();
						var header = template.find(settings.headers).addClass('header');
						var option = widgets.selector.append('<option />')
							.find('option:last');
						
						option.text(header.text()).val(position);
						
						storage.templates.push(template.removeClass('template'));
					});
					
					// Construct new template:
					widgets.constructor.bind('selectstart', methods.silence);
					widgets.constructor.bind('mousedown', methods.silence);
					widgets.constructor.bind('click', function() {
						if (jQuery(this).hasClass('disabled')) return;
						
						var position = widgets.selector.val();
						
						if (position >= 0) methods.construct(storage.templates[position]);
					});
					
					object.unbind('initialize', methods.initialize);
					methods.refresh();
				}
			};
			
			object.bind('initialize', methods.initialize);
			
			if (delay_initialize != true) object.trigger('initialize');
			
			return object;
		});
		
		return objects;
	};
	
	jQuery.fn.symphonyDuplicatorWithName = function(custom_settings, custom_strings) {
		var objects = jQuery(this).symphonyDuplicator(custom_settings, custom_strings, true);
		
		objects = objects.map(function() {
			var object = jQuery(this);
			
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
			
			object.trigger('initialize');
		});
		
		return objects;
	};
	
/*---------------------------------------------------------------------------*/