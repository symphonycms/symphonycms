<?php
	/**
	 * @package content
	 */
	/**
	 * The AjaxParameters returns an JSON array of all available Data Source output parameters.
	 */
	require_once(TOOLKIT . '/class.datasourcemanager.php');

	Class contentAjaxParameters extends AjaxPage {

		public function view() {
			$params = array();
			$datasources = DatasourceManager::listAll();
			
			// Get Data Sources
			foreach($datasources as $datasource) {
				$current = DatasourceManager::create($datasource['handle']);
				$prefix = '{$ds-' . $datasource['handle'] . '.';
				$suffix = '}';
				
				// Get parameters
				if(is_array($current->dsParamPARAMOUTPUT)) {
					foreach($current->dsParamPARAMOUTPUT as $id => $param) {
						$params[] = $prefix . $param . $suffix;
					}
				}
			}
			
			$this->_Result = json_encode($params);
		}

		public function generate(){
			header('Content-Type: application/json');
			echo $this->_Result;
			exit;
		}

	}
