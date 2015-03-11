<?php

/**
 * @package toolkit
 */
/**
 * The `FieldManager` class is responsible for managing all fields types in Symphony.
 * Fields are stored on the file system either in the `/fields` folder of `TOOLKIT` or
 * in a `fields` folder in an extension directory.
 */

class FieldManager implements FileResource
{
    /**
     * An array of all the objects that the Manager is responsible for.
     * Defaults to an empty array.
     * @var array
     */
    protected static $_pool = array();

    /**
     * An array of all fields whose have been created by ID
     * @var array
     */
    private static $_initialiased_fields = array();

    /**
     * Given the filename of a Field, return it's handle. This will remove
     * the Symphony conventions of `field.*.php`
     *
     * @param string $filename
     *  The filename of the Field
     * @return string
     */
    public static function __getHandleFromFilename($filename)
    {
        return preg_replace(array('/^field./i', '/.php$/i'), '', $filename);
    }

    /**
     * Given a type, returns the full class name of a Field. Fields use a
     * 'field' prefix
     *
     * @param string $type
     *  A field handle
     * @return string
     */
    public static function __getClassName($type)
    {
        return 'field' . $type;
    }

    /**
     * Finds a Field by type by searching the `TOOLKIT . /fields` folder and then
     * any fields folders in the installed extensions. The function returns
     * the path to the folder where the field class resides.
     *
     * @param string $type
     *  The field handle, that is, `field.{$handle}.php`
     * @return string|boolean
     */
    public static function __getClassPath($type)
    {
        if (is_file(TOOLKIT . "/fields/field.{$type}.php")) {
            return TOOLKIT . '/fields';
        } else {
            $extensions = Symphony::ExtensionManager()->listInstalledHandles();

            if (is_array($extensions) && !empty($extensions)) {
                foreach ($extensions as $e) {
                    if (is_file(EXTENSIONS . "/{$e}/fields/field.{$type}.php")) {
                        return EXTENSIONS . "/{$e}/fields";
                    }
                }
            }
        }

        return false;
    }

    /**
     * Given a field type, return the path to it's class
     *
     * @see __getClassPath()
     * @param string $type
     *  The handle of the field to load (it's type)
     * @return string
     */
    public static function __getDriverPath($type)
    {
        return self::__getClassPath($type) . "/field.{$type}.php";
    }

    /**
     * This function is not implemented by the `FieldManager` class
     *
     * @return boolean
     */
    public static function about($name)
    {
        return false;
    }

    /**
     * Given an associative array of fields, insert them into the database
     * returning the resulting Field ID if successful, or false if there
     * was an error. As fields are saved in order on a section, a query is
     * made to determine the sort order of this field to be current sort order
     * +1.
     *
     * @throws DatabaseException
     * @param array $fields
     *  Associative array of field names => values for the Field object
     * @return integer|boolean
     *  Returns a Field ID of the created Field on success, false otherwise.
     */
    public static function add(array $fields)
    {
        if (!isset($fields['sortorder'])) {
            $fields['sortorder'] = self::fetchNextSortOrder();
        }

        if (!Symphony::Database()->insert($fields, 'tbl_fields')) {
            return false;
        }

        return Symphony::Database()->getInsertID();
    }

    /**
     * Save the settings for a Field given it's `$field_id` and an associative
     * array of settings.
     *
     * @throws DatabaseException
     * @since Symphony 2.3
     * @param integer $field_id
     *  The ID of the field
     * @param array $settings
     *  An associative array of settings, where the key is the column name
     *  and the value is the value.
     * @return boolean
     *  True on success, false on failure
     */
    public static function saveSettings($field_id, $settings)
    {
        // Get the type of this field:
        $type = self::fetchFieldTypeFromID($field_id);

        // Delete the original settings:
        Symphony::Database()->delete("`tbl_fields_".$type."`", sprintf("`field_id` = %d LIMIT 1", $field_id));

        // Insert the new settings into the type table:
        if (!isset($settings['field_id'])) {
            $settings['field_id'] = $field_id;
        }

        return Symphony::Database()->insert($settings, 'tbl_fields_'.$type);
    }

