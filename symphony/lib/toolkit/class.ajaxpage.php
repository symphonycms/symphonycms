<?php
/**
 * @package toolkit
 */
/**
 * AjaxPage extends the Page class to provide an object representation
 * of a Symphony backend AJAX page.
 *
 * @deprecated @since Symphony 2.4
 * @see TextPage
 * @see XMLPage
 * @see JSONPage
 */

require_once TOOLKIT . '/class.xmlpage.php';

abstract class AjaxPage extends XMLPage
{

}
