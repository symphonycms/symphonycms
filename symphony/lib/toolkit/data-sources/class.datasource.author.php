<?php

/**
 * @package data-sources
 */
/**
 * The `AuthorDatasource` extends the base `Datasource` class and allows
 * the retrieval of Author information from the current Symphony installation.
 *
 * @since Symphony 2.3
 */
class AuthorDatasource extends Datasource
{
    public function __processAuthorFilter($field, $filter)
    {
        if (!is_array($filter)) {
            $bits = preg_split('/,\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);
            $bits = array_map('trim', $bits);
        } else {
            $bits = $filter;
        }

        $authors = Symphony::Database()->fetchCol('id', sprintf("
                SELECT `id` FROM `tbl_authors`
                WHERE `%s` IN ('%s')",
                $field,
                implode("', '", $bits)
        ));

        return (is_array($authors) && !empty($authors) ? $authors : null);
    }

    public function execute(array &$param_pool = null)
    {
        $author_ids = array();

        if (is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)) {
            foreach ($this->dsParamFILTERS as $field => $value) {
                if (!is_array($value) && trim($value) == '') {
                    continue;
                }

                $ret = $this->__processAuthorFilter($field, $value);

                if (empty($ret)) {
                    $author_ids = array();
                    break;
                }

                if (empty($author_ids)) {
                    $author_ids = $ret;
                    continue;
                }

                $author_ids = array_intersect($author_ids, $ret);
            }

            $authors = AuthorManager::fetchByID(array_values($author_ids));
        } else {
            $authors = AuthorManager::fetch($this->dsParamSORT, $this->dsParamORDER);
        }

        if ((!is_array($authors) || empty($authors)) && $this->dsParamREDIRECTONEMPTY === 'yes') {
            throw new FrontendPageNotFoundException;
        } elseif (!is_array($authors) || empty($authors)) {
            $result = $this->emptyXMLSet();
            return $result;
        } else {
            if ($this->_negate_result === true) {
                return $this->negateXMLSet();
            }

            if (!$this->_param_output_only) {
                $result = new XMLElement($this->dsParamROOTELEMENT);
            }

            $singleParam = false;
            $key = 'ds-' . $this->dsParamROOTELEMENT;

            if (isset($this->dsParamPARAMOUTPUT)) {
                if (!is_array($this->dsParamPARAMOUTPUT)) {
                    $this->dsParamPARAMOUTPUT = array($this->dsParamPARAMOUTPUT);
                }

                $singleParam = count($this->dsParamPARAMOUTPUT) === 1;
            }

            foreach ($authors as $author) {
                if (isset($this->dsParamPARAMOUTPUT)) {
                    foreach ($this->dsParamPARAMOUTPUT as $param) {
                        // The new style of paramater is `ds-datasource-handle.field-handle`
                        $param_key = $key . '.' . str_replace(':', '-', $param);

                        if (!is_array($param_pool[$param_key])) {
                            $param_pool[$param_key] = array();
                        }

                        $param_pool[$param_key][] = ($param === 'name' ? $author->getFullName() : $author->get($param));

                        if ($singleParam) {
                            if (!is_array($param_pool[$key])) {
                                $param_pool[$key] = array();
                            }

                            $param_pool[$key][] = ($param === 'name' ? $author->getFullName() : $author->get($param));
                        }
                    }
                }

                if ($this->_param_output_only) {
                    continue;
                }

                $xAuthor = new XMLElement('author');
                $xAuthor->setAttributeArray(array(
                    'id' => $author->get('id'),
                    'user-type' => $author->get('user_type'),
                    'primary-account' => $author->get('primary')
                ));

                // No included elements, so just create the Author XML
                if (!isset($this->dsParamINCLUDEDELEMENTS) || !is_array($this->dsParamINCLUDEDELEMENTS) || empty($this->dsParamINCLUDEDELEMENTS)) {
                    $result->appendChild($xAuthor);
                } else {
                    // Name
                    if (in_array('name', $this->dsParamINCLUDEDELEMENTS)) {
                        $xAuthor->appendChild(
                            new XMLElement('name', $author->getFullName())
                        );
                    }

                    // Username
                    if (in_array('username', $this->dsParamINCLUDEDELEMENTS)) {
                        $xAuthor->appendChild(
                            new XMLElement('username', $author->get('username'))
                        );
                    }

                    // Email
                    if (in_array('email', $this->dsParamINCLUDEDELEMENTS)) {
                        $xAuthor->appendChild(
                            new XMLElement('email', $author->get('email'))
                        );
                    }

                    // Author Token
                    if (in_array('author-token', $this->dsParamINCLUDEDELEMENTS) && $author->isTokenActive()) {
                        $xAuthor->appendChild(
                            new XMLElement('author-token', $author->createAuthToken())
                        );
                    }

                    // Default Area
                    if (in_array('default-area', $this->dsParamINCLUDEDELEMENTS) && !is_null($author->get('default_area'))) {
                        // Section
                        if ($section = SectionManager::fetch($author->get('default_area'))) {
                            $default_area = new XMLElement('default-area', $section->get('name'));
                            $default_area->setAttributeArray(array('id' => $section->get('id'), 'handle' => $section->get('handle'), 'type' => 'section'));
                            $xAuthor->appendChild($default_area);

                            // Pages
                        } else {
                            $default_area = new XMLElement('default-area', $author->get('default_area'));
                            $default_area->setAttribute('type', 'page');
                            $xAuthor->appendChild($default_area);
                        }
                    }

                    $result->appendChild($xAuthor);
                }
            }
        }

        return $result;
    }
}
