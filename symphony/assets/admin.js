/**
 * Symphony Core JavaScript
 *
 * @version 2.2.0dev
 */


// Declare Symphony object globally
var Symphony = {};


(function($) {

	/**
	 * The Symphony object provides language, message and context management.
	 */
	Symphony = {

		/**
		 * Initialize the Symphony object
		 */
		init: function() {
			var html = $('html'),
				user = $('#usr li:first a');

			// Set JavaScript status
			$(document.documentElement).addClass('active');

			// Set basic context information
			Symphony.Context.add('user', {
				fullname: user.text(),
				name: user.attr('name'),
				type: user.attr('class'),
				id: user.attr('id').substring(4)
			});
			Symphony.Context.add('lang', html.attr('lang'));
			Symphony.Context.add('root', window.location.href.match('(.*)/symphony')[1]);

			// Initialise language
			Symphony.Language.add({
				'Add item': false,
				'Remove selected items': false,
				'Are you sure you want to {$action} {$name}?': false,
				'Are you sure you want to {$action} {$count} items?': false,
				'Are you sure you want to {$action}?': false,
				'Reordering was unsuccessful.': false,
				'Password': false,
				'Change Password': false,
				'Remove File': false,
				'at': false,
				'just now': false,
				'a minute ago': false,
				'{$minutes} minutes ago': false,
				'about 1 hour ago': false,
				'about {$hours} hours ago': false
			});

			/**
			 * Ensure backwards compatibility
			 *
			 * @deprecated The following variables will be removed in future Symphony versions
			 */
			Symphony.WEBSITE = Symphony.Context.get('root');
			Symphony.Language.NAME = Symphony.Context.get('lang');
		},

		/**
		 * The Context object contains general information about the system,
		 * the backend, the current user. It includes an add and a get function.
		 */
		Context: {

			/**
			 * @var object Storage object
			 *
			 * This object is private, use Symphony.Context.add() and
			 * Symphony.Context.get() to interact with the dictionary.
			 */
			Storage: {},

			/**
			 * Add data to the Context object
			 *
			 * @param string group
			 *  Name of the data group
			 * @param mixed values
			 *  Object or string to be stored
			 */
			add: function(group, values) {

				// Add new group
				if(!Symphony.Context.Storage[group]) {
					Symphony.Context.Storage[group] = values;
				}

				// Extend existing group
				else {
					Symphony.Context.Storage[group] = $.extend(Symphony.Context.Storage[group], values);
				}
			},

			/**
			 * Get data from the Context object
			 *
			 * @param string group
			 *  Name of the group to be returned
			 */
			get: function(group) {

				// Return full context, if no group is set
				if(!group) {
					return Symphony.Context.Storage;
				}

				// Return context group
				else {
					return Symphony.Context.Storage[group];
				}
			}

		},

		/**
		 * The Language object stores the dictionary with all needed translations.
		 * It offers public functions to add strings and get their translation and
		 * it offers private functions to handle variables and get the translations via
		 * an synchronous AJAX request.
		 */
		Language: {

			/**
			 * @var object Dictionary object
			 *
			 * This object is private, use Symphony.Language.add() to add and Symphony.Language.get()
			 * to interact with the dictionary.
			 */
			Dictionary: {},

			/**
			 * Add strings to the Dictionary
			 *
			 * @param object strings
			 *  Object with English string as key, value should be false
			 */
			add: function(strings) {

				// Set key as value
				$.each(strings, function(key, value) {
					strings[key] = key;
				});

				// Save English strings
				if(Symphony.Context.get('lang') == 'en') {
					Symphony.Language.Dictionary = $.extend(Symphony.Language.Dictionary, strings);
				}

				// Translate strings
				else {
					Symphony.Language.translate(strings);
				}
			},

			/**
			 * Get translated string from the Dictionary.
			 * The function replaces variables like {$name} with the a specified value if
			 * an object of inserts is passed in the function call.
			 *
			 * @param string string
			 *  English string to be translated
			 * @param object inserts
			 *  Object with variable name and value pairs
			 * @return string
			 *  Returns the translated string
			 */
			get: function(string, inserts) {

				// Get translated string
				translatedString = Symphony.Language.Dictionary[string];

				// Return string if it cannot be found in the dictionary
				if(translatedString !== false) string = translatedString;

				// Insert variables
				if(inserts !== undefined) string = Symphony.Language.insert(string, inserts);

				// Return translated string
				return string;
			},

			/**
			 * This private function replaces variables with a specified value.
			 * It should not be called directly.
			 *
			 * @param string string
			 *  Translated string with variables
			 * @param object inserts
			 *  Object with variable name and value pairs
			 * @return string
			 *  Returns translated strings with all variables replaced by their actual value
			 */
			insert: function(string, inserts) {

				// Replace variables
				$.each(inserts, function(index, value) {
					string = string.replace('{$' + index + '}', value);
				});
				return string;
			},

			/**
			 * This private function sends a synchronous AJAX request to fetch the translations
			 * for the English strings in the dictionary. It should not be called directly
			 *
			 * @param object strings
			 *  Object of strings to be translated
			 * @return object
			 *  Object with original string and translation pairs
			 */
			translate: function(strings) {

				// Load translations synchronous
				$.ajax({
					async: false,
					type: 'GET',
					url: Symphony.Context.get('root') + '/symphony/ajax/translate',
					data: strings,
					dataType: 'json',
					success: function(result) {
						Symphony.Language.Dictionary = $.extend(Symphony.Language.Dictionary, result);
					},
					error: function() {
						Symphony.Language.Dictionary = $.extend(Symphony.Language.Dictionary, strings);
					}
				});
			}

		},

		/**
		 * The message object handles system messages that should be displayed on the fly.
		 * It offers a post and a clear function to set and remove messages. Absolute dates
		 * and times will be replaced by a representation relative to the user's system time.
		 */
		Message: {

			/**
			 * @var array Message queue
			 *
			 * This array is private and should not be accessed directly.
			 */
			Queue: [],

			/**
			 * Post system message
			 *
			 * @param string message
			 *  Message to be shown
			 * @param string type
			 *  Message type to be used as class name
			 */
			post: function(message, type) {

				// Store previous message
				Symphony.Message.Queue = Symphony.Message.Queue.concat($('#notice').remove().get());

				// Add new message
				$('h1').before('<div id="notice" class="' + type + '">' + message + '</div>');
			},

			/**
			 * Clear message by type
			 *
			 * @param string type
			 *  Message type
			 */
			clear: function(type) {
				var message = $('#notice');

				// Remove messages of specified type
				message.filter('.' + type).remove();
				Symphony.Message.Queue = $(Symphony.Message.Queue).filter(':not(.' + type + ')').get();

				// Show previous message
				if(message.size() > 0 && Symphony.Message.Queue.length > 0) {
					$(Symphony.Message.Queue.pop()).insertBefore('h1');
				}
			},

			/**
			 * Fade message highlight color to grey
			 */
			fade: function(newclass, delay) {
				var notice = $('#notice.success').addClass(newclass),
					styles = {
						'color': notice.css('color'),
						'backgroundColor': notice.css('background-color'),
						'borderTopColor': notice.css('border-top-color'),
						'borderRightColor': notice.css('border-right-color'),
						'borderBottomColor': notice.css('border-bottom-color'),
						'borderLeftColor': notice.css('border-left-color'),
					};
				
				// Delayed animation to new styles
				notice.removeClass(newclass).delay(delay).animate(styles, 'slow', 'linear', function() {
					$(this).removeClass('success');
				});
			},

			/**
			 * Convert absolute message time to relative time and update continuously
			 */
			timer: function() {
				var time = Date.parse($('abbr.timeago').attr('title'));
				var from = new Date;
				from.setTime(time);

				// Set relative time
				$('abbr.timeago').text(this.distance(from, new Date));

				// Update continuously
				window.setTimeout("Symphony.Message.timer()", 60000);
			},

			/**
			 * Calculate relative time.
			 *
			 * @param Date from
			 *  Starting date
			 * @param Date to
			 *  Current date
			 */
  			distance: function(from, to) {

  				// Calculate time difference
  				var distance = to - from;

  				// Convert time to minutes
				var time = Math.floor(distance / 60000);

				// Return relative date based on passed time
				if(time < 1) {
					return Symphony.Language.get('just now');
				}
				if(time < 2) {
					return Symphony.Language.get('a minute ago');
				}
				if(time < 45) {
					return Symphony.Language.get('{$minutes} minutes ago', {
						'minutes': time
					});
				}
				if(time < 90) {
					return Symphony.Language.get('about 1 hour ago');
				}
				else {
					return Symphony.Language.get('about {$hours} hours ago', {
						'hours': Math.floor(time / 60)
					});
				}
			}

		}

	};

	/**
	 * Symphony core functionalities
	 */
	$(document).ready(function() {

		// Initialize Symphony
		Symphony.init();

		// Tags
		$('.tags').symphonyTags();

		// Pickers
		$('.picker').symphonyPicker();

		// Duplicators
		$('.filters-duplicator').symphonyDuplicator();
		$('#fields-duplicator').symphonyDuplicator({
			orderable: true
		});
		
		// Toggle field labels in section editor
		$('#fields-duplicator').bind('collapsestop', function(event, item) {
			var instance = jQuery(item);
			instance.find('.header > span:not(:has(i))').append(
				$('<i />').text(instance.find('label:first input').attr('value'))
			);
		});
		
		$('#fields-duplicator').bind('expandstop', function(event, item) {
			$(item).find('.header > span > i').remove();
		});		

		// Fade system messages
		Symphony.Message.fade('silence', 10000);
		
		// Relative times in system messages
		$('abbr.timeago').each(function() {
			var time = $(this).parent();
			time.html(time.html().replace(Symphony.Language.get('at') + ' ', ''));
		});
		Symphony.Message.timer();
		
	});


/*-----------------------------------------------------------------------------
	Things to be cleaned up
-----------------------------------------------------------------------------*/

	// Sortable lists
	var movable = {
		move: function(e) {
			var t,
			    n,
			    y = e.pageY;

			if (y < movable.min) {
				t = movable.target.prev();
				for (;;) {
					movable.delta--;
					n = t.prev();
					if (n.length === 0 || y >= (movable.min -= n.height())) {
						movable.target.insertBefore(t);
						break;
					}
					t = n;
				}
			} else if (y > movable.max) {
				t = movable.target.next();
				for (;;) {
					movable.delta++;
					n = t.next();
					if (n.length === 0 || y <= (movable.max += n.height())) {
						movable.target.insertAfter(t);
						break;
					}
					t = n;
				}
			} else {
				return;
			}

			movable.update(movable.target);
			movable.target.parent().children().each(function(i) { $(this).toggleClass('odd', i % 2 === 0); });
		},
		drop: function() {
			$(document).unbind('mousemove', movable.move);
			$(document).unbind('mouseup', movable.drop);

			movable.target.removeClass('movable');

			if (movable.delta) {
				movable.target.trigger($.Event('reorder'));
			}
		},
		update: function(target) {
			var a = target.height(),
			    b = target.offset().top,
				prev_offset = (target.prev().length) ? target.prev().offset().top : 0;

			movable.target = target;
			movable.min    = Math.min(b, a + (prev_offset || -Infinity));
			movable.max    = Math.max(a + b, b + (target.next().height() ||  Infinity));
		}
	};

	$('.orderable tr, .subsection > ol > li').live('mousedown', function(e) {
		if (!/^(?:h4|td)$/i.test(e.target.nodeName)) {
			return true;
		}

		movable.update($(this).addClass('movable'));
		movable.delta = 0;

		$(document).mousemove(movable.move);
		$(document).mouseup(movable.drop);

		return false;
	});

	$('table.orderable').live('reorder', function() {
		var t = $(this).addClass('busy');

		$.ajax({
			type: 'POST',
			url: Symphony.Context.get('root') + '/symphony/ajax/reorder' + location.href.slice(Symphony.Context.get('root').length + 9),
			data: $('input', this).map(function(i) { return this.name + '=' + i; }).get().join('&'),
			success: function() {
				Symphony.Message.clear('reorder');
			},
			error: function() {
				Symphony.Message.post(Symphony.Language.get('Reordering was unsuccessful.'), 'reorder error');
			},
			complete: function() {
				t.removeClass('busy');
			}
		});
	});

	$('.selectable td, .subsection h4').live('click', function(e) {
		if (movable.delta || !/^(?:td|h4)$/i.test(e.target.nodeName)) {
			return true;
		}

		var r = $(this.parentNode).toggleClass('selected');

		r.trigger($.Event(r.hasClass('selected') ? 'select' : 'deselect'));
		r.find('td input').each(function() { this.checked = !this.checked; });

		// when shift held when selecting a row
		if (e.shiftKey && r.hasClass('selected')) {

			// find first selected row above newly-selected row
			var selected_above = r.prevAll('.selected');
			if (selected_above.length) {
				var from = $('.selectable tr').index(selected_above);
				var to = $('.selectable tr').index(r);
				$('.selectable tr').each(function(i) {
					if (i > from && i < to) {
						var r = $(this).toggleClass('selected');
						r.trigger($.Event(r.hasClass('selected') ? 'select' : 'deselect'));
						r.find('td input').each(function() { this.checked = !this.checked; });
					}
				});
			}
			// de-select text caused by holding shift
			if (window.getSelection) window.getSelection().removeAllRanges();
		}

		return false;
	});

	// Document ready
	$(document).ready(function() {

		// Ugly DOM maintenance
		$('table:has(input)').addClass('selectable');

		if (/[?&]debug[&=][^#]*#line-\d+$/.test(location.href)) {
			$('ol a').eq(parseInt(/\d+$/.exec(location.href)[0], 10) - 1).addClass('active');
		}

		$('ul.tags > li').mousedown(silence);
		$('#nav').mouseover(silence);
		$('.orderable td, .subsection h4').bind('selectstart', silence); // Fix for IE bug

		function silence() { return false; }

		// Change user password
		$('#change-password').each(function() {

			// Do not hide fields if there is some error there.
			if ($('div.invalid', $(this)).length > 0) return;

			var a = $(this),
			    b = a.next('p.help').remove();

			if (a.find('label').length !== 3 && a.find('label').length !== 2) {
				return;
			}

			a.before('<div class="label">' + Symphony.Language.get('Password') + ' <span><button id="change-password" type="button">' + Symphony.Language.get('Change Password') + '</button></span></div>').remove();

			$('#change-password').click(function() {
				$(this.parentNode.parentNode).replaceWith(b);
				a.insertBefore(b).find('input')[0].focus();
			});
		});

		// Upload fields
		$('<em>' + Symphony.Language.get('Remove File') + '</em>').appendTo('label.file:has(a) span').click(function() {
			var s = $(this.parentNode),
			    d = '<input name="' + $(this).siblings('input').attr('name') + '" type="file">';

			setTimeout(function() { s.html(d); }, 50); // Delayed to avoid WebKit clickthrough bug
		});

		// confirm() dialogs
		$('button.confirm').live('click', function() {
			var n = document.title.split(/[\u2013]\s*/g)[2],
			    t = (n ? 'Are you sure you want to {$action} {$name}?' : 'Are you sure you want to {$action}?');

			return confirm(Symphony.Language.get(t, {
				'action': this.firstChild.data.toLowerCase(),
				'name': n
			}));
		});

		if ($('[name=with-selected] option.confirm').length > 0) {
			$('form').submit(function() {
				var i = $('table input:checked').length,
				    t = (i > 1 ? 'Are you sure you want to {$action} {$count} items?' : 'Are you sure you want to {$action} {$name}?'),
				    s = document.getElementsByName('with-selected')[0],
				    o = $(s.options[s.selectedIndex]);

				if (i == 0) return false;

				t = Symphony.Language.get(t, {
					'action': o.text().toLowerCase(),
					'name': $.trim($('table input:checked').parents('tr').find('td').eq(0).text()),
					'count': i
				});

				return i > 0 && !o.hasClass('confirm') || confirm(t);
			});
		}

		// XSLT utilities
		$('#utilities a').each(function() {
			var a = $(this.parentNode),
			    r = new RegExp('href=["\']?(?:\\.{2}/utilities/)?' + $(this).text());

			$('textarea').blur(function() {
				a[r.test(this.value) ? 'addClass' : 'removeClass']('selected');
			});
		});

		$('textarea').blur();

		// Repeating sections
		$('div.subsection').each(function() {
			var m = $(this),
			    t = m.find('.template'),
			    h = t.map(function() { return $(this).height(); }).get();

			t.remove().css('height', 0);
			m.append('<div class="actions"><a>' + Symphony.Language.get('Add item') + '</a><a class="inactive">' + Symphony.Language.get('Remove selected items') + '</a></div>')
			m.bind('select', select).bind('deselect', select);

			var r = m.find('.actions > a.inactive'),
			    i = 0;

			function select(e) {
				r.toggleClass('inactive', !(i += e.type === 'select' ? 1 : -1));
			}

			if (t.length > 1) {
				var s = document.createElement('select'),
				    l = t.find('h4');

				for (var i = 0; i < l.length; i++) {
					s.options[i] = new Option(l[i].firstChild.data, i);
				}

				$('.actions', this).prepend(s);
			}

			m.find('.actions > a').click(function() {
				var a = $(this);

				if (a.hasClass('inactive')) {
					return;
				}

				if (a.is(':last-child')) {
					m.find('li.selected').animate({height: 0}, function() {
						$(this).remove();
					});

					i = 0;
					a.addClass('inactive');
				} else {
					var j = s ? s.selectedIndex : 0,
					    w = m.find('ol');

					t.eq(j).clone(true).appendTo(w).animate({height: h[j]}, function() {
						$('input:not([type=hidden]), select, textarea', this).eq(0).focus();
					});
				}
			});

			$('form').submit(function() {
				m.find('ol > li').each(function(i) {
					$('input,select,textarea', this).each(function() {
						this.name = this.name.replace(/\[-?\d+(?=])/, '[' + i);
					});
				});
			});
		});

		// Data source switcheroo
		$('select.filtered > optgroup').each(function() {
			var s = this.parentNode,
			    l = this.label,
			    z = $(this).siblings('option').length,
			    o = $(this).remove().find('option');

			$('#context').change(function() {
				if ($(this.options[this.selectedIndex]).text() === l) {
					s.options.length = z;
					o.clone(true).appendTo(s);
				}
			});
		});

		$('*.contextual').each(function() {
			var a = $(this);

			$('#context').change(function() {
				var o = $(this.options[this.selectedIndex]).parent('optgroup'),
				    c = this.value.replace(/\W+/g, '_'),
				    g = o.attr('label').replace(/\W+/g, '_');

				a[(a.hasClass(c) || a.hasClass(g)) ^ a.hasClass('inverse') ? 'removeClass' : 'addClass']('irrelevant');
			});
		});

		$('#context').change();

	});

})(jQuery.noConflict());
