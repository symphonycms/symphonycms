/**
 * @package assets
 */

(function($) {

	/**
	 * Create collapsible elements.
	 *
	 * @name $.symphonyCollapsible
	 * @class
	 *
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {String} [options.items='.instance'] Selector to find collapsible items within the container
	 * @param {String} [options.handles='.header:first'] Selector to find clickable handles to trigger interaction
	 * @param {String} [options.content='.content'] Selector to find hideable content area
	 * @param {String} [options.save_state=true] Stores states of instances using local storage
	 * @param {String} [options.storage='symphony.collapsible.area.page.id'] Namespace used for local storage
	 *
	 * @example

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
				ignore:				'.ignore',
				save_state:			true,
				storage:			'symphony.collapsible.' + window.location.href.split(Symphony.Context.get('root') + '/')[1].replace(/\/(edit|new|created|saved)/g, '').replace(/\//g, '.')
			};

		$.extend(settings, options);

	/*-----------------------------------------------------------------------*/

		objects.each(function collapsible(index) {
			var object = $(this),
				storage = settings.storage + index + '.collapsed';

		/*---------------------------------------------------------------------
			Events
		---------------------------------------------------------------------*/

			// Collapse item
			object.on('collapse.collapsible', settings.items, function collapse(event, speed) {
				var item = $(this),
					content = item.find(settings.content);

				// Check speed
				if(!$.isNumeric(speed)) {
					speed = 'fast';
				}

				// Collapse item
				item.trigger('collapsestart.collapsible');
				object.addClass('collapsing');
				item.addClass('collapsing');
				content.slideUp(speed, function() {
					item.addClass('collapsed');
					object.removeClass('collapsing');
					item.removeClass('collapsing');
					item.trigger('collapsestop.collapsible');
				});
			});

			// Expand item
			object.on('expand.collapsible', settings.items, function expand(event) {
				var item = $(this),
					content = item.find(settings.content);

				// Collapse item
				item.trigger('expandstart.collapsible');
				object.addClass('expanding');
				item.addClass('expanding');
				content.slideDown('fast', function() {
					item.removeClass('collapsed');
					object.removeClass('expanding');
					item.removeClass('expanding');
					item.trigger('expandstop.collapsible');
				});
			});

			// Toggle single item
			object.on('click.collapsible', settings.handles, function toggle(event) {
				var handle = $(this),
					item = handle.parents(settings.items);

				if(!handle.is(settings.ignore) && !$(event.target).is(settings.ignore) && !item.is('.locked')) {

					// Expand
					if(item.is('.collapsed')) {
						item.trigger('expand.collapsible');
					}

					// Collapse
					else {
						item.trigger('collapse.collapsible');
					}
				}
			});

			// Toggle all
			object.on('dblclick.collapsible', settings.handles, function toogleAll(event) {
				var handle = $(this),
					item = handle.parents(settings.items),
					items = object.find(settings.items);

				if(!handle.is(settings.ignore) && !$(event.target).is(settings.ignore)) {

					// Expand all
					if(item.is('.collapsed')) {
						items.trigger('expand.collapsible');
					}

					// Collaps all
					else {
						items.trigger('collapse.collapsible');
					}
				}
			});

			// Save states
			object.on('collapsestop.collapsible expandstop.collapsible store.collapsible', settings.items, function saveState(event) {
				if(settings.save_state === true && Symphony.Support.localStorage === true) {
					var collapsed = object.find(settings.items).map(function(index) {
						if($(this).is('.collapsed')) {
							return index;
						};
					});

					// Put in a try/catch incase something goes wrong (no space, privileges etc)
					try {
						window.localStorage[storage] = collapsed.get().join(',');
					}
					catch(e) {
						window.onerror(e.message);
					}
				}
			});

			// Restore states
			object.on('restore.collapsible', function restoreState(event) {
				if(settings.save_state === true && Symphony.Support.localStorage === true && window.localStorage[storage]) {
					$.each(window.localStorage[storage].split(','), function(index, value) {
						var collapsed = object.find(settings.items).eq(value);
						if(collapsed.has('.invalid').length == 0) {
							collapsed.trigger('collapse.collapsible', [0]);
						}
					});
				}
			});

			// Refresh state storage
			object.on('orderstop.orderable', function refreshState(event) {
				object.find(settings.items).trigger('store.collapsible');
			});

		/*---------------------------------------------------------------------
			Initialisation
		---------------------------------------------------------------------*/

			// Prepare interface
			object.addClass('collapsible');

			// Restore states
			object.trigger('restore.collapsible');
		});

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(window.jQuery);