    /**
     * Given a Field ID and associative array of fields, update an existing Field
     * row in the `tbl_fields`table. Returns boolean for success/failure
     *
     * @throws DatabaseException
     * @param integer $id
     *  The ID of the Field that should be updated
     * @param array $fields
     *  Associative array of field names => values for the Field object
     *  This array does need to contain every value for the field object, it
     *  can just be the changed values.
     * @return boolean
     */
    public static function edit($id, array $fields)
    {
        if (!Symphony::Database()->update($fields, "tbl_fields", sprintf(" `id` = %d", $id))) {
            return false;
        }

        return true;
    }

    /**
     * Given a Field ID, delete a Field from Symphony. This will remove the field from
     * the fields table, all of the data stored in this field's `tbl_entries_data_$id` any
     * existing section associations. This function additionally call the Field's `tearDown`
     * method so that it can cleanup any additional settings or entry tables it may of created.
     *
     * @throws DatabaseException
     * @throws Exception
     * @param integer $id
     *  The ID of the Field that should be deleted
     * @return boolean
     */
    public static function delete($id)
    {
        $existing = self::fetch($id);
        $existing->tearDown();

        Symphony::Database()->delete('tbl_fields', sprintf(" `id` = %d", $id));
        Symphony::Database()->delete('tbl_fields_'.$existing->handle(), sprintf(" `field_id` = %d", $id));
        SectionManager::removeSectionAssociation($id);

        Symphony::Database()->query('DROP TABLE IF EXISTS `tbl_entries_data_'.$id.'`');

        return true;
    }

