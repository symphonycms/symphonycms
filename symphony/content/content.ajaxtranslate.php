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
			$strings = $_GET['strings'];

			$new = array();
			foreach($strings as $key => $value) {
				if(is_array($value)) {

					// Namespace found
					foreach($value as $key_n => $value_n) {
						$value_n = urldecode($value_n);
						$new[$key][$value_n] = Lang::translate($value_n, NULL, $key);
					 }

				} else {
					$value = urldecode($value);
					$new[$value] = __($value);
				}
			}
			$this->_Result = json_encode($new);
		}

		public function generate(){
			header('Content-Type: application/json');
			echo $this->_Result;
			exit;
		}

	}

