<?php

/**
 * @package data-sources
 */
/**
 * The `StaticXMLDatasource` allows a block of XML to be exposed to the
 * Frontend. It is a limited to providing the XML as is, and does not
 * support output parameters or any filtering.
 *
 * @since Symphony 2.3
 */
class StaticXMLDatasource extends Datasource
{
    public function execute(array &$param_pool = null)
    {
        include_once TOOLKIT . '/class.xsltprocess.php';

        $result = new XMLElement($this->dsParamROOTELEMENT);
        $this->dsParamSTATIC = stripslashes($this->dsParamSTATIC);

        if (!General::validateXML($this->dsParamSTATIC, $errors, false, new XsltProcess)) {
            $result->appendChild(
                new XMLElement('error', __('XML is invalid.'))
            );

            $element = new XMLElement('errors');

            foreach ($errors as $e) {
                if (strlen(trim($e['message'])) == 0) {
                    continue;
                }

                $element->appendChild(new XMLElement('item', General::sanitize($e['message'])));
            }
            $result->appendChild($element);
        } else {
            $result->setValue($this->dsParamSTATIC);
        }

        return $result;
    }
}
