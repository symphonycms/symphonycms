/**
 * @package assets
 */

(function($, Symphony) {

	// always put try/catches into their
	// own function to prevent callers from
	// going into un-optimized hell
	var save = function (storage, collapsed) {
		// Put in a try/catch incase something goes wrong (no space, privileges etc)
		try {
			window.localStorage[storage] = collapsed.get().join(',');
		}
		catch(e) {
			window.onerror(e.message);
		}
	};

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
	 * @param {Boolean} [options.save_state=true] Stores states of instances using local storage
	 * @param {String} [options.storage='symphony.collapsible.area.page.id'] Namespace used for local storage
	 * @param {Integer} [options.delay=250'] Time delay for animations
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
				items: '.instance',
				handles: '.frame-header',
				content: '.content',
				ignore: '.ignore',
				save_state: true,
				storage: 'symphony.collapsible.' + Symphony.Context.get('context-id'),
				delay: 250
			};

		$.extend(settings, options);

	/*-----------------------------------------------------------------------*/

		objects.each(function collapsible(index) {
			var object = $(this),
				storage = settings.storage + '.' + index + '.collapsed';

		/*---------------------------------------------------------------------
			Events
		---------------------------------------------------------------------*/

			// Collapse item
			object.on('collapse.collapsible', settings.items, function collapse(event, duration) {
				var item = $(this),
					heightMin = 0;
				
				// customization point
				item.trigger('collapsebefore.collapsible', settings);
				
				heightMin = item.data('heightMin');

				// Check duration
				if(duration !== 0) {
					item.addClass('js-animate');
				}

				// Collapse item
				item.trigger('collapsestart.collapsible')
					.addClass('collapsed');

				item.css('max-height', heightMin);

				setTimeout(function() {
					item.trigger('animationend.collapsible');
					item.trigger('animationend.duplicator');
				}, duration === 0 ? 0 : settings.delay);
			});

			// Collapse all items
			object.on('collapseall.collapsible', function collapseAll(event) {
				var items = object.find(settings.items + ':not(.collapsed)'),
					visibles = Symphony.Utilities.inSight(items),
					invisibles = visibles.nextAll();

				invisibles.trigger('collapse.collapsible', [0]);
				visibles.trigger('collapse.collapsible');
				invisibles.trigger('collapsestop.collapsible');
			});

			// Expand item
			object.on('expand.collapsible', settings.items, function expand(event, duration) {
				var item = $(this),
					heightMax = 0;
				
				// customization point
				item.trigger('expandbefore.collapsible', settings);
				
				heightMax = item.data('heightMax');

				// Check duration
				if(duration !== 0) {
					item.addClass('js-animate');
				}

				// Collapse item
				item.trigger('expandstart.collapsible');
				item.removeClass('collapsed');
				item.css('max-height', heightMax);

				setTimeout(function() {
					item.trigger('animationend.collapsible');
				}, duration === 0 ? 0 : settings.delay);
			});

			// Expand all items
			object.on('expandall.collapsible', function expandAll(event) {
				var items = object.find(settings.items + '.collapsed'),
					firsts = items.filter('.collapsed:lt(2)');

				firsts.trigger('expand.collapsible');
				items.not(firsts).trigger('expand.collapsible', [0]);
			});

			// Finish animations
			object.on('animationend.collapsible', settings.items, function finish(event) {
				var item = $(this);

					item.removeClass('js-animate');

				// Trigger events
				if(item.is('.collapsed')) {
					item.trigger('collapsestop.collapsible');
				}
				else {
					item.trigger('expandstop.collapsible');
				}
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
					item = handle.parents(settings.items);

				if(!handle.is(settings.ignore) && !$(event.target).is(settings.ignore)) {

					// Expand all
					if(item.is('.collapsed')) {
						object.trigger('expandall.collapsible');
					}

					// Collaps all
					else {
						object.trigger('collapseall.collapsible');
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

					// save it to local storage
					save(storage, collapsed);
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

			// Refresh state storage after ordering
			object.on('orderstop.orderable', function refreshOrderedState(event) {
				object.find(settings.items).trigger('store.collapsible');
			});

			// Refresh state storage after deleting and instance
			object.on('destructstop.duplicator', settings.items, function refreshState() {
				$(this).trigger('store.collapsible');
			});
			
			// Update sizes
			object.on('updatesize.collapsible', settings.items, function updateSizes() {
				var item = $(this),
					min = item.find(settings.handles).outerHeight(true),
					max = min + item.find(settings.content).outerHeight(true);
					
				item.data('heightMin', min);
				item.data('heightMax', max);
			});
			
			// Set sizes
			object.on('setsize.collapsible', settings.items, function setSizes() {
				var item = $(this);
				var heightMin = item.data('heightMin');
				var heightMax = item.data('heightMax');
				item.css({
					'min-height': heightMin,
					'max-height': heightMax
				});
			});

		/*---------------------------------------------------------------------
			Initialisation
		---------------------------------------------------------------------*/

			// Prepare interface
			object.addClass('collapsible').find(settings.items).each(function() {
				var item = $(this);
				item.addClass('instance');
				item.trigger('updatesize.collapsible');
				item.trigger('setsize.collapsible');
			});

			// Restore states
			object.trigger('restore.collapsible');
		});

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(window.jQuery, window.Symphony);
