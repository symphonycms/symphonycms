<?php

	if(!function_exists('__processUserFilter')){
		function __processUserFilter($field, $filter){

			if(!is_array($filter)){
				$bits = preg_split('/,\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);
				$bits = array_map('trim', $bits);
			}
			else $bits = $filter;

			$users = Symphony::Database()->query("
					SELECT
						`id`
					FROM
						`tbl_users`
					WHERE
						`%s` IN ('%s')
				",
				array($field, implode("', '", $bits))
			);

			return ($users->valid()) ? $users : NULL);

		}
	}

	$user_ids = array();

	if(is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)){
		foreach($this->dsParamFILTERS as $field => $value){

			if(!is_array($value) && trim($value) == '') continue;

			$ret = __processUserFilter($field, $value);

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

		$users = UserManager::fetchByID(
			array_values($user_ids), 
			$this->dsParamSORT, 
			$this->dsParamORDER, 
			$this->dsParamLIMIT, 
			(max(0, ($this->dsParamSTARTPAGE - 1)) * $this->dsParamLIMIT)
		);
	}

	else $users = UserManager::fetch(
					$this->dsParamSORT, 
					$this->dsParamORDER, 
					$this->dsParamLIMIT, 
					(max(0, ($this->dsParamSTARTPAGE - 1)) * $this->dsParamLIMIT)
				);


	if((!is_array($users) || empty($users)) && $this->dsParamREDIRECTONEMPTY == 'yes'){
		throw new FrontendPageNotFoundException;
	}

	else {

		if(!$this->_param_output_only) $result = new XMLElement($this->dsParamROOTELEMENT);

		foreach($users as $user){

			if(isset($this->dsParamPARAMOUTPUT)){
				$key = 'ds-' . $this->dsParamROOTELEMENT;
				if(!is_array($param_pool[$key])) $param_pool[$key] = array();

				$param_pool[$key][] = ($this->dsParamPARAMOUTPUT == 'name' ? $user->getFullName() : $user->{"{$this->dsParamPARAMOUTPUT}"});
			}

			if(!$this->_param_output_only){

				$xUser = new XMLElement('user');
				$xUser->setAttribute('id', $user->id);

				$fields = array(
					'name' => new XMLElement('name', $user->getFullName()),
					'username' => new XMLElement('username', $user->username),
					'email' => new XMLElement('email', $user->email)
				);

				if($user->isTokenActive()) $fields['authentication-token'] = new XMLElement('authentication-token', $user->createAuthToken());

				if($section = Symphony::Database()->fetchRow(0, "SELECT `id`, `handle`, `name` FROM `tbl_sections` WHERE `id` = '".$user->default_section."' LIMIT 1")){
					$default_section = new XMLElement('default-section', $section['name']);
					$default_section->setAttributeArray(array('id' => $section['id'], 'handle' => $section['handle']));
					$fields['default-section'] = $default_section;
				}

				$this->__appendIncludedElements($xUser, $fields);

				$result->appendChild($xUser);

			}
		}
	}

