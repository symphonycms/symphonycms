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
		 * for the same string, by using page namespaces.
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
			 */
			this.add = function(strings) {
				var temp = {},
					namespace = Symphony.Context.get('env')['page-namespace'];

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
					$.extend(true, Dictionary, temp);
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
			 * @return {String}
			 *  Returns the translated string
			 */
			this.get = function(string, inserts) {

				// Get translated string
				var translatedString,
					namespace = Symphony.Context.get('env')['page-namespace'];

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
				// Load translations synchronously
				$.ajax({
					async: false,
					type: 'GET',
					url: Symphony.Context.get('root') + '/symphony/ajax/translate/',
					data: { 'strings': strings },
					dataType: 'json',
					success: function(result) {
						$.extend(true, Dictionary, result);
					},
					error: function(jqXHR, textStatus, errorThrown) {
						// Extend the existing dictionary since an error occurred
						$.extend(true, Dictionary, strings);
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

})(jQuery.noConflict());
