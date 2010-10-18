<?php

	header('Expires: Mon, 12 Dec 1982 06:14:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');

	function __errorHandler($errno=NULL, $errstr, $errfile=NULL, $errline=NULL, $errcontext=NULL){
		return;
	}

	if(!defined('PHP_VERSION_ID')){
    	$version = PHP_VERSION;
    	define('PHP_VERSION_ID', ($version{0} * 10000 + $version{2} * 100 + $version{4}));
	}

	if (PHP_VERSION_ID >= 50300){
	    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
	} 
	else{
	    error_reporting(E_ALL ^ E_NOTICE);
	}
	
	set_error_handler('__errorHandler');

	define('kVERSION', '2.1.2');
	define('kINSTALL_ASSET_LOCATION', './symphony/assets/installer');	
	define('kINSTALL_FILENAME', basename(__FILE__));
	
	## Show PHP Info
	if(isset($_REQUEST['info'])){
		phpinfo(); 
		exit();
	}
	
	function setLanguage() {
		require_once('symphony/lib/toolkit/class.lang.php');
		$lang = NULL;

		if(!empty($_REQUEST['lang'])){
			$l = preg_replace('/[^a-zA-Z\-]/', NULL, $_REQUEST['lang']);
			if(file_exists("./symphony/lib/lang/lang.{$l}.php")) $lang = $l;
		}

		if($lang === NULL){
			foreach(Lang::getBrowserLanguages() as $l){
				if(file_exists("./symphony/lib/lang/lang.{$l}.php")) $lang = $l;
				break;
			}
		}

		## none of browser accepted languages is available, get first available
		if(is_null($lang)){
			
			## default to English
			$lang = 'en';
			
			if(!file_exists('./symphony/lib/lang/lang.en.php')){
				$l = Lang::getAvailableLanguages();
				if(is_array($l) && count($l) > 0) $lang = $l[0];
			}
		}

		if(is_null($lang)){ 
			return NULL;
		}

		try{
			Lang::load('./symphony/lib/lang/lang.%s.php', $lang);
		}
		catch(Exception $s){
			return NULL;
		}

		define('__LANG__', $lang);
		return $lang;
	}

	
	/***********************
	         TESTS
	************************/

	// Check for PHP 5.2+

	if(version_compare(phpversion(), '5.2', '<=')){

		$code = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Outstanding Requirements</title>
		<link rel="stylesheet" type="text/css" href="'.kINSTALL_ASSET_LOCATION.'/main.css"/>
		<script type="text/javascript" src="'.kINSTALL_ASSET_LOCATION.'/main.js"></script>
	</head>
		<body>
			<h1>Install Symphony <em>Version '.kVERSION.'</em></h1>
			<h2>Outstanding Requirements</h2>
			<p>Symphony needs the following requirements satisfied before installation can proceed.</p>

			<dl>
				<dt><abbr title="PHP: Hypertext Pre-processor">PHP</abbr> 5.2 or above</dt>
				<dd>Symphony needs a recent version of <abbr title="PHP: Hypertext Pre-processor">PHP</abbr>.</dd>
			</dl>

		</body>

</html>';

		die($code);

	}

	// Check and set language
	if(setLanguage() === NULL){

		$code = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Outstanding Requirements</title>
		<link rel="stylesheet" type="text/css" href="'.kINSTALL_ASSET_LOCATION.'/main.css"/>
		<script type="text/javascript" src="'.kINSTALL_ASSET_LOCATION.'/main.js"></script>
	</head>
		<body>
			<h1>Install Symphony <em>Version '.kVERSION.'</em></h1>
			<h2>Outstanding Requirements</h2>
			<p>Symphony needs at least one language file to be present before installation can proceed.</p>

		</body>

</html>';

		die($code);

	}

	// Make sure the install.sql file exists
	if(!file_exists('install.sql') || !is_readable('install.sql')){

		$code = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>'.__('Missing File').'</title>
		<link rel="stylesheet" type="text/css" href="'.kINSTALL_ASSET_LOCATION.'/main.css"/>
		<script type="text/javascript" src="'.kINSTALL_ASSET_LOCATION.'/main.js"></script>
	</head>
		<body>
			<h1>'.__('Install Symphony <em>Version %s</em>', array(kVERSION)).'</h1>
			<h2>'.__('Missing File').'</h2>
			<p>'.__('It appears that <code>install.sql</code> is either missing or not readable. This is required to populate the database and must be uploaded before installation can commence. Ensure that <code>PHP</code> has read permissions.').'</p>

		</body>

</html>';

		die($code);

	}

	// Check if Symphony is already installed
	if(file_exists('manifest/config.php')){

		$code = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>'.__('Existing Installation').'</title>
		<link rel="stylesheet" type="text/css" href="'.kINSTALL_ASSET_LOCATION.'/main.css"/>
		<script type="text/javascript" src="'.kINSTALL_ASSET_LOCATION.'/main.js"></script>
	</head>
		<body>
			<h1>'.__('Install Symphony <em>Version %s</em>', array(kVERSION)).'</h1>
			<h2>'.__('Existing Installation').'</h2>
			<p>'.__('It appears that Symphony has already been installed at this location.').'</p>

		</body>

</html>';

		die($code);

	}
		
	/////////////////////////
	
	function getDynamicConfiguration(){
	
		$conf = array();
	
		$conf['admin']['max_upload_size'] = '5242880';
		$conf['symphony']['pagination_maximum_rows'] = '17';
		$conf['symphony']['allow_page_subscription'] = '1';
		$conf['symphony']['lang'] = 'en';
		$conf['symphony']['version'] = '2.1.1';
		$conf['symphony']['pages_table_nest_children'] = 'yes';
		$conf['log']['archive'] = '1';
		$conf['log']['maxsize'] = '102400';
		$conf['general']['sitename'] = 'Symphony CMS';
		$conf['image']['cache'] = '1';
		$conf['image']['quality'] = '90';
		$conf['database']['driver'] = 'mysql';
		$conf['database']['character_set'] = 'utf8';
		$conf['database']['character_encoding'] = 'utf8';
		$conf['database']['runtime_character_set_alter'] = '1';
		$conf['database']['query_caching'] = 'default';
		$conf['public']['display_event_xml_in_source'] = 'yes';
		$conf['region']['time_format'] = 'H:i';
		$conf['region']['date_format'] = 'd F Y';
		$conf['maintenance_mode']['enabled'] = 'no';
	
		return $conf;
	
	}	
	
	function getTableSchema(){
		return file_get_contents('install.sql');
	}

	function getWorkspaceData(){
		return file_get_contents('workspace/install.sql');
	}
		
	define('INSTALL_REQUIREMENTS_PASSED', true);
	include_once('./symphony/lib/toolkit/include.install.php');

