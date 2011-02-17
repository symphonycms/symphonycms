/**
 * @package assets
 */

(function($) {

	/**
	 * This plugin makes items callapsible.
	 *
	 * @param {Object} custom_settings
	 *  An object specifying the item to be collapsed, their handles and 
	 *  a initialization delay
	 */
	$.fn.symphonyCollapsible = function(custom_settings) {
		var objects = this,
			settings = {
				items:				'.instance',
				handles:			'.header:first',
				delay_initialize:	false
			};
		
		$.extend(settings, custom_settings);
		
	/*-----------------------------------------------------------------------*/
		
		objects = objects.map(function() {
			var object = this,
				item = null;
			
			var start = function() {
				item = $(this).parents(settings.items);
				
				$(document).bind('mouseup.collapsible', stop);
				
				if(item.is('.collapsed')) {
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
				$(document).unbind('mouseup.collapsible', stop);
				
				if(item != null) {
					object.removeClass('expanding collapsing');
					item.removeClass('expanding collapsing');
					
					if(item.is('.collapsed')) {
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
			
			if(object instanceof $ === false) {
				object = $(object);
			}
			
			object.collapsible = {
				cancel: function() {
					$(document).unbind('mouseup', stop);
					
					if(item != null) {
						if(item.is('.collapsed')) {
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
						var item = $(this),
							handle = item.find(settings.handles);
						
						handle.unbind('mousedown.collapsible', start);
						handle.bind('mousedown.collapsible', start);
					});
				},
				
				collapseAll: function() {
					object.find(settings.items).each(function() {
						var item = $(this);
						
						if(item.is('.collapsed')) return;
						
						object.trigger('collapsestart', [item]);
						item.removeClass('expanded').addClass('collapsed');
						object.trigger('collapsestop', [item]);
					});
				},
				
				expandAll: function() {
					object.find(settings.items).each(function() {
						var item = $(this);
						
						if(item.is('.expanded')) return;
						
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

})(jQuery.noConflict());
