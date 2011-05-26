<?php

	if(!function_exists('__processNavigationParentFilter')){
		function __processNavigationParentFilter($parent){

			$parent_paths = preg_split('/,\s*/', $parent, -1, PREG_SPLIT_NO_EMPTY);
			$parent_paths = array_map(create_function('$a', 'return trim($a, " /");'), $parent_paths);

			return (is_array($parent_paths) && !empty($parent_paths) ? " AND p.`path` IN ('".implode("', '", $parent_paths)."')" : null);
		}
	}

	if(!function_exists('__processNavigationTypeFilter')){
		function __processNavigationTypeFilter($filter, $filter_type = DS_FILTER_OR) {
			$types = preg_split('/'.($filter_type == DS_FILTER_AND ? '\+' : '(?<!\\\\),').'\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);
			$types = array_map('trim', $types);

			$types = array_map(array('Datasource', 'removeEscapedCommas'), $types);

			if($filter_type == DS_FILTER_OR) {
				$type_sql = " AND pt.type IN ('" . implode("', '", $types) . "')";
			}
			else {
				foreach($types as $type) {
					$type_sql = " AND pt.type = '" . $type . "'";
				}
			}

			return $type_sql;
		}
	}

	if(!function_exists('__buildPageXML')){
		function __buildPageXML($page){

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
				foreach($children as $c) $oPage->appendChild(__buildPageXML($c));
			}

			return $oPage;
		}
	}

	### BEGIN XML GENERATION CODE ###

	$result = new XMLElement($this->dsParamROOTELEMENT);
	$type_sql = $parent_sql = null;

	if(trim($this->dsParamFILTERS['type']) != '') {
		$type_sql = __processNavigationTypeFilter($this->dsParamFILTERS['type'], $this->__determineFilterType($this->dsParamFILTERS['type']));
	}

	if(trim($this->dsParamFILTERS['parent']) != '') {
		$parent_sql = __processNavigationParentFilter($this->dsParamFILTERS['parent']);
	}

	// Build the Query appending the Parent and/or Type WHERE clauses
	$pages = Symphony::Database()->fetch(sprintf("
			SELECT DISTINCT p.*
			FROM `tbl_pages` AS p
			LEFT JOIN `tbl_pages_types` AS pt ON (p.id = pt.page_id)
			WHERE 1 = 1
			%s
			%s
			ORDER BY p.`sortorder` ASC
		",
		// Add Parent SQL
		!is_null($parent_sql) ? $parent_sql : " AND p.parent IS NULL ",
		// Add Types SQL
		!is_null($type_sql) ? $type_sql : ""
	));

	if((!is_array($pages) || empty($pages))){
		if($this->dsParamREDIRECTONEMPTY == 'yes'){
			throw new FrontendPageNotFoundException;
		}
		$result->appendChild($this->__noRecordsFound());
	}

	else {
		foreach($pages as $page) $result->appendChild(__buildPageXML($page));
	}
