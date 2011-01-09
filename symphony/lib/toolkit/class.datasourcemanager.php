<?php
	/**
	 * @package toolkit
	 */
	/**
	 * The DatasourceManager class is responsible for managing all Datasource objects
	 * in Symphony. Datasources's are stored on the file system either in the
	 * `WORKSPACE . /data-sources` folder or provided by an extension in `EXTENSIONS . /./data-sources` folder.
	 * Datasources are run from the Frontend to commonly output Entries and parameters,
	 * however there any different types of Datasource that query external sources for data.
	 * Typically, a Datasource returns XML.
	 */

	require_once(TOOLKIT . '/class.datasource.php');
	require_once(TOOLKIT . '/class.manager.php');

    Class DatasourceManager extends Manager{

		/**
		 * Given the filename of a Datasource return it's handle. This will remove
		 * the Symphony convention of `data.*.php`
		 *
		 * @param string $filename
		 *  The filename of the Datasource
		 * @return string
		 */
		public function __getHandleFromFilename($filename){
			$filename = preg_replace(array('/^data./i', '/.php$/i'), '', $filename);
			return $filename;
		}

		/**
		 * Given a name, returns the full class name of an Datasources. Datasources
		 * use a 'datasource' prefix.
		 *
		 * @param string $handle
		 *  The Datasource handle
		 * @return string
		 */
		public function __getClassName($handle){
			return 'datasource' . $handle;
		}

		/**
		 * Finds a Datasource by name by searching the data-sources folder in the
		 * workspace and in all installed extension folders and returns the path
		 * to it's folder.
		 *
		 * @param string $handle
		 *  The handle of the Datasource free from any Symphony conventions
		 *  such as `data.*.php`
		 * @return mixed
		 *  If the datasource is found, the function returns the path it's folder, otherwise false.
		 */
		public function __getClassPath($handle){
			if(is_file(DATASOURCES . "/data.$handle.php")) return DATASOURCES;
			else{
				$extensions = Symphony::ExtensionManager()->listInstalledHandles();

				if(is_array($extensions) && !empty($extensions)){
					foreach($extensions as $e){
						if(is_file(EXTENSIONS . "/$e/data-sources/data.$handle.php")) return EXTENSIONS . "/$e/data-sources";
					}
				}
			}

			return false;
		}

		/**
		 * Given a name, return the path to the Datasource class
		 *
		 * @see DatasourceManager::__getClassPath()
		 * @param string $handle
		 *  The handle of the Datasource free from any Symphony conventions
		 *  such as `data.*.php`
		 * @return string
		 */
		public function __getDriverPath($handle){
			return $this->__getClassPath($handle) . "/data.$handle.php";
		}

		/**
		 * Finds all available Datasources by searching the data-sources folder in
		 * the workspace and in all installed extension folders. Returns an
		 * associative array of data-sources.
		 *
		 * @see toolkit.Manager#about()
		 * @return array
		 *  Associative array of Datasources with the key being the handle of the
		 *  Datasource and the value being the Datasource's `about()` information.
		 */
		public function listAll(){

			$result = array();

			$structure = General::listStructure(DATASOURCES, '/data.[\\w-]+.php/', false, 'ASC', DATASOURCES);

			if(is_array($structure['filelist']) && !empty($structure['filelist'])){
				foreach($structure['filelist'] as $f){
					$f = self::__getHandleFromFilename($f);

					if($about = $this->about($f)){

						$classname = $this->__getClassName($f);
						$path = $this->__getDriverPath($f);

						$can_parse = false;
						$type = null;

						if(method_exists($classname,'allowEditorToParse')) {
							$can_parse = call_user_func(array($classname, 'allowEditorToParse'));
						}

						if(method_exists($classname,'getSource')) {
							$type = call_user_func(array($classname, 'getSource'));
						}

						$about['can_parse'] = $can_parse;
						$about['type'] = $type;
						$result[$f] = $about;
					}
				}
			}

			$extensions = Symphony::ExtensionManager()->listInstalledHandles();

			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $e){
					if(!is_dir(EXTENSIONS . "/$e/data-sources")) continue;

					$tmp = General::listStructure(EXTENSIONS . "/$e/data-sources", '/data.[\\w-]+.php/', false, 'ASC', EXTENSIONS . "/$e/data-sources");

					if(is_array($tmp['filelist']) && !empty($tmp['filelist'])){
						foreach($tmp['filelist'] as $f){
							$f = self::__getHandleFromFilename($f);

							if($about = $this->about($f)){
								$about['can_parse'] = false;
								$about['type'] = null;
								$result[$f] = $about;
							}
						}
					}
				}
			}

			ksort($result);
			return $result;
		}

		/**
		 * Creates an instance of a given class and returns it.
		 *
		 * @param string $handle
		 *  The handle of the Datasource to create
		 * @param array $env
		 *  The environment variables from the Frontend class which includes
		 *  any params set by Symphony or Events or by other Datasources
		 * @param boolean $process_params
		 * @return Datasource
		 */
		public function &create($handle, Array $env = null, $process_params=true){

			$classname = $this->__getClassName($handle);
			$path = $this->__getDriverPath($handle);

			if(!is_file($path)){
				throw new Exception(
					__(
						'Could not find Data Source <code>%s</code>. If the Data Source was provided by an Extension, ensure that it is installed, and enabled.',
						array($handle)
					)
				);
			}

			if(!class_exists($classname)) require_once($path);

			return new $classname($this->_Parent, $env, $process_params);

		}

    }
