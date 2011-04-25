<?php

	/**
	 * @package toolkit
	 */
	/**
	 * The Section class represents a Symphony Section object. A section is a model
	 * of a data structure using one or more Fields. Sections are stored in the database
	 * and are used as repositories for Entry objects, which are a model for this data
	 * structure. This class contains functions for finding Fields within a Section and
	 * saving a Section's settings.
	 */
	require_once(TOOLKIT . '/class.fieldmanager.php');

	Class Section{
		/**
		 * The class who initialised this Section, usually SectionManager
		 */
		public $_Parent;

		/**
		 * An array of the Section's settings
		 * @var array 
		 */
		protected $_data = array();

		/**
		 * An instance of the FieldManager class
		 * @var FieldManager
		 */
		protected $_fieldManager;

		/**
		 * The construct function sets the parent variable of this Section and
		 * initialises a new FieldManager object
		 *
		 * @param mixed $parent
		 * The class that initialised this Section, usually SectionManager
		 */
		public function __construct(&$parent){
			$this->_Parent = $parent;
			$this->_fieldManager = new FieldManager($this->_Parent);
		}

		/**
		 * A setter function that will save a section's setting into
		 * the poorly named `$this->_data` variable
		 *
		 * @param string $setting
		 *  The setting name
		 * @param string $value
		 *  The setting value
		 */
		public function set($setting, $value){
			$this->_data[$setting] = $value;
		}

		/**
		 * An accessor function for this Section's settings. If the
		 * $setting param is omitted, an array of all setting will
		 * be returned, otherwise it will return the data for
		 * the setting given
		 *
		 * @return array|string
		 *  If setting is provided, returns a string, if setting is omitted
		 *  returns an associative array of this Section's settings
		 */
		public function get($setting = null){
			if(is_null($setting)) return $this->_data;
			return $this->_data[$setting];
		}

		/**
		 * Returns any section associations this section has with other sections
		 * linked using fields. Has an optional parameter, respect_visibility that
		 * will only return associations that are deemed visible by a field that
		 * created the association. eg. An articles section may link to the authors
		 * section, but the field that links these sections has hidden this association
		 * so an Articles column will not appear on the Author's Publish Index
		 *
		 * @param boolean $respect_visibilty
		 *  Whether to return all the section associations regardless of if they
		 *  are deemed visible or not. Defaults to false, which will return all
		 *  associations.
		 * @return array
		 */
		public function fetchAssociatedSections($respect_visibility = false){
			return Symphony::Database()->fetch(sprintf("
					SELECT *
					FROM `tbl_sections_association` AS `sa`, `tbl_sections` AS `s`
					WHERE `sa`.`parent_section_id` = %d
					AND `s`.`id` = `sa`.`child_section_id`
					%s
					ORDER BY `s`.`sortorder` ASC
				",
				$this->get('id'),
				($respect_visibility) ? "AND `sa`.`hide_association` = 'no'" : ""
				)
			);
		}

		/**
		 * Returns an array of all the fields in this section that are to be displayed
		 * on the entries tablepage ordered by the order in which they appear
		 * in the Section Editor interface
		 *
		 * @return array
		 */
		public function fetchVisibleColumns(){
			return $this->_fieldManager->fetch(null, $this->get('id'), 'ASC', 'sortorder', null, null, " AND t1.show_column = 'yes' ");
		}

		/**
		 * Returns an array of all the fields in this section optionally filtered by
		 * the field type or it's location within the section.
		 *
		 * @param string $type
		 *  The field type (it's handle as returned by `$field->handle()`)
		 * @param string $location
		 *  The location of the fields in the entry creator, whether they are
		 *  'main' or 'sidebar'
		 * @return array
		 */
		public function fetchFields($type = null, $location = null){
			return $this->_fieldManager->fetch(null, $this->get('id'), 'ASC', 'sortorder', $type, $location);
		}

		/**
		 * Returns an array of all the fields that can be filtered.
		 *
		 * @deprecated This function will be removed in the next major release. It
		 *  is unused by Symphony.
		 * @param string $location
		 *  The location of the fields in the entry creator, whether they are
		 *  'main' or 'sidebar'
		 * @return array
		 */
		public function fetchFilterableFields($location = null){
			return $this->_fieldManager->fetch(null, $this->get('id'), 'ASC', 'sortorder', null, $location, null, Field::__FILTERABLE_ONLY__);
		}

		/**
		 * Returns an array of all the fields that can be toggled. This function
		 * is used to help build the With Selected drop downs on the Publish
		 * Index pages
		 *
		 * @param string $location
		 *  The location of the fields in the entry creator, whether they are
		 *  'main' or 'sidebar'
		 * @return array
		 */
		public function fetchToggleableFields($location = null){
			return $this->_fieldManager->fetch(null, $this->get('id'), 'ASC', 'sortorder', null, $location,null, Field::__TOGGLEABLE_ONLY__);
		}

		/**
		 * Returns the Schema of this section which includes all this sections
		 * fields and their settings.
		 *
		 * @return array
		 */
		public function fetchFieldsSchema(){
			return Symphony::Database()->fetch("SELECT `id`, `element_name`, `type`, `location` FROM `tbl_fields` WHERE `parent_section` = '".$this->get('id')."' ORDER BY `sortorder` ASC");
		}

		/**
		 * Commit the settings of this section from the section editor to
		 * create an instance of this section in `tbl_sections`. This function
		 * loops of each of the fields in this section and calls their commit
		 * function.
		 *
		 * @see toolkit.Field#commit()
		 * @return boolean
		 *	true if the commit was successful, false otherwise.
		 */
		public function commit(){
			$settings = $this->_data;
			$section_id = null;

			if(isset($settings['id'])){
				$id = $settings['id'];
				unset($settings['id']);
				$section_id = SectionManager::edit($id, $settings);

				if($section_id) $section_id = $id;

			}else{
				$section_id = SectionManager::add($settings);
			}

			if(is_numeric($section_id) && $section_id !== false){
				for($ii = 0, $length = count($this->_fields); $ii < $length; $ii++){
					$this->_fields[$ii]->set('parent_section', $section_id);
					$this->_fields[$ii]->commit();
				}
			}
		}
	}
