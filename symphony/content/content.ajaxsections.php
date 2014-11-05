<?php
/**
 * @package content
 */
/**
 * The AjaxSections page return an object of all sections and their fields
 * that are available for pre-population
 */

class contentAjaxSections extends JSONPage
{
    public function view()
    {
        $sections = SectionManager::fetch(null, 'ASC', 'sortorder');
        $options = array();

        if (is_array($sections) && !empty($sections)) {
            foreach ($sections as $section) {
                $section_fields = $section->fetchFields();

                if (!is_array($section_fields)) {
                    continue;
                }

                $fields = array();

                foreach ($section_fields as $f) {
                    if ($f->canPrePopulate()) {
                        $fields[] = array(
                            'id' => $f->get('id'),
                            'name' => $f->get('label'),
                            'handle' => $f->get('element_name'),
                            'type' => $f->get('type')
                        );
                    }
                }

                if (!empty($fields)) {
                    $options[] = array(
                        'name' => $section->get('name'),
                        'handle' => $section->get('handle'),
                        'fields' => $fields
                    );
                }
            }
        }

        $this->_Result['sections'] = $options;
    }
}
