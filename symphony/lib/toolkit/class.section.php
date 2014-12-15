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

class Section
{
    /**
     * An array of the Section's settings
     * @var array
     */
    protected $_data = array();

    /**
     * A setter function that will save a section's setting into
     * the poorly named `$this->_data` variable
     *
     * @param string $setting
     *  The setting name
     * @param string $value
     *  The setting value
     */
    public function set($setting, $value)
    {
        $this->_data[$setting] = $value;
    }

    /**
     * An accessor function for this Section's settings. If the
     * $setting param is omitted, an array of all settings will
     * be returned. Otherwise it will return the data for
     * the setting given.
     *
     * @param null|string $setting
     * @return array|string
     *    If setting is provided, returns a string, if setting is omitted
     *    returns an associative array of this Section's settings
     */
    public function get($setting = null)
    {
        if (is_null($setting)) {
            return $this->_data;
        }

        return $this->_data[$setting];
    }

    /**
     * Returns the default field this Section will be sorted by.
     * This is determined by the first visible field that is allowed to
     * to be sorted (defined by the field's `isSortable()` function).
     * If no fields exist or none of them are visible in the entries table,
     * 'id' is returned instead.
     *
     * @since Symphony 2.3
     * @throws Exception
     * @return string
     *    Either the field ID or the string 'id'.
     */
    public function getDefaultSortingField()
    {
        $fields = $this->fetchVisibleColumns();

        foreach ($fields as $field) {
            if (!$field->isSortable()) {
                continue;
            }

            return $field->get('id');
        }

        return 'id';
    }

    /**
     * Returns the field this Section will be sorted by, or calls
     * `getDefaultSortingField()` if the configuration file doesn't
     * contain any settings for that Section.
     *
     * @since Symphony 2.3
     * @throws Exception
     * @return string
     *    Either the field ID or the string 'id'.
     */
    public function getSortingField()
    {
        $result = Symphony::Configuration()->get('section_' . $this->get('handle') . '_sortby', 'sorting');

        return (is_null($result) ? $this->getDefaultSortingField() : $result);
    }

    /**
     * Returns the sort order for this Section. Defaults to 'asc'.
     *
     * @since Symphony 2.3
     * @return string
     *  Either 'asc' or 'desc'.
     */
    public function getSortingOrder()
    {
        $result = Symphony::Configuration()->get('section_' . $this->get('handle') . '_order', 'sorting');

        return (is_null($result) ? 'asc' : $result);
    }

    /**
     * Saves the new field this Section will be sorted by.
     *
     * @since Symphony 2.3
     * @param string $sort
     *  The field ID or the string 'id'.
     * @param boolean $write
     *  If false, the new settings won't be written on the configuration file.
     *  Defaults to true.
     */
    public function setSortingField($sort, $write = true)
    {
        Symphony::Configuration()->set('section_' . $this->get('handle') . '_sortby', $sort, 'sorting');

        if ($write) {
            Symphony::Configuration()->write();
        }
    }

    /**
     * Saves the new sort order for this Section.
     *
     * @since Symphony 2.3
     * @param string $order
     *  Either 'asc' or 'desc'.
     * @param boolean $write
     *  If false, the new settings won't be written on the configuration file.
     *  Defaults to true.
     */
    public function setSortingOrder($order, $write = true)
    {
        Symphony::Configuration()->set('section_' . $this->get('handle') . '_order', $order, 'sorting');

        if ($write) {
            Symphony::Configuration()->write();
        }
    }

    /**
     * Returns any section associations this section has with other sections
     * linked using fields. Has an optional parameter, `$respect_visibility` that
     * will only return associations that are deemed visible by a field that
     * created the association. eg. An articles section may link to the authors
     * section, but the field that links these sections has hidden this association
     * so an Articles column will not appear on the Author's Publish Index
     *
     * @param boolean $respect_visibility
     *  Whether to return all the section associations regardless of if they
     *  are deemed visible or not. Defaults to false, which will return all
     *  associations.
     * @return array
     */
    public function fetchAssociatedSections($respect_visibility = false)
    {
        return SectionManager::fetchChildAssociations($this->get('id'), $respect_visibility);
    }

