<?php
/**
 * @package content
 */
/**
 * The AjaxTranslate page is used for translating strings on the fly
 * that are used in Symphony's javascript
 */

class contentAjaxTranslate extends JSONPage
{
    public function view()
    {
        $strings = $_GET['strings'];
        $namespace = (empty($_GET['namespace']) ? null : General::sanitize($_GET['namespace']));

        $new = array();

        foreach ($strings as $key => $value) {
            // Check value
            if (empty($value) || $value = 'false') {
                $value = $key;
            }

            $value = General::sanitize($value);

            // Translate
            $new[$value] = Lang::translate(urldecode($value), null, $namespace);
        }

        $this->_Result = $new;
    }
}
