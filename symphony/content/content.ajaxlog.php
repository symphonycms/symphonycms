<?php
/**
 * @package content
 */
/**
 * The AjaxLog page accepts $_POST requests to write information to the
 * Symphony Log.
 */

class contentAjaxLog extends TextPage
{
    public function view()
    {
        if ($_REQUEST['error'] && Symphony::Log()) {
            Symphony::Log()->pushToLog(sprintf(
                    '%s - %s%s%s',
                    'Javascript',
                    $_REQUEST['error'],
                    ($_REQUEST['url'] ? " in file " . $_REQUEST['url'] : null),
                    ($_REQUEST['line'] ? " on line " . $_REQUEST['line'] : null)
                ),
                E_ERROR, true
            );
        }
    }
}
