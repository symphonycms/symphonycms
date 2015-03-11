<?php

require_once(TOOLKIT . '/class.jsonpage.php');

Class contentAjaxQuery extends JSONPage
{

    public function view()
    {
        $database = Symphony::Configuration()->get('db', 'database');
        $field_ids = explode(',', General::sanitize($_GET['field_id']));
        $search = General::sanitize($_GET['query']);
        $types = explode(',', General::sanitize($_GET['types']));
        $limit = intval(General::sanitize($_GET['limit']));

        // Set limit
        if ($limit === 0) {
            $max = '';
        } elseif (empty($limit)) {
            $max = ' LIMIT 100';
        } else {
            $max = sprintf(' LIMIT %d', $limit);
        }

        // Entries
        if(in_array('entry', $types)) {
            foreach($field_ids as $field_id) {
                $this->get($database, intval($field_id), $search, $max);
            }
        }

        // Associations
        if(in_array('association', $types)) {
            foreach($field_ids as $field_id) {
                $association_id = $this->getAssociationId($field_id);

                if($association_id) {
                    $this->get($database, $association_id, $search, $max);
                }
            }
        }

        // Static values
        if(in_array('static', $types)) {
            foreach($field_ids as $field_id) {
                $this->getStatic($field_id, $search);
            }
        }

        // Return results
        return $this->_Result;
    }

    private function getAssociationId($field_id)
    {
        $field = FieldManager::fetch($field_id);
        $parent_section = SectionManager::fetch($field->get('parent_section'));

        $association_id = Symphony::Database()->fetchCol('parent_section_field_id',
            sprintf(
                "SELECT `parent_section_field_id` FROM tbl_sections_association WHERE `child_section_field_id` = %d AND `child_section_id` = %d LIMIT 1;",
                $field_id, $parent_section->get('id')
            )
        );

        return $association_id[0];
    }

    private function getStatic($field_id, $search = null)
    {
        $options = array();

        if (!empty($field_id)) {
            $field = FieldManager::fetch($field_id);

            if (!empty($field) && $field->canPublishFilter() === true) {
                if (method_exists($field, 'getToggleStates')) {
                    $options = $field->getToggleStates();
                } elseif (method_exists($field, 'findAllTags')) {
                    $options = $field->findAllTags();
                }
            }
        }

        foreach ($options as $value => $data) {
            if (!$search || strripos($data, $search) !== false || strripos($value, $search) !== false) {
                $this->_Result['entries'][]['value'] = ($data ? $data : $value);
            }
        }
    }

    private function get($database, $field_id, $search, $max)
    {
        // Get entries
        if (!empty($search)) {

            // Get columns
            $columns = Symphony::Database()->fetchCol('column_name',
                sprintf(
                    "SELECT column_name
                    FROM information_schema.columns
                    WHERE table_schema = '%s'
                    AND table_name = 'tbl_entries_data_%d'
                    AND column_name != 'id'
                    AND column_name != 'entry_id';",
                    $database,
                    $field_id
                )
            );

            // Build where clauses
            $where = array();
            foreach ($columns as $column) {
                $where[] = "`$column` LIKE '%$search%'";
            }

            // Build query
            $query = sprintf(
                "SELECT * from tbl_entries_data_%d WHERE %s%s;",
                $field_id,
                implode($where, " OR "),
                $max
            );
        } else {
            $query = sprintf(
                "SELECT * from tbl_entries_data_%d%s;",
                $field_id,
                $max
            );
        }

        // Fetch field values
        $data = Symphony::Database()->fetch($query);

        if (!empty($data)) {
            $field = FieldManager::fetch($field_id);
            $parent_section = SectionManager::fetch($field->get('parent_section'));
            $parent_section_handle = $parent_section->get('handle');

            foreach ($data as $field_data) {
                $entry_id = $field_data['entry_id'];

                if ($field instanceof ExportableField && in_array(ExportableField::UNFORMATTED, $field->getExportModes())) {

                    // Get unformatted value
                    $value = $field->prepareExportValue($field_data, ExportableField::UNFORMATTED, $entry_id);
                } elseif ($field instanceof ExportableField && in_array(ExportableField::VALUE, $field->getExportModes())) {

                    // Get formatted value
                    $value = $field->prepareExportValue($field_data, ExportableField::VALUE, $entry_id);
                } else {

                    // Get value from parameter pool
                    $value = $field->getParameterPoolValue($field_data, $entry_id);
                }

                $this->_Result['entries'][$entry_id]['value'] = $value;
                $this->_Result['entries'][$entry_id]['section'] = $parent_section_handle;
                $this->_Result['entries'][$entry_id]['link'] = APPLICATION_URL . '/publish/' . $parent_section_handle . '/edit/' . $entry_id . '/';
            }
        }
    }

}
