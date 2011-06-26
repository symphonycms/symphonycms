/**
 * @package assets
 */

(function($) {

	/**
	 * This plugin makes items collapsible.
	 *
	 * @name $.symphonyCollapsible
	 * @class
	 *
	 * @param {Object} custom_settings An object specifying containing the attributes specified below
	 * @param {String} [custom_settings.items='.instance'] Selector to find collapsible items within the container
	 * @param {String} [custom_settings.handles='.header:first'] Selector to find clickable handles to trigger interaction
	 * @param {Boolean} [custom_settings.delay_initialize=false] Initialise plugin extensions before the collapsible itself is initialised
	 *
	 *	@example

			var collapsible = $('#duplicator').symphonyCollapsible({
				items:		'.instance',
				handles:	'.header span'
			});
			collapsible.collapseAll();
	 */
	$.fn.symphonyCollapsible = function(custom_settings) {
		var objects = this,
			settings = {
				items:				'.instance',
				handles:			'.header:first',
				delay_initialize:	false,
				save_state:			true,
				storage: 'symphony.collapsible.' + $('body').attr('id') + (Symphony.Context.get('env')[1] ? '.' + Symphony.Context.get('env')[1] : '')
			};
		
		$.extend(settings, custom_settings);
		
	/*-----------------------------------------------------------------------*/
		
		objects = objects.map(function(index) {
			var object = this,
				item = null,
				storage = settings.storage + '.' + index + '.collapsed';
			
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
				
				if (settings.save_state) {
					object.collapsible.saveState();
				}
				
				return false;
			};
			
		/*-------------------------------------------------------------------*/
			
			if(object instanceof $ === false) {	
				object = $(object);
			}
			
			object.collapsible = {
				
				/**
				 * Cancel collapsing
				 *
				 * @name $.symphonyCollapsible#cancel
				 * @function
				 */
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
				
				/**
				 * Set up the collapsible items and bind event handlers
				 *
				 * @name $.symphonyCollapsible#initialize
				 * @function
				 */
				initialize: function() {
					object.addClass('collapsible');
					object.find(settings.items).each(function() {
						var item = $(this),
							handle = item.find(settings.handles);
						
						handle.unbind('mousedown.collapsible', start);
						handle.bind('mousedown.collapsible', start);
					});
					object.bind('restorestate.collapsible', function(event) {
						if (settings.save_state) {
							object.collapsible.restoreState();
						}
					});
					object.bind('savestate.collapsible', function(event) {
						if (settings.save_state) {
							object.collapsible.saveState();
						}
					});
				},
				
				/**
				 * Collapse all open items
				 *
				 * @name $.symphonyCollapsible#collapseAll
				 * @function
				 */
				collapseAll: function() {
					object.find(settings.items).each(function() {
						var item = $(this);
						
						if(item.is('.collapsed')) return;
						
						object.trigger('collapsestart', [item]);
						item.removeClass('expanded').addClass('collapsed');
						object.trigger('collapsestop', [item]);
					});
					if (settings.save_state) {
						this.saveState();
					}
				},
				
				/**
				 * Expand all closed items
				 *
				 * @name $.symphonyCollapsible#expandAll
				 * @function
				 */
				expandAll: function() {
					object.find(settings.items).each(function() {
						var item = $(this);
						
						if(item.is('.expanded')) return;
						
						object.trigger('expandstart', [item]);
						item.removeClass('collapsed').addClass('expanded');
						object.trigger('expandstop', [item]);
					});
					if (settings.save_state) {
						this.saveState();
					}
				},
				
				/**
				 * Remember collapsed/expanded state between page refreshes.
				 *
				 * @name $.symphonyCollapsible#expandAll
				 * @function
				 */
				saveState: function() {
					var collapsed = [];
					if (!Symphony.Support.localStorage) { return false; }
					object.find(settings.items).each(function(index) {
						var item = $(this);
						
						if(item.is('.collapsed')) {
							collapsed.push(index);
						};
					});
					localStorage[storage] = collapsed;
					return true;
				},
				
				restoreState: function() {
					var collapsed;
					if (!Symphony.Support.localStorage || !localStorage[storage]) { return false; }
					collapsed = localStorage[storage];
					collapsed = collapsed.split(',');
					$.each(collapsed, function(index, val) {
						var item = object.find(settings.items).eq(val);
						
						if(item.is('.collapsed')) return;
						
						object.trigger('collapsestart', [item]);
						item.removeClass('expanded').addClass('collapsed');
						object.trigger('collapsestop', [item, true]);
					});
					return true;
				}
			};
			
			if (settings.delay_initialize !== true) {
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
