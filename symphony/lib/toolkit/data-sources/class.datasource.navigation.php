<?php

/**
 * @package data-sources
 */
/**
 * The `NavigationDatasource` outputs the Symphony page structure as XML.
 * This datasource supports filtering to narrow down the results to only
 * show pages that match a particular page type, have a specific parent, etc.
 *
 * @since Symphony 2.3
 */
class NavigationDatasource extends Datasource
{
    public function __processNavigationParentFilter($parent)
    {
        $parent_paths = preg_split('/,\s*/', $parent, -1, PREG_SPLIT_NO_EMPTY);
        $parent_paths = array_map(function($a) { return trim($a, ' /');}, $parent_paths);

        return (is_array($parent_paths) && !empty($parent_paths) ? " AND p.`path` IN ('".implode("', '", $parent_paths)."')" : null);
    }

    public function __processNavigationTypeFilter($filter, $filter_type = Datasource::FILTER_OR)
    {
        $types = preg_split('/'.($filter_type == Datasource::FILTER_AND ? '\+' : '(?<!\\\\),').'\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);
        $types = array_map('trim', $types);

        $types = array_map(array('Datasource', 'removeEscapedCommas'), $types);

        if ($filter_type == Datasource::FILTER_OR) {
            $type_sql = " AND pt.type IN ('" . implode("', '", $types) . "')";
        } else {
            foreach ($types as $type) {
                $type_sql = " AND pt.type = '" . $type . "'";
            }
        }

        return $type_sql;
    }

    public function __buildPageXML($page, $page_types)
    {
        $oPage = new XMLElement('page');
        $oPage->setAttribute('handle', $page['handle']);
        $oPage->setAttribute('id', $page['id']);
        $oPage->appendChild(new XMLElement('name', General::sanitize($page['title'])));

        if (in_array($page['id'], array_keys($page_types))) {
            $xTypes = new XMLElement('types');

            foreach ($page_types[$page['id']] as $type) {
                $xTypes->appendChild(new XMLElement('type', $type));
            }

            $oPage->appendChild($xTypes);
        }

        if ($page['children'] != '0') {
            $children = (new PageManager)
                ->select(['id', 'handle', 'title'])
                ->parent($page['id'])
                ->execute()
                ->rows();
            foreach ($children as $c) {
                $oPage->appendChild($this->__buildPageXML($c, $page_types));
            }
        }

        return $oPage;
    }

    public function execute(array &$param_pool = null)
    {
        $result = new XMLElement($this->dsParamROOTELEMENT);
        $type_sql = $parent_sql = null;

        if (trim($this->dsParamFILTERS['type']) != '') {
            $type_sql = $this->__processNavigationTypeFilter($this->dsParamFILTERS['type'], Datasource::determineFilterType($this->dsParamFILTERS['type']));
        }

        if (trim($this->dsParamFILTERS['parent']) != '') {
            $parent_sql = $this->__processNavigationParentFilter($this->dsParamFILTERS['parent']);
        }

        // Build the Query appending the Parent and/or Type WHERE clauses
        $childrenStm = Symphony::Database()
            ->select()
            ->count('id')
            ->from('tbl_pages', 'c')
            ->where(['c.parent' => '$p.id']);
        $stm = Symphony::Database()
            ->select(['p.id', 'p.title', 'p.handle', 'p.sortorder', 'children' => $childrenStm])
            ->distinct()
            ->from('tbl_pages', 'p')
            ->leftJoin('tbl_pages_types', 'pt')
            ->on(['p.id' => '$pt.page_id'])
            ->orderBy('p.sortorder');

        if ($parent_sql) {
            $parent_sql = $stm->replaceTablePrefix($parent_sql);
            $stm->unsafe()->unsafeAppendSQLPart('where', "1 = 1 $parent_sql");
        } else {
            $stm->where(['p.parent' => null]);
        }
        if ($type_sql) {
            $type_sql = $stm->replaceTablePrefix($type_sql);
            $stm->unsafe()->unsafeAppendSQLPart('where', "1 = 1 $type_sql");
        }

        if ((!is_array($pages) || empty($pages))) {
            if ($this->dsParamREDIRECTONEMPTY === 'yes') {
                throw new FrontendPageNotFoundException;
            }
            $result->appendChild($this->__noRecordsFound());
        } else {
            // Build an array of all the types so that the page's don't have to do
            // individual lookups.
            $page_types = PageManager::fetchAllPagesPageTypes();

            foreach ($pages as $page) {
                $result->appendChild($this->__buildPageXML($page, $page_types));
            }
        }

        return $result;
    }
}
