<?php

	
	
	require_once(CORE . '/class.symphony.php');
	require_once(TOOLKIT . '/class.xmldoc.php');	
	require_once(TOOLKIT . '/class.lang.php');
	require_once(TOOLKIT . '/class.manager.php');	
	require_once(TOOLKIT . '/class.htmlpage.php');
	require_once(TOOLKIT . '/class.ajaxpage.php');
		
	Class Administration extends Symphony{
		
		private $_currentPage;
		private $_callback;
		public $displayProfilerReport;
		public $Page;
		
		public static function instance(){
			if(!(self::$_instance instanceof Administration)) 
				self::$_instance = new self;
				
			return self::$_instance;
		}
		
		protected function __construct(){
			parent::__construct();
			$this->Profiler->sample('Engine Initialisation');
			$this->displayProfilerReport = false;
			$this->_callback = NULL;
		}
		
		public function isLoggedIn(){
			if($_REQUEST['auth-token'] && in_array(strlen($_REQUEST['auth-token']), array(6, 8))) return $this->loginFromToken($_REQUEST['auth-token']);
			
			return parent::isLoggedIn();
		}
		
		private function __buildPage($page){
	
			$this->isLoggedIn();
			
			if(empty($page)){
				
				if(!$this->isLoggedIn()): $page = '/login';
				
				else:
				
					$section_handle = $this->Database->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_sections` WHERE `id` = '".$this->Author->get('default_section')."' LIMIT 1");
				
					if(!$section_handle){
						$section_handle = $this->Database->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_sections` ORDER BY `sortorder` LIMIT 1");
					}
				
					if(!$section_handle){
						
						if($this->Author->isDeveloper()) redirect(URL . '/symphony/blueprints/sections/');
						else redirect(URL);
						
					}
				
					else{
						redirect(URL . '/symphony/publish/' . $section_handle . '/');
					}
				
				endif;
			}
			
			if(!$this->_callback = $this->getPageCallback($page)){
				$this->errorPageNotFound();
			}
				
			include_once((isset($this->_callback['driverlocation']) ? $this->_callback['driverlocation'] : CONTENT) . '/content.' . $this->_callback['driver'] . '.php'); 			
			$this->Page =& new $this->_callback['classname']($this);

			if(!$this->isLoggedIn() && $this->_callback['driver'] != 'login'){
				if(is_callable(array($this->Page, 'handleFailedAuthorisation'))) $this->Page->handleFailedAuthorisation();
				else{
				
					include_once(CONTENT . '/content.login.php'); 			
					$this->Page =& new contentLogin($this);
					$this->Page->build();
				
				}
			}
			
			else $this->Page->build($this->_callback['context']);
			
			return $this->Page;
		}
		
		public function getPageCallback($page=NULL, $update=false){
			
			if((!$page || !$update) && $this->_callback) return $this->_callback;
			elseif(!$page && !$this->_callback) trigger_error('Cannot request a page callback without first specifying the page.');
			
			$this->_currentPage = URL . preg_replace('/\/{2,}/', '/', '/symphony' . $page);
			$bits = preg_split('/\//', trim($page, '/'), 3, PREG_SPLIT_NO_EMPTY);
			
			if($bits[0] == 'login'){
			
				$callback = array(
						'driver' => 'login',
						'context' => preg_split('/\//', $bits[1] . '/' . $bits[2], -1, PREG_SPLIT_NO_EMPTY),
						'classname' => 'contentLogin',
						'pageroot' => '/login/'
					);
			}
			
			elseif($bits[0] == 'extension' && isset($bits[1])){
				
				$extention_name = $bits[1];
				$bits = preg_split('/\//', trim($bits[2], '/'), 2, PREG_SPLIT_NO_EMPTY);
				
				$callback = array(
								'driver' => NULL,
								'context' => NULL,
								'pageroot' => NULL,
								'classname' => NULL,
								'driverlocation' => EXTENSIONS . '/' . $extention_name . '/content/'
							);			
								
				$callback['driver'] = 'index'; //ucfirst($extention_name);
				$callback['classname'] = 'contentExtension' . ucfirst($extention_name) . 'Index';
				$callback['pageroot'] = '/extension/' . $extention_name. '/';	
				
				if(isset($bits[0])){
					$callback['driver'] = $bits[0];
					$callback['classname'] = 'contentExtension' . ucfirst($extention_name) . ucfirst($bits[0]);
					$callback['pageroot'] .= $bits[0] . '/';
				}
				
				if(isset($bits[1])) $callback['context'] = preg_split('/\//', $bits[1], -1, PREG_SPLIT_NO_EMPTY);
				
				if(!is_file($callback['driverlocation'] . '/content.' . $callback['driver'] . '.php')) return false;
								
			}
			
			elseif($bits[0] == 'publish'){
				
				if(!isset($bits[1])) return false;

				$callback = array(
								'driver' => 'publish',
								'context' => array('section_handle' => $bits[1], 'page' => NULL, 'entry_id' => NULL, 'flag' => NULL),
								'pageroot' => '/' . $bits[0] . '/' . $bits[1] . '/',
								'classname' => 'contentPublish'
							);
				
				if(isset($bits[2])){
					$extras = preg_split('/\//', $bits[2], -1, PREG_SPLIT_NO_EMPTY);
					
					$callback['context']['page'] = $extras[0];
					if(isset($extras[1])) $callback['context']['entry_id'] = intval($extras[1]);
				
					if(isset($extras[2])) $callback['context']['flag'] = $extras[2];
					
				}
				
				else $callback['context']['page'] = 'index';
				
			}
			
			else{
				
				$callback = array(
								'driver' => NULL,
								'context' => NULL,
								'pageroot' => NULL,
								'classname' => NULL
							);
			
				$callback['driver'] = ucfirst($bits[0]);
				$callback['pageroot'] = '/' . $bits[0] . '/';
				
				if(isset($bits[1])){
					$callback['driver'] = $callback['driver'] . ucfirst($bits[1]);
					$callback['pageroot'] .= $bits[1] . '/';
				}
			
				if(isset($bits[2])) $callback['context'] = preg_split('/\//', $bits[2], -1, PREG_SPLIT_NO_EMPTY);
			
				$callback['classname'] = 'content' . $callback['driver'];
				$callback['driver'] = strtolower($callback['driver']);
				
				if(!is_file(CONTENT . '/content.' . $callback['driver'] . '.php')) return false;
				
			}
			
			## TODO: Add delegate for custom callback creation
			
			return $callback;
			
		}
		
		public function getCurrentPageURL(){
			return $this->_currentPage;
		}
		
		public function display($page){
			
			$this->Profiler->sample('Page build process started');
			//$oPage = $this->__buildPage($page);
			$this->__buildPage($page);
			
			####
			# Delegate: AdminPagePreGenerate
			# Description: Immediately before generating the admin page. Provided with the page object
			# Global: Yes
			$this->ExtensionManager->notifyMembers('AdminPagePreGenerate', '/administration/', array('oPage' => &$this->Page));
			
			$output = $this->Page->generate();

			####
			# Delegate: AdminPagePostGenerate
			# Description: Immediately after generating the admin page. Provided with string containing page source
			# Global: Yes
			$this->ExtensionManager->notifyMembers('AdminPagePostGenerate', '/administration/', array('output' => &$output));

			$this->Profiler->sample('Page built');
			
			return $output;	
		}

		public function saveConfig(){
	
			$string  = '<?php' . self::CRLF
					 . "\tif(!defined('DOCROOT')) define('DOCROOT', '".DOCROOT."');" . self::CRLF
					 . "\tif(!defined('DOMAIN')) define('DOMAIN', '".DOMAIN."');" . self::CRLF . self::CRLF			
					 . "\t".'$settings = ' . (string)$this->Configuration . ';' . self::CRLF . self::CRLF	
					 . "\trequire_once(DOCROOT . '/symphony/lib/boot/bundle.php');";

			return General::writeFile(CONFIG, $string, $this->Configuration->get('write_mode', 'file'));
	
		}
		
		public function errorPageNotFound(){
			$this->customError(E_USER_ERROR, 'Page Not Found', 'The page you requested does not exist.', false, true, 'error', array('header' => 'HTTP/1.0 404 Not Found'));			
		}
		
