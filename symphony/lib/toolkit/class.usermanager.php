<?php
	
	Class UserManager{
		
		public static function add($fields){
			
			if(!Symphony::Database()->insert($fields, 'tbl_users')) return false;
			$author_id = Symphony::Database()->getInsertID();
			
			return $author_id;
		}

		public static function edit($id, $fields){
			
			if(!Symphony::Database()->update($fields, 'tbl_users', " `id` = '$id'")) return false;
			
			return true;			
		}
		
		public static function delete($id){		
			Symphony::Database()->delete('tbl_users', " `id` = '$id'");		
			return true;
		}
		
		public static function fetch($sortby=NULL, $sortdirection='ASC', $limit=NULL, $start=NULL){
		
	    	$sql = "SELECT tbl_users.*
				    FROM tbl_users
				    GROUP BY tbl_users.id 		
				 	ORDER BY ".($sortby ? $sortby : 'tbl_users.id')." $sortdirection " .
				 	($limit ? "LIMIT $limit ": '') . ($start && $limit ? ', ' . $start : '');

			$rec = Symphony::Database()->fetch($sql);

			if(!is_array($rec) || empty($rec)) return NULL;
			
			$authors = array();

			foreach($rec as $row){
				$author = new User;
				
				foreach($row as $field => $val)
					$author->set($field, $val);
				
				$authors[] = $author;
			}
			
			return $authors;		
		}
		
		public static function fetchByID($id, $sortby=NULL, $sortdirection='ASC', $limit=NULL, $start=NULL){
			
			$return_single = false;
			$result = array();
			
			if(!is_array($id)){
				$return_single = true;
				$id = array($id);
			}
			
			if(empty($id)) return;
			
			$rows = Symphony::Database()->fetch("SELECT * FROM `tbl_users` 
													 WHERE `id` IN ('" . @implode("', '", $id). "') 
													 ORDER BY `".($sortby ? $sortby : 'id')."` $sortdirection 
													 ".($limit ? "LIMIT $limit ": '') . ($start && $limit ? ', ' . $start : ''));
			
			if(!is_array($rows) || empty($rows)) return NULL;
			
			foreach($rows as $rec){
			
				$author = new User;
			
				foreach($rec as $field => $val)
					$author->set($field, $val);
				
				$result[] = $author;
				
			}
			
			return ($return_single ? $result[0] : $result);
			
		}
		
		public static function fetchByUsername($username){
			$rec = Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_users` WHERE `username` = '$username' LIMIT 1");
			
			if(!is_array($rec) || empty($rec)) return NULL;
			
			$author = new User;
			
			foreach($rec as $field => $val)
				$author->set($field, $val);
				
			return $author;		
		}
		
		public static function deactivateAuthToken($author_id){
			return Symphony::Database()->query("UPDATE `tbl_users` SET `auth_token_active` = 'no' WHERE `id` = '$author_id' LIMIT 1");
		}
		
		public static function activateAuthToken($author_id){
			return Symphony::Database()->query("UPDATE `tbl_users` SET `auth_token_active` = 'yes' WHERE `id` = '$author_id' LIMIT 1");
		}
	}

