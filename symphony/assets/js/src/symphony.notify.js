/**
 * @package assets
 */

(function($, Symphony) {

	/**
	 * Notify combines multiple system messages to an interface that focusses
	 * on a single message at a time and offers a navigation to move between message.
	 *
	 * @name $.symphonyNotify
	 * @class
	 *
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {String} [options.items='p.notice'] Selector to find messages
	 * @param {String} [options.storage='symphony.notify.root'] Namespace used for local storage
	 *
	 * @example

			$('#messages').symphonyNotify();
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
			'next': false,
			'at': false
		});

	/*-------------------------------------------------------------------------
		Events
	-------------------------------------------------------------------------*/

		// Attach message
		objects.on('attach.notify', function attachMessage(event, message, type) {
			var object = $(this),
				notifier = object.find('div.notifier'),
				items = notifier.find(settings.items),
				item, storage;

			notifier.trigger('attachstart.notify');

			// Create item
			item = $('<p />', {
				'class': type
			}).html(message.replace(Symphony.Language.get('at') + ' ', '')).addClass('notice active').symphonyTimeAgo();

			// Add ignore link to notices)
			if(!item.is('.error') && !item.is('.success') && !item.is('.protected')) {
				item.html(item.html() + ' <a class="ignore">' + Symphony.Language.get('Ignore?') + '</a>');
			}

			// Add navigator
			$('<nav />', {
				text: Symphony.Language.get('next')
			}).hide().appendTo(item);

			// Load exclusion rules
			if(Symphony.Support.localStorage === true) {
				storage = (window.localStorage[settings.storage]) ? $.parseJSON(window.localStorage[settings.storage]) : [];
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
		objects.on('detach.notify', settings.items, function detachMessage(event) {
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
			}, 'normal', function removeItem() {

				// No other items
				if(item.siblings().length == 0) {
					notifier.trigger('resize.notify', [$('<div />')]);
				}

				// More item
				else {
					notifier.trigger('move.notify');
				}

				// Remove item
				item.remove();
				notifier.trigger('detachstop.notify', [item]);
			});
		});

		// Resize notifier
		objects.on('resize.notify attachstop.notify', 'div.notifier', function resizeNotifer(event, item) {
			var notifier = $(this);

			// Adjust height
			if(!notifier.hasClass('constructing')) {
				var active = item || notifier.find('.active:not(:animated)');

				notifier.show().animate({
					height: active.innerHeight() || 0
				}, 100);
			}
		});

		// Count messages
		objects.on('attachstop.notify detachstop.notify', 'div.notifier', function toggleNavigator(event) {
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
		objects.on('click', 'nav', function switchMessage(event) {
			var nav = $(this),
				notifier = $(this).closest('div.notifier');

			// Move messages
			notifier.trigger('move.notify');
		});

		// Move messages
		objects.on('move.notify', 'div.notifier', function moveMessage(event) {
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

			// If next's height is not the same, resize first
			if(next.outerHeight() !== from) {
				notifier.trigger('resize.notify');
			}

			// Move to next message
			notifier.animate({
				scrollTop: offset
			}, 'fast', function stopMovingMessage() {
				notifier.trigger('movestop.notify');
			});
		});

		// Ignore message
		objects.on('click', 'a.ignore', function ignoreMessage(event) {
			var ignore = $(this),
				item = ignore.parents(settings.items),
				notifier = item.parents('div.notifier'),
				text = item.text(),
				storage;

			// Store exclusion rule
			if(Symphony.Support.localStorage === true) {
				// Put in a try/catch incase we exceed storage space
				try {
					storage = (window.localStorage[settings.storage]) ? $.parseJSON(window.localStorage[settings.storage]) : [];
					storage.push(text);
					window.localStorage[settings.storage] = JSON.stringify(storage);
				}
				catch(e) {
					window.onerror(e.message);
				}
			}

			// Remove item
			item.trigger('detach.notify');
		});

	/*-------------------------------------------------------------------------
		Initialisation
	-------------------------------------------------------------------------*/

		// Build interface
		objects.each(function initNotify() {
			var object = $(this),
				notifier = $('<div class="notifier" />').hide().prependTo(object),
				items = $(object.find(settings.items).get().reverse());

			// Construct notifier
			notifier.addClass('constructing');
			notifier.height(items.last().innerHeight());
			items.each(function buildMessages() {
				var item = $(this).remove(),
					message = item.html(),
					type = item.attr('class');

				object.trigger('attach.notify', [message, type]);
			});

			// Resize notifier
			if(notifier.find(settings.items).length > 0) {
				notifier.removeClass('constructing').trigger('resize.notify');
			}

			notifier.removeClass('constructing');

			// Update relative times in system messages
			setInterval(function updateRelativeTimes() {
				$('header p.notice').symphonyTimeAgo();
			}, 60000);
		});

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(window.jQuery, window.Symphony);
