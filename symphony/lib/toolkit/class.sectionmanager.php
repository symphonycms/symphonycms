<?php

	/**
	 * @package toolkit
	 */
	/**
	 * The SectionManager is responsible for managing all Sections in a Symphony
	 * installation. The SectionManager contains basic CRUD operations for Sections.
	 * Sections are stored in the database in `tbl_sections`.
	 */
	include_once(TOOLKIT . '/class.section.php');

	Class SectionManager {

		/**
		 * An array of all the objects that the Manager is responsible for.
		 * Defaults to an empty array.
		 * @var array
		 */
	    protected static $_pool = array();

		/**
		 * The parent class who initialised the SectionManager, usually a 
		 * Symphony instance, either Frontend or Administration
		 */
	    public $_Parent;

		/**
		 * The construct function sets the parent variable of the SectionManager
		 *
		 * @param mixed $parent
		 *  The class that initialised this Section, usually SectionManager
		 */
        public function __construct(&$parent){
			$this->_Parent = $parent;
        }

		/**
		 * Takes an associative array of Section settings and creates a new
		 * entry in the `tbl_sections` table, returning the ID of the Section.
		 * The ID of the section is generated using auto_increment
		 *
		 * @param array $settings
		 *  An associative of settings for a section with the key being
		 *  a column name from `tbl_sections`
		 * @return integer
		 */
		public function add($settings){
			if(!Symphony::Database()->insert($settings, 'tbl_sections')) return false;
			$section_id = Symphony::Database()->getInsertID();

			return $section_id;
		}

		/**
		 * Updates an existing Section given it's ID and an associative
		 * array of settings. The array does not have to contain all the
		 * settings for the Section as there is no deletion of settings
		 * prior to updating the Section
		 *
		 * @param integer $section_id
		 *  The ID of the Section to update
		 * @param array $settings
		 *  An associative of settings for a section with the key being
		 *  a column name from `tbl_sections`
		 * @return boolean
		 */
		public function edit($section_id, $settings){
			if(!Symphony::Database()->update($settings, 'tbl_sections', " `id` = $section_id")) return false;

			return true;
		}

		/**
		 * Deletes a Section by Section ID, removing all entries, fields, the
		 * Section and then any Section Associations in that order
		 *
		 * @param integer $section_id
		 *  The ID of the Section to delete
		 */
		public function delete($section_id){

			$query = "SELECT `sortorder` FROM tbl_sections WHERE `id` = '$section_id'";
			$details = Symphony::Database()->fetchRow(0, $query);

			## Delete all the entries
			include_once(TOOLKIT . '/class.entrymanager.php');
			$entryManager = new EntryManager($this->_Parent);
			$entries = Symphony::Database()->fetchCol('id', "SELECT `id` FROM `tbl_entries` WHERE `section_id` = '$section_id'");
			$entryManager->delete($entries);

			## Delete all the fields
			$fieldManager = new FieldManager($this->_Parent);
			$fields = Symphony::Database()->fetchCol('id', "SELECT `id` FROM `tbl_fields` WHERE `parent_section` = '$section_id'");

			if(is_array($fields) && !empty($fields)){
				foreach($fields as $field_id) $fieldManager->delete($field_id);
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
		 * @param integer $section_id
		 *  The ID of the section to return. Defaults to null
		 * @param string $order
		 *  If `$section_id` is omitted, this is the sortorder of the returned
		 *  objects. Defaults to ASC, other options id DESC
		 * @param string $sortfield
		 *  The name of the column in the `tbl_sections` table to sort
		 *  on. Defaults to name
		 * @return Section|array
		 *  A Section object or an array of Section objects
		 */
		public function fetch($section_id = null, $order = 'ASC', $sortfield = 'name'){

			if(!is_null($section_id) && is_numeric($section_id)) $returnSingle = true;

			if(!is_array(self::$_pool)) $this->flush();

			if($returnSingle && isset(self::$_pool[$section_id])){
				return self::$_pool[$section_id];
			}

			$sql = "
					SELECT `s`.*
					FROM `tbl_sections` AS `s`
					" . ($section_id? " WHERE `s`.`id` = '$section_id' " : '') . "
					" . (is_null($section_id) ? " ORDER BY `s`.`$sortfield` $order" : '');

			if(!$sections = Symphony::Database()->fetch($sql)) return false;

			$ret = array();

			foreach($sections as $s){
				$obj =& $this->create();

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
		public function fetchIDFromHandle($handle){
			return Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_sections` WHERE `handle` = '$handle' LIMIT 1");
		}

		/**
		 * This function will empty the $_pool array.
		 */
		public function flush(){
			self::$_pool = array();
		}

		/**
		 * Returns a new Section object, using the SectionManager
		 * as the Section's $parent.
		 *
		 * @return Section
		 */
		public function &create(){
			$obj = new Section($this);
			return $obj;
		}

	}
