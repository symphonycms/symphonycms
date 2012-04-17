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
			$namespace = (empty($_GET['namespace']) ? null : $_GET['namespace']);

			$new = array();
			foreach($strings as $key => $value) {
			
				// Check value
				$value = urldecode($value);
				if(empty($value)) {
					$value = urldecode($key);
				}
				
				// Translate
				$new[$value] = Lang::translate($value, null, $namespace);
			}
			$this->_Result = json_encode($new);
		}

		public function generate(){
			header('Content-Type: application/json');
			echo $this->_Result;
			exit;
		}

	}

