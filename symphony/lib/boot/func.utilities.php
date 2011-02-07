<?php

	/**
	 * @package boot
	 */

	/**
	 * Redirects the browser to a specified location. Safer than using a
	 * direct header() call
	 *
	 *	@param string $url
	 */
	function redirect ($url){
		// Just make sure.
		$url = str_replace('Location:', null, $url);

		if(headers_sent($filename, $line)){
			echo "<h1>Error: Cannot redirect to <a href=\"$url\">$url</a></h1><p>Output has already started in $filename on line $line</p>";
			exit;
		}

		header('Expires: Mon, 12 Dec 1982 06:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header("Location: $url");
		exit;
	}

	/**
	 * Returns the current working directory, replacing any \
	 *	with /. Use for Windows compatibility.
	 *
	 *	@return string
	 */
	function getcwd_safe(){
		return str_replace('\\', '/', getcwd());
	}

	/**
	 * Checks that a constant has not been defined before defining
	 * it. If the constant is already defined, this function will do
	 * nothing, otherwise, it will set the constant
	 *
	 * @param string $name
	 *	The name of the constant to set
	 * @param string $value
	 *	The value of the desired constant
	 */
	function define_safe($name, $val){
		if(!defined($name)) define($name, $val);
	}

	/**
	 * Returns the current URL string from within the Administration
	 * context. It omits the Symphony directory from the current URL.
	 *
	 *	@return string
	 */
	function getCurrentPage() {
		return isset($_GET['symphony-page']) ? '/' . trim($_GET['symphony-page'], '/') . '/' : null;
	}

	/**
	 * Used as a basic stopwatch for profiling. The default `$action`
	 * starts the timer. Setting `$action` to 'stop' and passing the
	 * start time returns the difference between now and that time.
	 *
	 *	@param string $action (optional)
	 *	@param integer $start_time (optional)
	 *	@return integer
	 */
	function precision_timer($action = 'start', $start_time = null){
		$currtime = microtime(true);

		if($action == 'stop')
			return $currtime - $start_time;

		return $currtime;
	}

	/**
	 * Convert php.ini size format to bytes
	 *
	 *	@param string $val (optional)
	 *	@return integer
	 */
	function ini_size_to_bytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);

		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}
