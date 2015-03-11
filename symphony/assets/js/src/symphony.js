/*!
 * Symphony 2.6.x, http://getsymphony.com, MIT license
 */

/**
 * @package assets
 */

/**
 * The Symphony object provides language, message and context management.
 *
 * @class
 */
var Symphony = (function($, crossroads) {

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
	}

	// Get localised strings
	function translate(strings) {
		var namespace = $.trim(Symphony.Context.get('env')['page-namespace']),
			data = {
				'strings': strings
			};

		// Validate and set namespace
		if($.type(namespace) === 'string' && namespace !== '') {
			data.namespace = namespace;
		}

		// Request translations
		$.ajax({
			async: false,
			type: 'GET',
			url: Symphony.Context.get('symphony') + '/ajax/translate/',
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
	}

	// request animation frame
	var raf = window.requestAnimationFrame || window.mozRequestAnimationFrame ||  
		window.webkitRequestAnimationFrame || window.msRequestAnimationFrame ||
		window.oRequestAnimationFrame || function (f) { return window.setTimeout(f, 16/1000) };
	var craf = window.cancelAnimationFrame || window.webkitCancelRequestAnimationFrame ||
		window.mozCancelRequestAnimationFrame || window.oCancelRequestAnimationFrame ||
		window.msCancelRequestAnimationFrame  || function (t) { window.clearTimeout(t); };
	

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
		 * Symphony backend view using Crossroads
		 *
		 * @since Symphony 2.4
		 */
		View: {

			/**
			 * Add function to view
			 *
			 * @param {String} pattern
			 *  Expression to match the view, using the Symphony URL as base
			 * @param {Function} handler
			 *  Function that should be applied to a view
			 * @param {Integer} priority
			 *  Priority of the function
			 * @param {Boolean} greedy
			 *  If set to `false`, only executes the first matched view, defaults to `true`
			 * @return {Route}
			 *  Returns a route object
			 */
			add: function addRoute(pattern, handler, priority, greedy) {
				var route;

				pattern = Symphony.Context.get('path') + pattern;
				route = crossroads.addRoute(pattern, handler, priority);

				if(greedy !== false) {
					route.greedy = true;
				}

				return route;
			},

			/**
			 * Render given URL, defaults to the current backend URL
			 *
			 * @param {String} url
			 *  The URL of the view that should be rendered, optional
			 * @param {Boolean} greedy
			 *  Determines, if only the first or all matching views are rendered,
			 *  defaults to `true (all)
			 */
			render: function renderRoute(url, greedy) {

				if(!url) {

					url = Symphony.Context.get('path') + Symphony.Context.get('route');
				}

				if(greedy === false) {
					crossroads.greedyEnabled = false;
				}

				crossroads.parse(url);
			}

		},

		/**
		 * Storage for the main Symphony elements`.
		 * Symphony automatically adds all main UI areas.
		 *
		 * @since Symphony 2.4
		 */
		Elements: {
			window: null,
			html: null,
			body: null,
			wrapper: null,
			header: null,
			nav: null,
			session: null,
			context: null,
			contents: null
		},

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

				// Add multiple groups
				if(!group && $.type(values) === 'object') {
					$.extend(Storage.Context, values);
				}

				// Add to existing group
				else if(Storage.Context[group] && $.type(values) !== 'string') {
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
		 * A namespace for core interface components
		 *
		 * @since Symphony 2.6
		 */
		Interface: {},

		/**
		 * A namespace for extension to store global functions
		 *
		 * @since Symphony 2.3
		 */
		Extensions: {},

		/**
		 * Helper functions
		 *
		 * @since Symphony 2.4
		 */
		Utilities: {

			/**
			 * Get a jQuery object of all elements within the current viewport
			 *
			 * @since Symphony 2.4
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
					else if (visibles.length > 0) {
						return false;
					}
				});

				return visibles;
			},

			/**
			 * Returns the XSRF token for the backend
			 *
			 * @since Symphony 2.4
			 * @param boolean $serialised
			 *  If passed as true, this function will return the string as a serialised
			 *  form elements, ie. field=value. If omitted, or false, this function
			 *  will just return the XSRF token.
			 */
			getXSRF: function(serialised) {
				var xsrf = Symphony.Elements.contents.find('input[name=xsrf]').val();

				if(serialised === true) {
					return 'xsrf=' + encodeURIComponent(xsrf);
				}
				else {
					return xsrf;
				}
			},

			/**
			 * Cross browser wrapper around requestFrameAnimation
			 *
			 * @since Symphony 2.5
			 * @param function $func
			 *  The callback to schedule for frame animation
			 */
			requestAnimationFrame: function (func) {
				return raf.call(window, func);
			},

			/**
			 * Cross browser wrapper around cancelAnimationFrame
			 *
			 * @since Symphony 2.5
			 * @param Integer $t
			 *  The request id
			 */
			cancelAnimationFrame: function (t) {
				return craf.call(window, t);
			}
		}
	};
}(window.jQuery, window.crossroads));
