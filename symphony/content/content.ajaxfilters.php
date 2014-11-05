<?php
/**
 * @package content
 */
/**
 * The AjaxSections page return an object of all sections and their fields
 * that are available for pre-population
 */

class contentAjaxFilters extends JSONPage
{
    public function view()
    {
        $handle = General::sanitize($_GET['handle']);
        $section = General::sanitize($_GET['section']);
        $options = array();
        $filters = array();

        if (!empty($handle) && !empty($section)) {
            $section_id = SectionManager::fetchIDFromHandle($section);
            $field_id = FieldManager::fetchFieldIDFromElementName($handle, $section_id);
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
            $filters[] = array(
                'value' => ($value ? $value : $data),
                'text' => ($data ? $data : $value)
            );
        }

        $this->_Result['filters'] = $filters;
    }
}
