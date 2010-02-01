<?php

	Class contentAjaxTranslate extends AjaxPage{

		function __construct(&$parent){
			$this->_Parent = $parent;
			$this->_status = self::STATUS_OK;
			$this->addHeaderToPage('Content-Type', 'application/json');
			$this->_Parent->Profiler->sample('Page template created', PROFILE_LAP);	
		}
		
		public function handleFailedAuthorisation(){
			$this->_status = self::STATUS_UNAUTHORISED;
			$this->_Result = json_encode(array('status' => __('You are not authorised to access this page.')));
		}

		public function view(){
			$strings = $_POST['language'];
			foreach($strings as $id => $string) {
				$strings[$id] = __($string);
			}
			$this->_Result = json_encode($strings);	
		}
		
		public function generate(){
			echo $this->_Result;
			exit;	
		}
				
	}