/*		public function uninstall(){
			return;
			## ASK EACH EXTENSION TO UNINSTALL ITSELF
			if($extensions = $this->ExtensionManager->listAll()){
				foreach($extensions as $name => $about){
					$this->ExtensionManager->uninstall($name);	
				}
			}
			
			## REMOVE ALL DB TABLES
			$tables = array(
				'tbl_authors',
				'tbl_cache',
				'tbl_entries',
				'tbl_extensions',
				'tbl_extensions_delegates',
				'tbl_fields',			
				'tbl_forgotpass',
				'tbl_pages',
				'tbl_pages_types',
				'tbl_sections',
				'tbl_sections_association'			
			);
			
			if($ret = $this->Database->fetch("SHOW TABLES LIKE 'tbl_entries_data_%'; " )){
				foreach($ret as $t) $tables[] = current($t);
			}
			
			if($ret = $this->Database->fetch("SHOW TABLES LIKE 'tbl_fields_%'; " )){
				foreach($ret as $t) $tables[] = current($t);
			}

			$sql = 'DROP TABLE `'.@implode('`, `', $tables).'`;';
			$this->Database->query($sql);
		
			## REMOVE WORKSPACE FOLDER
			General::rmdirr(WORKSPACE);
								
			## REMOVE MANIFEST FOLDER
			General::rmdirr(MANIFEST);
		
			unlink(DOCROOT . '/.htaccess');
			unlink(SYMPHONY . '/.htaccess');
			
			return;
		}
	*/
