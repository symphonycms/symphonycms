<?php

	require_once(TOOLKIT . '/class.htmldocument.php');

	Class contentLogout extends HTMLDocument{

		public function build(){
			$this->view();
		}

		public function view(){
			Administration::instance()->logout();
			redirect(URL);
		}

	}

