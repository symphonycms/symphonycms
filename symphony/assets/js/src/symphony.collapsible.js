/**
 * @package assets
 */

(function($, Symphony) {

	// Saves the value into the local storage at the specified storage key.
	var save = function (storage, value) {
		// Put in a try/catch in case something goes wrong (no space, privileges etc)
		// Always put try/catches into their own function to prevent callers from
		// going into un-optimized hell
		try {
			window.localStorage[storage] = value;
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

			var getDuration = function (duration) {
				return $.isNumeric(duration) ? duration : settings.delay;
			};

		/*---------------------------------------------------------------------
			Events
		---------------------------------------------------------------------*/

			var collapseItem = function collapse(item, duration) {
				var heightMin = 0;
				
				// Customization point
				item.trigger('collapsebefore.collapsible', settings);

				heightMin = item.data('heightMin');

				// Check duration
				if(duration !== 0) {
					item.addClass('js-animate');
					item.trigger('collapsestart.collapsible');
				}

				// Collapse item
				item.addClass('collapsed');
				item.css('max-height', heightMin);

				if(duration !== 0) {
					setTimeout(function() {
						item.trigger('animationend.collapsible');
						item.trigger('animationend.duplicator');
					}, duration);
				}
			};

			// Collapse item
			object.on('collapse.collapsible', settings.items, function collapse(event, duration) {
				var item = $(this);
				collapseItem(item, getDuration(duration));
			});

			// Collapse all items
			object.on('collapseall.collapsible', function collapseAll(event) {
				var items = object.find(settings.items + ':not(.collapsed)'),
					visibles = Symphony.Utilities.inSight(items),
					invisibles = $(),
					scrollTop = $(window).scrollTop(),
					visibleIndex = visibles.eq(0).index(),
					visibleCollapsedHeight = 0;
				
				// Find items that will be visible after collapse
				while (visibleIndex < items.length && visibleCollapsedHeight < window.innerHeight) {
					var currentItem = items.eq(visibleIndex);
					visibles = visibles.add(currentItem);
					visibleCollapsedHeight += currentItem.data('heightMin');
					visibleIndex++;
				}
				visibles.each(function () { collapseItem($(this), settings.delay); });

				setTimeout(function collapseAllInvisibleEnd() {
					var first = visibles.eq(0);
					var firstOffset = first.offset().top;
					// update invisible accordingly
					invisibles = items.not(visibles);
					invisibles.each(function () { collapseItem($(this), 0); });
					if (firstOffset > 0 && scrollTop > object.offset().top) {
						// scroll back to where we were,
						// which is last scroll position + delta of first visible item
						$(window).scrollTop(scrollTop + (first.offset().top - firstOffset));
					}
					invisibles.trigger('animationend.collapsible');
				}, settings.delay + 100);
			});

			// Expand item
			var expandItem = function (item, duration) {
				var heightMax = 0;
				
				// Customization point
				item.trigger('expandbefore.collapsible', settings);
				
				heightMax = item.data('heightMax');

				// Check duration
				if(duration !== 0) {
					item.addClass('js-animate');
					item.trigger('expandstart.collapsible');
				}

				// Collapse item
				item.removeClass('collapsed');
				item.css('max-height', heightMax);

				if(duration !== 0) {
					setTimeout(function() {
						item.trigger('animationend.collapsible');
					}, duration);
				}
			};
			
			object.on('expand.collapsible', settings.items, function expand(event, duration) {
				var item = $(this);
				expandItem(item, getDuration(duration));
			});

			// Expand all items
			object.on('expandall.collapsible', function expandAll(event) {
				var items = object.find(settings.items + '.collapsed'),
					visibles = Symphony.Utilities.inSight(items).filter('*:lt(4)'),
					invisibles = items.not(visibles),
					scrollTop = $(window).scrollTop();
				
				visibles.addClass('js-animate-all'); // prevent focus
				visibles.each(function () { expandItem($(this), settings.delay); });
				setTimeout(function expandAllInvisible() {
					var first = visibles.eq(0);
					var firstOffset = first.offset().top;
					invisibles.addClass('js-animate-all'); // prevent focus
					invisibles.each(function () { expandItem($(this), 0); });
					invisibles.trigger('animationend.collapsible');
					// if we are past the first item
					if (firstOffset > 0 && scrollTop > object.offset().top) {
						// scroll back to where we were,
						// which is last scroll position + delta of first visible item
						$(window).scrollTop(scrollTop + (first.offset().top - firstOffset));
					}
				}, settings.delay + 100);
			});

			// Finish animations
			object.on('animationend.collapsible', settings.items, function finish(event) {
				var item = $(this);

				// Trigger events
				if(item.is('.collapsed')) {
					item.trigger('collapsestop.collapsible');
				}
				else {
					item.trigger('expandstop.collapsible');
				}

				// clean up
				item.removeClass('js-animate js-animate-all');
			});

			// Toggle single item
			object.on('click.collapsible', settings.handles, function toggle(event) {
				var handle = $(this),
					item = handle.closest(settings.items);

				if(!handle.is(settings.ignore) && !$(event.target).is(settings.ignore) && !item.is('.locked')) {

					// Expand
					if(item.is('.collapsed')) {
						expandItem(item, settings.delay);
					}

					// Collapse
					else {
						collapseItem(item, settings.delay);
					}
				}
			});

			// Save states
			var saveTimer = 0;
			object.on('collapsestop.collapsible expandstop.collapsible store.collapsible', settings.items, function saveState(event) {
				if(settings.save_state === true && Symphony.Support.localStorage === true) {
					// save it to local storage, delayed, once
					clearTimeout(saveTimer);
					saveTimer = setTimeout(function () {
						var collapsed = object.find(settings.items).map(function(index) {
							if($(this).is('.collapsed')) {
								return index;
							};
						}).get().join(',');

						save(storage, collapsed);
					}, settings.delay);
				}
			});

			// Restore states
			object.on('restore.collapsible', function restoreState(event) {
				if(settings.save_state === true && Symphony.Support.localStorage === true && window.localStorage[storage]) {
					$.each(window.localStorage[storage].split(','), function(index, value) {
						var collapsed = object.find(settings.items).eq(value);
						if(collapsed.has('.invalid').length == 0) {
							collapseItem(collapsed, 0);
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
