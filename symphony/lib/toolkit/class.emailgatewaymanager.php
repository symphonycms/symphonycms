<?php

	/**
	 * @package toolkit
	 */

	/**
	 * A manager to standardize the finding and listing of installed gateways.
	 */

	require_once(TOOLKIT . '/class.emailgateway.php');
	require_once(FACE . '/interface.fileresource.php');

	Class EmailGatewayManager implements FileResource {

		/**
		 * The default gateway to use when one is not provided. This value can
		 * be overridden with the `setDefaultGateway` function. Defaults to 'sendmail'.
		 *
		 * @see setDefaultGateway()
		 * @var string
		 */
		protected static $_default_gateway = 'sendmail';

		/**
		 * Sets the default gateway.
		 * Will throw an exception if the gateway can not be found.
		 *
		 * @throws EmailGatewayException
		 * @param string $name
		 * @return void
		 */
		public static function setDefaultGateway($name){
			if(self::__getClassPath($name)){
				Symphony::Configuration()->set('default_gateway', $name, 'Email');
				Symphony::Configuration()->write();
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
		public static function getDefaultGateway(){
			$gateway = Symphony::Configuration()->get('default_gateway', 'Email');
			if($gateway){
				return $gateway;
			}
			else{
				return self::$_default_gateway;
			}
		}

		/**
		 * Returns the classname from the gateway name.
		 * Does not check if the gateway exists.
		 *
		 * @param string $name
		 * @return string
		 */
		public static function __getClassName($name){
			return $name . 'Gateway';
		}

		/**
		 * Finds the gateway by name
		 *
		 * @param string $name
		 *  The gateway to look for
		 * @return string|boolean
		 *  If the gateway is found, the path to the folder containing the
		 *  gateway is returned.
		 *  If the gateway is not found, false is returned.
		 */
		public static function __getClassPath($name){
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
		 * Returns the path to the gateway file.
		 *
		 * @param string $name
		 * 	The gateway to look for
		 * @return string|boolean
		 * @todo fix return if gateway does not exist.
		 */
		public static function __getDriverPath($name){
			return self::__getClassPath($name) . "/email.$name.php";
		}

		/**
		 * Finds the name from the filename.
		 * Does not check if the gateway exists.
		 *
		 * @param string $filename
		 * @return string|boolean
		 */
		public static function __getHandleFromFilename($filename){
			return preg_replace(array('/^email./i', '/.php$/i'), '', $filename);
		}

		/**
		 * Returns an array of all gateways.
		 * Each item in the array will contain the return value of the about()
		 * function of each gateway.
		 *
		 * @return array
		 */
		public static function listAll(){
			$result = array();

			$structure = General::listStructure(EMAILGATEWAYS, '/email.[\\w-]+.php/', false, 'ASC', EMAILGATEWAYS);

			if(is_array($structure['filelist']) && !empty($structure['filelist'])){
				foreach($structure['filelist'] as $f){
					$f = str_replace(array('email.', '.php'), '', $f);
					$result[$f] = self::about($f);
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
							$result[$f] = self::about($f);
						}
					}
				}
			}

			ksort($result);
			return $result;
		}

		public static function about($name) {
			$name = strtolower($name);
			$classname = self::__getClassName($name);
			$path = self::__getDriverPath($name);

			if(!@file_exists($path)) return false;

			require_once($path);

			$handle = self::__getHandleFromFilename(basename($path));

			if(is_callable(array($classname, 'about'))){
				$about = call_user_func(array($classname, 'about'));
				return array_merge($about, array('handle' => $handle));
			}
		}

		/**
		 * Creates a new object from a gateway name.
		 *
		 * @param string $name
		 *  The gateway to look for
		 * @return EmailGateway
		 *  If the gateway is found, an instantiated object is returned.
		 *  If the gateway is not found, an error is triggered.
		 */
		public static function create($name){
			$name = strtolower($name);
			$classname = self::__getClassName($name);
			$path = self::__getDriverPath($name);

			if(!is_file($path)){
				throw new Exception(
					__('Could not find Email Gateway %s.', array('<code>' . $name . '</code>'))
					. ' ' . __('If it was provided by an Extension, ensure that it is installed, and enabled.')
				);
			}

			if(!class_exists($classname))
				require_once($path);

			return new $classname;
		}

	}
