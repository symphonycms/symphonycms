/*-----------------------------------------------------------------------------
	Collapsible plugin
-----------------------------------------------------------------------------*/
	
	jQuery.fn.symphonyCollapsible = function(custom_settings) {
		var objects = this;
		var settings = {
			items:				'.instance',
			handles:			'.header:first',
			delay_initialize:	false
		};
		
		jQuery.extend(settings, custom_settings);
		
	/*-------------------------------------------------------------------------
		Collapsible
	-------------------------------------------------------------------------*/
		
		objects = objects.map(function() {
			var object = this;
			var item = null;
			
			var start = function() {
				item = jQuery(this).parents(settings.items);
				
				jQuery(document).mouseup(stop);
				
				if (item.is('.collapsed')) {
					object.addClass('expanding');
					item.addClass('expanding');
					object.trigger('expandstart', [item]);
				}
				
				else {
					object.addClass('collapsing');
					item.addClass('collapsing');
					object.trigger('collapsestart', [item]);
				}
				
				return false;
			};
			
			var stop = function() {
				jQuery(document).unbind('mouseup', stop);
				
				if (item != null) {
					object.removeClass('expanding collapsing');
					item.removeClass('expanding collapsing');
					
					if (item.is('.collapsed')) {
						item.removeClass('collapsed').addClass('expanded');
						object.trigger('expandstop', [item]);
					}
					
					else {
						item.removeClass('expanded').addClass('collapsed');
						object.trigger('collapsestop', [item]);
					}
					
					item = null;
				}
				
				return false;
			};
			
		/*-------------------------------------------------------------------*/
			
			if (object instanceof jQuery === false) {
				object = jQuery(object);
			}
			
			object.collapsible = {
				cancel: function() {
					jQuery(document).unbind('mouseup', stop);
					
					if (item != null) {
						if (item.is('.collapsed')) {
							object.removeClass('expanding');
							item.removeClass('expanding');
							object.trigger('expandcancel', [item]);
						}
						
						else {
							object.removeClass('collapsing');
							item.removeClass('collapsing');
							object.trigger('collapsecancel', [item]);
						}
					}
				},
				
				initialize: function() {
					object.addClass('collapsible');
					object.find(settings.items).each(function() {
						var item = jQuery(this);
						var handle = item.find(settings.handles);
						
						handle.unbind('mousedown', start);
						handle.bind('mousedown', start);
					});
				},
				
				collapseAll: function() {
					object.find(settings.items).each(function() {
						var item = jQuery(this);
						
						object.trigger('collapsestart', [item]);
						item.removeClass('expanded').addClass('collapsed');
						object.trigger('collapsestop', [item]);
					});
				},
				
				expandAll: function() {
					object.find(settings.items).each(function() {
						var item = jQuery(this);
						
						object.trigger('expandstart', [item]);
						item.removeClass('collapsed').addClass('expanded');
						object.trigger('expandstop', [item]);
					});
				}
			};
			
			if (settings.delay_initialize === true) {
				object.collapsible.initialize();
			}
			
			return object;
		});
		
		objects.collapsible = {
			collapseAll: function() {
				objects.each(function() {
					this.collapsible.collapseAll();
				});
			},
			
			expandAll: function() {
				objects.each(function() {
					this.collapsible.expandAll();
				});
			}
		};
		
		return objects;
	};
	
/*---------------------------------------------------------------------------*/
