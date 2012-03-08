<?php
	/**
	 * @package content
	 */
	/**
	 * The AjaxHandle page is used for generating handles on the fly
	 * that are used in Symphony's javascript
	 */
	Class contentAjaxHandle extends AjaxPage{

		public function handleFailedAuthorisation(){
			$this->_status = self::STATUS_UNAUTHORISED;
			$this->_Result = json_encode(array('status' => __('You are not authorised to access this page.')));
		}

		public function view(){
			$string = $_GET['string'];

			$this->_Result = json_encode(Lang::createHandle($string, 255, '-', true));
		}

		public function generate(){
			header('Content-Type: application/json');
			echo $this->_Result;
			exit;
		}

	}

