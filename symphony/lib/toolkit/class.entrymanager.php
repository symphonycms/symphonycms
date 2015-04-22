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
     * to null, which implies the Entry ID (id column in `tbl_entries`)
     * @var integer
     */
    protected static $_fetchSortField = null;

    /**
     * The direction that entries should be sorted in, available options are
     * RAND, ASC or DESC. Defaults to null, which implies ASC
     * @var string
     */
    protected static $_fetchSortDirection = null;

    /**
     * Setter function for the default sorting direction of the Fetch
     * function. Available options are RAND, ASC or DESC.
     *
     * @param string $direction
     *  The direction that entries should be sorted in, available options
     *  are RAND, ASC or DESC.
     */
    public static function setFetchSortingDirection($direction)
    {
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
     * @param integer $field_id
     *  The ID of the Field that should be sorted on
     */
    public static function setFetchSortingField($field_id)
    {
        self::$_fetchSortField = $field_id;
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
    public static function setFetchSorting($field_id, $direction = 'ASC')
    {
        self::setFetchSortingField($field_id);
        self::setFetchSortingDirection($direction);
    }

    /**
     * Returns an object representation of the sorting for the
     * EntryManager, with the field and direction provided
     *
     * @return StdClass
     */
    public static function getFetchSorting()
    {
        return (object)array(
            'field' => self::$_fetchSortField,
            'direction' => self::$_fetchSortDirection
        );
    }

    /**
     * Given an Entry object, iterate over all of the fields in that object
     * an insert them into their relevant entry tables.
     *
     * @param Entry $entry
     *  An Entry object to insert into the database
     * @throws DatabaseException
     * @return boolean
     */
    public static function add(Entry $entry)
    {
        $fields = $entry->get();
        Symphony::Database()->insert($fields, 'tbl_entries');

        if (!$entry_id = Symphony::Database()->getInsertID()) {
            return false;
        }

        foreach ($entry->getData() as $field_id => $field) {
            if (!is_array($field) || empty($field)) {
                continue;
            }

            Symphony::Database()->delete('tbl_entries_data_' . $field_id, sprintf("
                `entry_id` = %d", $entry_id
            ));

            $data = array(
                'entry_id' => $entry_id
            );

            $fields = array();

            foreach ($field as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $ii => $v) {
                        $fields[$ii][$key] = $v;
                    }
                } else {
                    $fields[max(0, count($fields) - 1)][$key] = $value;
                }
            }

            $fieldCount = count($fields);
            for ($ii = 0; $ii < $fieldCount; $ii++) {
                $fields[$ii] = array_merge($data, $fields[$ii]);
            }

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
     * @throws DatabaseException
     * @return boolean
     */
    public static function edit(Entry $entry)
    {
        // Update modification date.
        Symphony::Database()->update(
            array(
                'modification_date' => $entry->get('modification_date'),
                'modification_date_gmt' => $entry->get('modification_date_gmt')
            ),
            'tbl_entries',
            sprintf(' `id` = %d', $entry->get('id'))
        );

        // Iterate over all data for this entry, deleting existing data first
        // then inserting a new row for the data
        foreach ($entry->getData() as $field_id => $field) {
            if (empty($field_id)) {
                continue;
            }

            try {
                Symphony::Database()->delete('tbl_entries_data_' . $field_id, sprintf("
                    `entry_id` = %d", $entry->get('id')
                ));
            } catch (Exception $e) {
                // Discard?
            }

            if (!is_array($field) || empty($field)) {
                continue;
            }

            $data = array(
                'entry_id' => $entry->get('id')
            );

            $fields = array();

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

            Symphony::Database()->insert($fields, 'tbl_entries_data_' . $field_id);
        }

        return true;
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
            $section = SectionManager::fetch($section_id);

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
            $entry_list = implode("', '", $chunk);

            // If we weren't given a `section_id` we'll have to process individually
            // If we don't need data for any field, we can process the whole chunk
            // without building Entry objects, otherwise we'll need to build
            // Entry objects with data
            if (is_null($section_id) || !$needs_data) {
                $entries = $chunk;
            } elseif ($needs_data) {
                $entries = self::fetch($chunk, $section_id);
            }

            if ($needs_data) {
                foreach ($entries as $id) {
                    // Handles the case where `section_id` was not provided
                    if (is_null($section_id)) {
                        $e = self::fetch($id);

                        if (!is_array($e)) {
                            continue;
                        }

                        $e = current($e);

                        if (!$e instanceof Entry) {
                            continue;
                        }

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
                        $field = FieldManager::fetch($field_id);
                        $field->entryDataCleanup($id, $data);
                    }
                }
            } else {
                foreach ($fields as $field) {
                    $field->entryDataCleanup($chunk);
                }
            }

            Symphony::Database()->delete('tbl_entries', " `id` IN ('$entry_list') ");
        }

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
        $sort = null;

        if (!$entry_id && !$section_id) {
            return false;
        }

        if (!$section_id) {
            $section_id = self::fetchEntrySectionID($entry_id);
        }

        $section = SectionManager::fetch($section_id);
        if (!is_object($section)) {
            return false;
        }

        // SORTING
        // A single $entry_id doesn't need to be sorted on, or if it's explicitly disabled
        if ((!is_array($entry_id) && !is_null($entry_id) && is_int($entry_id)) || !$enable_sort) {
            $sort = null;

        // Check for RAND first, since this works independently of any specific field
        } elseif (self::$_fetchSortDirection == 'RAND') {
            $sort = 'ORDER BY RAND() ';

        // Handle Creation Date or the old Date sorting
        } elseif (self::$_fetchSortField === 'system:creation-date' || self::$_fetchSortField === 'date') {
            $sort = sprintf('ORDER BY `e`.`creation_date_gmt` %s', self::$_fetchSortDirection);

        // Handle Modification Date sorting
        } elseif (self::$_fetchSortField === 'system:modification-date') {
            $sort = sprintf('ORDER BY `e`.`modification_date_gmt` %s', self::$_fetchSortDirection);

        // Handle sorting for System ID
        } elseif (self::$_fetchSortField == 'system:id' || self::$_fetchSortField == 'id') {
            $sort = sprintf('ORDER BY `e`.`id` %s', self::$_fetchSortDirection);

        // Handle when the sort field is an actual Field
        } elseif (self::$_fetchSortField && $field = FieldManager::fetch(self::$_fetchSortField)) {
            if ($field->isSortable()) {
                $field->buildSortingSQL($joins, $where, $sort, self::$_fetchSortDirection);
            }

        // Handle if the section has a default sorting field
        } elseif ($section->getSortingField() && $field = FieldManager::fetch($section->getSortingField())) {
            if ($field->isSortable()) {
                $field->buildSortingSQL($joins, $where, $sort, $section->getSortingOrder());
            }

        // No sort specified, so just sort on system id
        } else {
            $sort = sprintf('ORDER BY `e`.`id` %s', self::$_fetchSortDirection);
        }

        if ($field && !$group) {
            $group = $field->requiresSQLGrouping();
        }

        if ($entry_id && !is_array($entry_id)) {
            $entry_id = array($entry_id);
        }

        $sql = sprintf("
            SELECT %s`e`.id, `e`.section_id, e.`author_id`,
                UNIX_TIMESTAMP(e.`creation_date`) AS `creation_date`,
                UNIX_TIMESTAMP(e.`modification_date`) AS `modification_date`
            FROM `tbl_entries` AS `e`
            %s
            WHERE 1
            %s
            %s
            %s
            %s
            %s
            ", 
            $group ? 'DISTINCT ' : '',
            $joins,
            $entry_id ? "AND `e`.`id` IN ('".implode("', '", $entry_id)."') " : '',
            $section_id ? sprintf("AND `e`.`section_id` = %d", $section_id) : '',
            $where,
            $sort,
            $limit ? sprintf('LIMIT %d, %d', $start, $limit) : ''
        );

        $rows = Symphony::Database()->fetch($sql);

        return ($buildentries && (is_array($rows) && !empty($rows)) ? self::__buildEntries($rows, $section_id, $element_names) : $rows);
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
     * @throws DatabaseException
     * @return array
     *  An array of Entry objects
     */
    public static function __buildEntries(array $rows, $section_id, $element_names = null)
    {
        $entries = array();

        if (empty($rows)) {
            return $entries;
        }

        $schema = FieldManager::fetchFieldIDFromElementName($element_names, $section_id);

        if (is_int($schema)) {
            $schema = array($schema);
        }

        $raw = array();
        $rows_string = '';

        // Append meta data:
        foreach ($rows as $entry) {
            $raw[$entry['id']]['meta'] = $entry;
            $rows_string .= $entry['id'] . ',';
        }

        $rows_string = trim($rows_string, ',');

        // Append field data:
        if (is_array($schema)) {
            foreach ($schema as $field_id) {
                try {
                    $row = Symphony::Database()->fetch("SELECT * FROM `tbl_entries_data_{$field_id}` WHERE `entry_id` IN ($rows_string) ORDER BY `id` ASC");
                } catch (Exception $e) {
                    // No data due to error
                    continue;
                }

                if (!is_array($row) || empty($row)) {
                    continue;
                }

                foreach ($row as $r) {
                    $entry_id = $r['entry_id'];

                    unset($r['id']);
                    unset($r['entry_id']);

                    if (!isset($raw[$entry_id]['fields'][$field_id])) {
                        $raw[$entry_id]['fields'][$field_id] = $r;
                    } else {
                        foreach (array_keys($r) as $key) {
                            // If this field already has been set, we need to take the existing
                            // value and make it array, adding the current value to it as well
                            // There is a special check incase the the field's value has been
                            // purposely set to null in the database.
                            if (
                                (
                                    isset($raw[$entry_id]['fields'][$field_id][$key])
                                    || is_null($raw[$entry_id]['fields'][$field_id][$key])
                                )
                                && !is_array($raw[$entry_id]['fields'][$field_id][$key])
                            ) {
                                $raw[$entry_id]['fields'][$field_id][$key] = array(
                                    $raw[$entry_id]['fields'][$field_id][$key],
                                    $r[$key]
                                );

                                // This key/value hasn't been set previously, so set it
                            } elseif (!isset($raw[$entry_id]['fields'][$field_id][$key])) {
                                $raw[$entry_id]['fields'][$field_id] = array($r[$key]);

                                // This key has been set and it's an array, so just append
                                // this value onto the array
                            } else {
                                $raw[$entry_id]['fields'][$field_id][$key][] = $r[$key];
                            }
                        }
                    }
                }
            }
        }

        // Loop over the array of entry data and convert it to an array of Entry objects
        foreach ($raw as $entry) {
            $obj = self::create();

            $obj->set('id', $entry['meta']['id']);
            $obj->set('author_id', $entry['meta']['author_id']);
            $obj->set('section_id', $entry['meta']['section_id']);
            $obj->set('creation_date', DateTimeObj::get('c', $entry['meta']['creation_date']));

            if (isset($entry['meta']['modification_date'])) {
                $obj->set('modification_date', DateTimeObj::get('c', $entry['meta']['modification_date']));
            } else {
                $obj->set('modification_date', $obj->get('creation_date'));
            }

            $obj->creationDate = $obj->get('creation_date');

            if (isset($entry['fields']) && is_array($entry['fields'])) {
                foreach ($entry['fields'] as $field_id => $data) {
                    $obj->setData($field_id, $data);
                }
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
    public static function fetchEntrySectionID($entry_id)
    {
        return Symphony::Database()->fetchVar('section_id', 0, sprintf("
            SELECT `section_id` FROM `tbl_entries` WHERE `id` = %d LIMIT 1",
            $entry_id
        ));
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
    public static function fetchCount($section_id = null, $where = null, $joins = null, $group = false)
    {
        if (is_null($section_id)) {
            return false;
        }

        $section = SectionManager::fetch($section_id);

        if (!is_object($section)) {
            return false;
        }

        return Symphony::Database()->fetchVar('count', 0, sprintf("
                SELECT COUNT(%s`e`.id) as `count`
                FROM `tbl_entries` AS `e`
                %s
                WHERE `e`.`section_id` = %d
                %s
            ",
            $group ? 'DISTINCT ' : '',
            $joins,
            $section_id,
            $where
        ));
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
     * @throws Exception
     * @return array
     *  Either an array of Entry objects, or an associative array containing
     *  the total entries, the start position, the entries per page and the
     *  Entry objects
     */
    public static function fetchByPage($page = 1, $section_id, $entriesPerPage, $where = null, $joins = null, $group = false, $records_only = false, $buildentries = true, array $element_names = null)
    {
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
}
