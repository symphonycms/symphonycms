<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	Class fieldDate extends Field{

		const SIMPLE = 0;
		const REGEXP = 1;
		const RANGE = 3;
		const ERROR = 4;
		
		private $key;
		
		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'Date';
			$this->key = 1;
		}

		function allowDatasourceOutputGrouping(){
			return true;
		}
		
		function allowDatasourceParamOutput(){
			return true;
		}		
		
		function canFilter(){
			return true;
		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){

			$timestamp = NULL;
			
			//$obDate = $Admin->getDateObj();
			//$obDate->setFormat('d M Y H:i');
			
			## For new entries, the value will be a formatted date. Otherwise it will be a timestamp
			if($data) {
				$value = $data['gmt'];
				$timestamp = (!is_numeric($value) ? strtotime($value) : $value);
				//$obDate->set((!is_numeric($value) ? strtotime($value) : $value), false);
			}
			
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, ($data || $this->get('pre_populate') == 'yes' ? DateTimeObj::get(__SYM_DATETIME_FORMAT__, $timestamp) : NULL)));
			
			$label->setAttribute('class', 'date');
			
			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);
			
		}
		
		function checkPostFieldData($data, &$message, $entry_id=NULL){

			if(empty($data)) return self::__OK__; 

			$message = NULL;

			if(!self::__isValidDateString($data)){
				$message = "The date specified in '". $this->get('label')."' is invalid.";
				return self::__INVALID_FIELDS__;
			}
			
			return self::__OK__;		
		}
			
		function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){
			
			$status = self::__OK__;
			$timestamp = NULL;
			
			if($data != '') $timestamp = strtotime($data);
							
			return array(
				'value' => DateTimeObj::get('c', $timestamp),
				'local' => strtotime(DateTimeObj::get('c', $timestamp)),
				'gmt' => strtotime(DateTimeObj::getGMT('c', $timestamp))			
			);
		}
		
		function appendFormattedElement(&$wrapper, $data, $encode=false){
			$wrapper->appendChild(General::createXMLDateObject($data['local'], $this->get('element_name')));
		}

		function prepareTableValue($data, XMLElement $link=NULL){
			return parent::prepareTableValue(array('value' => DateTimeObj::get(__SYM_DATE_FORMAT__, $data['local'])), $link);
		}		
			
		function groupRecords($records){

			if(!is_array($records) || empty($records)) return;

			$groups = array('year' => array());

			foreach($records as $r){
				$data = $r->getData($this->get('id'));
				
				$info = getdate($data['local']);
				
				$year = $info['year'];
				$month = ($info['mon'] < 10 ? '0' . $info['mon'] : $info['mon']);
				
				if(!isset($groups['year'][$year])) $groups['year'][$year] = array('attr' => array('value' => $year),
																				  'records' => array(), 
																				  'groups' => array());
				
				if(!isset($groups['year'][$year]['groups']['month'])) $groups['year'][$year]['groups']['month'] = array();
				
				if(!isset($groups['year'][$year]['groups']['month'][$month])) $groups['year'][$year]['groups']['month'][$month] = array('attr' => array('value' => $month),
																				  					  'records' => array(), 
																				  					  'groups' => array());		
																						

				$groups['year'][$year]['groups']['month'][$month]['records'][] = $r;

			}

			return $groups;

		}


		function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			$joins .= "INNER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$sort = 'ORDER BY ' . (strtolower($order) == 'random' ? 'RAND()' : "`ed`.`gmt` $order");
		}

		function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation=false){
			
			$parsed = array();

			foreach($data as $string){
				$type = self::__parseFilter($string);
				
				if($type == self::ERROR) return false;
				
				if(!is_array($parsed[$type])) $parsed[$type] = array();
				
				$parsed[$type][] = $string;
			}

			foreach($parsed as $type => $data){
				
				switch($type){
				
					case self::RANGE:
						$this->__buildRangeFilterSQL($data, $joins, $where, $andOperation);
						break;
				
					case self::SIMPLE:
						$this->__buildSimpleFilterSQL($data, $joins, $where, $andOperation);
						break;
								
				}
			}
			
			return true;
		}
		
		private function __buildSimpleFilterSQL($data, &$joins, &$where, $andOperation=false){
			
			$field_id = $this->get('id');
			
			if($andOperation):
			
				foreach($data as $date){
					$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
					$where .= " AND DATE_FORMAT(`t$field_id".$this->key."`.value, '%Y-%m-%d') = '".DateTimeObj::get('Y-m-d', strtotime($date))."' ";
					
					$this->key++;
				}
							
			else:
				
				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
				$where .= " AND DATE_FORMAT(`t$field_id".$this->key."`.value, '%Y-%m-%d') IN ('".@implode("', '", $data)."') ";
				$this->key++;
				
			endif;			
			
			
		}
		
		private function __buildRangeFilterSQL($data, &$joins, &$where, $andOperation=false){	
			
			$field_id = $this->get('id');
			
			if(empty($data)) return;
			
			if($andOperation):
				
				foreach($data as $date){
					$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
					$where .= " AND (DATE_FORMAT(`t$field_id".$this->key."`.value, '%Y-%m-%d') >= '".DateTimeObj::get('Y-m-d', strtotime($date['start']))."' 
								     AND DATE_FORMAT(`t$field_id".$this->key."`.value, '%Y-%m-%d') <= '".DateTimeObj::get('Y-m-d', strtotime($date['end']))."') ";
								
					$this->key++;
				}
							
			else:

				$tmp = array();
				
				foreach($data as $date){
					
					$tmp[] = "(DATE_FORMAT(`t$field_id".$this->key."`.value, '%Y-%m-%d') >= '".DateTimeObj::get('Y-m-d', strtotime($date['start']))."' 
								     AND DATE_FORMAT(`t$field_id".$this->key."`.value, '%Y-%m-%d') <= '".DateTimeObj::get('Y-m-d', strtotime($date['end']))."') ";
				}
				
				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
				$where .= " AND (".@implode(' OR ', $tmp).") ";
				
				$this->key++;
						
			endif;			
			
		}
		
		private static function __cleanFilterString($string){
			$string = trim($string);
			$string = trim($string, '-/');
			
			return $string;
		}
		
		private static function __parseFilter(&$string){
			
			$string = self::__cleanFilterString($string);
			
			## Check its not a regexp
			if(preg_match('/^regexp:/i', $string)){
				$string = str_replace('regexp:', '', $string);
				return self::REGEXP;
			}
			
			## Look to see if its a shorthand date (year only), and convert to full date
			elseif(preg_match('/^(1|2)\d{3}$/i', $string)){
				$string = "$string-01-01 to $string-12-31";
			}	
			
			## Look to see if its a shorthand date (year and month), and convert to full date
			elseif(preg_match('/^(1|2)\d{3}[-\/]\d{1,2}$/i', $string)){
				
				$start = "$string-01";
				
				if(!self::__isValidDateString($start)) return self::ERROR;
				
				$string = "$start to $string-" . date('t', strtotime($start));
			}
					
			## Match for a simple date (Y-m-d), check its ok using checkdate() and go no further
			elseif(!preg_match('/to/i', $string)){

				if(!self::__isValidDateString($string)) return self::ERROR;
				
				$string = DateTimeObj::get('Y-m-d', strtotime($string));
				return self::SIMPLE;
				
			}
		
			## Parse the full date range and return an array
			
			if(!$parts = preg_split('/to/', $string, 2, PREG_SPLIT_NO_EMPTY)) return self::ERROR;
			
			$parts = array_map(array('self', '__cleanFilterString'), $parts);

			list($start, $end) = $parts;
			
			if(!self::__isValidDateString($start) || !self::__isValidDateString($end)) return self::ERROR;
			
			$string = array('start' => $start, 'end' => $end);

			return self::RANGE;
		}
		
		private static function __isValidDateString($string){
			
			$string = trim($string);
			
			if(empty($string)) return false;
			
			## Its not a valid date, so just return it as is
			if(!$info = getdate($string)) return false;
			elseif(!checkdate($info['mon'], $info['mday'], $info['year'])) return false;

			return true;	
		}
		
		function isSortable(){
			return true;
		}		

		function commit(){
			
			if(!parent::commit()) return false;
			
			$id = $this->get('id');

			if($id === false) return false;	
			
			$fields = array();

			$fields['field_id'] = $id;
			$fields['pre_populate'] = ($this->get('pre_populate') ? $this->get('pre_populate') : 'no');
			$fields['calendar'] = ($this->get('calendar') ? $this->get('calendar') : 'no');
			
			$this->_engine->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");			
			$this->_engine->Database->insert($fields, 'tbl_fields_' . $this->handle());
					
		}
		
		function findDefaults(&$fields){	
			if(!isset($fields['pre_populate'])) $fields['pre_populate'] = 'yes';
			if(!isset($fields['calendar'])) $fields['calendar'] = 'no';
		}
		
		function displaySettingsPanel(&$wrapper){
			
			parent::displaySettingsPanel($wrapper);

			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][pre_populate]', 'yes', 'checkbox');
			if($this->get('pre_populate') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' Pre-populate this field with today\'s date');
			$wrapper->appendChild($label);		
			
			/*								
			
			## Display as Calendar
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][calendar]', 'yes', 'checkbox');
			if($this->get('calendar') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' Display calendar');
			$wrapper->appendChild($label);*/
			
			$this->appendShowColumnCheckbox($wrapper);
						
		}

		function createTable(){
			
			return $this->_engine->Database->query(
			
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `value` varchar(80) default NULL,
				  `local` int(11) NOT NULL,
				  `gmt` int(11) NOT NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `value` (`value`)
				) TYPE=MyISAM;"
			
			);
		}

	}

