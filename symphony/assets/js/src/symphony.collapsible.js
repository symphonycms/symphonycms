/**
 * @package assets
 */

(function($, Symphony) {

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
				items: '.instance',
				handles: '.frame-header',
				content: '.content',
				ignore: '.ignore',
				save_state: true,
				storage: 'symphony.collapsible.' + Symphony.Context.get('context-id')
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
					heightMin = item.data('heightMin') || item.find(settings.handles).outerHeight() - 1;

				// Check duration
				if(duration !== 0) {
					item.addClass('js-animate');
				}

				// Collapse item
				item.trigger('collapsestart.collapsible')
					.addClass('collapsed')
					.css('max-height', heightMin);

				setTimeout(function() {
					item.trigger('animationend.duplicator');
				}, 350);
			});

			// Collapse all items
			object.on('collapseall.collapsible', function collapseAll(event) {
				var items = object.find(settings.items + ':not(.collapsed)'),
					visibles = Symphony.Utilities.inSight(items),
					invisibles = visibles.nextAll();

				invisibles.each(function() {
					var item = $(this);
					item.css('max-height', item.data('heightMin'));
				});
				visibles.trigger('collapse.collapsible');
				invisibles.trigger('collapsestop.collapsible');
			});

			// Expand item
			object.on('expand.collapsible', settings.items, function expand(event, duration) {
				var item = $(this),
					heightMax = item.data('heightMax') || this.scrollHeight;

				// Check duration
				if(duration !== 0) {
					item.addClass('js-animate');
				}

				// Collapse item
				item.trigger('expandstart.collapsible')
					.removeClass('collapsed')
					.css('max-height', heightMax);

				setTimeout(function() {
					item.trigger('animationend.collapsible');
				}, 350);
			});

			// Expand all items
			object.on('expandall.collapsible', function expandAll(event) {
				var items = object.find(settings.items + '.collapsed'),
					firsts = items.filter('.collapsed:lt(2)');

				firsts.trigger('expand.collapsible');
				setTimeout(function() {
					items.not(firsts).each(function() {
						var item = $(this);
						item.css('max-height', item.data('heightMax'));
					}).removeClass('collapsed').trigger('expandstop.collapsible');
				}, 250);
			});

			// Finish animations
			object.on('animationend.collapsible', settings.items, function finish(event) {
				var item = $(this);

				setTimeout(function() {
					item.removeClass('js-animate');
				}, 200);

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

			// Refresh state storage after ordering
			object.on('orderstop.orderable', function refreshOrderedState(event) {
				object.find(settings.items).trigger('store.collapsible');
			});

			// Refresh state storage after deleting and instance
			object.on('destructstop.duplicator', settings.items, function refreshState() {
				$(this).trigger('store.collapsible');
			})

		/*---------------------------------------------------------------------
			Initialisation
		---------------------------------------------------------------------*/

			// Prepare interface
			object.addClass('collapsible').find(settings.items).each(function() {
				var item = $(this),
					min = item.find(settings.handles).outerHeight() - 1,
					max = this.scrollHeight;

				item.css('max-height', max);
				item.data('heightMin', min);
				item.data('heightMax', max);
				item.addClass('instance');
			});

			// Restore states
			object.trigger('restore.collapsible');
		});

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(window.jQuery, window.Symphony);
