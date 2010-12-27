<?php
	/**
	 * @package content
	 */
	/**
	 * The AjaxTranslate page is used for translating strings on the fly
	 * that are used in Symphony's javascript
	 */
	Class contentAjaxTranslate extends AjaxPage{

		public function handleFailedAuthorisation(){
			$this->_status = self::STATUS_UNAUTHORISED;
			$this->_Result = json_encode(array('status' => __('You are not authorised to access this page.')));
		}

		public function view(){
			$strings = $_GET;
			$new = array();
			foreach($strings as $id => $string) {
				if($id == 'mode' || $id == 'symphony-page') continue;
				$string = urldecode($string);
				$new[$string] = __($string);
			}
			$this->_Result = json_encode($new);
		}

		public function generate(){
			header('Content-Type: application/json');
			echo $this->_Result;
			exit;
		}

	}

