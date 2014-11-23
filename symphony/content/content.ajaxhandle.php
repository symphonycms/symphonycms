<?php
/**
 * @package content
 */
/**
 * The AjaxHandle page is used for generating handles on the fly
 * that are used in Symphony's javascript
 */
class contentAjaxHandle extends JSONPage
{
    public function view()
    {
        $string = $_GET['string'];
        $this->_Result['handle'] = Lang::createHandle($string, 255, '-', true);
    }
}
