<?php
	/**
	 * @package content
	 */
	/**
	 * The AjaxReorder page is used for reordering objects in the Symphony
	 * backend through Javascript.
	 */
	Class contentAjaxReorder extends AjaxPage{

		const kREORDER_PAGES = 0;
		const kREORDER_SECTIONS = 1;
		const kREORDER_EXTENSION = 2;
		const kREORDER_UNKNOWN = 3;

		public function view(){

			$destination = self::kREORDER_UNKNOWN;

			if($this->_context[0] == 'blueprints' && $this->_context[1] == 'pages') $destination = self::kREORDER_PAGES;
			elseif($this->_context[0] == 'blueprints' && $this->_context[1] == 'sections') $destination = self::kREORDER_SECTIONS;
			elseif($this->_context[0] == 'extensions') $destination = self::kREORDER_EXTENSION;

			$items = $_REQUEST['items'];

			if(!is_array($items) || empty($items)) return;

			switch($destination){
				case self::kREORDER_PAGES:
					foreach($items as $id => $position) {
						if(!Symphony::Database()->query("UPDATE `tbl_pages` SET `sortorder` = '$position' WHERE `id` = '$id' LIMIT 1")){
							$this->_status = self::STATUS_ERROR;
							$this->_Result->setValue(__('A database error occurred while attempting to reorder.'));
							break;
						}

					}
					break;

				case self::kREORDER_SECTIONS:
					foreach($items as $id => $position) {
						if(!Symphony::Database()->query("UPDATE `tbl_sections` SET `sortorder` = '$position' WHERE `id` = '$id' LIMIT 1")){
							$this->_status = self::STATUS_ERROR;
							$this->_Result->setValue(__('A database error occurred while attempting to reorder.'));
							break;
						}
					}
					break;

				case self::kREORDER_EXTENSION:
					## TODO
					break;

				case self::kREORDER_UNKNOWN:
				default:
					$this->_status = self::STATUS_BAD;
					break;

			}

		}

	}
