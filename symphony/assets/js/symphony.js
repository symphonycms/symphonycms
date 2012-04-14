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

/*-----------------------------------------------------------------------*/

	// Add data to Symphony context
	function addContext(group, values) {

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
	};

	// Get data from Symphony context
	function getContext(group) {

		// Return full context, if no group is set
		if(!group) {
			return Storage;
		}

		// Return false if group does not exist in Storage
		if(typeof Storage.Context[group] === undefined) {
			return false;
		}

		// Default: Return context group
		return Storage.Context[group];
	};

	// Register language string
	function addStrings(strings) {
		var temp = {},
			namespace = (Symphony.Context.get('env') ? Symphony.Context.get('env')['page-namespace'] : '');

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
		if(Symphony.Context.get('lang') === 'en') {
			$.extend(true, Storage.Dictionary, temp);
		}

		// Translate strings and defer merging objects until translate() has returned
		else {
			translate(temp);
		}
	};	
	
	// Get localised string
	function getString(string, inserts) {

		// Get translated string
		var translatedString,
			namespace = (Symphony.Context.get('env') ? Symphony.Context.get('env')['page-namespace'] : '');

		if($.type(namespace) === 'string' && $.trim(namespace) !== '' && Storage.Dictionary[namespace] !== undefined) {
			translatedString = Storage.Dictionary[namespace][string];
		} else {
			translatedString = Storage.Dictionary[string];
		}

		// Return string if it cannot be found in the dictionary
		if(translatedString !== false) {
			string = translatedString;
		}

		// Insert variables
		if(inserts !== undefined && inserts !== null) {
			string = replaceVariables(string, inserts);
		}

		// Return translated string
		return string;
	};
	
	// Replace variables
	function replaceVariables(string, inserts) {
		$.each(inserts, function(index, value) {
			string = string.replace('{$' + index + '}', value);
		});
		return string;
	};

	// Get localised strings
	function translate(strings) {
		$.ajax({
			async: false,
			type: 'GET',
			url: Symphony.Context.get('root') + '/symphony/ajax/translate/',
			data: { 'strings': strings },
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

	// Set system message
	function postMessage(message, type) {
		$('header div.notifier').trigger('attach.notify', [message, type]);
	};
	
	// Remove system message
	function clearMessage(type) {
		$('header p.notice').filter('.' + type).first().trigger('detach.notify');
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
			 *
			 * @example
		
					Symphony.Context.add(group, values);
			 */
			add: addContext,

			/**
			 * Get data from the Context object
			 *
			 * @param {String} group
			 *  Name of the group to be returned
			 *
			 * @example
		
					Symphony.Context.get(group);
			 */
			get: getContext
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
			 *
			 * @example
		
					Symphony.Language.add(strings);
			 */
			add: addStrings,

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
			 *
			 * @example
		
					Symphony.Language.get(string);
			 */
			get: getString
		},
		
		/**
		 * The message object handles system messages that should be displayed on the fly.
		 * It offers a post and a clear function to set and remove messages. Absolute dates
		 * and times will be replaced by a representation relative to the user's system time.
		 *
		 * @class
		 * @private
		 */
		Message: {

			/**
			 * Post system message
			 *
			 * @param {String} message
			 *  Message to be shown
			 * @param {String} type
			 *  Message type to be used as class name
			 *
			 * @example
		
					Symphony.Language.get(message, type);
			 */
			post: postMessage,

			/**
			 * Clear last message of a type
			 *
			 * @param {String} type
			 *  Message type
			 *
			 * @example
		
					Symphony.Language.get(type);
			 */
			clear: clearMessage
		},
		
		/**
		 * A collection of properties that represent the presence of
		 * different browser features and also contains the test results
		 * from jQuery.support.
		 *
		 * @class
		 *
		 * @example
	
				Symphony.Support.localStorage;
		 */
		Support: Storage.Support
	};
}(jQuery.noConflict()));
