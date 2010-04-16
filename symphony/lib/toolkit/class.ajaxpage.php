<?php

	require_once(TOOLKIT . '/class.page.php');
	
	Abstract Class AjaxPage extends Page{
		
		const STATUS_OK = 200;
		const STATUS_BAD = 400;
		const STATUS_UNAUTHORISED = 401;
		const STATUS_ERROR = 400;		
		
		protected $_Parent;
		protected $_Result;
		protected $_status;
		
		abstract public function view();
		
		function __construct(&$parent){
			
			$this->_Parent = $parent;
			
			$this->_Result = $this->createElement('result');
			$this->_Result->setIncludeHeader(true);
			
			$this->_status = self::STATUS_OK;
			
			$this->addHeaderToPage('Content-Type', 'text/xml');

			Administration::instance()->Profiler->sample('Page template created', PROFILE_LAP);	
		}
		
		public function build($context=NULL){
			if($context) $this->_context = $context;
			$this->view();
		}
		
		public function handleFailedAuthorisation(){
			$this->_status = self::STATUS_UNAUTHORISED;
			$this->_Result->setValue(__('You are not authorised to access this page.'));
		}
		
		public function generate(){

			switch($this->_status){
				
				case self::STATUS_OK:
					$status_message = '200 OK';
					break;
				
				case self::STATUS_BAD:
				case self::STATUS_ERROR:
					$status_message = '400 Bad Request';				
					break;
				
				case self::STATUS_UNAUTHORISED:
					$status_message = '401 Unauthorized';
					break;
										
			}
			
			$this->addHeaderToPage('HTTP/1.0 ' . $status_message);
			$this->_Result->setAttribute('status', $this->_status);
			parent::generate();
			return $this->_Result->generate(true);
		}
		
	}

