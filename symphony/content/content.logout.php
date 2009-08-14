<?php
	
	Class contentLogout extends HTMLPage{

		public function build(){
			$this->view();
		}
	
		public function view(){
			Administration::instance()->logout();
			redirect(URL);
		}
	
	}
	