    /**
     * Returns any section associations this section has with other sections
     * linked using fields, and where this section is the parent in the association.
     * Has an optional parameter, `$respect_visibility` that
     * will only return associations that are deemed visible by a field that
     * created the association. eg. An articles section may link to the authors
     * section, but the field that links these sections has hidden this association
     * so an Articles column will not appear on the Author's Publish Index
     *
     * @since Symphony 2.3.3
     * @param boolean $respect_visibility
     *  Whether to return all the section associations regardless of if they
     *  are deemed visible or not. Defaults to false, which will return all
     *  associations.
     * @return array
     */
    public function fetchChildAssociations($respect_visibility = false)
    {
        return SectionManager::fetchChildAssociations($this->get('id'), $respect_visibility);
    }

    /**
     * Returns any section associations this section has with other sections
     * linked using fields, and where this section is the child in the association.
     * Has an optional parameter, `$respect_visibility` that
     * will only return associations that are deemed visible by a field that
     * created the association. eg. An articles section may link to the authors
     * section, but the field that links these sections has hidden this association
     * so an Articles column will not appear on the Author's Publish Index
     *
     * @since Symphony 2.3.3
     * @param boolean $respect_visibility
     *  Whether to return all the section associations regardless of if they
     *  are deemed visible or not. Defaults to false, which will return all
     *  associations.
     * @return array
     */
    public function fetchParentAssociations($respect_visibility = false)
    {
        return SectionManager::fetchParentAssociations($this->get('id'), $respect_visibility);
    }

    /**
     * Returns an array of all the fields in this section that are to be displayed
     * on the entries table page ordered by the order in which they appear
     * in the Section Editor interface
     *
     * @throws Exception
     * @return array
     */
    public function fetchVisibleColumns()
    {
        return FieldManager::fetch(null, $this->get('id'), 'ASC', 'sortorder', null, null, " AND t1.show_column = 'yes' ");
    }

    /**
     * Returns an array of all the fields in this section optionally filtered by
     * the field type or it's location within the section.
     *
     * @param string $type
     *    The field type (it's handle as returned by `$field->handle()`)
     * @param string $location
     *    The location of the fields in the entry creator, whether they are
     *    'main' or 'sidebar'
     * @throws Exception
     * @return array
     */
    public function fetchFields($type = null, $location = null)
    {
        return FieldManager::fetch(null, $this->get('id'), 'ASC', 'sortorder', $type, $location);
    }

    /**
     * Returns an array of all the fields that can be filtered.
     *
     * @param string $location
     *    The location of the fields in the entry creator, whether they are
     *    'main' or 'sidebar'
     * @throws Exception
     * @return array
     */
    public function fetchFilterableFields($location = null)
    {
        return FieldManager::fetch(null, $this->get('id'), 'ASC', 'sortorder', null, $location, null, Field::__FILTERABLE_ONLY__);
    }

    /**
     * Returns an array of all the fields that can be toggled. This function
     * is used to help build the With Selected drop downs on the Publish
     * Index pages
     *
     * @param string $location
     *    The location of the fields in the entry creator, whether they are
     *    'main' or 'sidebar'
     * @throws Exception
     * @return array
     */
    public function fetchToggleableFields($location = null)
    {
        return FieldManager::fetch(null, $this->get('id'), 'ASC', 'sortorder', null, $location, null, Field::__TOGGLEABLE_ONLY__);
    }

    /**
     * Returns the Schema of this section which includes all this sections
     * fields and their settings.
     *
     * @return array
     */
    public function fetchFieldsSchema()
    {
        return FieldManager::fetchFieldsSchema($this->get('id'));
    }

    /**
     * Commit the settings of this section from the section editor to
     * create an instance of this section in `tbl_sections`. This function
     * loops of each of the fields in this section and calls their commit
     * function.
     *
     * @see toolkit.Field#commit()
     * @return boolean
     *  true if the commit was successful, false otherwise.
     */
    public function commit()
    {
        $settings = $this->_data;

        if (isset($settings['id'])) {
            $id = $settings['id'];
            unset($settings['id']);
            $section_id = SectionManager::edit($id, $settings);

            if ($section_id) {
                $section_id = $id;
            }
        } else {
            $section_id = SectionManager::add($settings);
        }

        if (is_numeric($section_id) && $section_id !== false) {
            for ($ii = 0, $length = count($this->_fields); $ii < $length; $ii++) {
                $this->_fields[$ii]->set('parent_section', $section_id);
                $this->_fields[$ii]->commit();
            }
        }
    }
}
