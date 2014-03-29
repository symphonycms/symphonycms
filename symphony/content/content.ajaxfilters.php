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
			$handle = General::sanitize($_GET['handle']);
			$section = General::sanitize($_GET['section']);
			$options = array();

 			if(!empty($handle) && !empty($section)) {
				$section_id = SectionManager::fetchIDFromHandle($section);
				$field_id = FieldManager::fetchFieldIDFromElementName($handle, $section_id);
				$field = FieldManager::fetch($field_id);

				if(!empty($field) && $field->canPublishFilter() === true) {
					if(method_exists($field, 'getToggleStates')) {
						$options = $field->getToggleStates();
					}
					elseif(method_exists($field, 'findAllTags')) {
						$options = $field->findAllTags();
					}
				}
			}

			$this->_Result['filters'] = $options;
		}
	}
