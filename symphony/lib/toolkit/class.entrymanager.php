<?php

	/**
	 * @package toolkit
	 */
	/**
	 * The EntryManager is responsible for all Entry objects in Symphony.
	 * Entries are stored in the database in a cluster of tables. There is a
	 * parent entry row stored in `tbl_entries` and then each field's data is
	 * stored in a separate table, `tbl_entries_data_{field_id}`. Where Field ID
	 * is generated when the Section is saved. This Manager provides basic
	 * add, edit, delete and fetching methods for Entries.
	 */

	include_once(TOOLKIT . '/class.sectionmanager.php');
	include_once(TOOLKIT . '/class.textformattermanager.php');
	include_once(TOOLKIT . '/class.entry.php');

	Class EntryManager{

		/**
		 * The class that initialised the Entry, usually the EntryManager
		 * @var mixed
		 */
		protected $_Parent;

		/**
		 * An instance of the TextFormatterManager
		 * @var TextFormatterManager
		 */
		public $formatterManager;

		/**
		 * An instance of the SectionManager
		 * @var SectionManager
		 */
		public $sectionManager;

		/**
		 * An instance of the FieldManager
		 * @var FieldManager
		 */
		public $fieldManager;

		/**
		 * The Field ID that will be used to sort when fetching Entries, defaults
		 * to null, which implies the Entry ID (id column in `tbl_entries`)
		 * @var integer
		 */
		protected $_fetchSortField = null;

		/**
		 * The direction that entries should be sorted in, available options are
		 * RAND, ASC or DESC. Defaults to null, which implies ASC
		 * @var string
		 */
		protected $_fetchSortDirection = null;

		/**
		 * The constructor initialises the formatterManager, sectionManager and
		 * fieldManager variables and sets the `$this->_Parent` to the param provided.
		 *
		 * @param Administration $parent
		 *  The Administration object that this page has been created from
		 *  passed by reference
		 */
		public function __construct($parent){
			$this->_Parent = $parent;

			$this->formatterManager = new TextformatterManager($this->_Parent);
			$this->sectionManager = new SectionManager($this->_Parent);
			$this->fieldManager = new FieldManager($this->_Parent);
		}

		/**
		 * Setter function for the default sorting direction of the Fetch
		 * function. Available options are RAND, ASC or DESC.
		 *
		 * @param string $direction
		 *  The direction that entries should be sorted in, available options
		 *  are RAND, ASC or DESC.
		 */
		public function setFetchSortingDirection($direction){
			$direction = strtoupper($direction);
			if($direction == 'RANDOM') $direction = 'RAND';
			$this->_fetchSortDirection = (in_array($direction, array('RAND', 'ASC', 'DESC')) ? $direction : null);
		}

		/**
		 * Sets the field to applying the sorting direction on when fetching
		 * entries
		 *
		 * @param integer $field_id
		 *  The ID of the Field that should be sorted on
		 */
		public function setFetchSortingField($field_id){
			$this->_fetchSortField = $field_id;
		}

		/**
		 * Convenience function that will set sorting field and direction
		 * by calling `setFetchSortingField()` & `setFetchSortingDirection()`
		 *
		 * @see toolkit.EntryManager#setFetchSortingField()
		 * @see toolkit.EntryManager#setFetchSortingDirection()
		 * @param integer $field_id
		 *  The ID of the Field that should be sorted on
		 * @param string $direction
		 *  The direction that entries should be sorted in, available options
		 *  are RAND, ASC or DESC. Defaults to ASC
		 */
		public function setFetchSorting($field_id, $direction='ASC'){
			$this->setFetchSortingField($field_id);
			$this->setFetchSortingDirection($direction);
		}

		/**
		 * Returns an object representation of the sorting for the
		 * EntryManager, with the field and direction provided
		 *
		 * @return StdClass
		 */
		public function getFetchSorting(){
			return (object)array(
				'field' => $this->_fetchSortField,
				'direction' => $this->_fetchSortDirection
			);
		}

		/**
		 * Given an Entry object, iterate over all of the fields in that object
		 * an insert them into their relevant entry tables.
		 *
		 * @param Entry $entry
		 *  An Entry object to insert into the database
		 * @return boolean
		 */
		public function add(Entry $entry){

			$fields = $entry->get();

			Symphony::Database()->insert($fields, 'tbl_entries');

			if(!$entry_id = Symphony::Database()->getInsertID()) return false;

			foreach($entry->getData() as $field_id => $field){
				if(!is_array($field) || empty($field)) continue;

				Symphony::Database()->delete('tbl_entries_data_' . $field_id, " `entry_id` = '$entry_id'");

				$data = array(
					'entry_id' => $entry_id
				);

				$fields = array();

				foreach($field as $key => $value){

					if(is_array($value)){
						foreach($value as $ii => $v) $fields[$ii][$key] = $v;
					}

					else{
						$fields[max(0, count($fields) - 1)][$key] = $value;
					}
				}

				for($ii = 0; $ii < count($fields); $ii++) $fields[$ii] = array_merge($data, $fields[$ii]);

				Symphony::Database()->insert($fields, 'tbl_entries_data_' . $field_id);

			}

			$entry->set('id', $entry_id);

			return true;

		}

		/**
		 * Update an existing Entry object given an Entry object
		 *
		 * @param Entry $entry
		 *  An Entry object
		 * @return boolean
		 */
		public function edit(Entry $entry){
			foreach ($entry->getData() as $field_id => $field) {
				if (empty($field_id)) continue;

				try{
					Symphony::Database()->delete('tbl_entries_data_' . $field_id, " `entry_id` = '".$entry->get('id')."'");
				}
				catch(Exception $e){
					// Discard?
				}

				if(!is_array($field) || empty($field)) continue;

				$data = array(
					'entry_id' => $entry->get('id')
				);

				$fields = array();

				foreach($field as $key => $value){

					if(is_array($value)){
						foreach($value as $ii => $v) $fields[$ii][$key] = $v;
					}
					else{
						$fields[max(0, count($fields) - 1)][$key] = $value;
					}
				}

				foreach ($fields as $index => $field_data) {
					$fields[$index] = array_merge($data, $field_data);
				}

				Symphony::Database()->insert($fields, 'tbl_entries_data_' . $field_id);

			}

			return true;

		}

		/**
		 * Given an Entry object, or an array of Entry objects delete all
		 * data associated with this Entry using a Field's `entryDataCleanup()`
		 * function, and then remove this Entry from `tbl_entries`.
		 *
		 * @param array|Entry $entries
		 *  An Entry object, or an array of Entry objects to delete
		 * @param boolean
		 */
		public function delete($entries){

			if(!is_array($entries)) {
				$entries = array($entries);
			}

			foreach($entries as $id){
				$e = $this->fetch($id);

				if(!is_object($e[0])) continue;

				foreach($e[0]->getData() as $field_id => $data){
					$field = $this->fieldManager->fetch($field_id);
					$field->entryDataCleanup($id, $data);
				}
			}

			$entry_list = implode("', '", $entries);
			Symphony::Database()->delete('tbl_entries', " `id` IN ('$entry_list') ");

			return true;
		}

		/**
		 * This function will return an array of Entry objects given an ID or an array of ID's.
		 * Do not provide `$entry_id` as an array if not specifying the `$section_id`. This function
		 * is commonly passed custom SQL statements through the `$where` and `$join` parameters
		 * that is generated by the fields of this section
		 *
		 * @param integer|array $entry_id
		 *  An array of Entry ID's or an Entry ID to return
		 * @param integer $section_id
		 *  The ID of the Section that these entries are contained in
		 * @param integer $limit
		 *  The limit of entries to return
		 * @param integer $start
		 *  The starting offset of the entries to return
		 * @param string $where
		 *  Any custom WHERE clauses
		 * @param string $joins
		 *  Any custom JOIN's
		 * @param boolean $group
		 *  Whether the entries need to be grouped by Entry ID or not
		 * @param boolean $records_only
		 *  If this is set to true, an array of Entry objects will be returned
		 *  without any basic pagination information. Defaults to false
		 * @param boolean $buildentries
		 *  Whether to return an array of entry ID's or Entry objects. Defaults to
		 *  true, which will return Entry objects
		 * @param array $element_names
		 *  Choose whether to get data from a subset of fields or all fields in a section,
		 *  by providing an array of field names. Defaults to null, which will load data
		 *  from all fields in a section.
		 */
		public function fetch($entry_id = null, $section_id = null, $limit = null, $start = null, $where = null, $joins = null, $group = false, $buildentries = true, $element_names = null){
			$sort = null;

			if (!$entry_id && !$section_id) return false;

			if (!$section_id) $section_id = $this->fetchEntrySectionID($entry_id);

			$section = $this->sectionManager->fetch($section_id);

			if (!is_object($section)) return false;

			## SORTING
			// A single $entry_id doesn't need to be sorted on
			if (!is_array($entry_id) && !is_null($entry_id) && is_int($entry_id)) {
				$sort = null;
			}
			// Check for RAND first, since this works independently of any specific field
			else if($this->_fetchSortDirection == 'RAND'){
				$sort = 'ORDER BY RAND() ';
			}

			else if ($this->_fetchSortField == 'date') {
				$sort = 'ORDER BY `e`.`creation_date` ' . $this->_fetchSortDirection;
			}

			else if ($this->_fetchSortField == 'id') {
				$sort = 'ORDER BY `e`.`id`' . $this->_fetchSortDirection;
			}

			else if ($this->_fetchSortField && $field = $this->fieldManager->fetch($this->_fetchSortField)) {
				$field->buildSortingSQL($joins, $where, $sort, $this->_fetchSortDirection);
				if (!$group) $group = $field->requiresSQLGrouping();
			}

			else if ($section->get('entry_order') && $field = $this->fieldManager->fetch($section->get('entry_order'))) {
				$field->buildSortingSQL($joins, $where, $sort, $section->get('entry_order_direction'));
				if (!$group) $group = $field->requiresSQLGrouping();
			}

			else {
				$sort = 'ORDER BY `e`.`id`' . $this->_fetchSortDirection;
			}

			if ($entry_id && !is_array($entry_id)) $entry_id = array($entry_id);

			$sql = "
				SELECT  ".($group ? 'DISTINCT ' : '')."`e`.id,
						`e`.section_id, e.`author_id`,
						UNIX_TIMESTAMP(e.`creation_date`) AS `creation_date`
				FROM `tbl_entries` AS `e`
				$joins
				WHERE 1
				".($entry_id ? "AND `e`.`id` IN ('".implode("', '", $entry_id)."') " : '')."
				".($section_id && !is_null($sort) ? "AND `e`.`section_id` = '$section_id' " : '')."
				$where
				$sort
				".($limit ? 'LIMIT ' . intval($start) . ', ' . intval($limit) : '');

			$rows = Symphony::Database()->fetch($sql);

			return ($buildentries && (is_array($rows) && !empty($rows)) ? $this->__buildEntries($rows, $section_id, $element_names) : $rows);

		}

		/**
		 * Given an array of Entry data from `tbl_entries` and a section ID, return an
		 * array of Entry objects. For performance reasons, it's possible to pass an array
		 * of field handles via `$element_names`, so that only a subset of the section schema
		 * will be queried. This function currently only supports Entry from one section at a
		 * time.
		 *
		 * @param array $rows
		 *  An array of Entry data from `tbl_entries` including the Entry ID, Entry section,
		 *  the ID of the Author who created the Entry, and a Unix timestamp of creation
		 * @param integer $section_id
		 *  The section ID of the entries in the `$rows`
		 * @param array $element_names
		 *  Choose whether to get data from a subset of fields or all fields in a section,
		 *  by providing an array of field names. Defaults to null, which will load data
		 *  from all fields in a section.
		 * @return array
		 */
		public function __buildEntries(Array $rows, $section_id, $element_names = null){
			$entries = array();

			if (empty($rows)) return $entries;

			// choose whether to get data from a subset of fields or all fields in a section
			if (!is_null($element_names) && is_array($element_names)){

				// allow for pseudo-fields containing colons (e.g. Textarea formatted/unformatted)
				foreach ($element_names as $index => $name) {
					$parts = explode(':', $name, 2);

					if(count($parts) == 1) continue;

					unset($element_names[$index]);
					$element_names[] = trim($parts[0]);
				}

				$schema_sql = sprintf(
					"SELECT `id` FROM `tbl_fields` WHERE `parent_section` = %d AND `element_name` IN ('%s')",
					$section_id,
					implode("', '", array_unique($element_names))
				);

			}
			else{
				$schema_sql = sprintf(
					"SELECT `id` FROM `tbl_fields` WHERE `parent_section` = %d",
					$section_id
				);
			}

			$schema = Symphony::Database()->fetch($schema_sql);

			$raw = array();
			$rows_string = '';

			// Append meta data:
			foreach ($rows as $entry) {
				$raw[$entry['id']]['meta'] = $entry;
				$rows_string .= $entry['id'] . ',';
			}
			$rows_string = trim($rows_string, ',');

			// Append field data:
			foreach ($schema as $f) {
				$field_id = $f['id'];

				try{
					$row = Symphony::Database()->fetch("SELECT * FROM `tbl_entries_data_{$field_id}` WHERE `entry_id` IN ($rows_string) ORDER BY `id` ASC");
				}
				catch(Exception $e){
					// No data due to error
					continue;
				}

				if (!is_array($row) || empty($row)) continue;

				foreach ($row as $r) {
					$entry_id = $r['entry_id'];

					unset($r['id']);
					unset($r['entry_id']);

					if (!isset($raw[$entry_id]['fields'][$field_id])) {
						$raw[$entry_id]['fields'][$field_id] = $r;
					}

					else {
						foreach (array_keys($r) as $key) {
							if (isset($raw[$entry_id]['fields'][$field_id][$key]) && !is_array($raw[$entry_id]['fields'][$field_id][$key])) {
								$raw[$entry_id]['fields'][$field_id][$key] = array($raw[$entry_id]['fields'][$field_id][$key], $r[$key]);
							}

							else if (!isset($raw[$entry_id]['fields'][$field_id][$key])) {
								$raw[$entry_id]['fields'][$field_id] = array($r[$key]);
							}

							else {
								$raw[$entry_id]['fields'][$field_id][$key][] = $r[$key];
							}
						}
					}
				}
			}

			// Loop over the array of entry data and convert it to an array of Entry objects
			foreach ($raw as $entry) {
				$obj = $this->create();

				$obj->creationDate = DateTimeObj::get('c', $entry['meta']['creation_date']);
				$obj->set('id', $entry['meta']['id']);
				$obj->set('author_id', $entry['meta']['author_id']);
				$obj->set('section_id', $entry['meta']['section_id']);

				if(isset($entry['fields']) && is_array($entry['fields'])){
					foreach ($entry['fields'] as $field_id => $data) $obj->setData($field_id, $data);
				}

				$entries[] = $obj;
			}

			return $entries;
		}


		/**
		 * Given an Entry ID, return the Section ID that it belongs to
		 *
		 * @param integer $entry_id
		 *  The ID of the Entry to return it's section
		 * @return integer
		 *  The Section ID for this Entry's section
		 */
		public function fetchEntrySectionID($entry_id){
			return Symphony::Database()->fetchVar('section_id', 0, "SELECT `section_id` FROM `tbl_entries` WHERE `id` = '$entry_id' LIMIT 1");
		}

		/**
		 * Return the count of the number of entries in a particular section.
		 *
		 * @param integer $section_id
		 *  The ID of the Section where the Entries are to be counted
		 * @param string $where
		 *  Any custom WHERE clauses
		 * @param string $joins
		 *  Any custom JOIN's
		 * @param boolean $group
		 *  Whether the entries need to be grouped by Entry ID or not
		 * @return integer
		 */
		public function fetchCount($section_id = null, $where = null, $joins = null, $group = false){
			if(is_null($section_id)) return false;

			$section = $this->sectionManager->fetch($section_id);

			if(!is_object($section)) return false;

			$sql = "
				SELECT count(".($group ? 'DISTINCT ' : '')."`e`.id) as `count`
				FROM `tbl_entries` AS `e`
				$joins
				WHERE `e`.`section_id` = '$section_id'
				$where
			";

			return Symphony::Database()->fetchVar('count', 0, $sql);
		}

		/**
		 * Returns an array of Entry objects, with some basic pagination given
		 * the number of Entry's to return and the current starting offset. This
		 * function in turn calls the fetch function that does alot of the heavy
		 * lifting. For instance, if there are 60 entries in a section and the pagination
		 * dictates that per page, 15 entries are to be returned, by passing 2 to
		 * the $page parameter you could return entries 15-30
		 *
		 * @param integer $page
		 *  The page to return, defaults to 1
		 * @param integer $section_id
		 *  The ID of the Section that these entries are contained in
		 * @param integer $entriesPerPage
		 *  The number of entries to return per page.
		 * @param string $where
		 *  Any custom WHERE clauses
		 * @param string $joins
		 *  Any custom JOIN's
		 * @param boolean $group
		 *  Whether the entries need to be grouped by Entry ID or not
		 * @param boolean $records_only
		 *  If this is set to true, an array of Entry objects will be returned
		 *  without any basic pagination information. Defaults to false
		 * @param boolean $buildentries
		 *  Whether to return an array of entry ID's or Entry objects. Defaults to
		 *  true, which will return Entry objects
		 * @param array $element_names
		 *  Choose whether to get data from a subset of fields or all fields in a section,
		 *  by providing an array of field names. Defaults to null, which will load data
		 *  from all fields in a section.
		 * @return array
		 *  Either an array of Entry objects, or an associative array containing
		 *  the total entries, the start position, the entries per page and the
		 *  Entry objects
		 */
		public function fetchByPage($page = 1, $section_id, $entriesPerPage, $where = null, $joins = null, $group = false, $records_only = false, $buildentries = true, Array $element_names = null){

			if($entriesPerPage != NULL && !is_string($entriesPerPage) && !is_numeric($entriesPerPage)){
				throw new Exception(__('Entry limit specified was not a valid type. String or Integer expected.'));
			}
			else if($entriesPerPage == NULL) {
				$records = $this->fetch(NULL, $section_id, NULL, NULL, $where, $joins, $group, $buildentries, $element_names);

				$count = $this->fetchCount($section_id, $where, $joins, $group);

				$entries = array(
					'total-entries' => $count,
					'total-pages' => 1,
					'remaining-pages' => 0,
					'remaining-entries' => 0,
					'start' => 1,
					'limit' => $count,
					'records' => $records
				);

				return $entries;
			}
			else {
				$start = (max(1, $page) - 1) * $entriesPerPage;

				$records = ($entriesPerPage == '0' ? NULL : $this->fetch(NULL, $section_id, $entriesPerPage, $start, $where, $joins, $group, $buildentries, $element_names));

				if($records_only) return array('records' => $records);

				$entries = array(
					'total-entries' => $this->fetchCount($section_id, $where, $joins, $group),
					'records' => $records,
					'start' => max(1, $start),
					'limit' => $entriesPerPage
				);

				$entries['remaining-entries'] = max(0, $entries['total-entries'] - ($start + $entriesPerPage));
				$entries['total-pages'] = max(1, ceil($entries['total-entries'] * (1 / $entriesPerPage)));
				$entries['remaining-pages'] = max(0, $entries['total-pages'] - $page);

				return $entries;
			}

		}

		/**
		 * Creates a new Entry object using this class as the parent.
		 *
		 * @return Entry
		 */
		public function create(){
			$obj = new Entry($this);
			return $obj;
		}

	}
