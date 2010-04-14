<?php

	if(!defined('INSTALL_REQUIREMENTS_PASSED') || !INSTALL_REQUIREMENTS_PASSED){
		die('<h1>Symphony Fatal Error</h1><p>This file cannot be accessed directly</p>');
	}

	$clean_path = $_SERVER["HTTP_HOST"] . dirname($_SERVER["PHP_SELF"]);
	$clean_path = rtrim($clean_path, '/\\');
	$clean_path = preg_replace('/\/{2,}/i', '/', $clean_path);

	define('_INSTALL_DOMAIN_', $clean_path); 
	define('_INSTALL_URL_', 'http://' . $clean_path);
	
	## If its not an update, we need to set a couple of important constants.
	define('__IN_SYMPHONY__', true);
	define('DOCROOT', './');
	
	require_once(DOCROOT . '/symphony/lib/boot/func.utilities.php');
	require_once(DOCROOT . '/symphony/lib/boot/defines.php');
	
	## Include some parts of the Symphony engine
	require_once(CORE . '/class.log.php');
	require_once(CORE . '/class.datetimeobj.php');	
	require_once(TOOLKIT . '/class.mysql.php');
	require_once(TOOLKIT . '/class.xmlelement.php');
	require_once(TOOLKIT . '/class.general.php');
	require_once(TOOLKIT . '/class.widget.php');

	define('CRLF', "\r\n");

	define('BAD_BROWSER', 0);
	define('MISSING_MYSQL', 3);
	define('MISSING_ZLIB', 5);
	define('MISSING_XSL', 6);
	define('MISSING_XML', 7);
	define('MISSING_PHP', 8);
	define('MISSING_MOD_REWRITE', 9);
	
	$header = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title><!-- TITLE --></title>
		<link rel="stylesheet" type="text/css" href="'.kINSTALL_ASSET_LOCATION.'/main.css"/>
		<script type="text/javascript" src="'.kINSTALL_ASSET_LOCATION.'/main.js"></script>
	</head>' . CRLF;

	define('kHEADER', $header);

	$footer = '
