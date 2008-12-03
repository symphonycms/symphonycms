<?php
	
	Class AuthorManager{
		
		var $Database;
		var $_Parent;
		
		function __construct(&$parent){
			$this->_Parent =& $parent;
			$this->Database = $this->_Parent->Database;
		}
		
		function &create(){	
			$obj = new Author($this);
			return $obj;
		}
		
		function add($fields){
			
			if(!$this->_Parent->Database->insert($fields, 'tbl_authors')) return false;
			$author_id = $this->_Parent->Database->getInsertID();
			
			return $author_id;
		}

		function edit($id, $fields){
			
			if(!$this->_Parent->Database->update($fields, 'tbl_authors', " `id` = '$id'")) return false;
			
			return true;			
		}
		
		function delete($id){		

			## TODO: Delete author's entries
			//$entries = $this->_Parent->Database->fetchCol('id', "SELECT `id` FROM `tbl_entries` WHERE `author_id` = '".$id."'");  

			$this->_Parent->Database->delete('tbl_authors', " `id` = '$id'");
			
			//$this->_Parent->Database->delete('tbl_entries', " `author_id` = '$id'");
			//$this->_Parent->Database->delete('tbl_entries_data', " `entry_id` IN ('".@implode("', '", $entries)."')");			
				
			return true;
		}
		
		function fetch($sortby=NULL, $sortdirection='ASC', $limit=NULL, $start=NULL){
		
	    	$sql = "SELECT tbl_authors.*
				    FROM tbl_authors
				    GROUP BY tbl_authors.id 		
				 	ORDER BY ".($sortby ? $sortby : 'tbl_authors.id')." $sortdirection " .
				 	($limit ? "LIMIT $limit ": '') . ($start && $limit ? ', ' . $start : '');

			$rec = $this->_Parent->Database->fetch($sql);

			if(!is_array($rec) || empty($rec)) return NULL;
			
			$authors = array();

			foreach($rec as $row){
				$author = $this->create();
				
				foreach($row as $field => $val)
					$author->set($field, $val);
				
				$authors[] = $author;
			}
			
			return $authors;		
		}
		
		function fetchByID($id, $sortby=NULL, $sortdirection='ASC', $limit=NULL, $start=NULL){
			
			$return_single = false;
			$result = array();
			
			if(!is_array($id)){
				$return_single = true;
				$id = array($id);
			}
			
			if(empty($id)) return;
			
			$rows = $this->_Parent->Database->fetch("SELECT * FROM `tbl_authors` 
													 WHERE `id` IN ('" . @implode("', '", $id). "') 
													 ORDER BY `".($sortby ? $sortby : 'id')."` $sortdirection 
													 ".($limit ? "LIMIT $limit ": '') . ($start && $limit ? ', ' . $start : ''));
			
			if(!is_array($rows) || empty($rows)) return NULL;
			
			foreach($rows as $rec){
			
				$author = $this->create();
			
				foreach($rec as $field => $val)
					$author->set($field, $val);
				
				$result[] = $author;
				
			}
			
			return ($return_single ? $result[0] : $result);
			
		}
		
		function fetchByUsername($username){
			$rec = $this->_Parent->Database->fetchRow(0, "SELECT * FROM `tbl_authors` WHERE `username` = '$username' LIMIT 1");
			
			if(!is_array($rec) || empty($rec)) return NULL;
			
			$author = $this->create();
			
			foreach($rec as $field => $val)
				$author->set($field, $val);
				
			return $author;		
		}
		
		function deactivateAuthToken($author_id){
			return $this->_Parent->Database->query("UPDATE `tbl_authors` SET `auth_token_active` = 'no' WHERE `id` = '$author_id' LIMIT 1");
		}
		
		function activateAuthToken($author_id){
			return $this->_Parent->Database->query("UPDATE `tbl_authors` SET `auth_token_active` = 'yes' WHERE `id` = '$author_id' LIMIT 1");
		}
	}

