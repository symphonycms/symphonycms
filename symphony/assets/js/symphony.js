/**
 * @package assets
 */

/**
 * The Symphony object provides language, message and context management.
 *
 * @class
 */
var Symphony = (function($) {

	// Internal Symphony storage
	var Storage = {
		Context: {},
		Dictionary: {},
		Support: {}
	};

/*-------------------------------------------------------------------------
	Functions
-------------------------------------------------------------------------*/

	// Replace variables in string
	function replaceVariables(string, inserts) {
		if($.type(string) === 'string' && $.type(inserts) === 'object') {
			$.each(inserts, function(index, value) {
				string = string.replace('{$' + index + '}', value);
			});
		}
		return string;
	};

	// Get localised strings
	function translate(strings) {
		var namespace = $.trim(Symphony.Context.get('env')['page-namespace']),
			data = {
				'strings': strings
			};

		// Validate and set namespace
		if($.type(namespace) === 'string' && namespace !== '') {
			data['namespace'] = namespace;
		}

		// Request translations
		$.ajax({
			async: false,
			type: 'GET',
			url: Symphony.Context.get('root') + '/symphony/ajax/translate/',
			data: data,
			dataType: 'json',

			// Add localised strings
			success: function(result) {
				$.extend(true, Storage.Dictionary, result);
			},

			// Use English strings on error
			error: function(jqXHR, textStatus, errorThrown) {
				$.extend(true, Storage.Dictionary, strings);
			}
		});
	};

/*-----------------------------------------------------------------------*/

	// Set browser support information
	try {
		Storage.Support.localStorage = !!localStorage.getItem;
	} catch(e) {
		Storage.Support.localStorage = false;
	}

	// Deep copy jQuery.support
	$.extend(true, Storage.Support, $.support);

/*-------------------------------------------------------------------------
	Symphony API
-------------------------------------------------------------------------*/

	return {

		/**
		 * The Context object contains general information about the system,
		 * the backend, the current user. It includes an add and a get function.
		 * This is a private object and can only be accessed via add and get.
		 *
		 * @class
		 */
	 	Context: {

	 		/**
			 * Add data to the Context object
			 *
			 * @param {String} group
			 *  Name of the data group
			 * @param {String|Object} values
			 *  Object or string to be stored
			 */
			add: function addContext(group, values) {

				// Extend existing group
				if(Storage.Context[group] && $.type(values) !== 'string') {
					$.extend(Storage.Context[group], values);
				}

				// Add new group
				else {
					Storage.Context[group] = values;
				}

				// Always return
				return true;
			},

			/**
			 * Get data from the Context object
			 *
			 * @param {String} group
			 *  Name of the group to be returned
			 */
			get: function getContext(group) {

				// Return full context, if no group is set
				if(!group) {
					return Storage.Context;
				}

				// Return false if group does not exist in Storage
				if(typeof Storage.Context[group] === undefined) {
					return false;
				}

				// Default: Return context group
				return Storage.Context[group];
			}
		},

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
		Language: {

			/**
			 * Add strings to the Dictionary
			 *
			 * @param {Object} strings
			 *  Object with English string as key, value should be false
			 */
			add: function addStrings(strings) {

				// English system
				if(Symphony.Context.get('lang') === 'en') {
					$.extend(true, Storage.Dictionary, strings);
				}

				// Localised system
				else {

					// Check if strings have already been translated
					$.each(strings, function checkStrings(index, key) {
						if(key in Storage.Dictionary) {
							delete strings[key];
						}
					});

					// Translate strings
					if(!$.isEmptyObject(strings)) {
						translate(strings);
					}
				}
			},

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
			get: function getString(string, inserts) {
				var translation = Storage.Dictionary[string];

				// Validate and set translation
				if($.type(translation) === 'string') {
					string = translation;
				}

				// Insert variables
				string = replaceVariables(string, inserts);

				// Return translated string
				return string;
			}
		},

		/**
		 * A collection of properties that represent the presence of
		 * different browser features and also contains the test results
		 * from jQuery.support.
		 *
		 * @class
		 */
		Support: Storage.Support,

		/**
		 * A namespace for extension to store global functions
		 *
		 * @since Symphony 2.3
		 */
		Extensions: {},

		/**
		 *
		 */
		Utilities: {

			/**
			 *
			 */
			inSight: function inSight(elements) {
				var windowHeight = window.innerHeight,
					visibles = $(); 

				elements.each(function() {
					var context = this.getBoundingClientRect();

					if(
						(context.top >= 0 && context.top <= windowHeight) || // Element top in sight
						(context.bottom >= 0 && context.bottom <= windowHeight) || // Element bottom in sight
						(context.top <= 0 && context.bottom >= windowHeight) // Element overflowing viewport
					) {
						visibles = visibles.add(this);
					}
					else {
						return false;
					}
				});

				return visibles;
			}
		}
	};
}(window.jQuery));
