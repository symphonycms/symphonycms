<?php
	
	/**
	 * @package boot
	 */
	
	if (!function_exists('http_build_url')) {
		/**
		 * Replace every part of the first URL when there's one of the second URL.
		 * @var integer
		 */
		define('HTTP_URL_REPLACE', 1);
		
		/**
		 * Join relative paths.
		 * @var integer
		 */
		define('HTTP_URL_JOIN_PATH', 2);
		
		/**
		 * Join query strings.
		 * @var integer
		 */
		define('HTTP_URL_JOIN_QUERY', 4);
		
		/**
		 * Strip any user authentication information.
		 * @var integer
		 */
		define('HTTP_URL_STRIP_USER', 8);
		
		/**
		 * Strip any password authentication information.
		 * @var integer
		 */
		define('HTTP_URL_STRIP_PASS', 16);
		
		/**
		 * Strip any authentication information.
		 * @var integer
		 */
		define('HTTP_URL_STRIP_AUTH', 32);
		
		/**
		 * Strip explicit port numbers
		 * @var integer
		 */
		define('HTTP_URL_STRIP_PORT', 64);
		
		/**
		 * Strip complete path.
		 * @var integer
		 */
		define('HTTP_URL_STRIP_PATH', 128);
		
		/**
		 * Strip query string.
		 * @var integer
		 */
		define('HTTP_URL_STRIP_QUERY', 256);
		
		/**
		 * Strip any fragments (#identifier).
		 * @var integer
		 */
		define('HTTP_URL_STRIP_FRAGMENT', 512);
		
		/**
		 * Strip anything but scheme and host.
		 * @var integer
		 */
		define('HTTP_URL_STRIP_ALL', 1024);
		
		/**
		* The parts of the second URL will be merged into the first according to the flags argument. 
		*
		* @param	mixed	(Part(s) of) an URL in form of a string or associative array like parse_url() returns
		* @param	mixed	Same as the first argument
		* @param	int		A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
		* @param	array	If set, it will be filled with the parts of the composed url like parse_url() would return 
		*/
		function http_build_url($url, $parts = array(), $flags = HTTP_URL_REPLACE, &$new_url = false) {
			$keys = array('user','pass','port','path','query','fragment');
			
			// HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
			if ($flags & HTTP_URL_STRIP_ALL) {
				$flags |= HTTP_URL_STRIP_USER;
				$flags |= HTTP_URL_STRIP_PASS;
				$flags |= HTTP_URL_STRIP_PORT;
				$flags |= HTTP_URL_STRIP_PATH;
				$flags |= HTTP_URL_STRIP_QUERY;
				$flags |= HTTP_URL_STRIP_FRAGMENT;
			}
			
			// HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
			else if ($flags & HTTP_URL_STRIP_AUTH) {
				$flags |= HTTP_URL_STRIP_USER;
				$flags |= HTTP_URL_STRIP_PASS;
			}
			
			// Parse the original URL
			$parse_url = parse_url($url);
			
			// Scheme and Host are always replaced
			if (isset($parts['scheme'])) {
				$parse_url['scheme'] = $parts['scheme'];
			}
			
			if (isset($parts['host'])) {
				$parse_url['host'] = $parts['host'];
			}
			
			// (If applicable) Replace the original URL with it's new parts
			if ($flags & HTTP_URL_REPLACE) {
				foreach ($keys as $key) {
					if (isset($parts[$key])) {
						$parse_url[$key] = $parts[$key];
					}
				}
			}
			
			else {
				// Join the original URL path with the new path
				if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
					if (isset($parse_url['path'])) {
						$parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
					}
					
					else {
						$parse_url['path'] = $parts['path'];
					}
				}
				
				// Join the original query string with the new query string
				if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
					if (isset($parse_url['query'])) {
						$parse_url['query'] .= '&' . $parts['query'];
					}
					
					else {
						$parse_url['query'] = $parts['query'];
					}
				}
			}
			
			// Strips all the applicable sections of the URL
			// Note: Scheme and Host are never stripped
			foreach ($keys as $key) {
				if ($flags & (int)constant('HTTP_URL_STRIP_' . strtoupper($key))) {
					unset($parse_url[$key]);
				}
			}
			
			
			$new_url = $parse_url;
			
			return 
				 (
				 	(isset($parse_url['scheme']))
				 		? $parse_url['scheme'] . '://'
				 		: null
				 )
				. (
					(isset($parse_url['user']))
						? $parse_url['user'] . (
							(isset($parse_url['pass']))
								? ':' . $parse_url['pass']
								: null
						) . '@'
						: null
				)
				. (
					(isset($parse_url['host']))
						? $parse_url['host']
						: null
				)
				. (
					(isset($parse_url['port']))
						? ':' . $parse_url['port']
						: null
				)
				. (
					(isset($parse_url['path']))
						? $parse_url['path']
						: null
				)
				. (
					(isset($parse_url['query']))
						? '?' . $parse_url['query']
						: null
				)
				. (
					(isset($parse_url['fragment']))
						? '#' . $parse_url['fragment']
						: null
				);
		}
	}
	
?>