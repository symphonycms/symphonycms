<?php

	Class contentAjaxReorder extends AjaxPage{

		const kREORDER_PAGES = 0;
		const kREORDER_SECTIONS = 1;
		const kREORDER_EXTENSION = 2;
		const kREORDER_UNKNOWN = 3;

		public function view(){

			$destination = self::kREORDER_UNKNOWN;

			if($this->_context[0] == 'blueprints' && $this->_context[1] == 'pages') $destination = self::kREORDER_PAGES;

			$items = $_REQUEST['items'];

			if(!is_array($items) || empty($items)) return;

			switch($destination){

				case self::kREORDER_SECTIONS:
					foreach($items as $id => $position) {
						if(!Symphony::Database()->update('tbl_sections', array('sortorder' => $postion), array($id), "`id` = %d LIMIT 1")){
							$this->_status = self::STATUS_ERROR;
							$this->_Result->setValue(__('A database error occurred while attempting to reorder.'));
							break;
						}
					}
					break;

				case self::kREORDER_UNKNOWN:
				default:
					$this->_status = self::STATUS_BAD;
					break;

			}

		}

	}
