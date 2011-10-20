/**
 * @package assets
 */


/**
 * The Symphony object provides language, message and context management.
 *
 * @class
 */
var Symphony = {};


(function($) {

	Symphony = {

		/**
		 * Initialize the Symphony object
		 */
		init: function() {
			var html = $('html'),
				user = $('#usr li:first a');

			// Set JavaScript status
			html.addClass('active');

			// Set basic context information
			Symphony.Context.add('user', {
				fullname: user.text(),
				name: user.data('name'),
				type: user.data('type'),
				id: user.data('id')
			});
			Symphony.Context.add('lang', html.attr('lang'));

			// Set browser support information
			Symphony.Support.localStorage = ('localStorage' in window && window['localStorage'] !== null) ? true : false;
			// Deep copy jQuery.support
			$.extend(true, Symphony.Support, $.support);

			// Initialise language
			Symphony.Language.add({
				'Add item': false,
				'Remove selected items': false,
				'Are you sure you want to proceed?': false,
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
			 * @deprecated You should now use Symphony.Context.get('root')
			 */
			Symphony.WEBSITE = Symphony.Context.get('root');

			/**
			 * @deprecated You should now use Symphony.Context.get('lang')
			 */
			Symphony.Language.NAME = Symphony.Context.get('lang');
		},

		/**
		 * The Context object contains general information about the system,
		 * the backend, the current user. It includes an add and a get function.
		 * This is a private object and can only be accessed via add and get.
		 *
		 * @class
		 */
		Context: new (function(){

			/**
			 * This object is private and can not be accessed without
			 * Symphony.Context.add() and Symphony.Context.get() which interact
			 * with the dictionary.
			 *
			 * @private
			 */
			var Storage = {};

			/**
			 * Add data to the Context object
			 *
			 * @param {String} group
			 *  Name of the data group
			 * @param {String|Object} values
			 *  Object or string to be stored
			 */
			this.add = function(group, values) {

				// Extend existing group
				if(Storage[group] && $.type(values) !== 'string') {
					$.extend(Storage[group], values);
				}

				// Add new group
				else {
					Storage[group] = values;
				}

				// Always return
				return true;
			};

			/**
			 * Get data from the Context object
			 *
			 * @param {String} group
			 *  Name of the group to be returned
			 */
			this.get = function(group) {

				// Return full context, if no group is set
				if(!group) {
					return Storage;
				}

				// Return false if group does not exist in Storage
				if(typeof Storage[group] == 'undefined') {
					return false;
				}

				// Default: Return context group
				return Storage[group];
			};

		}),

		/**
		 * The Language object stores the dictionary with all needed translations.
		 * It offers public functions to add strings and get their translation and
		 * it offers private functions to handle variables and get the translations via
		 * an synchronous AJAX request.
		 * Since Symphony 2.3, it is also possible to define different translations
		 * for the same string, by using namespaces.
		 * This is a private object
		 *
		 * @class
		 */
		Language: new (function(){

			/**
			 * This object is private and can not be accessed without
			 * Symphony.Language.add() to add and Symphony.Language.get() which
			 * interact with the dictionary.
			 *
			 * @private
			 */
			var Dictionary = {};

			/**
			 * Add strings to the Dictionary
			 *
			 * @param {Object} strings
			 *  Object with English string as key, value should be false
			 * @param {String} namespace
			 *  Optional namespace for the translation
			 */
			this.add = function(strings, namespace) {
				var temp = {};

				// Don't process empty strings
				if($.isEmptyObject(strings)) {
					return true;
				}

				// Set key as value
				if($.type(namespace) === 'string' && $.trim(namespace) !== '') {
					if (!temp[namespace]) {
						temp[namespace] = {};
					}

					$.each(strings, function(key, value) {
						temp[namespace][key] = key;
					});
				} else {
					$.each(strings, function(key, value) {
						temp[key] = key;
					});
				}

				// Save English strings
				if(Symphony.Context.get('lang') == 'en') {
					$.extend(Dictionary, temp);
				}

				// Translate strings and defer merging objects until translate() has returned
				else {
					translate(temp);
				}
			};

			/**
			 * Get translated string from the Dictionary.
			 * The function replaces variables like {$name} with the a specified value if
			 * an object of inserts is passed in the function call.
			 *
			 * @param {String} string
			 *  English string to be translated
			 * @param {Object} inserts
			 *  Object with variable name and value pairs
			 * @param {String} namespace
			 *  Optional namespace for the translation
			 * @return {String}
			 *  Returns the translated string
			 */
			this.get = function(string, inserts, namespace) {

				// Get translated string
				var translatedString;

				if($.type(namespace) === 'string' && $.trim(namespace) !== '' && Dictionary[namespace] !== undefined) {
					translatedString = Dictionary[namespace][string];
				} else {
					translatedString = Dictionary[string];
				}

				// Return string if it cannot be found in the dictionary
				if(translatedString !== false) {
					string = translatedString;
				}

				// Insert variables
				if(inserts !== undefined && inserts !== null) {
					string = insert(string, inserts);
				}

				// Return translated string
				return string;
			};

			/**
			 * This private function replaces variables with a specified value.
			 * It can not be called directly.
			 *
			 * @param {String} string
			 *  Translated string with variables
			 * @param {Object} inserts
			 *  Object with variable name and value pairs
			 * @return {String}
			 *  Returns translated strings with all variables replaced by their actual value
			 *
			 * @private
			 */
			var insert = function(string, inserts) {

				// Replace variables
				$.each(inserts, function(index, value) {
					string = string.replace('{$' + index + '}', value);
				});
				return string;
			};

			/**
			 * This private function sends a synchronous AJAX request to fetch the translations
			 * for the English strings in the dictionary. It can not be called directly
			 *
			 * @param {Object} strings
			 *  Object of strings to be translated
			 * @return {Object}
			 *  Object with original string and translation pairs
			 *
			 * @private
			 */
			var translate = function(strings) {
				// Load translations synchronous
				$.ajax({
					async: false,
					type: 'GET',
					url: Symphony.Context.get('root') + '/symphony/ajax/translate/',
					data: { 'strings': strings },
					dataType: 'json',
					success: function(result) {
						$.extend(Dictionary, result);
					},
					error: function(jqXHR, textStatus, errorThrown) {
						// Extend the existing dictionary since an error occurred
						$.extend(Dictionary, strings);
					}
				});
			};

		}),

		/**
		 * The message object handles system messages that should be displayed on the fly.
		 * It offers a post and a clear function to set and remove messages. Absolute dates
		 * and times will be replaced by a representation relative to the user's system time.
		 *
		 * @class
		 * @private
		 */
		Message: new (function(){

			/**
			 * This array is private and can not be accessed directly.
			 *
			 * @private
			 */
			var Queue = [];

			/**
			 * Post system message
			 *
			 * @param {String} message
			 *  Message to be shown
			 * @param {String} type
			 *  Message type to be used as class name
			 */
			this.post = function(message, type) {

				// Store previous message
				Queue = Queue.concat($('#notice').remove().get());

				// Add new message
				$('h1').before('<div id="notice" class="' + type + '">' + message + '</div>');
			};

			/**
			 * Clear message by type
			 *
			 * @param {String} type
			 *  Message type
			 */
			this.clear = function(type) {
				var message = $('#notice');

				// Remove messages of specified type
				message.filter('.' + type).remove();
				Symphony.Message.Queue = $(Queue).filter(':not(.' + type + ')').get();

				// Show previous message
				if(message.size() > 0 && Queue.length > 0) {
					$(Queue.pop()).insertBefore('h1');
				}
			};

			/**
			 * Fade message highlight color to grey
			 */
			this.fade = function(newclass, delay) {
				var notice = $('#notice.success').addClass(newclass),
					styles = {
						'color': notice.css('color'),
						'backgroundColor': notice.css('background-color'),
						'borderTopColor': notice.css('border-top-color'),
						'borderRightColor': notice.css('border-right-color'),
						'borderBottomColor': notice.css('border-bottom-color'),
						'borderLeftColor': notice.css('border-left-color')
					};

				// Delayed animation to new styles
				if(notice.is(':visible')) {
					notice.removeClass(newclass).delay(delay).animate(styles, 'slow', 'linear', function() {
						$(this).removeClass('success');
					});
				}
			};

			/**
			 * Convert absolute message time to relative time and update continuously
			 */
			this.timer = function() {
				var time = Date.parse($('abbr.timeago').attr('title')),
					to = new Date(),
					from = new Date();

				// Set time
				from.setTime(time);

				// Set relative time
				$('abbr.timeago').text(this.distance(from, to));

				// Update continuously
				window.setTimeout("Symphony.Message.timer()", 60000);
			};

			/**
			 * Calculate relative time.
			 *
			 * @param {Date} from
			 *  Starting date
			 * @param {Date} to
			 *  Current date
			 */
			this.distance = function(from, to) {

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
			};

		}),

		/**
		 * A collection of properties that represent the presence of
 		 * different browser features and also contains the test results
		 * from jQuery.support.
		 *
		 * @class
		 */
		Support: {

			/**
			 * Does the browser have support for the HTML5 localStorage API
			 * @type Boolean
			 * @default false*
			 * @example

				if(Symphony.Support.localStorage) { ... }

			 */
			localStorage: false
		}
	};

	/**
	 * Symphony core interactions
	 */
	$(document).ready(function() {

		// Initialize Symphony
		Symphony.init();

		// Tags
		$('.tags').symphonyTags();

		// Pickers
		$('.picker').symphonyPickable();

		// Selectable
		var selectable = $('table.selectable');
		selectable.symphonySelectable();
		selectable.find('a').mousedown(function(event) {
			event.stopPropagation();
		});

		// Orderable list
		$('ul.orderable').symphonyOrderable();

		// Orderable tables
		var old_sorting, orderable = $('table.orderable');
		orderable.symphonyOrderable({
			items: 'tr',
			handles: 'td'
		});

		// Don't start ordering while clicking on links
		orderable.find('a').mousedown(function(event) {
			event.stopPropagation();
		});

		// Store current sort order
		orderable.live('orderstart', function() {
			old_sorting = orderable.find('input').map(function(e, i) { return this.name + '=' + (e + 1); }).get().join('&');
		});

		// Process sort order
		orderable.live('orderstop', function() {
			orderable.addClass('busy');

			// Get new sort order
			var new_sorting = orderable.find('input').map(function(e, i) { return this.name + '=' + (e + 1); }).get().join('&');

			// Store new sort order
			if(new_sorting != old_sorting) {

				// Update items
				orderable.trigger('orderchange');

				// Send request
				$.ajax({
					type: 'POST',
					url: Symphony.Context.get('root') + '/symphony/ajax/reorder' + location.href.slice(Symphony.Context.get('root').length + 9),
					data: new_sorting,
					success: function() {
						Symphony.Message.clear('reorder');
					},
					error: function() {
						Symphony.Message.post(Symphony.Language.get('Reordering was unsuccessful.'), 'reorder error');
					},
					complete: function() {
						orderable.removeClass('busy').find('tr').removeClass('selected');
						old_sorting = '';
					}
				});
			}
			else {
				orderable.removeClass('busy');
			}

		});

		// Duplicators
		$('.filters-duplicator').symphonyDuplicator();

		// Collapsible duplicators
		var duplicator = $('#fields-duplicator');
		duplicator.symphonyDuplicator({
			orderable: true,
			collapsible: true
		});
		duplicator.bind('collapsestop', function(event, item) {
			var instance = $(item);
			instance.find('.header > span:not(:has(i))').append(
				$('<i />').text(instance.find('label:first input').attr('value'))
			);
		});
		duplicator.bind('expandstop', function(event, item) {
			$(item).find('.header > span > i').remove();
		});
		duplicator.trigger('restorestate');

		// Dim system messages
		Symphony.Message.fade('silence', 10000);

		// Relative times in system messages
		$('abbr.timeago').each(function() {
			var time = $(this).parent();
			time.html(time.html().replace(Symphony.Language.get('at') + ' ', ''));
		});
		Symphony.Message.timer();

		// XSLT utilities
		$('textarea').blur(function() {
			var source = $(this).val(),
				utilities = $('#utilities li');

			// Remove current selection
			utilities.removeClass('selected');

			// Get utitities names
			utilities.find('a').each(function() {
				var utility = $(this),
					expression = new RegExp('href=["\']?(?:\\.{2}/utilities/)?' + utility.text());

				// Check for utility occurrences
				if(expression.test(source)) {
					utility.parent().addClass('selected');
				}
			});
		}).blur();

		// Clickable utilities in the XSLT editor
		$('#utilities li').click(function(event) {
			if ($(event.target).is('a')) return;

			var editor = $('textarea.code'),
				lines = editor.val().split('\n'),
				link = $(this).find('a').text(),
				statement = '<xsl:import href="../utilities/' + link + '"/>',
				regexp = '^<xsl:import href="(?:\.\./utilities/)?' + link + '"',
				newLine = '\n',
				numberOfNewLines = 1;

			if ($(this).hasClass('selected')) {
				for (var i = 0; i < lines.length; i++) {
					if ($.trim(lines[i]).match(regexp) != null) {
						(lines[i + 1] === '' && $.trim(lines[i - 1]).substring(0, 11) !== '<xsl:import') ? lines.splice(i, 2) : lines.splice(i, 1);
						break;
					}
				}

				editor.val(lines.join(newLine));
				$(this).removeClass('selected');
			}
			else {
				for (var i = 0; i < lines.length; i++) {
					if ($.trim(lines[i]).substring(0, 4) === '<!--' || $.trim(lines[i]).match('^<xsl:(?:import|variable|output|comment|template)')) {

						numberOfNewLines = $.trim(lines[i]).substring(0, 11) === '<xsl:import' ? 1 : 2;

						if (Symphony.Context.get('env')[0] != 'template') {
							lines[i] = statement.replace('../utilities/', '') + Array(numberOfNewLines + 1).join(newLine) + lines[i];
						}
						else {
							// we are inside the page template editor
							lines[i] = statement + Array(numberOfNewLines + 1).join(newLine) + lines[i];
						}
						break;
					}
				}

				editor.val(lines.join(newLine));
				$(this).addClass('selected');
			}
		});

		// Change user password
		$('#change-password').each(function() {
			var password = $(this),
				labels = password.find('label'),
				help = password.next('p.help'),
				placeholder = $('<label>' + Symphony.Language.get('Password') + ' <span class="frame"><button>' + Symphony.Language.get('Change Password') + '</button></span></label>'),
				invalid = password.has('.invalid');

			if(invalid.size() == 0) {

				// Hide password fields
				password.removeClass();
				labels.hide();
				help.hide();

				// Add placeholder
				password.append(placeholder).find('button').click(function(event) {
					event.preventDefault();

					// Hide placeholder
					placeholder.hide();

					// Shwo password fields
					password.addClass('group');
					if(password.find('input[name="fields[old-password]"]').length) password.addClass('triple');
					labels.show();
					help.show();
				});

			}

		});

		// Confirm actions
		$('button.confirm').live('click', function() {
			var button = $(this),
				name = document.title.split(/[\u2013]\s*/g)[2],
				message = button.attr('data-message');

			// Set default message
			if(!message) {
				message = Symphony.Language.get('Are you sure you want to proceed?');
			}

			return confirm(message);
		});

		// Confirm with selected actions
		$('form').submit(function(event) {
			var select = $('select[name="with-selected"]'),
				option = select.find('option:selected'),
				input = $('table input:checked'),
				count = input.size(),
				message = option.attr('data-message');

			// Needs confirmation
			if(option.is('.confirm')) {

				// Set default message
				if(!message) {
					message = Symphony.Language.get('Are you sure you want to proceed?');
				}

				return confirm(message);
			}
		});

		// Data source manager options
		$('select.filtered > optgroup').each(function() {
			var optgroup = $(this),
				select = optgroup.parents('select'),
				label = optgroup.attr('label'),
				options = optgroup.remove().find('option').addClass('optgroup');

			// Fix for Webkit browsers to initially show the options
			if (select.attr('multiple')) {
				select.scrollTop(0);
			}

			// Show only relevant options based on context
			$('#ds-context').change(function() {
				if($(this).find('option:selected').text() == label) {
					select.find('option.optgroup').remove();
					select.append(options.clone(true));
				}
			});
		});

		// Data source manager context
		$('*.contextual').each(function() {
			var area = $(this);

			$('#ds-context').change(function() {
				var select = $(this),
					optgroup = select.find('option:selected').parent(),
					value = select.val().replace(/\W+/g, '_'),
					group = optgroup.attr('label').replace(/\W+/g, '_');

				// Show only relevant interface components based on context
				area[(area.hasClass(value) || area.hasClass(group)) ^ area.hasClass('inverse') ? 'removeClass' : 'addClass']('irrelevant');
			});
		});

		// Set data source manager context
		$('#ds-context').change();

		// Once pagination is disabled, max_records and page_number are disabled too
		var max_record = $('input[name*=max_records]'),
			page_number = $('input[name*=page_number]');

		$('input[name*=paginate_results]').change(function(event) {

			// Turn on pagination
			if($(this).is(':checked')) {
				max_record.attr('disabled', false);
				page_number.attr('disabled', false);
			}

			// Turn off pagination
			else {
				max_record.attr('disabled', true);
				page_number.attr('disabled', true);
			}
		}).change();

		// Disable paginate_results checking/unchecking when clicking on either max_records or page_number
		max_record.add(page_number).click(function(event) {
			event.preventDefault();
		});

		// Enabled fields on submit
		$('form').bind('submit', function() {
			max_record.attr('disabled', false);
			page_number.attr('disabled', false);
		});

		// Upload fields
		$('<em>' + Symphony.Language.get('Remove File') + '</em>').appendTo('label.file:has(a) span').click(function(event) {
			var span = $(this).parent(),
				name = span.find('input').attr('name');

			// Prevent clicktrough
			event.preventDefault();

			// Add new empty file input
			span.empty().append('<input name="' + name + '" type="file">');
		});

		// Focus first text-input or textarea when creating entries
		if(Symphony.Context.get('env') != null && (Symphony.Context.get('env')[0] == 'new' || Symphony.Context.get('env').page == 'new')) {
			$('input[type="text"], textarea').first().focus();
		}

		// Accessible navigation
		$('#nav').delegate('a', 'focus blur', function() {
			$(this).parents('li').eq(1).toggleClass('current');
		});

	    // Auto-highlight content in pagination input
	    $('.page input').focus(function() {
			$(this).select();
	    });

	    // Validate pagination input on submit
	    $('.page form').submit(function() {
			if(!$(this).find('input').val().match('^[0-9]+$') || $(this).find('input').val() > parseInt($(this).find('span').html())) {
				$(this).find('input').addClass("error");
			    window.setTimeout(function() { $('.page form input').removeClass("error"); }, 500);
			    return false;
			  }
		});
	});

})(jQuery.noConflict());
