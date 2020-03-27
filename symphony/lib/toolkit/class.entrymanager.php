<?php

/**
 * @package toolkit
 */
/**
 * The `EntryManager` is responsible for all `Entry` objects in Symphony.
 * Entries are stored in the database in a cluster of tables. There is a
 * parent entry row stored in `tbl_entries` and then each field's data is
 * stored in a separate table, `tbl_entries_data_{field_id}`. Where Field ID
 * is generated when the Section is saved. This Manager provides basic
 * add, edit, delete and fetching methods for Entries.
 */
class EntryManager
{
    /**
     * The Field ID that will be used to sort when fetching Entries, defaults
     * to null, which implies the Entry ID (id column in `tbl_entries`).
     * To order by core fields, use one of
     * 'system:creation-date', 'system:modification-date', 'system:id'.
     * @deprecated @since Symphony 3.0.0
     *  Use select()->sort() instead
     * @var integer|string
     */
    protected static $_fetchSortField = null;

    /**
     * The direction that entries should be sorted in, available options are
     * RAND, ASC or DESC. Defaults to null, which implies ASC
     * @deprecated @since Symphony 3.0.0
     *  Use select()->sort() instead
     * @var string
     */
    protected static $_fetchSortDirection = null;

    /**
     * Setter function for the default sorting direction of the Fetch
     * function. Available options are RAND, ASC or DESC.
     *
     * @deprecated @since Symphony 3.0.0
     *  Use select()->sort() instead
     * @param string $direction
     *  The direction that entries should be sorted in, available options
     *  are RAND, ASC or DESC.
     */
    public static function setFetchSortingDirection($direction)
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog(
                'EntryManager::setFetchSortingDirection()',
                'EntryManager::select()->sort()'
            );
        }

        $direction = strtoupper($direction);

        if ($direction == 'RANDOM') {
            $direction = 'RAND';
        }

        self::$_fetchSortDirection = (in_array($direction, array('RAND', 'ASC', 'DESC')) ? $direction : null);
    }

    /**
     * Sets the field to applying the sorting direction on when fetching
     * entries
     *
     * @deprecated @since Symphony 3.0.0
     *  Use select()->sort() instead
     * @param integer $field_id
     *  The ID of the Field that should be sorted on
     */
    public static function setFetchSortingField($field_id)
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog(
                'EntryManager::setFetchSortingField()',
                'EntryManager::select()->sort()'
            );
        }
        self::$_fetchSortField = $field_id;
    }

    /**
     * Convenience function that will set sorting field and direction
     * by calling `setFetchSortingField()` & `setFetchSortingDirection()`
     *
     * @deprecated @since Symphony 3.0.0
     *  Use select()->sort() instead
     * @see toolkit.EntryManager#setFetchSortingField()
     * @see toolkit.EntryManager#setFetchSortingDirection()
     * @param integer $field_id
     *  The ID of the Field that should be sorted on
     * @param string $direction
     *  The direction that entries should be sorted in, available options
     *  are RAND, ASC or DESC. Defaults to ASC
     */
    public static function setFetchSorting($field_id, $direction = 'ASC')
    {
        self::setFetchSortingField($field_id);
        self::setFetchSortingDirection($direction);
    }

    /**
     * Returns an object representation of the sorting for the
     * EntryManager, with the field and direction provided
     *
     * @deprecated @since Symphony 3.0.0
     *  Use select()->sort() instead
     * @return StdClass
     */
    public static function getFetchSorting()
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog(
                'EntryManager::getFetchSorting()',
                'EntryManager::select()->sort()'
            );
        }
        return (object)array(
            'field' => self::$_fetchSortField,
            'direction' => self::$_fetchSortDirection
        );
    }

    /**
     * Executes the SQL queries need to save a field's data for the specified
     * entry id.
     *
     * It first locks the table for writes, it then deletes existing data and then
     * it inserts a new row for the data. Errors are discarded and the lock is
     * released, if it was acquired.
     *
     * @param int $entry_id
     *  The entry id to save the data for
     * @param int $field_id
     *  The field id to save the data for
     * @param array $field
     *  The field data to save
     */
    protected static function saveFieldData($entry_id, $field_id, $field)
    {
        // Check that we have a field id
        if (empty($field_id)) {
            return;
        }

        // Ignore parameter when not an array
        if (!is_array($field)) {
            $field = [];
        }

        // Check if table exists
        $table_name = 'tbl_entries_data_' . General::intval($field_id);
        if (!Symphony::Database()->tableExists($table_name)) {
            return;
        }

        // Delete old data
        Symphony::Database()
            ->delete($table_name)
            ->where(['entry_id' => $entry_id])
            ->execute();

        // Insert new data
        $data = [
            'entry_id' => $entry_id
        ];

        $fields = [];

        foreach ($field as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $ii => $v) {
                    $fields[$ii][$key] = $v;
                }
            } else {
                $fields[max(0, count($fields) - 1)][$key] = $value;
            }
        }

        foreach ($fields as $index => $field_data) {
            $fields[$index] = array_merge($data, $field_data);
        }

        // Insert only if we have field data
        if (!empty($fields)) {
            foreach ($fields as $f) {
                Symphony::Database()
                    ->insert($table_name)
                    ->values($f)
                    ->execute();
            }
        }
    }

    /**
     * Given an Entry object, iterate over all of the fields in that object
     * an insert them into their relevant entry tables.
     *
     * @see EntryManager::saveFieldData()
     * @param Entry $entry
     *  An Entry object to insert into the database
     * @throws DatabaseException
     * @return boolean
     */
    public static function add(Entry $entry)
    {
        return Symphony::Database()->transaction(function (Database $db) use ($entry) {
            $fields = $entry->get();
            $inserted = $db
                ->insert('tbl_entries')
                ->values($fields)
                ->execute()
                ->success();

            if (!$inserted || !$entry_id = $db->getInsertID()) {
                throw new DatabaseException('Could not insert in the entries table.');
            }

            // Iterate over all data for this entry
            foreach ($entry->getData() as $field_id => $field) {
                // Write data
                static::saveFieldData($entry_id, $field_id, $field);
            }

            $entry->set('id', $entry_id);
        })->execute()->success();
    }

    /**
     * Update an existing Entry object given an Entry object
     *
     * @see EntryManager::saveFieldData()
     * @param Entry $entry
     *  An Entry object
     * @throws DatabaseException
     * @return boolean
     */
    public static function edit(Entry $entry)
    {
        return Symphony::Database()->transaction(function (Database $db) use ($entry) {
            // Update modification date and modification author.
            $updated = $db
                ->update('tbl_entries')
                ->set([
                    'modification_author_id' => $entry->get('modification_author_id'),
                    'modification_date' => $entry->get('modification_date'),
                    'modification_date_gmt' => $entry->get('modification_date_gmt')
                ])
                ->where(['id' => $entry->get('id')])
                ->execute()
                ->success();

            if (!$updated) {
                throw new DatabaseException('Could not update the entries table.');
            }

            // Iterate over all data for this entry
            foreach ($entry->getData() as $field_id => $field) {
                // Write data
                static::saveFieldData($entry->get('id'), $field_id, $field);
            }
        })->execute()->success();
    }

    /**
     * Given an Entry ID, or an array of Entry ID's, delete all
     * data associated with this Entry using a Field's `entryDataCleanup()`
     * function, and then remove this Entry from `tbl_entries`. If the `$entries`
     * all belong to the same section, passing `$section_id` will improve
     * performance
     *
     * @param array|integer $entries
     *  An entry_id, or an array of entry id's to delete
     * @param integer $section_id (optional)
     *  If possible, the `$section_id` of the the `$entries`. This parameter
     *  should be left as null if the `$entries` array contains entry_id's for
     *  multiple sections.
     * @throws DatabaseException
     * @throws Exception
     * @return boolean
     */
    public static function delete($entries, $section_id = null)
    {
        $needs_data = true;

        if (!is_array($entries)) {
            $entries = array($entries);
        }

        // Get the section's schema
        if (!is_null($section_id)) {
            $section = (new SectionManager)->select()->section($section_id)->execute()->next();

            if ($section instanceof Section) {
                $fields = $section->fetchFields();
                $data = array();

                foreach ($fields as $field) {
                    $reflection = new ReflectionClass($field);
                    // This field overrides the default implementation, so pass it data.
                    $data[$field->get('element_name')] = $reflection->getMethod('entryDataCleanup')->class == 'Field' ? false : true;
                }

                $data = array_filter($data);

                if (empty($data)) {
                    $needs_data = false;
                }
            }
        }

        // We'll split $entries into blocks of 2500 (random number)
        // and process the deletion in chunks.
        $chunks = array_chunk($entries, 2500);

        foreach ($chunks as $chunk) {
            // If we weren't given a `section_id` we'll have to process individually
            // If we don't need data for any field, we can process the whole chunk
            // without building Entry objects, otherwise we'll need to build
            // Entry objects with data
            if (is_null($section_id) || !$needs_data) {
                $entries = $chunk;
            } elseif ($needs_data) {
                $entries = (new EntryManager)
                    ->select()
                    ->entries($chunk)
                    ->section($section_id)
                    ->includeAllFields()
                    ->disableDefaultSort()
                    ->execute()
                    ->rows();
            }

            if ($needs_data) {
                foreach ($entries as $id) {
                    // Handles the case where `section_id` was not provided
                    if (is_null($section_id)) {
                        $e = (new EntryManager)->select()->entry($id)->execute()->next();

                        if (!$e) {
                            continue;
                        }

                        $e = (new EntryManager)
                            ->select()
                            ->entry($id)
                            ->section($e->get('section_id'))
                            ->includeAllFields()
                            ->disableDefaultSort()
                            ->execute()
                            ->next();

                        // If we needed data, whole Entry objects will exist
                    } elseif ($needs_data) {
                        $e = $id;
                        $id = $e->get('id');
                    }

                    // Time to loop over it and send it to the fields.
                    // Note we can't rely on the `$fields` array as we may
                    // also be dealing with the case where `section_id` hasn't
                    // been provided
                    $entry_data = $e->getData();

                    foreach ($entry_data as $field_id => $data) {
                        $field = (new FieldManager)->select()->field($field_id)->execute()->next();
                        $field->entryDataCleanup($id, $data);
                    }
                }
            } else {
                foreach ($fields as $field) {
                    $field->entryDataCleanup($chunk);
                }
            }

            Symphony::Database()
                ->delete('tbl_entries')
                ->where(['id' => ['in' => $chunk]])
                ->execute();
        }

        return true;
    }

    /**
     * This function will return an array of Entry objects given an ID or an array of ID's.
     * Do not provide `$entry_id` as an array if not specifying the `$section_id`. This function
     * is commonly passed custom SQL statements through the `$where` and `$join` parameters
     * that is generated by the fields of this section.
     *
     * @deprecated @since Symphony 3.0.0
     *  Use select() instead
     * @since Symphony 2.7.0 it will also call a new method on fields,
     * `buildSortingSelectSQL()`, to make sure fields can add ordering columns in
     * the SELECT clause. This is required on MySQL 5.7+ strict mode.
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
     *  Any custom WHERE clauses. The tbl_entries alias is `e`
     * @param string $joins
     *  Any custom JOIN's
     * @param boolean $group
     *  Whether the entries need to be grouped by Entry ID or not
     * @param boolean $buildentries
     *  Whether to return an array of entry ID's or Entry objects. Defaults to
     *  true, which will return Entry objects
     * @param array $element_names
     *  Choose whether to get data from a subset of fields or all fields in a section,
     *  by providing an array of field names. Defaults to null, which will load data
     *  from all fields in a section.
     * @param boolean $enable_sort
     *  Defaults to true, if false this function will not apply any sorting
     * @throws Exception
     * @return array
     *  If `$buildentries` is true, this function will return an array of Entry objects,
     *  otherwise it will return an associative array of Entry data from `tbl_entries`
     */
    public static function fetch($entry_id = null, $section_id = null, $limit = null, $start = null, $where = null, $joins = null, $group = false, $buildentries = true, $element_names = null, $enable_sort = true)
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog('EntryManager::fetch()', 'EntryManager::select()');
        }

        if (!$entry_id && !$section_id) {
            return [];
        }

        if (!$section_id) {
            $section_id = self::fetchEntrySectionID($entry_id);
        }

        $section = (new SectionManager)->select()->section($section_id)->execute()->next();
        if (!is_object($section)) {
            return [];
        }

        $query = (new EntryManager)->select();

        if ($group) {
            $query->distinct();
        }

        // A single $entry_id doesn't need to be sorted on, or if it's explicitly disabled
        if ((!is_array($entry_id) && General::intval($entry_id) > 0) || !$enable_sort) {
            $query->disableDefaultSort();
        } elseif (self::$_fetchSortField) {
            $query->sort((string)self::$_fetchSortField, self::$_fetchSortDirection);
        }

        if ($entry_id && !is_array($entry_id)) {
            // The entry ID may be a comma-separated string, so explode it to make it
            // a proper array
            $entry_id = explode(',', $entry_id);
        }

        // An existing entry ID will be an array now, and we can force integer values
        if ($entry_id) {
            $entry_id = array_map(array('General', 'intval'), $entry_id);
        }
        if (!empty($entry_id)) {
            $query->entries($entry_id);
        }
        if ($limit) {
            $query->limit($limit);
        }
        if ($start) {
            $query->offset($start);
        }
        if ($element_names === null) {
            $element_names = array_map(function ($field) {
                return $field['element_name'];
            }, $section->fetchFieldsSchema());
        }
        if ($buildentries && is_array($element_names)) {
            $query->schema($element_names);
        }
        if ($joins) {
            $joins = $query->replaceTablePrefix($joins);
            $query->unsafeAppendSQLPart('join', $joins);
        }
        if ($where) {
            $where = $query->replaceTablePrefix($where);
            // Ugly hack: mysqli allowed this....
            $where = str_replace('IN ()', 'IN (0)', $where);
            $query->unsafe()->unsafeAppendSQLPart('where', "WHERE 1=1 $where");
        }
        $query->section($section_id);

        return $query->execute()->rows();
    }

    /**
     * Given an Entry ID, return the Section ID that it belongs to
     *
     * @param integer $entry_id
     *  The ID of the Entry to return it's section
     * @return integer
     *  The Section ID for this Entry's section
     */
    public static function fetchEntrySectionID($entry_id)
    {
        return (new EntryManager)
            ->select()
            ->entry($entry_id)
            ->limit(1)
            ->execute()
            ->integer('section_id');
    }

    /**
     * Return the count of the number of entries in a particular section.
     *
     * @deprecated @since Symphony 3.0.0
     *  Use select() instead
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
    public static function fetchCount($section_id = null, $where = null, $joins = null, $group = false)
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog('EntryManager::fetchCount()', 'EntryManager::select()->count()');
        }

        if (is_null($section_id)) {
            return false;
        }

        $section = (new SectionManager)->select()->section($section_id)->execute()->next();

        if (!is_object($section)) {
            return false;
        }

        $sql = (new EntryManager)->select()->count()->section($section_id);

        if ($group) {
            $sql->distinct();
        }
        if ($joins) {
            $joins = $sql->replaceTablePrefix($joins);
            $sql->unsafeAppendSQLPart('join', $joins);
        }
        if ($where) {
            $where = $sql->replaceTablePrefix($where);
            // Ugly hack: mysqli allowed this....
            $where = str_replace('IN ()', 'IN (0)', $where);
            $sql->unsafe()->unsafeAppendSQLPart('where', $where);
        }

        return $sql->execute()->integer(0);
    }

    /**
     * Returns an array of Entry objects, with some basic pagination given
     * the number of Entry's to return and the current starting offset. This
     * function in turn calls the fetch function that does alot of the heavy
     * lifting. For instance, if there are 60 entries in a section and the pagination
     * dictates that per page, 15 entries are to be returned, by passing 2 to
     * the $page parameter you could return entries 15-30
     *
     * @deprecated @since Symphony 3.0.0
     *  Use select() instead
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
     * @throws Exception
     * @return array
     *  Either an array of Entry objects, or an associative array containing
     *  the total entries, the start position, the entries per page and the
     *  Entry objects
     */
    public static function fetchByPage($page = 1, $section_id, $entriesPerPage, $where = null, $joins = null, $group = false, $records_only = false, $buildentries = true, array $element_names = null)
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog(
                'EntryManager::fetchByPage()',
                'EntryManager::select()->paginate()'
            );
        }

        if ($entriesPerPage != null && !is_string($entriesPerPage) && !is_numeric($entriesPerPage)) {
            throw new Exception(__('Entry limit specified was not a valid type. String or Integer expected.'));
        } elseif ($entriesPerPage == null) {
            $records = self::fetch(null, $section_id, null, null, $where, $joins, $group, $buildentries, $element_names);

            $count = self::fetchCount($section_id, $where, $joins, $group);

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
        } else {
            $start = (max(1, $page) - 1) * $entriesPerPage;

            $records = ($entriesPerPage == '0' ? null : self::fetch(null, $section_id, $entriesPerPage, $start, $where, $joins, $group, $buildentries, $element_names));

            if ($records_only) {
                return array('records' => $records);
            }

            $entries = array(
                'total-entries' => self::fetchCount($section_id, $where, $joins, $group),
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
    public static function create()
    {
        return new Entry;
    }

    /**
     * Factory method that creates a new EntryQuery.
     *
     * @since Symphony 3.0.0
     * @param array $values
     *  The fields to select. By default it's none of them, so the query
     *  only populates the object with its data.
     * @return EntryQuery
     */
    public function select(array $schema = [])
    {
        return new EntryQuery(Symphony::Database(), $schema);
    }
}
