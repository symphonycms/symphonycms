<?php
	/**
	 * @package content
	 */
	/**
	 * The AjaxSections page return an object of all sections and their fields
	 * that are available for pre-population
	 */
	
	require_once(TOOLKIT . '/class.jsonpage.php');
	
	Class contentAjaxFilters extends JSONPage{
		
		public function view(){
			$field_id = General::sanitize($_GET['field-id']);
			$options = array();

			if(!empty($field_id)) {
				$field = FieldManager::fetch($field_id);

				if(!empty($field)) {
					if($field->canFilter() == true) {
						if(method_exists($field, 'getToggleStates')) {
							$options = $field->getToggleStates();
						}
						elseif(method_exists($field, 'findAllTags')) {
							$options = $field->findAllTags();
						}
					}
				}
			}

			$this->_Result['filters'] = $options;
		}
	}
