<?php

	Class UserManager{

		public static function add($fields){

			if(!Symphony::Database()->insert($fields, 'tbl_users')) return false;
			$user_id = Symphony::Database()->getInsertID();

			return $user_id;
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
			$query = sprintf(
				"
					SELECT
						u.*
					FROM
						`tbl_users` AS u
					GROUP BY
						u.id
					ORDER BY
						%s %s
					%s
				",
				($sortby ? $sortby : 'u.id'),
				$sortdirection,
				($limit ? "LIMIT $limit ": '') . ($start && $limit ? ', ' . $start : '')
			);

			$result = Symphony::Database()->query($query);

			if(!$result->valid()) return null;

			$users = array();

			foreach($result as $row){
				$user = new User;

				foreach($row as $field => $val)
					$user->set($field, $val);

				$users[] = $user;
			}

			return $users;
		}

		// TODO: Remove this redundant function and integrate into the fetch() above
		public static function fetchByID($id, $sortby=NULL, $sortdirection='ASC', $limit=NULL, $start=NULL){

			$return_single = false;
			$result = array();

			if(!is_array($id)){
				$return_single = true;
				$id = array($id);
			}

			if(empty($id)) return;

			$query = sprintf(
				"
					SELECT
						u.*
					FROM
						`tbl_users` AS u
					WHERE
						u.id IN ('%%s')
					ORDER BY
						%s %s
					%s
				",
				($sortby ? $sortby : 'u.id'),
				$sortdirection,
				($limit ? "LIMIT $limit ": '') . ($start && $limit ? ', ' . $start : '')
			);

			$rows = Symphony::Database()->query($query, array(
				implode("','", $id)
			));

			if(!$rows->valid()) return null;

			foreach($rows as $rec){

				$user = new User;

				foreach($rec as $field => $val)
					$user->set($field, $val);

				$result[] = $user;

			}

			return ($return_single ? $result[0] : $result);

		}

		/* TODO: Is this needed? It's not called anywhere in Symphony */
		public static function fetchByUsername($username){
			$result = Symphony::Database()->query("
					SELECT
						u.*
					FROM
						`tbl_users` AS u
					WHERE
						u.username = '%%s'
					LIMIT
						1
				",
				array($username)
			);

			if(!$result->valid()) return null;

			$user = new User;

			foreach($result as $field => $val)
				$user->set($field, $val);

			return $user;
		}

		/* TODO: Make these use update() */
		public static function deactivateAuthToken($user_id){
			return Symphony::Database()->query("UPDATE `tbl_users` SET `auth_token_active` = 'no' WHERE `id` = '$user_id' LIMIT 1");
		}

		public static function activateAuthToken($user_id){
			return Symphony::Database()->query("UPDATE `tbl_users` SET `auth_token_active` = 'yes' WHERE `id` = '$user_id' LIMIT 1");
		}
	}

