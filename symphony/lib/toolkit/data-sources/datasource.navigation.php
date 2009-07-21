<?php

	if(!function_exists('__processNavigationParentFilter')){
		function __processNavigationParentFilter($parent){
			
			$parent_paths = preg_split('/,\s*/', $parent, -1, PREG_SPLIT_NO_EMPTY);			
			$parent_paths = array_map(create_function('$a', 'return trim($a, " /");'), $parent_paths);

			return (is_array($parent_paths) && !empty($parent_paths) ? "`path` IN ('".implode("', '", $parent_paths)."')" : NULL);
		}
	}

	if(!function_exists('__processNavigationTypeFilter')){	
		function __processNavigationTypeFilter($filter, $database, $filtertype=DS_FILTER_OR){

			$types = preg_split('/'.($filtertype == DS_FILTER_AND ? '\+' : ',').'\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);			
			$types = array_map('trim', $types);

			switch($filtertype){
		
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
	}

	if(!function_exists('__buildPageXML')){
		function __buildPageXML($page, &$database){
	
			$oPage = new XMLElement('page');
			$oPage->setAttribute('handle', $page['handle']);
			$oPage->setAttribute('id', $page['id']);
			$oPage->appendChild(new XMLElement('name', General::sanitize($page['title'])));
	
			$types = $database->fetchCol('type', "SELECT `type` FROM `tbl_pages_types` WHERE `page_id` = '".$page['id']."'");
			if(is_array($types) && !empty($types)){
				$xTypes = new XMLElement('types');
				foreach($types as $type) $xTypes->appendChild(new XMLElement('type', $type));
				$oPage->appendChild($xTypes);
			}
			
			if($children = $database->fetch("SELECT * FROM `tbl_pages` WHERE `parent` = '".$page['id']."' ORDER BY `sortorder` ASC")){
				foreach($children as $c) $oPage->appendChild(__buildPageXML($c, $database));
			}
	
			return $oPage;
		}
	}
	
	### BEGIN XML GENERATION CODE ###
	
	$result = new XMLElement($this->dsParamROOTELEMENT);
		
	if(trim($this->dsParamFILTERS['type']) != '') 
		$types = __processNavigationTypeFilter($this->dsParamFILTERS['type'], $this->_Parent->Database, $this->__determineFilterType($this->dsParamFILTERS['type']));
	
	$sql = "SELECT * FROM `tbl_pages` 
			WHERE ".(NULL != ($parent_sql = __processNavigationParentFilter($this->dsParamFILTERS['parent'])) ? $parent_sql : '`parent` IS NULL') . "
			".($types != NULL ? " AND `id` IN ('" . @implode("', '", $types) . "') " : NULL) . "
		 	ORDER BY `sortorder` ASC";

	$pages = $this->_Parent->Database->fetch($sql);
	
	if((!is_array($pages) || empty($pages))){
		if($this->dsParamREDIRECTONEMPTY == 'yes') $this->__redirectToErrorPage();
		$result->appendChild($this->__noRecordsFound());
	}
	
	else foreach($pages as $p) $result->appendChild(__buildPageXML($p, $this->_Parent->Database));


?>