    /**
     * The fetch method returns a instance of a Field from tbl_fields. The most common
     * use of this function is to retrieve a Field by ID, but it can be used to retrieve
     * Fields from a Section also. There are several parameters that can be used to fetch
     * fields by their Type, Location, by a Field Constant or with a custom WHERE query.
     *
     * @throws DatabaseException
     * @throws Exception
     * @param integer|array $id
     *  The ID of the field to retrieve. Defaults to null which will return multiple field
     *  objects. Since Symphony 2.3, `$id` will accept an array of Field ID's
     * @param integer $section_id
     *  The ID of the section to look for the fields in. Defaults to null which will allow
     *  all fields in the Symphony installation to be searched on.
     * @param string $order
     *  Available values of ASC (Ascending) or DESC (Descending), which refer to the
     *  sort order for the query. Defaults to ASC (Ascending)
     * @param string $sortfield
     *  The field to sort the query by. Can be any from the tbl_fields schema. Defaults to
     *  'sortorder'
     * @param string $type
     *  Filter fields by their type, ie. input, select. Defaults to null
     * @param string $location
     *  Filter fields by their location in the entry form. There are two possible values,
     *  'main' or 'sidebar'. Defaults to null
     * @param string $where
     *  Allows a custom where query to be included. Must be valid SQL. The tbl_fields alias
     *  is t1
     * @param integer|string $restrict
     *  Only return fields if they match one of the Field Constants. Available values are
     *  `__TOGGLEABLE_ONLY__`, `__UNTOGGLEABLE_ONLY__`, `__FILTERABLE_ONLY__`,
     *  `__UNFILTERABLE_ONLY__` or `__FIELD_ALL__`. Defaults to `__FIELD_ALL__`
     * @return array
     *  An array of Field objects. If no Field are found, null is returned.
     */
    public static function fetch($id = null, $section_id = null, $order = 'ASC', $sortfield = 'sortorder', $type = null, $location = null, $where = null, $restrict = Field::__FIELD_ALL__)
    {
        $fields = array();
        $returnSingle = false;
        $ids = array();
        $field_contexts = array();

        if (!is_null($id)) {
            if (is_numeric($id)) {
                $returnSingle = true;
            }

            if (!is_array($id)) {
                $field_ids = array((int)$id);
            } else {
                $field_ids = $id;
            }

            // Loop over the `$field_ids` and check to see we have
            // instances of the request fields
            foreach ($field_ids as $key => $field_id) {
                if (
                    isset(self::$_initialiased_fields[$field_id])
                    && self::$_initialiased_fields[$field_id] instanceof Field
                ) {
                    $fields[$field_id] = self::$_initialiased_fields[$field_id];
                    unset($field_ids[$key]);
                }
            }
        }

        // If there is any `$field_ids` left to be resolved lets do that, otherwise
        // if `$id` wasn't provided in the first place, we'll also continue
        if (!empty($field_ids) || is_null($id)) {
            $sql = sprintf(
                "SELECT t1.*
                FROM tbl_fields AS `t1`
                WHERE 1
                %s %s %s %s
                %s",
                (isset($type) ? " AND t1.`type` = '{$type}' " : null),
                (isset($location) ? " AND t1.`location` = '{$location}' " : null),
                (isset($section_id) ? " AND t1.`parent_section` = '{$section_id}' " : null),
                $where,
                isset($field_ids) ? " AND t1.`id` IN(" . implode(',', $field_ids) . ") " : " ORDER BY t1.`{$sortfield}` {$order}"
            );

            if (!$result = Symphony::Database()->fetch($sql)) {
                return ($returnSingle ? null : array());
            }

            // Loop over the resultset building an array of type, field_id
            foreach ($result as $f) {
                $ids[$f['type']][] = $f['id'];
            }

            // Loop over the `ids` array, which is grouped by field type
            // and get the field context.
            foreach ($ids as $type => $field_id) {
                $field_contexts[$type] = Symphony::Database()->fetch(sprintf(
                    "SELECT * FROM `tbl_fields_%s` WHERE `field_id` IN (%s)",
                    $type,
                    implode(',', $field_id)
                ), 'field_id');
            }

            foreach ($result as $f) {
                // We already have this field in our static store
                if (
                    isset(self::$_initialiased_fields[$f['id']])
                    && self::$_initialiased_fields[$f['id']] instanceof Field
                ) {
                    $field = self::$_initialiased_fields[$f['id']];

                    // We don't have an instance of this field, so let's set one up
                } else {
                    $field = self::create($f['type']);
                    $field->setArray($f);
                    // If the field has said that's going to have associations, then go find the
                    // association setting value. In future this check will be most robust with
                    // an interface, but for now, this is what we've got. RE: #2082
                    if ($field->canShowAssociationColumn()) {
                        $field->set('show_association', SectionManager::getSectionAssociationSetting($f['id']));
                    }

                    // Get the context for this field from our previous queries.
                    $context = $field_contexts[$f['type']][$f['id']];

                    if (is_array($context) && !empty($context)) {
                        try {
                            unset($context['id']);
                            $field->setArray($context);
                        } catch (Exception $e) {
                            throw new Exception(__(
                                'Settings for field %s could not be found in table tbl_fields_%s.',
                                array($f['id'], $f['type'])
                            ));
                        }
                    }

                    self::$_initialiased_fields[$f['id']] = $field;
                }

                // Check to see if there was any restricts imposed on the fields
                if (
                    $restrict == Field::__FIELD_ALL__
                    || ($restrict == Field::__TOGGLEABLE_ONLY__ && $field->canToggle())
                    || ($restrict == Field::__UNTOGGLEABLE_ONLY__ && !$field->canToggle())
                    || ($restrict == Field::__FILTERABLE_ONLY__ && $field->canFilter())
                    || ($restrict == Field::__UNFILTERABLE_ONLY__ && !$field->canFilter())
                ) {
                    $fields[$f['id']] = $field;
                }
            }
        }

        return count($fields) <= 1 && $returnSingle ? current($fields) : $fields;
    }

    /**
     * Given a field ID, return the type of the field by querying `tbl_fields`
     *
     * @param integer $id
     * @return string
     */
    public static function fetchFieldTypeFromID($id)
    {
        return Symphony::Database()->fetchVar('type', 0, sprintf("
            SELECT `type` FROM `tbl_fields` WHERE `id` = %d LIMIT 1",
            $id
        ));
    }

