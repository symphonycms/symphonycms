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
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {String} [options.items='.instance'] Selector to find collapsible items within the container
	 * @param {String} [options.handles='.header:first'] Selector to find clickable handles to trigger interaction
	 * @param {String} [options.content='.content'] Selector to find hideable content area
	 * @param {String} [options.controls=true] Add controls to collapse/expand all instances
	 * @param {String} [options.save_state=true] Stores states of instances using local storage
	 * @param {String} [options.storage='symphony.collapsible.id.env'] Namespace used for local storage
	 *
	 *	@example

			var collapsible = $('#duplicator').symphonyCollapsible({
				items:		'.instance',
				handles:	'.header span'
			});
			collapsible.collapseAll();
	 */
	$.fn.symphonyCollapsible = function(options) {
		var objects = this,
			settings = {
				items:				'.instance',
				handles:			'.header:first',
				content:			'.content',
				controls:			true,
				save_state:			true,
				storage: 			'symphony.collapsible.' + $('body').attr('id') + (Symphony.Context.get('env')[1] ? '.' + Symphony.Context.get('env')[1] : '')
			};
		
		$.extend(settings, options);
		
	/*-----------------------------------------------------------------------*/

		// Language strings
		Symphony.Language.add({
			'Expand all': false,
			'Collapse all': false
		});

	/*-----------------------------------------------------------------------*/
		
		objects.each(function(index) {
			var object = $(this),
				storage = settings.storage + '.' + index + '.collapsed';
			
		/*-------------------------------------------------------------------*/
			
			// Collapse item
			object.on('collapse.collapsible', settings.items, function(event, speed) {
				var item = $(this),
					content = item.find(settings.content);

				// Check speed
				if(!$.isNumeric(speed)) {
					speed = 'fast';
				}
				
				// Collapse item				
				item.trigger('collapsestart.collapsible');
				content.slideUp(speed, function() {
					item.addClass('collapsed');
					item.trigger('collapsestop.collapsible');
				});
			});
									
			// Expand item
			object.on('expand.collapsible', settings.items, function(event) {
				var item = $(this),
					content = item.find(settings.content);
				
				// Collapse item				
				item.trigger('expandstart.collapsible');
				content.slideDown('fast', function() {
					item.removeClass('collapsed');
					item.trigger('expandstop.collapsible');
				});
			});

			// Toggle single item
			object.on('click.collapsible', settings.handles, function() {
				var handle = $(this),
					item = handle.parents(settings.items);
					
				// Expand
				if(item.is('.collapsed')) {
					item.trigger('expand.collapsible');
				}
				
				// Collapse
				else {
					item.trigger('collapse.collapsible');
				}
			});
			
			// Toggle all
			object.on('click.collapsible', 'a.collapser', function(event) {
				var collapser = $(this),
					items = object.find(settings.items);
				
				// Collapse all
				if(collapser.text() == Symphony.Language.get('Collapse all')) {
					items.trigger('collapse.collapsible');
				}
				
				// Expand all
				else {
					items.trigger('expand.collapsible');
				}
			});
			
			// Update controls to expand all
			object.on('collapsestop.collapsible', settings.items, function(event) {
				var item = $(this);
				
				if(item.siblings().filter(':not(.collapsed)').length == 0) {
					object.find('a.collapser').text(Symphony.Language.get('Expand all'));
				}
			});
					
			// Update controls to collapse all
			object.on('expandstop.collapsible', settings.items, function(event) {
				var item = $(this);
				
				if(item.siblings().filter('.collapsed').length == 0) {
					object.find('a.collapser').text(Symphony.Language.get('Collapse all'));
				}
			});
			
			// Save states
			object.on('collapsestop.collapsible expandstop.collapsible store.collapsible', settings.items, function(event) {
				if(settings.save_state === true && Symphony.Support.localStorage === true) {
					var collapsed = object.find(settings.items).map(function(index) {
						if($(this).is('.collapsed')) {
							return index;
						};
					});
					window.localStorage[storage] = collapsed.get().join(',');
				}
			});
			
			// Restore states
			object.on('restore.collapsible', function(event) {
				if(settings.save_state === true && Symphony.Support.localStorage === true && window.localStorage[storage]) {
					$.each(window.localStorage[storage].split(','), function(index, value) {
						var collapsed = object.find(settings.items).eq(value);
						if(collapsed.has('.invalid').length == 0) {
							collapsed.trigger('collapse.collapsible', [0]);
						}
					});
				}
			});
			
			// Activate controls
			object.on('activate.collapsible',  function(event, speed) {
			
				// Check speed
				if(!$.isNumeric(speed)) {
					speed = 100;
				}
				
				// Activate controls, show top control
				object.removeClass('empty');
				object.find('div.controls.top').slideDown(speed);
				object.find('div.controls a.collapser').removeClass('disabled');
			});
			
			// Deactivate controls
			object.on('deactivate.collapsible', function(event, speed) {
				
				// Check speed
				if(!$.isNumeric(speed)) {
					speed = 100;
				}
				
				// Deactivate controls, hide top control
				object.find('div.controls.top').slideUp(speed);
				object.find('div.controls a.collapser').addClass('disabled');
				object.addClass('empty');
			});
			
			// Refresh state storage
			object.on('orderstop.orderable', function(event) {
				object.find(settings.items).trigger('store.collapsible');
			});
			
		/*-------------------------------------------------------------------*/

			// Prepare interface
			object.addClass('collapsible');

			// Build controls
			if(settings.controls === true) {
				var top = object.find('> :first-child'),
					bottom = object.find('> :last-child'),
					collapser = $('<a />', {
						class: 'collapser',
						text: Symphony.Language.get('Collapse all')
					}),
					controls = $('<div />', {
						class: 'controls'
					}).append(collapser.clone());
				
				// Existing top controls
				if(top.is('.controls')) {
					top.prepend(collapser.clone());
				}
				
				// Create missing top controls
				else {
					object.prepend(controls.clone().hide().addClass('top'));
				}
				
				// Existing bottom controls
				if(bottom.is('.controls')) {
					bottom.prepend(collapser.clone());
				}
				
				// Create missing bottom controls
				else {
					object.append(controls.clone());	
				}
				
				// Activate controls
				if(object.find(settings.items).length > 0) {
					object.trigger('activate.collapsible', [0]);
				}
				
				// Deactivate controls
				else {
					object.trigger('deactivate.collapsible', [0]);
				}
			}
			
			// Restore states
			object.trigger('restore.collapsible');
		});

	/*-----------------------------------------------------------------------*/
		
		return objects;
	};

})(jQuery.noConflict());
