/**
 * @package assets
 */

(function($) {

	/**
	 * @todo: documentation
	 */
	$.fn.symphonyNotify = function(options) {
		var objects = this,
			settings = {
				items: 'p.notice',
				storage: 'symphony.notify.' + Symphony.Context.get('root').replace('http://', '')
			};

		$.extend(settings, options);

	/*-----------------------------------------------------------------------*/

		Symphony.Language.add({
			'Ignore?': false,
			'next': false
		});

	/*-----------------------------------------------------------------------*/

		// Attach message
		objects.on('attach.notify', function(event, message, type) {
			var object = $(this),
				notifier = object.find('div.notifier'),
				items = notifier.find(settings.items),
				item, storage;

			notifier.trigger('attachstart.notify');

			// Create item
			item = $('<p />', {
				html: message.replace(Symphony.Language.get('at') + ' ', ''),
				class: type
			}).addClass('notice active').symphonyTimeAgo();

			// Add ignore link to notices)
			if(!item.is('.error') && !item.is('.success')) {
				item.html(item.html() + ' <a class="ignore">' + Symphony.Language.get('Ignore?') + '</a>');
			}

			// Add navigator
			$('<nav />', {
				text: Symphony.Language.get('next')
			}).hide().appendTo(item);

			// Load exclusion rules
			if(Symphony.Support.localStorage === true) {
				storage = $.parseJSON(localStorage[settings.storage]) || [];
			}

			// Prepend item
			if($.inArray(item.text(), storage) == -1) {
				items.removeClass('active');
				item.addClass('active').prependTo(notifier);
				notifier.scrollTop(0);

				notifier.trigger('attachstop.notify', [item]);
			}
			else {
				notifier.trigger('attachcancel.notify', [item]);
			}
		});

		// Detach message
		objects.on('deattach.notify', settings.items, function(event) {
			var item = $(this),
				notifier = item.parents('div.notifier');

			notifier.trigger('detachstart.notify', [item]);

			// Prepare item removal
			notifier.one('movestop.notify', function(event) {
				var notifier = $(this),
					offset = notifier.scrollTop();

				// Adjust offset
				if(offset > 0) {
					notifier.scrollTop(offset - item.outerHeight());
				}

				// Remove item
				item.remove();

				notifier.trigger('detachstop.notify', [item]);
			});

			// Fade item
			item.animate({
				opacity: 0
			}, 'normal', function() {
				var items = item.siblings(),
					notifier = item.parents('div.notifier');

				// No items
				if(items.length == 0) {
					notifier.slideUp('fast');
					notifier.trigger('detachstop.notify', [item]);
				}

				// More item
				else {
					notifier.trigger('move.notify');
				}
			});
		});

		// Resize notifier
		objects.on('resize.notify attachstop.notify movestop.notify', 'div.notifier', function(event) {
			var notifier = $(this),
				active = notifier.find('.active'),
				speed = 100;

			// Adjust height
			if(!notifier.is('.constructing')) {
				notifier.animate({
					height: active.innerHeight()
				}, 100);
			}
		});

		// Count messages
		objects.on('attachstop.notify detachstop.notify', 'div.notifier', function(event) {
			var notifier = $(this),
				items = notifier.find(settings.items);

			// Hide navigator
			if(items.length == 1) {
				items.find('nav').hide();
			}

			// Show navigator
			else {
				items.find('nav').show();
			}
		});

		// Next message
		objects.on('click', 'nav', function(event) {
			var nav = $(this),
				notifier = $(this).parents('div.notifier');

			// Move messages
			notifier.trigger('move.notify');
		});

		// Move messages
		objects.on('move.notify', 'div.notifier', function(event) {
			var notifier = $(this),
				current = notifier.find('.active'),
				next = current.next(settings.items),
				from = current.outerHeight(),
				offset;

			notifier.trigger('movestart.notify');

			// Deactivate current message
			current.removeClass('active');

			// Activate next message and get offset
			if(next.length > 0) {
				next.addClass('active');
				offset = notifier.scrollTop() + from;
			}
			else {
				next = notifier.find(settings.items).first().addClass('active');
				offset = 0;
			}

			// If next's height is smaller, resize first
			if(next.outerHeight() < from) {
				notifier.trigger('resize.notify');
			}

			// Move to next message
			notifier.animate({
				scrollTop: offset
			}, 'fast', function() {
				notifier.trigger('movestop.notify');
			});
		});

		// Ignore message
		objects.on('click', 'a.ignore', function(event) {
			var ignore = $(this),
				item = ignore.parents(settings.items),
				notifier = item.parents('div.notifier'),
				text = item.text(),
				storage;

			// Store exclusion rule
			if(Symphony.Support.localStorage === true) {
				storage = $.parseJSON(localStorage[settings.storage]) || [];
				storage.push(text);
				localStorage[settings.storage] = JSON.stringify(storage);
			}

			// Remove item
			item.trigger('deattach.notify');
		});

	/*-----------------------------------------------------------------------*/

		// Build interface
		objects.each(function() {
			var object = $(this),
				notifier = $('<div class="notifier" />').prependTo(object),
				items = $(object.find(settings.items).get().reverse());

			// Construct notifier
			notifier.addClass('constructing');
			notifier.height(items.last().innerHeight());
			items.each(function() {
				var item = $(this).remove(),
					message = item.html(),
					type = item.attr('class');

				object.trigger('attach.notify', [message, type]);
			});

			// No messages (based on exclusion list)
			if(notifier.find(settings.items).length == 0) {
				notifier.removeClass('constructing').hide();
			}

			// Finish construction
			else {
				notifier.removeClass('constructing').trigger('resize.notify');
			}

			// Update relative times in system messages
			setInterval(function() {
				$('header p.notice').symphonyTimeAgo();
			}, 60000);
		});

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(jQuery.noConflict());
