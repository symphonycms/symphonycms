<?php
	/*
	**	No DOMDocument or DBC integration has been done this class as yet 
	*/
	Class NavigationDataSource extends DataSource {
		
		public function __construct(){
			// Set Default Values
			$this->_about = new StdClass;
			$this->_parameters = (object)array(
				'root-element' => NULL,
				'parent' => NULL,
				'type' => NULL
			);
		}
		
		final public function type(){
			return 'ds_navigation';
		}
		
		public function template(){
			return EXTENSIONS . '/ds_navigation/templates/datasource.php';
		}
		
		protected function buildParentFilter($parent) {
			$parent_paths = preg_split('/,\s*/', $parent, -1, PREG_SPLIT_NO_EMPTY);			
			$parent_paths = array_map(create_function('$a', 'return trim($a, " /");'), $parent_paths);
			
			return (is_array($parent_paths) && !empty($parent_paths) ? "`path` IN ('".implode("', '", $parent_paths)."')" : NULL);
		}
		
		protected function buildTypeFilter($filter, $filtertype = DS_FILTER_OR) {
			$database = Symphony::Database();
			$types = preg_split('/'.($filtertype == DS_FILTER_AND ? '\+' : ',').'\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);			
			$types = array_map('trim', $types);
			
			switch ($filtertype) {
				case DS_FILTER_AND:
					$sql = "SELECT `a`.`id`
							FROM (
	
								SELECT `tbl_pages`.id, COUNT(`tbl_pages`.id) AS `count` 
								FROM  `tbl_pages`, `tbl_pages_types` 
								WHERE `tbl_pages_types`.type IN ('".implode("', '", $types)."')
								AND `tbl_pages`.`id` = `tbl_pages_types`.page_id
								GROUP BY `tbl_pages`.`id`
				
							) AS `a` 
							WHERE `a`.`count` >= " . count($types);
			
					break;
			
				case DS_FILTER_OR:
					$sql = "SELECT `page_id` AS `id` FROM `tbl_pages_types` WHERE `type` IN ('".implode("', '", $types)."')";		
					break;
		
			}
			
			$pages = $database->fetchCol('id', $sql);
			
			return (is_array($pages) && !empty($pages) ? $pages : NULL);
		}
		
		protected function buildPageXML($page) {
			$oPage = new XMLElement('page');
			$oPage->setAttribute('handle', $page['handle']);
			$oPage->setAttribute('id', $page['id']);
			$oPage->appendChild(new XMLElement('name', General::sanitize($page['title'])));
	
			$types = Symphony::Database()->fetchCol('type', "SELECT `type` FROM `tbl_pages_types` WHERE `page_id` = '".$page['id']."'");
			if(is_array($types) && !empty($types)){
				$xTypes = new XMLElement('types');
				foreach($types as $type) $xTypes->appendChild(new XMLElement('type', $type));
				$oPage->appendChild($xTypes);
			}
			
			if($children = Symphony::Database()->fetch("SELECT * FROM `tbl_pages` WHERE `parent` = '".$page['id']."' ORDER BY `sortorder` ASC")){
				foreach($children as $c) $oPage->appendChild($this->buildPageXML($c));
			}
	
			return $oPage;
		}
		
		public function render(Register &$ParameterOutput){
			throw new Exception('TODO: Fix navigation datasource template.');
			
			$result = new XMLElement($this->getRootElement());
			
			try {
				$result = new XMLElement($this->getRootElement());
				$filters = $this->getFilters();
				
				if (trim($filters['type']) != '') {
					$types = $this->buildTypeFilter(
						$filters['type'],
						$this->__determineFilterType($filters['type'])
					);
				}
				
				$sql = "SELECT * FROM `tbl_pages` 
						WHERE ".(NULL != ($parent_sql = $this->buildParentFilter($filters['parent'])) ? $parent_sql : '`parent` IS NULL') . "
						".($types != NULL ? " AND `id` IN ('" . @implode("', '", $types) . "') " : NULL) . "
					 	ORDER BY `sortorder` ASC";
				
				$pages = Symphony::Database()->fetch($sql);
				
				if((!is_array($pages) || empty($pages))){
					if ($this->canRedirectOnEmpty()) {
						throw new FrontendPageNotFoundException;
					}
					
					$result->appendChild($this->__noRecordsFound());
				}
				
				else foreach($pages as $p) $result->appendChild($this->buildPageXML($p));
			}
			
			catch (FrontendPageNotFoundException $error) {
				FrontendPageNotFoundExceptionHandler::render($error);
			}
			
			catch (Exception $error) {
				$result->appendChild(new XMLElement(
					'error', General::sanitize($error->getMessage())
				));
			}	
			
			return $result;
		}
	}