    /**
     * Given a field ID, return the handle of the field by querying `tbl_fields`
     *
     * @param integer $id
     * @return string
     */
    public static function fetchHandleFromID($id)
    {
        return Symphony::Database()->fetchVar('element_name', 0, sprintf("
            SELECT `element_name` FROM `tbl_fields` WHERE `id` = %d LIMIT 1",
            $id
        ));
    }

    /**
     * Given an `$element_name` and a `$section_id`, return the Field ID. Symphony enforces
     * a uniqueness constraint on a section where every field must have a unique
     * label (and therefore handle) so whilst it is impossible to have two fields
     * from the same section, it would be possible to have two fields with the same
     * name from different sections. Passing the `$section_id` lets you to specify
     * which section should be searched. If `$element_name` is null, this function will
     * return all the Field ID's from the given `$section_id`.
     *
     * @throws DatabaseException
     * @since Symphony 2.3 This function can now accept $element_name as an array
     *  of handles. These handles can now also include the handle's mode, eg. `title: formatted`
     * @param string|array $element_name
     *  The handle of the Field label, or an array of handles. These handles may contain
     *  a mode as well, eg. `title: formatted`.
     * @param integer $section_id
     *  The section that this field belongs too
     *  The field ID, or an array of field ID's
     * @return mixed
     */
    public static function fetchFieldIDFromElementName($element_name, $section_id = null)
    {
        if (is_null($element_name)) {
            $schema_sql = sprintf("
                SELECT `id`
                FROM `tbl_fields`
                WHERE `parent_section` = %d
                ORDER BY `sortorder` ASC",
                $section_id
            );
        } else {
            $element_names = !is_array($element_name) ? array($element_name) : $element_name;

            // allow for pseudo-fields containing colons (e.g. Textarea formatted/unformatted)
            foreach ($element_names as $index => $name) {
                $parts = explode(':', $name, 2);

                if (count($parts) == 1) {
                    continue;
                }

                unset($element_names[$index]);

                // Prevent attempting to look up 'system', which will arise
                // from `system:pagination`, `system:id` etc.
                if ($parts[0] == 'system') {
                    continue;
                }

                $element_names[] = Symphony::Database()->cleanValue(trim($parts[0]));
            }

            $schema_sql = empty($element_names) ? null : sprintf("
                SELECT `id`
                FROM `tbl_fields`
                WHERE 1
                %s
                AND `element_name` IN ('%s')
                ORDER BY `sortorder` ASC",
                (!is_null($section_id) ? sprintf("AND `parent_section` = %d", $section_id) : ""),
                implode("', '", array_unique($element_names))
            );
        }

        if (is_null($schema_sql)) {
            return false;
        }

        $result = Symphony::Database()->fetch($schema_sql);

        if (count($result) == 1) {
            return (int)$result[0]['id'];
        } elseif (empty($result)) {
            return false;
        } else {
            foreach ($result as &$r) {
                $r = (int)$r['id'];
            }

            return $result;
        }
    }

    /**
     * Work out the next available sort order for a new field
     *
     * @return integer
     *  Returns the next sort order
     */
    public static function fetchNextSortOrder()
    {
        $next = Symphony::Database()->fetchVar(
            "next",
            0,
            "SELECT
                MAX(p.sortorder) + 1 AS `next`
            FROM
                `tbl_fields` AS p
            LIMIT 1"
        );
        return ($next ? (int)$next : 1);
    }

    /**
     * Given a `$section_id`, this function returns an array of the installed
     * fields schema. This includes the `id`, `element_name`, `type`
     * and `location`.
     *
     * @throws DatabaseException
     * @since Symphony 2.3
     * @param integer $section_id
     * @return array
     *  An associative array that contains four keys, `id`, `element_name`,
     * `type` and `location`
     */
    public static function fetchFieldsSchema($section_id)
    {
        return Symphony::Database()->fetch(sprintf(
            "SELECT `id`, `element_name`, `type`, `location`
            FROM `tbl_fields`
            WHERE `parent_section` = %d
            ORDER BY `sortorder` ASC",
            $section_id
        ));
    }

