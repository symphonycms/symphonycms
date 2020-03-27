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
     * @return integer
     *  Returns a Field ID of the created Field on success, 0 otherwise.
     */
    public static function add(array $fields)
    {
        if (!isset($fields['sortorder'])) {
            $fields['sortorder'] = self::fetchNextSortOrder();
        }

        $inserted = Symphony::Database()
            ->insert('tbl_fields')
            ->values($fields)
            ->execute()
            ->success();

        return $inserted ? Symphony::Database()->getInsertID() : 0;
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
     *  true on success, false on failure
     */
    public static function saveSettings($field_id, $settings)
    {
        return Symphony::Database()->transaction(function (Database $db) use ($field_id, $settings) {
            // Get the type of this field:
            $type = self::fetchFieldTypeFromID($field_id);

            if (!$type) {
                throw new Exception("Field id `$field_id` does not map to a field");
            }

            // Delete the original settings:
            $db
                ->delete("tbl_fields_$type")
                ->where(['field_id' => $field_id])
                ->limit(1)
                ->execute();

            // Insert the new settings into the type table:
            if (!isset($settings['field_id'])) {
                $settings['field_id'] = $field_id;
            }

            $db
                ->insert("tbl_fields_$type")
                ->values($settings)
                ->execute();
        })->execute()->success();
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
        return Symphony::Database()
            ->update('tbl_fields')
            ->set($fields)
            ->where(['id' => (int)$id])
            ->execute()
            ->success();
    }

    /**
     * Given a Field ID, delete a Field from Symphony. This will remove the field from
     * the fields table, all of the data stored in this field's `tbl_entries_data_$id` any
     * existing section associations. This function additionally call the Field's `tearDown`
     * method so that it can cleanup any additional settings or entry tables it may of created.
     *
     * @since Symphony 2.7.0 it will check to see if the field requires a data table before
     * blindly trying to delete it.
     *
     * @throws DatabaseException
     * @throws Exception
     * @param integer $id
     *  The ID of the Field that should be deleted
     * @return boolean
     */
    public static function delete($id)
    {
        $existing = (new FieldManager)->select()->field($id)->execute()->next();
        if (!$existing) {
            return true;
        }
        $existing->tearDown();

        Symphony::Database()
            ->delete('tbl_fields')
            ->where(['id' => (int)$id])
            ->execute();
        Symphony::Database()
            ->delete('tbl_fields_' . $existing->handle())
            ->where(['field_id' => (int)$id])
            ->execute();
        SectionManager::removeSectionAssociation($id);

        if ($existing->requiresTable()) {
            return Symphony::Database()
                ->drop("tbl_entries_data_$id")
                ->ifExists()
                ->execute()
                ->success();
        }

        return true;
    }

    /**
     * @internal Checks if we already have a Field object for this $field_id.
     *
     * @since Symphony 3.0.0
     * @param int $field_id
     *  The field id to look for
     * @return Field
     *  The Field object instance, if it exists. null otherwise.
     */
    public static function getInitializedField($field_id)
    {
        if (isset(self::$_initialiased_fields[$field_id]) &&
            self::$_initialiased_fields[$field_id] instanceof Field) {
            return self::$_initialiased_fields[$field_id];
        }
        return null;
    }

    /**
     * @internal Sets a Field object in the static store.
     *
     * @since Symphony 3.0.0
     * @throws Exception
     *  If the Field is already in the cache, an Exception is thrown.
     * @param Field $field
     *  The Field object to store
     * @return void
     */
    public static function setInitializedField(Field $field)
    {
        $field_id = $field->get('id');
        if (self::getInitializedField($field_id)) {
            throw new Exception('Field is already in the cache');
        }
        self::$_initialiased_fields[$field_id] = $field;
    }

    /**
     * The fetch method returns a instance of a Field from tbl_fields. The most common
     * use of this function is to retrieve a Field by ID, but it can be used to retrieve
     * Fields from a Section also. There are several parameters that can be used to fetch
     * fields by their Type, Location, by a Field Constant or with a custom WHERE query.
     *
     * @deprecated @since Symphony 3.0.0
     *  Use select() instead
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
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog('FieldManager::fetch()', 'FieldManager::select()');
        }

        $fields = [];
        $returnSingle = false;
        $ids = [];
        $field_contexts = [];

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
                if ($if = self::getInitializedField($field_id)) {
                    $fields[$field_id] = $if;
                    unset($field_ids[$key]);
                }
            }
        }

        // If there is any `$field_ids` left to be resolved lets do that, otherwise
        // if `$id` wasn't provided in the first place, we'll also continue
        if (!empty($field_ids) || is_null($id)) {
            $query = (new FieldManager)->select();

            if ($type) {
                $query->type($type);
            }
            if ($location) {
                $query->location($location);
            }
            if ($section_id) {
                $query->section($section_id);
            }
            if ($field_ids) {
                $query->fields($field_ids);
            }
            if ($where) {
                $where = $query->replaceTablePrefix($where);
                // Replace legacy `t1` alias
                $where = str_replace('t1.', '`f`.', $where);
                $where = str_replace('`t1`.', '`f`.', $where);
                // Ugly hack: mysqli allowed this....
                $where = str_replace('IN ()', 'IN (0)', $where);
                $wherePrefix = $query->containsSQLParts('where') ? '' : 'WHERE 1 = 1';
                $query->unsafe()->unsafeAppendSQLPart('where', "$wherePrefix $where");
            }
            if ($sortfield) {
                $query->sort((string)$sortfield);
            }

            $result = $query->execute()->rows();

            if (empty($result)) {
                return ($returnSingle ? null : []);
            }
            foreach ($result as $field) {
                $fields[$field->get('id')] = $field;
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
        return Symphony::Database()
            ->select(['type'])
            ->from('tbl_fields')
            ->where(['id' => (int)$id])
            ->limit(1)
            ->execute()
            ->string('type');
    }

    /**
     * Given a field ID, return the handle of the field by querying `tbl_fields`
     *
     * @param integer $id
     * @return string
     */
    public static function fetchHandleFromID($id)
    {
        return Symphony::Database()
            ->select(['element_name'])
            ->from('tbl_fields')
            ->where(['id' => (int)$id])
            ->limit(1)
            ->execute()
            ->string('element_name');
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
        $schema_sql = Symphony::Database()
            ->select(['id'])
            ->from('tbl_fields')
            ->orderBy(['sortorder' => 'ASC'])
            ->usePlaceholders();

        if ($element_name) {
            $element_names = !is_array($element_name) ? [$element_name] : $element_name;

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

                $element_names[] = trim($parts[0]);
            }

            if (!empty($element_names)) {
                $schema_sql->where(['element_name' => ['in' => array_unique($element_names)]]);
            }
        }

        if ($section_id) {
            $schema_sql->where(['parent_section' => $section_id]);
        }

        $result = $schema_sql->execute()->column('id');

        if (empty($result)) {
            return false;
        } elseif (count($result) === 1) {
            return (int)$result[0];
        }
        return array_map('intval', $result);
    }

    /**
     * Work out the next available sort order for a new field
     *
     * @return integer
     *  Returns the next sort order
     */
    public static function fetchNextSortOrder()
    {
        $next = Symphony::Database()
            ->select(['MAX(sortorder)'])
            ->from('tbl_fields')
            ->execute()
            ->integer(0);

        return $next + 1;
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
     *  An array of associative arrays that contains four keys, `id`, `element_name`,
     * `type` and `location`
     */
    public static function fetchFieldsSchema($section_id)
    {
        return Symphony::Database()
            ->select(['id', 'element_name', 'type', 'location'])
            ->from('tbl_fields')
            ->where(['parent_section' => $section_id])
            ->orderBy(['sortorder' => 'ASC'])
            ->execute()
            ->rows();
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
        return Symphony::Database()
            ->select()
            ->count()
            ->from('tbl_fields')
            ->where(['type' => $field_type])
            ->execute()
            ->integer(0) > 0;
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
        $fields = Symphony::Database()
            ->select(['type'])
            ->distinct()
            ->from('tbl_fields')
            ->where(['type' => ['not in' => [
                'author', 'checkbox', 'date', 'input', 'select', 'taglist', 'upload'
            ]]])
            ->execute()
            ->column('type');

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $table = 0;
                try {
                    $table = Symphony::Database()
                        ->select()
                        ->count()
                        ->from("tbl_fields_$field")
                        ->where(['formatter' => $text_formatter_handle])
                        ->execute()
                        ->integer(0);
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

    /**
     * Factory method that creates a new FieldQuery.
     *
     * @since Symphony 3.0.0
     * @param array $projection
     *  The projection to select.
     *  If no projection gets added, it defaults to `FieldQuery::getDefaultProjection()`.
     * @return FieldQuery
     */
    public function select(array $projection = [])
    {
        return new FieldQuery(Symphony::Database(), $projection);
    }
}
