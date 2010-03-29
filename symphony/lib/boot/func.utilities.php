<?php

	/***
	
	Method: redirect
	Description: redirects the browser to a specified location. Safer than using a direct header() call
	Param: $url - location to redirect to
	
	***/		
   	function redirect ($url){
		
		$url = str_replace('Location:', NULL, $url); //Just make sure.
		
		if(headers_sent($filename, $line)){
			print "<h1>Error: Cannot redirect to <a href=\"$url\">$url</a></h1><p>Output has already started in $filename on line $line</p>";
			exit();
		}
		
		header('Expires: Mon, 12 Dec 1982 06:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
        header("Location: $url");
        exit();	
    }

	function array_union_simple($xx, $yy){
		return                   
	        array_merge(
	            array_intersect($xx, $yy),
	            array_diff($xx, $yy),
	            array_diff($yy, $xx)
	        );
	}

	function getcwd_safe(){
		return str_replace('\\', '/', getcwd());
	}
	
	function define_safe($name, $val){
		if(!defined($name)) define($name, $val);
	}
	
	function getCurrentPage($page = NULL) {
		if (is_null($page) && isset($_GET['symphony-page'])){
			$page = $_GET['symphony-page'];
		}
		
		return (strlen(trim($page, '/')) > 0 ? '/' . trim($page, '/') . '/' : NULL);
	}
	
	function precision_timer($action = 'start', $start_time = NULL){
		$currtime = microtime(true);
		
		if($action == 'stop')
			return $currtime - $start_time;
		
		return $currtime;
	}
	
	## 'sys_get_temp_dir' doesnt exist in PHP 5.2 or lower.
	## minghong at gmail dot com
	## http://au2.php.net/sys_get_temp_dir
	if (!function_exists('sys_get_temp_dir')){
		function sys_get_temp_dir(){
			
			## Try to get from environment variable
			if(!empty($_ENV['TMP'])): return realpath($_ENV['TMP']);
			elseif(!empty($_ENV['TMPDIR'])): return realpath($_ENV['TMPDIR']);
			elseif(!empty($_ENV['TEMP'])): return realpath($_ENV['TEMP']);

			## Try creating a temporary file instead
			else:
		
				$temp_file = tempnam(md5(uniqid(rand(), TRUE)), NULL);
			
				if(!$temp_file) return FALSE;

				$temp_dir = realpath(dirname($temp_file));
				unlink($temp_file);
				return $temp_dir;
		
			endif;
		
		}
	}
	
	// Convert php.ini size format to bytes
	function ini_size_to_bytes($val) {
	    $val = trim($val);
	    $last = strtolower($val[strlen($val)-1]);
	    switch($last) {
	        // The 'G' modifier is available since PHP 5.1.0
	        case 'g':
	            $val *= 1024;
	        case 'm':
	            $val *= 1024;
	        case 'k':
	            $val *= 1024;
	    }

	    return $val;
	}
