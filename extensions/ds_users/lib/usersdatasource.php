<?php
	
	Class UsersDataSource extends DataSource {
		
		public function __construct(){
			// Set Default Values
			$this->_about = new StdClass;
			$this->_parameters = (object)array(
				'root-element' => NULL,
				'included-elements' => array(),
				'filters' => array()
			);
		}
		final public function type(){
			return 'ds_users';
		}
		
		public function template(){
			return EXTENSIONS . '/ds_users/templates/datasource.php';
		}

		public function save(MessageStack &$errors){

			//if(strlen(trim($this->parameters()->url)) == 0){
			//	$errors->append('url', __('This is a required field'));
			//}

			return parent::save($errors);
		}
		
		protected function processUserFilter($field, $filter) {
			if (!is_array($filter)) {
				$bits = preg_split('/,\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);
				$bits = array_map('trim', $bits);
			}
			
			else {
				$bits = $filter;
			}
			
			$users = Symphony::Database()->fetchCol('id', "SELECT `id` FROM `tbl_users` WHERE `{$field}` IN ('".implode("', '", $bits)."')");
			
			return (is_array($users) && !empty($users) ? $users : NULL);
		}
		
		public function render(Register &$ParameterOutput){
			throw new Exception('TODO: Fix users datasource template.');
			
			$doc = new XMLDocument;
			$root = $doc->createElement($this->parameters()->{'root-element'});
			
			try {
				$user_ids = array();
				
				if (is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)) {
					foreach ($this->dsParamFILTERS as $field => $value){
						if(!is_array($value) && trim($value) == '') continue;
						
						$ret = $this->processUserFilter($field, $value);
					
						if(empty($ret)){
							$user_ids = array();
							break;
						}
					
						if(empty($user_ids)) {
							$user_ids = $ret;
							continue;
						}
					
						$user_ids = array_intersect($user_ids, $ret);
						
					}
					
					$users = UserManager::fetchByID(array_values($user_ids), $this->dsParamSORT, $this->dsParamORDER, $this->dsParamLIMIT, (max(0, ($this->dsParamSTARTPAGE - 1)) * $this->dsParamLIMIT));
				}
				
				else $users = UserManager::fetch($this->dsParamSORT, $this->dsParamORDER, $this->dsParamLIMIT, (max(0, ($this->dsParamSTARTPAGE - 1)) * $this->dsParamLIMIT));
			
				
				if((!is_array($users) || empty($users)) && $this->dsParamREDIRECTONEMPTY == 'yes'){
					throw new FrontendPageNotFoundException;
				}
				
				else{
				
					if(!$this->_param_output_only) $result = Symphony::Parent()->Page->createElement($this->dsParamROOTELEMENT);
				
					foreach($users as $user){
					
						if(isset($this->dsParamPARAMOUTPUT)){
							$key = 'ds-' . $this->dsParamROOTELEMENT;
							if(!is_array($param_pool[$key])) $param_pool[$key] = array();
						
							$param_pool[$key][] = ($this->dsParamPARAMOUTPUT == 'name' ? $user->getFullName() : $user->{"{$this->dsParamPARAMOUTPUT}"});
						}
					
						if(!$this->_param_output_only){
					
							$xUser = Symphony::Parent()->Page->createElement('user');
							$xUser->setAttribute('id', $user->id);
				
							$fields = array(
								'name' => Symphony::Parent()->Page->createElement('name', $user->getFullName()),
								'username' => Symphony::Parent()->Page->createElement('username', $user->username),
								'email' => Symphony::Parent()->Page->createElement('email', $user->email)
							);
				
							if($user->isTokenActive()) $fields['authentication-token'] = Symphony::Parent()->Page->createElement('authentication-token', $user->createAuthToken());
				
							if($section = Symphony::Database()->fetchRow(0, "SELECT `id`, `handle`, `name` FROM `tbl_sections` WHERE `id` = '".$user->default_section."' LIMIT 1")){
								$default_section = Symphony::Parent()->Page->createElement('default-section', $section['name']);
								$default_section->setAttributeArray(array('id' => $section['id'], 'handle' => $section['handle']));
								$fields['default-section'] = $default_section;
							}
							
							$this->__appendIncludedElements($xUser, $fields);
							
							$result->appendChild($xUser);
						
						}
					}
				}
			}
			
			catch (FrontendPageNotFoundException $error) {
				FrontendPageNotFoundExceptionHandler::render($error);
			}
			
			catch (Exception $error) {
				$result->appendChild($doc->createElement(
					'error', General::sanitize($error->getMessage())
				));
				
				return $result;
			}
			
			if ($this->_force_empty_result) $result = $this->emptyXMLSet();
			
			return $doc;
		}
	}
