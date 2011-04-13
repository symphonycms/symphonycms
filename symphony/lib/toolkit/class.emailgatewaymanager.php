<?php

	/**
	 * @package toolkit
	 */

	/**
	 * A manager to standardize the finding and listing of installed gateways.
	 */

	require_once(TOOLKIT . '/class.emailgateway.php');
	require_once(TOOLKIT . '/class.manager.php');

	Class EmailGatewayManager extends Manager{

		protected $_default_gateway = 'sendmail';

		public function __construct() {}

		/**
		 * Sets the default gateway.
		 * Will throw an exception if the gateway can not be found.
		 *
		 * @param string $name
		 * @return void
		 */
		public function setDefaultGateway($name){
			if($this->__find($name)){
				Symphony::Configuration()->set('default_gateway', $name, 'Email');
				Administration::instance()->saveConfig();
			}
			else{
				throw new EmailGatewayException(__('This gateway can not be found. Can not save as default.'));
			}
		}

		/**
		 * Returns the default gateway.
		 * Will throw an exception if the gateway can not be found.
		 *
		 * @return string
		 */
		public function getDefaultGateway(){
			$gateway = Symphony::Configuration()->get('default_gateway', 'Email');
			if($gateway){
				return $gateway;
			}
			else{
				return $this->_default_gateway;
			}
		}

		/**
		 * Finds the gateway by name
		 *
		 * @param string $name
		 * 	The gateway to look for
		 * @return string|boolean
		 *	If the gateway is found, the path to the folder containing the 
		 *  gateway is returned.
		 *	If the gateway is not found, false is returned.
		 */
		public function __find($name){

			if(is_file(EMAILGATEWAYS . "/email.$name.php")) return EMAILGATEWAYS;
			else{

				$extensions = Symphony::ExtensionManager()->listInstalledHandles();

				if(is_array($extensions) && !empty($extensions)){
					foreach($extensions as $e){
						if(is_file(EXTENSIONS . "/$e/email-gateways/email.$name.php")) return EXTENSIONS . "/$e/email-gateways";
					}
				}
			}

			return false;
		}

		/**
		 * Returns the classname from the gateway name.
		 * Does not check if the gateway exists.
		 *
		 * @param string $name
		 * @return string
		 */
		public function __getClassName($name){
			return $name . 'Gateway';
		}

		/**
		 * Alias for __find
		 *
		 * @param string $name
		 * @return string|boolean
		 */
		public function __getClassPath($name){
			return $this->__find($name);
		}

		/**
		 * Returns the path to the gateway file.
		 *
		 * @param string $name
		 * 	The gateway to look for
		 * @return string|boolean
		 * @todo fix return if gateway does not exist.
		 */
		public function __getDriverPath($name){
			return $this->__getClassPath($name) . "/email.$name.php";
		}

		/**
		 * Finds the name from the filename.
		 * Does not check if the gateway exists.
		 *
		 * @param string $filename
		 * @return string|boolean
		 */
		public function __getHandleFromFilename($filename){
			return preg_replace(array('/^email./i', '/.php$/i'), '', $filename);
		}

		/**
		 * Returns an array of all gateways.
		 * Each item in the array will contain the return value of the about() 
		 * function of each gateway.
		 *
		 * @return array
		 */
		public function listAll(){

			$result = array();
			$people = array();

			$structure = General::listStructure(EMAILGATEWAYS, '/email.[\\w-]+.php/', false, 'ASC', EMAILGATEWAYS);

			if(is_array($structure['filelist']) && !empty($structure['filelist'])){
				foreach($structure['filelist'] as $f){
					$f = str_replace(array('email.', '.php'), '', $f);
					$result[$f] = $this->about($f);
				}
			}

			$extensions = Symphony::ExtensionManager()->listInstalledHandles();

			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $e){

					if(!is_dir(EXTENSIONS . "/$e/email-gateways")) continue;

					$tmp = General::listStructure(EXTENSIONS . "/$e/email-gateways", '/email.[\\w-]+.php/', false, 'ASC', EXTENSIONS . "/$e/email-gateways");

					if(is_array($tmp['filelist']) && !empty($tmp['filelist'])){
						foreach($tmp['filelist'] as $f){
							$f = preg_replace(array('/^email./i', '/.php$/i'), '', $f);
							$result[$f] = $this->about($f);
						}
					}
				}
			}

			ksort($result);
			return $result;
		}

		/**
		 * Creates a new object from a gateway name.
		 *
		 * @param string $name
		 * 	The gateway to look for
		 * @return EmailGateway
		 *	If the gateway is found, an instantiated object is returned.
		 *	If the gateway is not found, an error is triggered.
		 */
		public function &create($name){

			$classname = $this->__getClassName($name);
			$path = $this->__getDriverPath($name);

			if(!is_file($path)){
				trigger_error(__('Could not find Email Gateway <code>%s</code>. If the Email Gateway was provided by an Extensions, ensure that it is installed, and enabled.', array($name)), E_USER_ERROR);
				return false;
			}

			if(!@class_exists($classname))
				require_once($path);

			return new $classname;

		}

	}
