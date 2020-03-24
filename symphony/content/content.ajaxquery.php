<?php
/**
 * @package content
 */
/**
 * The AjaxQuery returns an JSON array of entries, associations and other
 * static values, depending on the parameters received.
 */

class contentAjaxQuery extends JSONPage
{
    public function view()
    {
        $database = Symphony::Configuration()->get('db', 'database');
        $field_ids = array_map(array('General','intval'), explode(',', General::sanitize($_GET['field_id'])));
        $search = General::sanitize($_GET['query']);
        $types = explode(',', General::sanitize($_GET['types']));
        $limit = General::intval(General::sanitize($_GET['limit']));

        // Entries
        if (in_array('entry', $types)) {
            foreach ($field_ids as $field_id) {
                $this->get($database, intval($field_id), $search, $limit);
            }
        }

        // Associations
        if (in_array('association', $types)) {
            foreach ($field_ids as $field_id) {
                $association_id = $this->getAssociationId($field_id);

                if ($association_id) {
                    $this->get($database, $association_id, $search, $limit);
                }
            }
        }

        // Static values
        if (in_array('static', $types)) {
            foreach ($field_ids as $field_id) {
                $this->getStatic($field_id, $search);
            }
        }

        // Return results
        return $this->_Result;
    }

    private function getAssociationId($field_id)
    {
        $field = (new FieldManager)
            ->select()
            ->field($field_id)
            ->execute()
            ->next();
        $parent_section = (new SectionManager)
            ->select()
            ->section($field->get('parent_section'))
            ->execute()
            ->next();

        return Symphony::Database()
            ->select(['parent_section_field_id'])
            ->from('tbl_sections_association')
            ->where(['child_section_field_id' => $field_id])
            ->where(['child_section_id' => $parent_section->get('id')])
            ->limit(1)
            ->execute()
            ->integer('parent_section_field_id');
    }

    private function getStatic($field_id, $search = null)
    {
        $options = array();

        if (!empty($field_id)) {
            $field = (new FieldManager)->select()->field($field_id)->execute()->next();

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

    private function get($database, $field_id, $search, $limit)
    {
        // Build query
        $query = Symphony::Database()
            ->select()
            ->from("tbl_entries_data_$field_id");

        // Set limit
        if ($limit === 0) {
            // no limit
        } elseif ($limit < 0) {
            $query->limit(100);
        } else {
            $query->limit($limit);
        }

        // Get entries
        if (!empty($search)) {
            $field_id = General::intval($field_id);

            // Get columns
            $fieldsQuery = Symphony::Database()
                ->select(['column_name'])
                ->from('information_schema.columns');
            $columns = $fieldsQuery
                ->where(['table_schema' => $database])
                ->where(['table_name' => $fieldsQuery->replaceTablePrefix("tbl_entries_data_$field_id")])
                ->where(['column_name' => ['!=' => 'id']])
                ->where(['column_name' => ['!=' => 'entry_id']])
                ->execute()
                ->column('column_name');

            // Build where clauses
            $where = array();
            foreach ($columns as $column) {
                $where[] = [$column => ['like' => "%$search%"]];
            }
            if (!empty($where)) {
                $query->where(['or' => $where]);
            }
            unset($fieldsQuery);
        }

        // Fetch field values
        $data = $query->execute()->rows();

        if (!empty($data)) {
            $field = (new FieldManager)
                ->select()
                ->field($field_id)
                ->execute()
                ->next();
            $parent_section = (new SectionManager)
                ->select()
                ->section($field->get('parent_section'))
                ->execute()
                ->next();
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