/*		
		public function export(){
			
			$sql_schema = $sql_data = NULL;
			
			require_once(TOOLKIT . '/class.mysqldump.php');
			
			$dump = new MySQLDump($this->Database);

			$tables = array(
				'tbl_authors',
				'tbl_cache',
				'tbl_entries',
				'tbl_extensions',
				'tbl_extensions_delegates',
				'tbl_fields',
				'tbl_fields_%',			
				'tbl_forgotpass',
				'tbl_pages',
				'tbl_pages_types',
				'tbl_sections',
				'tbl_sections_association'			
			);
			
			## Grab the schema
			foreach($tables as $t) $sql_schema .= $dump->export($t, MySQLDump::STRUCTURE_ONLY);
			$sql_schema = str_replace('`' . $this->Configuration->get('tbl_prefix', 'database'), '`tbl_', $sql_schema);
			
			$sql_schema = preg_replace('/AUTO_INCREMENT=\d+/i', '', $sql_schema);
			
			$tables = array(
				'tbl_entries',
				'tbl_extensions',
				'tbl_extensions_delegates',
				'tbl_fields',			
				'tbl_pages',
				'tbl_pages_types',
				'tbl_sections',
				'tbl_sections_association'			
			);			
			
			## Field data and entry data schemas needs to be apart of the workspace sql dump
			$sql_data  = $dump->export('tbl_fields_%', MySQLDump::ALL);
			$sql_data .= $dump->export('tbl_entries_%', MySQLDump::ALL);
			
			## Grab the data
			foreach($tables as $t) $sql_data .= $dump->export($t, MySQLDump::DATA_ONLY);
			$sql_data = str_replace('`' . $this->Configuration->get('tbl_prefix', 'database'), '`tbl_', $sql_data);
			
			$config_string = NULL;
			$config = $this->Configuration->get();			
			
			unset($config['symphony']['build']);
			unset($config['symphony']['cookie_prefix']);
			unset($config['general']['useragent']);
			unset($config['file']['write_mode']);
			unset($config['directory']['write_mode']);
			unset($config['database']['host']);
			unset($config['database']['port']);
			unset($config['database']['user']);
			unset($config['database']['password']);
			unset($config['database']['db']);
			unset($config['database']['tbl_prefix']);
			unset($config['region']['timezone']);

			foreach($config as $group => $set){
				foreach($set as $key => $val) $config_string .= "		\$conf['".$group."']['".$key."'] = '".$val."';" . self::CRLF;
			}
			
			$install_template = str_replace(
				
									array(
										'<!-- BUILD -->',
										'<!-- ENCODED SQL SCHEMA DUMP -->',
										'<!-- ENCODED SQL DATA DUMP -->',
										'<!-- CONFIGURATION -->'
									),
				
									array(
										$this->Configuration->get('build', 'symphony'),
										base64_encode($sql_schema),
										base64_encode($sql_data),
										trim($config_string),										
									),
				
									file_get_contents(TEMPLATE . '/installer.tpl')
			);
			
			require_once(TOOLKIT . '/class.archivezip.php');
			
			$archive = new ArchiveZip;

			$archive->addDirectory(EXTENSIONS, DOCROOT, ArchiveZip::IGNORE_HIDDEN);		
			$archive->addDirectory(SYMPHONY, DOCROOT, ArchiveZip::IGNORE_HIDDEN);
			$archive->addDirectory(WORKSPACE, DOCROOT, ArchiveZip::IGNORE_HIDDEN);
			$archive->addFromFile(DOCROOT . '/index.php', 'index.php');
			
			if(is_file(DOCROOT . '/README.txt')) $archive->addFromFile(DOCROOT . '/README.txt', 'README.txt');
			if(is_file(DOCROOT . '/LICENCE.txt')) $archive->addFromFile(DOCROOT . '/LICENCE.txt', 'LICENCE.txt');
			if(is_file(DOCROOT . '/update.php')) $archive->addFromFile(DOCROOT . '/update.php', 'update.php');
			
			$archive->addFromString($install_template, 'install.php');
						
			$raw = $archive->save();

			header('Content-type: application/octet-stream');	
			header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		    header('Content-disposition: attachment; filename='.Lang::createFilename($this->Configuration->get('sitename', 'general')).'-ensemble.zip');
		    header('Pragma: no-cache');

			print $raw;
			exit();
			
		}	*/	
	}