    /**
     * Returns an array of all available field handles discovered in the
     * `TOOLKIT . /fields` or `EXTENSIONS . /extension_handle/fields`.
     *
     * @return array
     *  A single dimensional array of field handles.
     */
    public static function listAll()
    {
        $structure = General::listStructure(TOOLKIT . '/fields', '/field.[a-z0-9_-]+.php/i', false, 'asc', TOOLKIT . '/fields');
        $extensions = Symphony::ExtensionManager()->listInstalledHandles();
        $types = array();

        if (is_array($extensions) && !empty($extensions)) {
            foreach ($extensions as $handle) {
                $path = EXTENSIONS . '/' . $handle . '/fields';
                if (is_dir($path)) {
                    $tmp = General::listStructure($path, '/field.[a-z0-9_-]+.php/i', false, 'asc', $path);

                    if (is_array($tmp['filelist']) && !empty($tmp['filelist'])) {
                        $structure['filelist'] = array_merge($structure['filelist'], $tmp['filelist']);
                    }
                }
            }

            $structure['filelist'] = General::array_remove_duplicates($structure['filelist']);
        }

        foreach ($structure['filelist'] as $filename) {
            $types[] = self::__getHandleFromFilename($filename);
        }

        return $types;
    }

    /**
     * Creates an instance of a given class and returns it. Adds the instance
     * to the `$_pool` array with the key being the handle.
     *
     * @throws Exception
     * @param string $type
     *  The handle of the Field to create (which is it's handle)
     * @return Field
     */
    public static function create($type)
    {
        if (!isset(self::$_pool[$type])) {
            $classname = self::__getClassName($type);
            $path = self::__getDriverPath($type);

            if (!file_exists($path)) {
                throw new Exception(
                    __('Could not find Field %1$s at %2$s.', array('<code>' . $type . '</code>', '<code>' . $path . '</code>'))
                    . ' ' . __('If it was provided by an Extension, ensure that it is installed, and enabled.')
                );
            }

            if (!class_exists($classname)) {
                require_once($path);
            }

            self::$_pool[$type] = new $classname;

            if (self::$_pool[$type]->canShowTableColumn() && !self::$_pool[$type]->get('show_column')) {
                self::$_pool[$type]->set('show_column', 'yes');
            }
        }

        return clone self::$_pool[$type];
    }

    /**
     * Return boolean if the given `$field_type` is in use anywhere in the
     * current Symphony install.
     *
     * @since Symphony 2.3
     * @param string $field_type
     * @return boolean
     */
    public static function isFieldUsed($field_type)
    {
        return Symphony::Database()->fetchVar('count', 0, sprintf(
            "SELECT COUNT(*) AS `count` FROM `tbl_fields` WHERE `type` = '%s'",
            $field_type
        )) > 0;
    }

    /**
     * Check if a specific text formatter is used by a Field
     *
     * @since Symphony 2.3
     * @param string $text_formatter_handle
     *  The handle of the `TextFormatter`
     * @return boolean
     *  true if used, false if not
     */
    public static function isTextFormatterUsed($text_formatter_handle)
    {
        $fields = Symphony::Database()->fetchCol('type', "SELECT DISTINCT `type` FROM `tbl_fields` WHERE `type` NOT IN ('author', 'checkbox', 'date', 'input', 'select', 'taglist', 'upload')");

        if (!empty($fields)) {
            foreach ($fields as $field) {
                try {
                    $table = Symphony::Database()->fetchVar('count', 0, sprintf(
                        "SELECT COUNT(*) AS `count`
                        FROM `tbl_fields_%s`
                        WHERE `formatter` = '%s'",
                        Symphony::Database()->cleanValue($field),
                        $text_formatter_handle
                    ));
                } catch (DatabaseException $ex) {
                    // Table probably didn't have that column
                }

                if ($table > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