</html>';

	define('kFOOTER', $footer);


	
	$warnings = array(
	
		'no-symphony-dir' => __('No <code>/symphony</code> directory was found at this location. Please upload the contents of Symphony\'s install package here.'),
		'no-write-permission-workspace' => __('Symphony does not have write permission to the existing <code>/workspace</code> directory. Please modify permission settings on this directory and its contents to allow this, such as with a recursive <code>chmod -R</code> command.'),
		'no-write-permission-manifest' => __('Symphony does not have write permission to the <code>/manifest</code> directory. Please modify permission settings on this directory and its contents to allow this, such as with a recursive <code>chmod -R</code> command.'),
		'no-write-permission-root' => __('Symphony does not have write permission to the root directory. Please modify permission settings on this directory. This is necessary only if you are not including a workspace, and can be reverted once installation is complete.'),
		'no-write-permission-htaccess' => __('Symphony does not have write permission to the temporary <code>htaccess</code> file. Please modify permission settings on this file so it can be written to, and renamed.'),
		'no-write-permission-symphony' => __('Symphony does not have write permission to the <code>/symphony</code> directory. Please modify permission settings on this directory. This is necessary only during installation, and can be reverted once installation is complete.'),
		'existing-htaccess' => __('There appears to be an existing <code>.htaccess</code> file in the Symphony install location. To avoid name clashes, you will need to delete or rename this file.'),
		'existing-htaccess-symphony' => __('There appears to be an existing <code>.htaccess</code> file in the <code>/symphony</code> directory.'),
		'no-database-connection' => __('Symphony was unable to connect to the specified database. You may need to modify host or port settings.'),
		'database-incorrect-version' => __('Symphony requires <code>MySQL 4.1</code> or greater to work. This requirement must be met before installation can proceed.'),
		'database-table-clash' => __('The table prefix <code><!-- TABLE-PREFIX --></code> is already in use. Please choose a different prefix to use with Symphony.'),
		'user-password-mismatch' => __('The password and confirmation did not match. Please retype your password.'),
		'user-invalid-email' => __('This is not a valid email address. You must provide an email address since you will need it if you forget your password.'),
		'user-no-username' => __('You must enter a Username. This will be your Symphony login information.'),
		'user-no-password' => __('You must enter a Password. This will be your Symphony login information.'),
		'user-no-name' => __('You must enter your name.')
		
	);

	$notices = array(
		'existing-workspace' => __('An existing <code>/workspace</code> directory was found at this location. Symphony will use this workspace.')
	);

	$languages = array();
	foreach(array_keys(Lang::getAvailableLanguages()) as $lang){
		$languages[] = '<a href="?lang='.$lang.'">'.$lang.'</a>';
	}
	$languages = (count($languages) > 1 ? implode(', ', $languages) : '');
	

    function installResult(&$Page, &$install_log, $start){

        if(!defined('_INSTALL_ERRORS_')){
            
            $install_log->writeToLog("============================================", true);
            $install_log->writeToLog("INSTALLATION COMPLETED: Execution Time - ".max(1, time() - $start)." sec (" . date("d.m.y H:i:s") . ")", true);
            $install_log->writeToLog("============================================" . CRLF . CRLF . CRLF, true);
        
        }else{  
			          
            $install_log->pushToLog(_INSTALL_ERRORS_, E_ERROR, true);
            $install_log->writeToLog("============================================", true);
            $install_log->writeToLog("INSTALLATION ABORTED: Execution Time - ".max(1, time() - $start)." sec (" . date("d.m.y H:i:s") . ")", true);
            $install_log->writeToLog("============================================" . CRLF . CRLF . CRLF, true);

			$Page->setPage('failure');
        }                
        
    }

    function writeConfig($dest, $conf, $mode){

        $string  = "<?php\n";

		$string .= "\n\t\$settings = array(";
		foreach($conf['settings'] as $group => $data){
			$string .= "\r\n\r\n\r\n\t\t###### ".strtoupper($group)." ######";
			$string .= "\r\n\t\t'$group' => array(";
			foreach($data as $key => $value){
				$string .= "\r\n\t\t\t'$key' => ".(strlen($value) > 0 ? "'".addslashes($value)."'" : 'NULL').",";
			}
			$string .= "\r\n\t\t),";
			$string .= "\r\n\t\t########";
		}
		$string .= "\r\n\t);\n\n";

        return GeneralExtended::writeFile($dest . '/config.php', $string, $mode);

    }

    function fireSql(&$db, $data, &$error, $compatibility='NORMAL'){

		$compatibility = strtoupper($compatibility);

		if($compatibility == 'HIGH'){	
			$data = str_replace('ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci', NULL, $data);
			$data = str_replace('collate utf8_unicode_ci', NULL, $data);
		}

		## Silently attempt to change the storage engine. This prevents INNOdb errors.
		$db->query('SET storage_engine=MYISAM');
		
        $queries = preg_split('/;[\\r\\n]+/', $data, -1, PREG_SPLIT_NO_EMPTY);

        if(is_array($queries) && !empty($queries)){                                
            foreach($queries as $sql) {
                if(strlen(trim($sql)) > 0) $result = $db->query($sql);
                if(!$result){ 
					$err = $db->getLastError();
					$error = $err['num'] . ': ' . $err['msg'];
					return false;
				}
            }
        }
		
        return true;

    }

	if(!function_exists('timezone_identifiers_list')){
		function timezone_identifiers_list(){
			return array(
				'Africa/Abidjan', 'Africa/Accra', 'Africa/Addis_Ababa', 'Africa/Algiers', 'Africa/Asmera', 
				'Africa/Bamako', 'Africa/Bangui', 'Africa/Banjul', 'Africa/Bissau', 'Africa/Blantyre', 
				'Africa/Brazzaville', 'Africa/Bujumbura', 'Africa/Cairo', 'Africa/Casablanca', 'Africa/Ceuta', 
				'Africa/Conakry', 'Africa/Dakar', 'Africa/Dar_es_Salaam', 'Africa/Djibouti', 'Africa/Douala', 
				'Africa/El_Aaiun', 'Africa/Freetown', 'Africa/Gaborone', 'Africa/Harare', 'Africa/Johannesburg', 
				'Africa/Kampala', 'Africa/Khartoum', 'Africa/Kigali', 'Africa/Kinshasa', 'Africa/Lagos', 
				'Africa/Libreville', 'Africa/Lome', 'Africa/Luanda', 'Africa/Lubumbashi', 'Africa/Lusaka', 
				'Africa/Malabo', 'Africa/Maputo', 'Africa/Maseru', 'Africa/Mbabane', 'Africa/Mogadishu', 
				'Africa/Monrovia', 'Africa/Nairobi', 'Africa/Ndjamena', 'Africa/Niamey', 'Africa/Nouakchott',
			 	'Africa/Ouagadougou', 'Africa/Porto-Novo', 'Africa/Sao_Tome', 'Africa/Timbuktu', 'Africa/Tripoli', 
				'Africa/Tunis', 'Africa/Windhoek', 'America/Adak', 'America/Anchorage', 'America/Anguilla', 
				'America/Antigua', 'America/Araguaina', 'America/Argentina/Buenos_Aires', 'America/Argentina/Catamarca',
				'America/Argentina/ComodRivadavia', 'America/Argentina/Cordoba', 'America/Argentina/Jujuy',
				'America/Argentina/La_Rioja', 'America/Argentina/Mendoza', 'America/Argentina/Rio_Gallegos',
				'America/Argentina/San_Juan', 'America/Argentina/Tucuman', 'America/Argentina/Ushuaia', 'America/Aruba', 
				'America/Asuncion', 'America/Atikokan', 'America/Atka', 'America/Bahia', 'America/Barbados', 'America/Belem', 
				'America/Belize', 'America/Blanc-Sablon', 'America/Boa_Vista', 'America/Bogota', 'America/Boise', 
				'America/Buenos_Aires', 'America/Cambridge_Bay', 'America/Campo_Grande', 'America/Cancun', 'America/Caracas', 
				'America/Catamarca', 'America/Cayenne', 'America/Cayman', 'America/Chicago', 'America/Chihuahua', 
				'America/Coral_Harbour', 'America/Cordoba', 'America/Costa_Rica', 'America/Cuiaba', 'America/Curacao', 
				'America/Danmarkshavn', 'America/Dawson', 'America/Dawson_Creek', 'America/Denver', 'America/Detroit', 
				'America/Dominica', 'America/Edmonton', 'America/Eirunepe', 'America/El_Salvador', 'America/Ensenada', 
				'America/Fort_Wayne', 'America/Fortaleza', 'America/Glace_Bay', 'America/Godthab', 'America/Goose_Bay', 
				'America/Grand_Turk', 'America/Grenada', 'America/Guadeloupe', 'America/Guatemala', 'America/Guayaquil', 
				'America/Guyana', 'America/Halifax', 'America/Havana', 'America/Hermosillo', 'America/Indiana/Indianapolis', 
				'America/Indiana/Knox', 'America/Indiana/Marengo', 'America/Indiana/Petersburg', 'America/Indiana/Vevay', 
				'America/Indiana/Vincennes', 'America/Indianapolis', 'America/Inuvik', 'America/Iqaluit', 'America/Jamaica', 
				'America/Jujuy', 'America/Juneau', 'America/Kentucky/Louisville', 'America/Kentucky/Monticello', 
				'America/Knox_IN', 'America/La_Paz', 'America/Lima', 'America/Los_Angeles', 'America/Louisville', 
				'America/Maceio', 'America/Managua', 'America/Manaus', 'America/Martinique', 'America/Mazatlan', 
				'America/Mendoza', 'America/Menominee', 'America/Merida', 'America/Mexico_City', 'America/Miquelon', 
				'America/Moncton', 'America/Monterrey', 'America/Montevideo', 'America/Montreal', 'America/Montserrat', 
				'America/Nassau', 'America/New_York', 'America/Nipigon', 'America/Nome', 'America/Noronha', 
				'America/North_Dakota/Center', 'America/North_Dakota/New_Salem', 'America/Panama', 'America/Pangnirtung', 
				'America/Paramaribo', 'America/Phoenix', 'America/Port-au-Prince', 'America/Port_of_Spain', 'America/Porto_Acre', 
				'America/Porto_Velho', 'America/Puerto_Rico', 'America/Rainy_River', 'America/Rankin_Inlet', 'America/Recife', 
				'America/Regina', 'America/Rio_Branco', 'America/Rosario', 'America/Santiago', 'America/Santo_Domingo', 
				'America/Sao_Paulo', 'America/Scoresbysund', 'America/Shiprock', 'America/St_Johns', 'America/St_Kitts', 
				'America/St_Lucia', 'America/St_Thomas', 'America/St_Vincent', 'America/Swift_Current', 'America/Tegucigalpa', 
				'America/Thule', 'America/Thunder_Bay', 'America/Tijuana', 'America/Toronto', 'America/Tortola', 
				'America/Vancouver', 'America/Virgin', 'America/Whitehorse', 'America/Winnipeg', 'America/Yakutat', 
				'America/Yellowknife', 'Antarctica/Casey', 'Antarctica/Davis', 'Antarctica/DumontDUrville', 'Antarctica/Mawson', 
				'Antarctica/McMurdo', 'Antarctica/Palmer', 'Antarctica/Rothera', 'Antarctica/South_Pole', 'Antarctica/Syowa', 
				'Antarctica/Vostok', 'Arctic/Longyearbyen', 'Asia/Aden', 'Asia/Almaty', 'Asia/Amman', 'Asia/Anadyr', 'Asia/Aqtau', 
				'Asia/Aqtobe', 'Asia/Ashgabat', 'Asia/Ashkhabad', 'Asia/Baghdad', 'Asia/Bahrain', 'Asia/Baku', 'Asia/Bangkok', 
				'Asia/Beirut', 'Asia/Bishkek', 'Asia/Brunei', 'Asia/Calcutta', 'Asia/Choibalsan', 'Asia/Chongqing', 
				'Asia/Chungking', 'Asia/Colombo', 'Asia/Dacca', 'Asia/Damascus', 'Asia/Dhaka', 'Asia/Dili', 'Asia/Dubai', 
				'Asia/Dushanbe', 'Asia/Gaza', 'Asia/Harbin', 'Asia/Hong_Kong', 'Asia/Hovd', 'Asia/Irkutsk', 'Asia/Istanbul', 
				'Asia/Jakarta', 'Asia/Jayapura', 'Asia/Jerusalem', 'Asia/Kabul', 'Asia/Kamchatka', 'Asia/Karachi', 'Asia/Kashgar', 
				'Asia/Katmandu', 'Asia/Krasnoyarsk', 'Asia/Kuala_Lumpur', 'Asia/Kuching', 'Asia/Kuwait', 'Asia/Macao', 
				'Asia/Macau', 'Asia/Magadan', 'Asia/Makassar', 'Asia/Manila', 'Asia/Muscat', 'Asia/Nicosia', 'Asia/Novosibirsk', 
				'Asia/Omsk', 'Asia/Oral', 'Asia/Phnom_Penh', 'Asia/Pontianak', 'Asia/Pyongyang', 'Asia/Qatar', 'Asia/Qyzylorda', 
				'Asia/Rangoon', 'Asia/Riyadh', 'Asia/Saigon', 'Asia/Sakhalin', 'Asia/Samarkand', 'Asia/Seoul', 'Asia/Shanghai', 
				'Asia/Singapore', 'Asia/Taipei', 'Asia/Tashkent', 'Asia/Tbilisi', 'Asia/Tehran', 'Asia/Tel_Aviv', 'Asia/Thimbu', 
				'Asia/Thimphu', 'Asia/Tokyo', 'Asia/Ujung_Pandang', 'Asia/Ulaanbaatar', 'Asia/Ulan_Bator', 'Asia/Urumqi', 
				'Asia/Vientiane', 'Asia/Vladivostok', 'Asia/Yakutsk', 'Asia/Yekaterinburg', 'Asia/Yerevan', 'Atlantic/Azores', 
				'Atlantic/Bermuda', 'Atlantic/Canary', 'Atlantic/Cape_Verde', 'Atlantic/Faeroe', 'Atlantic/Jan_Mayen', 
				'Atlantic/Madeira', 'Atlantic/Reykjavik', 'Atlantic/South_Georgia', 'Atlantic/St_Helena', 'Atlantic/Stanley', 
				'Australia/ACT', 'Australia/Adelaide', 'Australia/Brisbane', 'Australia/Broken_Hill', 'Australia/Canberra', 
				'Australia/Currie', 'Australia/Darwin', 'Australia/Hobart', 'Australia/LHI', 'Australia/Lindeman', 
				'Australia/Lord_Howe', 'Australia/Melbourne', 'Australia/North', 'Australia/NSW', 'Australia/Perth', 
				'Australia/Queensland', 'Australia/South', 'Australia/Sydney', 'Australia/Tasmania', 'Australia/Victoria', 
				'Australia/West', 'Australia/Yancowinna', 'Brazil/Acre', 'Brazil/DeNoronha', 'Brazil/East', 'Brazil/West', 
				'Canada/Atlantic', 'Canada/Central', 'Canada/East-Saskatchewan', 'Canada/Eastern', 'Canada/Mountain', 
				'Canada/Newfoundland', 'Canada/Pacific', 'Canada/Saskatchewan', 'Canada/Yukon', 'CET', 'Chile/Continental', 
				'Chile/EasterIsland', 'CST6CDT', 'Cuba', 'EET', 'Egypt', 'Eire', 'EST', 'EST5EDT', 'Etc/GMT', 'Etc/GMT+0', 
				'Etc/GMT+1', 'Etc/GMT+10', 'Etc/GMT+11', 'Etc/GMT+12', 'Etc/GMT+2', 'Etc/GMT+3', 'Etc/GMT+4', 'Etc/GMT+5', 
				'Etc/GMT+6', 'Etc/GMT+7', 'Etc/GMT+8', 'Etc/GMT+9', 'Etc/GMT-0', 'Etc/GMT-1', 'Etc/GMT-10', 'Etc/GMT-11', 
				'Etc/GMT-12', 'Etc/GMT-13', 'Etc/GMT-14', 'Etc/GMT-2', 'Etc/GMT-3', 'Etc/GMT-4', 'Etc/GMT-5', 'Etc/GMT-6', 
				'Etc/GMT-7', 'Etc/GMT-8', 'Etc/GMT-9', 'Etc/GMT0', 'Etc/Greenwich', 'Etc/UCT', 'Etc/Universal', 'Etc/UTC', 
				'Etc/Zulu', 'Europe/Amsterdam', 'Europe/Andorra', 'Europe/Athens', 'Europe/Belfast', 'Europe/Belgrade', 
				'Europe/Berlin', 'Europe/Bratislava', 'Europe/Brussels', 'Europe/Bucharest', 'Europe/Budapest', 'Europe/Chisinau', 
				'Europe/Copenhagen', 'Europe/Dublin', 'Europe/Gibraltar', 'Europe/Guernsey', 'Europe/Helsinki', 
				'Europe/Isle_of_Man', 'Europe/Istanbul', 'Europe/Jersey', 'Europe/Kaliningrad', 'Europe/Kiev', 'Europe/Lisbon', 
				'Europe/Ljubljana', 'Europe/London', 'Europe/Luxembourg', 'Europe/Madrid', 'Europe/Malta', 'Europe/Mariehamn', 
				'Europe/Minsk', 'Europe/Monaco', 'Europe/Moscow', 'Europe/Nicosia', 'Europe/Oslo', 'Europe/Paris', 
				'Europe/Podgorica', 'Europe/Prague', 'Europe/Riga', 'Europe/Rome', 'Europe/Samara', 'Europe/San_Marino', 
				'Europe/Sarajevo', 'Europe/Simferopol', 'Europe/Skopje', 'Europe/Sofia', 'Europe/Stockholm', 'Europe/Tallinn', 
				'Europe/Tirane', 'Europe/Tiraspol', 'Europe/Uzhgorod', 'Europe/Vaduz', 'Europe/Vatican', 'Europe/Vienna', 
				'Europe/Vilnius', 'Europe/Volgograd', 'Europe/Warsaw', 'Europe/Zagreb', 'Europe/Zaporozhye', 'Europe/Zurich', 
				'Factory', 'GB', 'GB-Eire', 'GMT', 'GMT+0', 'GMT-0', 'GMT0', 'Greenwich', 'Hongkong', 'HST', 'Iceland', 
				'Indian/Antananarivo', 'Indian/Chagos', 'Indian/Christmas', 'Indian/Cocos', 'Indian/Comoro', 'Indian/Kerguelen', 
				'Indian/Mahe', 'Indian/Maldives', 'Indian/Mauritius', 'Indian/Mayotte', 'Indian/Reunion', 'Iran', 'Israel', 
				'Jamaica', 'Japan', 'Kwajalein', 'Libya', 'MET', 'Mexico/BajaNorte', 'Mexico/BajaSur', 'Mexico/General', 'MST', 
				'MST7MDT', 'Navajo', 'NZ', 'NZ-CHAT', 'Pacific/Apia', 'Pacific/Auckland', 'Pacific/Chatham', 'Pacific/Easter', 
				'Pacific/Efate', 'Pacific/Enderbury', 'Pacific/Fakaofo', 'Pacific/Fiji', 'Pacific/Funafuti', 'Pacific/Galapagos', 
				'Pacific/Gambier', 'Pacific/Guadalcanal', 'Pacific/Guam', 'Pacific/Honolulu', 'Pacific/Johnston', 
				'Pacific/Kiritimati', 'Pacific/Kosrae', 'Pacific/Kwajalein', 'Pacific/Majuro', 'Pacific/Marquesas', 
				'Pacific/Midway', 'Pacific/Nauru', 'Pacific/Niue', 'Pacific/Norfolk', 'Pacific/Noumea', 'Pacific/Pago_Pago', 
				'Pacific/Palau', 'Pacific/Pitcairn', 'Pacific/Ponape', 'Pacific/Port_Moresby', 'Pacific/Rarotonga', 
				'Pacific/Saipan', 'Pacific/Samoa', 'Pacific/Tahiti', 'Pacific/Tarawa', 'Pacific/Tongatapu', 'Pacific/Truk', 
				'Pacific/Wake', 'Pacific/Wallis', 'Pacific/Yap', 'Poland', 'Portugal', 'PRC', 'PST8PDT', 'ROC', 'ROK', 
				'Singapore', 'Turkey', 'UCT', 'Universal', 'US/Alaska', 'US/Aleutian', 'US/Arizona', 'US/Central', 
				'US/East-Indiana', 'US/Eastern', 'US/Hawaii', 'US/Indiana-Starke', 'US/Michigan', 'US/Mountain', 'US/Pacific', 
				'US/Pacific-New', 'US/Samoa', 'UTC', 'W-SU', 'WET', 'Zulu'
			);
		}
	}

	Class GeneralExtended extends General{
		
        public static function realiseDirectory($path, $mode){

            if(!empty($path)){
                
                if(@file_exists($path) && !@is_dir($path)){
                    return false;
                    
                }elseif(!@is_dir($path)){

			        @mkdir($path);
       
			        $oldmask = @umask(0);
			        @chmod($path, @intval($mode, 8));
			        @umask($oldmask);
        				    
				}             
            }
                
            return true;
            
        }

	    public static function checkRequirement($item, $type, $expected){

	 		switch($type){

			    case 'func':

			        $test = function_exists($item);
			        if($test != $expected) return false;
			        break;

			    case 'setting':
			        $test = ini_get($item);
			        if(strtolower($test) != strtolower($expected)) return false;
			        break;

			    case 'ext':
			        foreach(explode(':', $item) as $ext){
			            $test = extension_loaded($ext);         
			            if($test == $expected) return true;
			        }

					return false;
			        break;

			     case 'version':    
			        if(version_compare($item, $expected, '>=') != 1) return false;
			        break;       

			     case 'permission':
			        if(!is_writable($item)) return false;
			        break;

			     case 'remote':
			        $result = curler($item);
			        if(strpos(strtolower($result), 'error') !== false) return false;   
			        break;

			}

			return true;

	    }	
		
	}

    Class SymphonyLog extends Log{
        
		function SymphonyLog($path){
			$this->setLogPath($path);
				
			if(@file_exists($this->getLogPath())){
				$this->open();
				
			}else{
				$this->open('OVERRIDE');
				$this->writeToLog('Symphony Installer Log', true);
				$this->writeToLog('Opened: '. DateTimeObj::get('c'), true);
				$this->writeToLog('Version: '. kVERSION, true);
				$this->writeToLog('Domain: '._INSTALL_URL_, true);
				$this->writeToLog('--------------------------------------------', true);
			}			
		}		
	}

	Class Action{

		function requirements(&$Page){

			$missing = array();

			if(!GeneralExtended::checkRequirement(phpversion(), 'version', '5.2')){
				$Page->log->pushToLog('Requirement - PHP Version is not correct. '.phpversion().' detected.' , E_ERROR, true);
				$missing[] = MISSING_PHP;	
			}		

			if(!GeneralExtended::checkRequirement('mysql_connect', 'func', true)){
				$Page->log->pushToLog('Requirement - MySQL extension not present' , E_ERROR, true);
				$missing[] = MISSING_MYSQL;
			}
			
			if(!GeneralExtended::checkRequirement('zlib', 'ext', true)){
				$Page->log->pushToLog('Requirement - ZLib extension not present' , E_ERROR, true);
				$missing[] = MISSING_ZLIB;
			}

			if(!GeneralExtended::checkRequirement('xml:libxml', 'ext', true)){
				$Page->log->pushToLog('Requirement - No XML extension present' , E_ERROR, true);
				$missing[] = MISSING_XML;
			}

			if(!GeneralExtended::checkRequirement('xsl:xslt', 'ext', true) && !GeneralExtended::checkRequirement('domxml_xslt_stylesheet', 'func', true))	{
				$Page->log->pushToLog('Requirement - No XSL extension present' , E_ERROR, true);
				$missing[] = MISSING_XSL;
			}

			$Page->missing = $missing;

			return;

		}
	
		function install(&$Page, $fields){
			
			global $warnings;
			
			$database_connection_error = false;
			
			try{
				$db = new DBCMySQLProfiler;
				$db->connect($fields['database']['host'], 
							 $fields['database']['username'], 
							 $fields['database']['password'], 
							 $fields['database']['port']);

				$tables = $db->query("
						SHOW TABLES FROM `%s` LIKE '%s'
					",
					mysql_escape_string($fields['database']['name']),
					mysql_escape_string($fields['database']['prefix']) . '%'
				));

			}
			catch(DatabaseException $e){
				$database_connection_error = true;
			}
			
			$mysql_version = $db->query("SELECT VERSION() AS `version`");
			$mysql_version = ($mysql_version->valid()) ? $mysql_version->current()->version : null;
			
			## Invalid path
			if(!@is_dir(rtrim($fields['docroot'], '/') . '/symphony')){
				$Page->log->pushToLog("Configuration - Bad Document Root Specified: " . $fields['docroot'], E_NOTICE, true);
				define("kENVIRONMENT_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'no-symphony-dir');
			}
	
			## Existing .htaccess
			elseif(is_file(rtrim($fields['docroot'], '/') . '/.htaccess')){
				$Page->log->pushToLog("Configuration - Existing '.htaccess' file found: " . $fields['docroot'] . '/.htaccess', E_NOTICE, true);
				define("kENVIRONMENT_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'existing-htaccess');
			}

			## Cannot write to workspace
			elseif(is_dir(rtrim($fields['docroot'], '/') . '/workspace') && !is_writable(rtrim($fields['docroot'], '/') . '/workspace')){
				$Page->log->pushToLog("Configuration - Workspace folder not writable: " . $fields['docroot'] . '/workspace', E_NOTICE, true);
				define("kENVIRONMENT_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'no-write-permission-workspace');
			}

			## Cannot write to root folder.
			elseif(!is_writable(rtrim($fields['docroot'], '/'))){
				$Page->log->pushToLog("Configuration - Root folder not writable: " . $fields['docroot'], E_NOTICE, true);
				define("kENVIRONMENT_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'no-write-permission-root');
			}

			## Failed to establish database connection	
			elseif($database_connection_error){
				$Page->log->pushToLog("Configuration - Could not establish database connection", E_NOTICE, true);
				define("kDATABASE_CONNECTION_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'no-database-connection');
			}
			
			## Incorrect MySQL version
			elseif(version_compare($mysql_version, '4.1', '<')){
				$Page->log->pushToLog('Configuration - MySQL Version is not correct. '.$mysql_version.' detected.', E_NOTICE, true);
				define("kDATABASE_VERSION_WARNING", true);

				$warnings['database-incorrect-version'] = __('Symphony requires <code>MySQL 4.1</code> or greater to work, however version <code>%s</code> was detected. This requirement must be met before installation can proceed.', array($version));

				if(!defined("ERROR")) define("ERROR", 'database-incorrect-version');
			}

			## Failed to select database
			elseif(!$db->select($fields['database']['name'])){
				$Page->log->pushToLog("Configuration - Database '".$fields['database']['name']."' Not Found", E_NOTICE, true);	
				define("kDATABASE_CONNECTION_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'no-database-connection');
			}

			## Failed to establish connection	
			elseif(is_array($tables) && !empty($tables)){
				$Page->log->pushToLog("Configuration - Database table prefix clash with '".$fields['database']['name']."'", E_NOTICE, true);	
				define("kDATABASE_PREFIX_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'database-table-clash');
			}

			## Username Not Entered	
			elseif(trim($fields['user']['username']) == ''){
				$Page->log->pushToLog("Configuration - No username entered.", E_NOTICE, true);	
				define("kUSER_USERNAME_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'user-no-username');
			}

			## Password Not Entered	
			elseif(trim($fields['user']['password']) == ''){
				$Page->log->pushToLog("Configuration - No password entered.", E_NOTICE, true);	
				define("kUSER_PASSWORD_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'user-no-password');
			}

			## Password mismatch	
			elseif($fields['user']['password'] != $fields['user']['confirm-password']){
				$Page->log->pushToLog("Configuration - Passwords did not match.", E_NOTICE, true);	
				define("kUSER_PASSWORD_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'user-password-mismatch');
			}
			
			## No Name entered
			elseif(trim($fields['user']['firstname']) == '' || trim($fields['user']['lastname']) == ''){
				$Page->log->pushToLog("Configuration - Did not enter First and Last names.", E_NOTICE, true);	
				define("kUSER_NAME_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'user-no-name');
			}
					
			## Invalid Email	
			elseif(!preg_match('/^\w(?:\.?[\w%+-]+)*@\w(?:[\w-]*\.)+?[a-z]{2,}$/i', $fields['user']['email'])){
				$Page->log->pushToLog("Configuration - Invalid email address supplied.", E_NOTICE, true);	
				define("kUSER_EMAIL_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'user-invalid-email');
			}
			
			## Otherwise there are no error, proceed with installation
			else{

				$config = $fields;
				
				$kDOCROOT = rtrim($config['docroot'], '/');
				
		        $database = array_map("trim", $fields['database']);

		        if(!isset($database['host']) || $database['host'] == "") $database['host'] = "localhost";    
		        if(!isset($database['port']) || $database['port'] == "") $database['port'] = "3306";
		        if(!isset($database['prefix']) || $database['prefix'] == "") $database['prefix'] = "sym_";

		        $install_log = $Page->log;

		        $start = time(); 

	            $install_log->writeToLog(CRLF . '============================================', true);
	            $install_log->writeToLog('INSTALLATION PROCESS STARTED (' . DateTimeObj::get('c') . ')', true);
	            $install_log->writeToLog('============================================', true);	         

		        $install_log->pushToLog("MYSQL: Establishing Connection...", E_NOTICE, true, false);       
		        $db = new MySQL;

		        if(!$db->connect($database['host'], $database['username'], $database['password'], $database['port'])){
		            define('_INSTALL_ERRORS_', "There was a problem while trying to establish a connection to the MySQL server. Please check your settings.");                                
		            $install_log->pushToLog("Failed", E_NOTICE,true, true, true);
		            installResult($Page, $install_log, $start);
		        }else{
		            $install_log->pushToLog("Done", E_NOTICE,true, true, true);           
		        }      

		        $install_log->pushToLog("MYSQL: Selecting Database '".$database['name']."'...", E_NOTICE, true, false); 

		        if(!$db->select($database['name'])){
		            define('_INSTALL_ERRORS_', "Could not connect to specified database. Please check your settings.");       
		            $install_log->pushToLog("Failed", E_NOTICE,true, true, true);                         
		            installResult($Page, $install_log, $start);
		        }else{
		            $install_log->pushToLog("Done", E_NOTICE,true, true, true);           
		        }

				$db->setPrefix($database['prefix']); 
				
				$conf = getDynamicConfiguration();
				if($conf['database']['runtime_character_set_alter'] == '1'){
					$db->setCharacterEncoding($conf['database']['character_encoding']);
					$db->setCharacterSet($conf['database']['character_set']);			
				}
			
		        $install_log->pushToLog("MYSQL: Importing Table Schema...", E_NOTICE, true, false);
		        $error = NULL;
		        if(!fireSql($db, getTableSchema(), $error, ($config['database']['high-compatibility'] == 'yes' ? 'high' : 'normal'))){
		            define('_INSTALL_ERRORS_', "There was an error while trying to import data to the database. MySQL returned: $error");       
		            $install_log->pushToLog("Failed", E_ERROR,true, true, true);                         
		            installResult($Page, $install_log, $start);
		        }else{
		            $install_log->pushToLog("Done", E_NOTICE,true, true, true);           
		        }

				$user_sql = sprintf(
					"INSERT INTO `tbl_users` (
						`id`, 
						`username`, 
						`password`, 
						`first_name`, 
						`last_name`, 
						`email`, 
						`last_seen`,
						`default_section`, 
						`auth_token_active`
					)
					VALUES (
						1,
						'%s',
						MD5('%s'),
						'%s',  
						'%s',  
						'%s', 
						NULL,
						'6',  
						'no'
					);", 
					
					$db->escape($config['user']['username']), 
					$db->escape($config['user']['password']), 
					$db->escape($config['user']['firstname']), 
					$db->escape($config['user']['lastname']), 
					$db->escape($config['user']['email'])
				);
				
				$install_log->pushToLog("MYSQL: Creating Default User...", E_NOTICE, true, false);
		        if(!$db->query($user_sql)){
					$error = $db->getLastError();
		            define('_INSTALL_ERRORS_', "There was an error while trying create the default user. MySQL returned: " . $error['num'] . ': ' . $error['msg']);       
		            $install_log->pushToLog("Failed", E_ERROR, true, true, true);                         
		            installResult($Page, $install_log, $start);   

		        }else{	        
					$install_log->pushToLog("Done", E_NOTICE, true, true, true); 
				}	


				$conf = array();
			
				if(@is_dir($fields['docroot'] . '/workspace')){
					foreach(getDynamicConfiguration() as $group => $settings){
						if(!is_array($conf['settings'][$group])) $conf['settings'][$group] = array();
						$conf['settings'][$group] = array_merge($conf['settings'][$group], $settings);
					}
				}
				
				else{
					
					$conf['settings']['admin']['max_upload_size'] = '5242880';
					$conf['settings']['symphony']['pagination_maximum_rows'] = '17';
					$conf['settings']['symphony']['allow_page_subscription'] = '1';
					$conf['settings']['symphony']['lang'] = (defined('__LANG__') ? __LANG__ : 'en');
					$conf['settings']['symphony']['pages_table_nest_children'] = 'no';
					$conf['settings']['log']['archive'] = '1';
					$conf['settings']['log']['maxsize'] = '102400';
					$conf['settings']['image']['cache'] = '1';
					$conf['settings']['image']['quality'] = '90';
					$conf['settings']['database']['driver'] = 'mysql';
					$conf['settings']['database']['character_set'] = 'utf8';
					$conf['settings']['database']['character_encoding'] = 'utf8';
					$conf['settings']['database']['runtime_character_set_alter'] = '1';
					$conf['settings']['public']['display_event_xml_in_source'] = 'no';
				}
				
		        $conf['settings']['symphony']['version'] = kVERSION;		
				$conf['settings']['symphony']['cookie_prefix'] = 'sym-';
				$conf['settings']['symphony']['sitename'] = (strlen(trim($config['symphony']['sitename'])) > 0 ? $config['symphony']['sitename'] : __('Website Name'));
		        $conf['settings']['symphony']['file_write_mode'] = $config['permission']['file'];
		        $conf['settings']['symphony']['directory_write_mode'] = $config['permission']['directory'];
		        $conf['settings']['database']['host'] = $database['host'];
		        $conf['settings']['database']['port'] = $database['port'];
		        $conf['settings']['database']['user'] = $database['username'];
		        $conf['settings']['database']['password'] = $database['password'];
		        $conf['settings']['database']['db'] = $database['name'];
		        $conf['settings']['database']['tbl_prefix'] = $database['prefix'];
				$conf['settings']['region']['time_format'] = $config['region']['time_format'];
				$conf['settings']['region']['date_format'] = $config['region']['date_format'];
				$conf['settings']['region']['timezone'] = $config['region']['timezone'];
				

				## Create Manifest directory structure
				#

		        $install_log->pushToLog("WRITING: Creating 'manifest' folder (/manifest)", E_NOTICE, true, true);
		        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/manifest', $conf['settings']['symphony']['directory_write_mode'])){
		            define('_INSTALL_ERRORS_', "Could not create 'manifest' directory. Check permission on the root folder.");       
		            $install_log->pushToLog("ERROR: Creation of 'manifest' folder failed.", E_ERROR, true, true);                         
		            installResult($Page, $install_log, $start);
					return;
		        }

		        $install_log->pushToLog("WRITING: Creating 'logs' folder (/manifest/logs)", E_NOTICE, true, true);
		        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/manifest/logs', $conf['settings']['symphony']['directory_write_mode'])){
		            define('_INSTALL_ERRORS_', "Could not create 'logs' directory. Check permission on /manifest.");       
		            $install_log->pushToLog("ERROR: Creation of 'logs' folder failed.", E_ERROR, true, true);                         
		            installResult($Page, $install_log, $start);
					return;
		        }

		        $install_log->pushToLog("WRITING: Creating 'cache' folder (/manifest/cache)", E_NOTICE, true, true);
		        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/manifest/cache', $conf['settings']['symphony']['directory_write_mode'])){
		            define('_INSTALL_ERRORS_', "Could not create 'cache' directory. Check permission on /manifest.");       
		            $install_log->pushToLog("ERROR: Creation of 'cache' folder failed.", E_ERROR, true, true);                         
		            installResult($Page, $install_log, $start);
					return;
		        }

		        $install_log->pushToLog("WRITING: Creating 'tmp' folder (/manifest/tmp)", E_NOTICE, true, true);
		        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/manifest/tmp', $conf['settings']['symphony']['directory_write_mode'])){
		            define('_INSTALL_ERRORS_', "Could not create 'tmp' directory. Check permission on /manifest.");       
		            $install_log->pushToLog("ERROR: Creation of 'tmp' folder failed.", E_ERROR, true, true);                         
		            installResult($Page, $install_log, $start);
					return;
		        }

		        $install_log->pushToLog("WRITING: Configuration File", E_NOTICE, true, true);
		        if(!writeConfig($kDOCROOT . '/manifest/', $conf, $conf['settings']['symphony']['file_write_mode'])){
		            define('_INSTALL_ERRORS_', "Could not write config file. Check permission on /manifest.");       
		            $install_log->pushToLog("ERROR: Writing Configuration File Failed", E_ERROR, true, true);                         
		            installResult($Page, $install_log, $start);
		        }

		        $rewrite_base = trim(dirname($_SERVER['PHP_SELF']), '/'); 

		        if(strlen($rewrite_base) > 0){
					$rewrite_base .= '/';
				}

		        $htaccess = '
### Symphony 2.0.x ###
Options +FollowSymlinks

<IfModule mod_rewrite.c>

	RewriteEngine on
	RewriteBase /'.$rewrite_base.'

	### DO NOT APPLY RULES WHEN REQUESTING "favicon.ico"
	RewriteCond %{REQUEST_FILENAME} favicon.ico [NC]
	RewriteRule .* - [S=14]	

	### IMAGE RULES	
	RewriteRule ^image\/(.+\.(jpg|gif|jpeg|png|bmp))$ extensions/jit_image_manipulation/lib/image.php?param=$1 [L,NC]

	### CHECK FOR TRAILING SLASH - Will ignore files
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_URI} !/$
	RewriteCond %{REQUEST_URI} !(.*)/$
	RewriteRule ^(.*)$ $1/ [L,R=301]

	### ADMIN REWRITE
	RewriteRule ^symphony\/?$ index.php?mode=administration&%{QUERY_STRING} [NC,L]

	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_FILENAME} !-f	
	RewriteRule ^symphony(\/(.*\/?))?$ index.php?symphony-page=$1&mode=administration&%{QUERY_STRING}	[NC,L]

	### FRONTEND REWRITE - Will ignore files and folders
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*\/?)$ index.php?symphony-page=$1&%{QUERY_STRING}	[L]
	
</IfModule>
######
';

		        $install_log->pushToLog("CONFIGURING: Frontend", E_NOTICE, true, true);
		        if(!GeneralExtended::writeFile($kDOCROOT . "/.htaccess", $htaccess, $conf['settings']['symphony']['file_write_mode'])){
		            define('_INSTALL_ERRORS_', "Could not write .htaccess file. Check permission on " . $kDOCROOT);       
		            $install_log->pushToLog("ERROR: Writing .htaccess File Failed", E_ERROR, true, true);                          
		            installResult($Page, $install_log, $start);
		        }
		
				if(@!is_dir($fields['docroot'] . '/workspace')){
					
					### Create the workspace folder structure
					#
					
			        $install_log->pushToLog("WRITING: Creating 'workspace' folder (/workspace)", E_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace', $conf['settings']['symphony']['directory_write_mode'])){
			            define('_INSTALL_ERRORS_', "Could not create 'workspace' directory. Check permission on the root folder.");       
			            $install_log->pushToLog("ERROR: Creation of 'workspace' folder failed.", E_ERROR, true, true);                         
			            installResult($Page, $install_log, $start);
						return;
			        }

			        $install_log->pushToLog("WRITING: Creating 'data-sources' folder (/workspace/data-sources)", E_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace/data-sources', $conf['settings']['symphony']['directory_write_mode'])){
			            define('_INSTALL_ERRORS_', "Could not create 'workspace/data-sources' directory. Check permission on the root folder.");       
			            $install_log->pushToLog("ERROR: Creation of 'workspace/data-sources' folder failed.", E_ERROR, true, true);                         
			            installResult($Page, $install_log, $start);
						return;
			        }

			        $install_log->pushToLog("WRITING: Creating 'events' folder (/workspace/events)", E_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace/events', $conf['settings']['symphony']['directory_write_mode'])){
			            define('_INSTALL_ERRORS_', "Could not create 'workspace/events' directory. Check permission on the root folder.");       
			            $install_log->pushToLog("ERROR: Creation of 'workspace/events' folder failed.", E_ERROR, true, true);                         
			            installResult($Page, $install_log, $start);
						return;
			        }

			        $install_log->pushToLog("WRITING: Creating 'pages' folder (/workspace/pages)", E_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace/pages', $conf['settings']['symphony']['directory_write_mode'])){
			            define('_INSTALL_ERRORS_', "Could not create 'workspace/pages' directory. Check permission on the root folder.");       
			            $install_log->pushToLog("ERROR: Creation of 'workspace/pages' folder failed.", E_ERROR, true, true);                         
			            installResult($Page, $install_log, $start);
						return;
			        }

			        $install_log->pushToLog("WRITING: Creating 'utilities' folder (/workspace/utilities)", E_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace/utilities', $conf['settings']['symphony']['directory_write_mode'])){
			            define('_INSTALL_ERRORS_', "Could not create 'workspace/utilities' directory. Check permission on the root folder.");       
			            $install_log->pushToLog("ERROR: Creation of 'workspace/utilities' folder failed.", E_ERROR, true, true);                         
			            installResult($Page, $install_log, $start);
						return;
			        }
					
				}
				
				else {
					
					$install_log->pushToLog("MYSQL: Importing Workspace Data...", E_NOTICE, true, false);
			        $error = NULL;
			        if(!fireSql($db, getWorkspaceData(), $error, ($config['database']['high-compatibility'] == 'yes' ? 'high' : 'normal'))){
			            define('_INSTALL_ERRORS_', "There was an error while trying to import data to the database. MySQL returned: $error");       
			            $install_log->pushToLog("Failed", E_ERROR,true, true, true);                         
			            installResult($Page, $install_log, $start);
			        }else{
			            $install_log->pushToLog("Done", E_NOTICE,true, true, true);           
			        }
					
				}	
							
				if(@!is_dir($fields['docroot'] . '/extensions')){
			        $install_log->pushToLog("WRITING: Creating 'campfire' folder (/extensions)", E_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/extensions', $conf['settings']['symphony']['directory_write_mode'])){
			            define('_INSTALL_ERRORS_', "Could not create 'extensions' directory. Check permission on the root folder.");       
			            $install_log->pushToLog("ERROR: Creation of 'extensions' folder failed.", E_ERROR, true, true);                         
			            installResult($Page, $install_log, $start);
						return;
			        }	
				}

		        $install_log->pushToLog("Installation Process Completed In ".max(1, time() - $start)." sec", E_NOTICE, true);

		        installResult($Page, $install_log, $start);
		
				redirect('http://' . rtrim(str_replace('http://', '', _INSTALL_DOMAIN_), '/') . '/symphony/');

		    }

		}

	}

	Class Page{
		
		var $_header;
		var $_footer;
		var $_content;
		var $_vars;
		var $_result;
		var $log;
		var $missing;
		var $_page;
		
		function Page(&$log){
			$this->_header = $this->_footer = $this->_content = NULL;
			$this->_result = NULL;
			$this->_vars = $this->missing = array();	
			$this->log = $log;		
		}
		
		function setPage($page){
			$this->_page = $page;
		}
		
		function getPage(){
			return $this->_page;
		}
		
		function setFooter($footer){
			$this->_footer = $footer;
		}
		
		function setHeader($header){
			$this->_header = $header;
		}
		
		function setContent($content){
			$this->_content = $content;
		}
		
		function setTemplateVar($name, $value){
			$this->_vars[$name] = $value;
		}
		
		function render(){
			$this->_result = $this->_header . $this->_content . $this->_footer;
			
			if(is_array($this->_vars) && !empty($this->_vars)){
				foreach($this->_vars as $name => $val){
					$this->_result = str_replace('<!-- ' . strtoupper($name) . ' -->', $val, $this->_result);
				}
			}
			
			return $this->_result;
			
		}
		
		function display(){
			return ($this->_result ? $this->_result : $this->render());
		}
		
	}
	
	$fields = array();
	
	if(isset($_POST['fields'])) $fields = $_POST['fields'];
	else{
		
		$fields['docroot'] = rtrim(getcwd_safe(), '/');
		$fields['database']['host'] = 'localhost';
		$fields['database']['port'] = '3306';
		$fields['database']['prefix'] = 'sym_';
		$fields['permission']['file'] = '0775';
		$fields['permission']['directory'] = '0775';
		
		$conf = getDynamicConfiguration();
		$fields['symphony']['sitename'] = $conf['symphony']['sitename'];
		$fields['region']['date_format'] = $conf['region']['date_format'];
		$fields['region']['time_format'] = $conf['region']['time_format'];
		
	}
	
	Class Display{
		
		function index(&$Page, &$Contents, $fields){
		
			global $warnings;
			global $notices;
			global $languages;
		
			$Form = new XMLElement('form');
			$Form->setAttribute('action', kINSTALL_FILENAME.($_GET['lang'] ? '?lang='.$_GET['lang'] : ''));
			$Form->setAttribute('method', 'post');
		
			/** 
			 *
			 * START ENVIRONMENT SETTINGS 
			 *
			**/

				$Environment = new XMLElement('fieldset');
				$Environment->appendChild(new XMLElement('legend', __('Environment Settings')));
				$Environment->appendChild(new XMLElement('p', __('Symphony is ready to be installed at the following location.')));
	
				$class = NULL;
				if(defined('kENVIRONMENT_WARNING') && kENVIRONMENT_WARNING == true) $class = 'warning';

				$Environment->appendChild(Widget::label(__('Root Path'), Widget::input('fields[docroot]', $fields['docroot']), $class));
			
				if(defined('ERROR') && defined('kENVIRONMENT_WARNING')) $Environment->appendChild(new XMLElement('p', $warnings[ERROR], array('class' => 'warning')));
				
				$Form->appendChild($Environment);
				
				
			/** END ENVIRONMENT SETTINGS **/
								
			/** 
			 *
			 * START LOCALE SETTINGS 
			 *
			**/					

				$Environment = new XMLElement('fieldset');
				$Environment->appendChild(new XMLElement('legend', __('Website Preferences')));
//				$Environment->appendChild(new XMLElement('p', '.'));
				
				$Environment->appendChild(Widget::label(__('Name'), Widget::input('fields[general][sitename]', $fields['symphony']['sitename'])));
				

				
				$Fieldset = new XMLElement('fieldset');
				$Fieldset->appendChild(new XMLElement('legend', __('Date and Time')));
				$Fieldset->appendChild(new XMLElement('p', __('Customise how Date and Time values are displayed throughout the Administration interface.')));
				
				
				$options = array();
				$groups = array();
				
				$system_tz = (isset($fields['region']['timezone']) ? $fields['region']['timezone'] : date_default_timezone_get());
				
				foreach(timezone_identifiers_list() as $tz){
					
					if(preg_match('/\//', $tz)){
						$parts = preg_split('/\//', $tz, 2, PREG_SPLIT_NO_EMPTY);
						
						$groups[$parts[0]][] = $parts[1];
					}
					
					else $groups[$tz] = $tz;
	
				}
				
				foreach($groups as $key => $val){
					if(is_array($val)){
						$tmp = array('label' => $key, 'options' => array());
						foreach($val as $zone){
							$tmp['options'][] = array("$key/$zone", "$key/$zone" == $system_tz, str_replace('_', ' ', $zone));
						}
						$options[] = $tmp;
					}
					else $options[] = array($key, $key == $system_tz, str_replace('_', ' ', $key));
				}
				
				$Fieldset->appendChild(Widget::label(__('Region'), Widget::Select('fields[region][timezone]', $options)));
								
				//$Div->appendChild(Widget::label('Date Format', Widget::input('fields[general][sitename]', $fields['symphony']['sitename'])));
				//$Div->appendChild(Widget::label('Time Format', Widget::input('fields[general][sitename]', $fields['symphony']['sitename'])));

				$dateformat = $fields['region']['date_format'];
				$label = Widget::Label(__('Date Format'));
				$dateFormats = array( 			
					array('Y/m/d', $dateformat == 'Y/m/d', DateTimeObj::get('Y/m/d')),
					array('m/d/Y', $dateformat == 'm/d/Y', DateTimeObj::get('m/d/Y')),
					array('m/d/y', $dateformat == 'm/d/y', DateTimeObj::get('m/d/y')),
					array('d F Y', $dateformat == 'd F Y', DateTimeObj::get('d F Y')),
				);
				$label->appendChild(Widget::Select('fields[region][date_format]', $dateFormats));
				$Fieldset->appendChild($label);	

				$timeformat = $fields['region']['time_format'];
				$label = Widget::Label(__('Time Format'));
				
				//$label->setAttribute('title', __('Local') . (date('I') == 1 ? ' daylight savings' : '') . ' time for ' . date_default_timezone_get());
				//if(date('I') == 1) $label->appendChild(new XMLElement('i', __('Daylight savings time')));

				$timeformats = array(
					array('H:i:s', $timeformat == 'H:i:s', DateTimeObj::get('H:i:s')),
					array('H:i', $timeformat == 'H:i', DateTimeObj::get('H:i')),
					array('g:i:s a', $timeformat == 'g:i:s a', DateTimeObj::get('g:i:s a')),
					array('g:i a', $timeformat == 'g:i a', DateTimeObj::get('g:i a')),
				);
				$label->appendChild(Widget::Select('fields[region][time_format]', $timeformats));
				$Fieldset->appendChild($label);
				
				
				$Environment->appendChild($Fieldset);	
				
				$Form->appendChild($Environment);
				
			/** END LOCALE SETTINGS **/

			/** 
			 *
			 * START DATABASE SETTINGS 
			 *
			**/

				$Database = new XMLElement('fieldset');
				$Database->appendChild(new XMLElement('legend', __('Database Connection')));
				$Database->appendChild(new XMLElement('p', __('Please provide Symphony with access to a database.')));

				$class = NULL;
				if(defined('kDATABASE_VERSION_WARNING') && kDATABASE_VERSION_WARNING == true) $class = ' warning';
				 
				## fields[database][name]
				$label = Widget::label(__('Database'), Widget::input('fields[database][name]', $fields['database']['name']), $class);
				$Database->appendChild($label);
				
				if(defined('ERROR') && defined('kDATABASE_VERSION_WARNING'))
					$Database->appendChild(new XMLElement('p', $warnings[ERROR], array('class' => 'warning')));
				
				$class = NULL;
				if(defined('kDATABASE_CONNECTION_WARNING') && kDATABASE_CONNECTION_WARNING == true) $class = ' warning';
				
				$Div = new XMLElement('div');
				$Div->setAttribute('class', 'group' . $class);

				## fields[database][username]
				$Div->appendChild(Widget::label(__('Username'), Widget::input('fields[database][username]', $fields['database']['username'])));
		
				## fields[database][password]								
				$Div->appendChild(Widget::label(__('Password'), Widget::input('fields[database][password]', $fields['database']['password'], 'password')));

				$Database->appendChild($Div);
			
				if(defined('ERROR') && defined('kDATABASE_CONNECTION_WARNING'))
					$Database->appendChild(new XMLElement('p', $warnings[ERROR], array('class' => 'warning')));

				$Fieldset = new XMLElement('fieldset');
				$Fieldset->appendChild(new XMLElement('legend', __('Advanced Configuration')));
				$Fieldset->appendChild(new XMLElement('p', __('Leave these fields unless you are sure they need to be changed.')));
		
				$Div = new XMLElement('div');
				$Div->setAttribute('class', 'group');
		
				## fields[database][host]
				$Div->appendChild(Widget::label(__('Host'), Widget::input('fields[database][host]', $fields['database']['host'])));
		
				## fields[database][port]								
				$Div->appendChild(Widget::label(__('Port'), Widget::input('fields[database][port]', $fields['database']['port'])));
														
				$Fieldset->appendChild($Div);

				$class = NULL;
				if(defined('kDATABASE_PREFIX_WARNING') && kDATABASE_PREFIX_WARNING == true) $class = 'warning';		

				## fields[database][prefix]
				$Fieldset->appendChild(Widget::label(__('Table Prefix'), Widget::input('fields[database][prefix]', $fields['database']['prefix']), $class));
				
				if(defined('ERROR') && defined('kDATABASE_PREFIX_WARNING'))
					$Fieldset->appendChild(new XMLElement('p', $warnings[ERROR], array('class' => 'warning')));
				
				$Page->setTemplateVar('TABLE-PREFIX', $fields['database']['prefix']);
										
				## fields[database][high-compatibility]
				$Fieldset->appendChild(Widget::label(__('Use compatibility mode'), Widget::input('fields[database][high-compatibility]', 'yes', 'checkbox'), 'option'));											

				$Fieldset->appendChild(new XMLElement('p', __('Symphony normally specifies UTF-8 character encoding for database entries. With compatibility mode enabled, Symphony will instead use the default character encoding of your database.')));
				
				$Database->appendChild($Fieldset);		
						
				$Form->appendChild($Database);

			/** END DATABASE SETTINGS **/
	
			/** 
			 *
			 * START PERMISSION SETTINGS 
			 *
			**/

				$Permissions = new XMLElement('fieldset');
				$Permissions->appendChild(new XMLElement('legend', __('Permission Settings')));
				$Permissions->appendChild(new XMLElement('p', __('Symphony needs permission to read and write both files and directories.')));

				$Div = new XMLElement('div');
				$Div->setAttribute('class', 'group');
				
				$Div->appendChild(Widget::label(__('Files'), Widget::input('fields[permission][file]', $fields['permission']['file'])));
				$Div->appendChild(Widget::label(__('Directories'), Widget::input('fields[permission][directory]', $fields['permission']['directory'])));

				$Permissions->appendChild($Div);
				$Form->appendChild($Permissions);
		
			/** END PERMISSION SETTINGS **/

			/** 
			 *
			 * START USER SETTINGS 
			 *
			**/

				$User = new XMLElement('fieldset');
				$User->appendChild(new XMLElement('legend', __('User Information')));
				$User->appendChild(new XMLElement('p', __('Once installed, you will be able to login to the Symphony admin with these user details.')));

				$class = NULL;
				if(defined('kUSER_USERNAME_WARNING') && kUSER_PASSWORD_WARNING == true) $class = 'warning';

				## fields[user][username]
				$User->appendChild(Widget::label(__('Username'), Widget::input('fields[user][username]', $fields['user']['username']), $class));

				if(defined('ERROR') && defined('kUSER_USERNAME_WARNING'))
					$User->appendChild(new XMLElement('p', $warnings[ERROR], array('class' => 'warning')));

				$class = NULL;
				if(defined('kUSER_PASSWORD_WARNING') && kUSER_PASSWORD_WARNING == true) $class = ' warning';

				$Div = new XMLElement('div');
				$Div->setAttribute('class', 'group' . $class);

				## fields[user][password]							
				$Div->appendChild(Widget::label(__('Password'), Widget::input('fields[user][password]', $fields['user']['password'], 'password')));
		
				## fields[user][confirm-password]						
				$Div->appendChild(Widget::label(__('Confirm Password'), Widget::input('fields[user][confirm-password]', $fields['user']['confirm-password'], 'password')));		

				$User->appendChild($Div);

				if(defined('ERROR') && defined('kUSER_PASSWORD_WARNING'))
					$User->appendChild(new XMLElement('p', $warnings[ERROR], array('class' => 'warning')));

				$Fieldset = new XMLElement('fieldset');
				$Fieldset->appendChild(new XMLElement('legend', __('Personal Information')));
				$Fieldset->appendChild(new XMLElement('p', __('Please add the following personal details for this user.')));

				$class = NULL;
				if(defined('kUSER_NAME_WARNING') && kUSER_EMAIL_WARNING == true) $class = ' warning';
			
				$Div = new XMLElement('div');
				$Div->setAttribute('class', 'group' . $class);

				## fields[database][host]
				$Div->appendChild(Widget::label(__('First Name'), Widget::input('fields[user][firstname]', $fields['user']['firstname'])));

				## fields[database][port]								
				$Div->appendChild(Widget::label(__('Last Name'), Widget::input('fields[user][lastname]', $fields['user']['lastname'])));

				$Fieldset->appendChild($Div);

				if(defined('ERROR') && defined('kUSER_NAME_WARNING'))
					$Fieldset->appendChild(new XMLElement('p', $warnings[ERROR], array('class' => 'warning')));

				$class = NULL;
				if(defined('kUSER_EMAIL_WARNING') && kUSER_EMAIL_WARNING == true) $class = 'warning';
			
				## fields[user][email]
				$Fieldset->appendChild(Widget::label(__('Email Address'), Widget::input('fields[user][email]', $fields['user']['email']), $class));

				if(defined('ERROR') && defined('kUSER_EMAIL_WARNING'))
					$Fieldset->appendChild(new XMLElement('p', $warnings[ERROR], array('class' => 'warning')));	
							
				$User->appendChild($Fieldset);		

				$Form->appendChild($User);

			/** END USER SETTINGS **/

	
			/** 
			 *
			 * START FORM SUBMIT AREA
			 *
			**/
	
				$Form->appendChild(new XMLElement('h2', __('Install Symphony')));
				$Form->appendChild(new XMLElement('p', __('Make sure that you delete <code>%s</code> file after Symphony has installed successfully.', array(kINSTALL_FILENAME))));
				 
				$Submit = new XMLElement('div');
				$Submit->setAttribute('class', 'submit');

				### submit		
				$Submit->appendChild(Widget::input('submit', __('Install Symphony'), 'submit'));
		
				### action[install]
				$Submit->appendChild(Widget::input('action[install]', 'true', 'hidden'));	

				$Form->appendChild($Submit);
				$Contents->appendChild($Form);
		
			/** END FORM SUBMIT AREA **/
		

			$Page->setTemplateVar('title', __('Install Symphony'));
			$Page->setTemplateVar('tagline', __('Version %s', array(kVERSION)));
			$Page->setTemplateVar('languages', $languages);
		}
	
		function requirements(&$Page, &$Contents){
	
			$Contents->appendChild(new XMLElement('h2', __('Outstanding Requirements')));
			$Contents->appendChild(new XMLElement('p', __('Symphony needs the following requirements satisfied before installation can proceed.')));
			
	
			$messages = array();
		
			if(in_array(MISSING_PHP, $Page->missing))					
				$messages[] = array(__('<abbr title="PHP: Hypertext Pre-processor">PHP</abbr> 5.1 or above'),
							  		__('Symphony needs a recent version of <abbr title="PHP: Hypertext Pre-processor">PHP</abbr>.'));
		
			if(in_array(MISSING_MYSQL, $Page->missing))				
				$messages[] = array(__('My<abbr title="Structured Query Language">SQL</abbr> 4.1 or above'),
							  	__('Symphony needs a recent version of My<abbr title="Structured Query Language">SQL</abbr>.'));

			if(in_array(MISSING_ZLIB, $Page->missing))
				$messages[] = array(__('ZLib Compression Library'),
							  		__('Data retrieved from the Symphony support server is decompressed with the ZLib compression library.'));

			if(in_array(MISSING_XSL, $Page->missing) || in_array(MISSING_XML, $Page->missing))
				$messages[] = array(__('<abbr title="eXtensible Stylesheet Language Transformation">XSLT</abbr> Processor'),
							  		__('Symphony needs an XSLT processor such as Lib<abbr title="eXtensible Stylesheet Language Transformation">XSLT</abbr> or Sablotron to build pages.'));
		
			$dl = new XMLElement('dl');
			foreach($messages as $m){
				$dl->appendChild(new XMLElement('dt', $m[0]));
				$dl->appendChild(new XMLElement('dd', $m[1]));
			}
																
			$Contents->appendChild($dl);	
		
			$Page->setTemplateVar('title', __('Missing Requirements'));
			$Page->setTemplateVar('tagline', __('Version %s', array(kVERSION)));

			global $languages;
			$Page->setTemplateVar('languages', $languages);
		}

		function uptodate(&$Page, &$Contents){
			$Contents->appendChild(new XMLElement('h2', __('Update Symphony')));
			$Contents->appendChild(new XMLElement('p', __('You are already using the most recent version of Symphony. There is no need to run the installer, and can be safely deleted.')));

			$Page->setTemplateVar('title', __('Update Symphony'));
			$Page->setTemplateVar('tagline', __('Version %s', array(kVERSION)));

			global $languages;
			$Page->setTemplateVar('languages', $languages);
		}

		function incorrectVersion(&$Page, &$Contents){
			$Contents->appendChild(new XMLElement('h2', __('Update Symphony')));
			$Contents->appendChild(new XMLElement('p', __('You are not using the most recent version of Symphony. This update is only compatible with Symphony 2.')));

			$Page->setTemplateVar('title', __('Update Symphony'));
			$Page->setTemplateVar('tagline', __('Version %s', array(kVERSION)));

			global $languages;
			$Page->setTemplateVar('languages', $languages);
		}

		function failure(&$Page, &$Contents){

			$Contents->appendChild(new XMLElement('h2', __('Installation Failure')));
			$Contents->appendChild(new XMLElement('p', __('An error occurred during installation. You can view you log <a href="install-log.txt">here</a> for more details.')));

			$Page->setTemplateVar('title', __('Installation Failure'));
			$Page->setTemplateVar('tagline', __('Version %s', array(kVERSION)));		

			global $languages;
			$Page->setTemplateVar('languages', $languages);
		}

	}

	$Log = new SymphonyLog('install-log.txt');
	
	$Page = new Page($Log);
	
	$Page->setHeader(kHEADER);
	$Page->setFooter(kFOOTER);

	$Contents = new XMLElement('body');
	$Contents->appendChild(new XMLElement('h1', '<!-- TITLE --> <em><!-- TAGLINE --></em> <em><!-- LANGUAGES --></em>'));

	if(defined('__IS_UPDATE__') && __IS_UPDATE__ == true)
		$Page->setPage('update');
		
	elseif(defined('__ALREADY_UP_TO_DATE__') && __ALREADY_UP_TO_DATE__ == true)	
		$Page->setPage('uptodate');
				
	else{
		$Page->setPage('index');	
		Action::requirements($Page);
	}

	if(is_array($Page->missing) && !empty($Page->missing)) $Page->setPage('requirements');
	elseif(isset($_POST['action'])){

		$action = array_keys($_POST['action']);
		$action = $action[0];

		call_user_func_array(array('Action', $action), array(&$Page, $fields));
	}
	
	call_user_func_array(array('Display', $Page->getPage()), array(&$Page, &$Contents, $fields));
	
	$Page->setContent($Contents->generate(true, 2));
	$output = $Page->display();
	
	header('Content-Type: text/html; charset=UTF-8');	
	header(sprintf('Content-Length: %d', strlen($output)));
	echo $output;

