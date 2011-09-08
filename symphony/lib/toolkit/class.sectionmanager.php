<?php

	/**
	 * @package toolkit
	 */
	/**
	 * The `SectionManager` is responsible for managing all Sections in a Symphony
	 * installation by exposing basic CRUD operations. Sections are stored in the
	 * database in `tbl_sections`.
	 */
	include_once(TOOLKIT . '/class.section.php');

	Class SectionManager {

		/**
		 * An array of all the objects that the Manager is responsible for.
		 *
		 * @var array
		 *   Defaults to an empty array.
		 */
		protected static $_pool = array();

		/**
		 * Takes an associative array of Section settings and creates a new
		 * entry in the `tbl_sections` table, returning the ID of the Section.
		 * The ID of the section is generated using auto_increment and returned
		 * as the Section ID.
		 *
		 * @param array $settings
		 *  An associative of settings for a section with the key being
		 *  a column name from `tbl_sections`
		 * @return integer
		 *  The newly created Section's ID
		 */
		public static function add($settings){
			if(!Symphony::Database()->insert($settings, 'tbl_sections')) return false;

			return Symphony::Database()->getInsertID();
		}

		/**
		 * Updates an existing Section given it's ID and an associative
		 * array of settings. The array does not have to contain all the
		 * settings for the Section as there is no deletion of settings
		 * prior to updating the Section
		 *
		 * @param integer $section_id
		 *  The ID of the Section to edit
		 * @param array $settings
		 *  An associative of settings for a section with the key being
		 *  a column name from `tbl_sections`
		 * @return boolean
		 */
		public static function edit($section_id, $settings){
			if(!Symphony::Database()->update($settings, 'tbl_sections', " `id` = $section_id")) return false;

			return true;
		}

		/**
		 * Deletes a Section by Section ID, removing all entries, fields, the
		 * Section and any Section Associations in that order
		 *
		 * @param integer $section_id
		 *  The ID of the Section to delete
		 * @param boolean
		 *  Returns true when completed
		 */
		public static function delete($section_id){
			$details = Symphony::Database()->fetchRow(0, "SELECT `sortorder` FROM tbl_sections WHERE `id` = '$section_id'");

			## Delete all the entries
			include_once(TOOLKIT . '/class.entrymanager.php');
			$entries = Symphony::Database()->fetchCol('id', "SELECT `id` FROM `tbl_entries` WHERE `section_id` = '$section_id'");
			EntryManager::delete($entries);

			## Delete all the fields
			$fields = Symphony::Database()->fetchCol('id', "SELECT `id` FROM `tbl_fields` WHERE `parent_section` = '$section_id'");

			if(is_array($fields) && !empty($fields)){
				foreach($fields as $field_id) FieldManager::delete($field_id);
			}

			## Delete the section
			Symphony::Database()->delete('tbl_sections', " `id` = '$section_id'");

			## Update the sort orders
			Symphony::Database()->query("UPDATE tbl_sections SET `sortorder` = (`sortorder` - 1) WHERE `sortorder` > '".$details['sortorder']."'");

			## Delete the section associations
			Symphony::Database()->delete('tbl_sections_association', " `parent_section_id` = '$section_id'");

			return true;
		}

		/**
		 * Returns a Section object by ID, or returns an array of Sections
		 * if the Section ID was omitted. If the Section ID is omitted, it is
		 * possible to sort the Sections by providing a sort order and sort
		 * field. By default, Sections will be order in ascending order by
		 * their name
		 *
		 * @param integer|array $section_id
		 *  The ID of the section to return, or an array of ID's. Defaults to null
		 * @param string $order
		 *  If `$section_id` is omitted, this is the sortorder of the returned
		 *  objects. Defaults to ASC, other options id DESC
		 * @param string $sortfield
		 *  The name of the column in the `tbl_sections` table to sort
		 *  on. Defaults to name
		 * @return Section|array
		 *  A Section object or an array of Section objects
		 */
		public static function fetch($section_id = null, $order = 'ASC', $sortfield = 'name'){
			$returnSingle = false;
			$section_ids = array();

			if(!is_null($section_id)) {
				if(is_numeric($section_id)) {
					$returnSingle = true;
				}

				if(!is_array($section_id)) {
					$section_ids = array((int)$section_id);
				}
				else {
					$section_ids = $section_id;
				}
			}

			if($returnSingle && isset(self::$_pool[$section_id])){
				return self::$_pool[$section_id];
			}

			$sql = sprintf("
					SELECT `s`.*
					FROM `tbl_sections` AS `s`
					%s
					%s
				",
				!empty($section_id) ? " WHERE `s`.`id` IN (" . implode(',', $section_ids) . ") " : "",
				empty($section_id) ? " ORDER BY `s`.`$sortfield` $order" : ""
			);

			if(!$sections = Symphony::Database()->fetch($sql)) return ($returnSingle ? false : array());

			$ret = array();

			foreach($sections as $s){
				$obj = self::create();

				foreach($s as $name => $value){
					$obj->set($name, $value);
				}

				self::$_pool[$obj->get('id')] = $obj;

				$ret[] = $obj;
			}

			return (count($ret) == 1 && $returnSingle ? $ret[0] : $ret);
		}

		/**
		 * Return a Section ID by the handle
		 *
		 * @param string $handle
		 *  The handle of the section
		 * @return integer
		 *  The Section ID
		 */
		public static function fetchIDFromHandle($handle){
			return Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_sections` WHERE `handle` = '$handle' LIMIT 1");
		}

		/**
		 * Returns a new Section object, using the SectionManager
		 * as the Section's $parent.
		 *
		 * @return Section
		 */
		public static function create(){
			$obj = new Section;
			return $obj;
		}
	}
