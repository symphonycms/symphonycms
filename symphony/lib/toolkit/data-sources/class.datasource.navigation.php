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
    public function processNavigationParentFilter($parent, $stm)
    {
        $parent_paths = preg_split('/,\s*/', $parent, -1, PREG_SPLIT_NO_EMPTY);
        $parent_paths = array_map(function($a) {
            return trim($a, ' /');
        }, $parent_paths);

        if (!empty($parent_paths)) {
            $stm->where(['p.path' => ['in' => $parent_paths]]);
        }
    }

    public function processNavigationTypeFilter($filter, $stm)
    {
        $filter_type = Datasource::determineFilterType($filter);
        $types = preg_split('/'.($filter_type == Datasource::FILTER_AND ? '\+' : '(?<!\\\\),').'\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);
        $types = array_map('trim', $types);
        $types = array_map(array('Datasource', 'removeEscapedCommas'), $types);
        $op = ($filter_type === Datasource::FILTER_OR) ? 'or' : 'and';

        $stm->where([
            $op => array_map(function ($filter) {
                return ['pt.type' => $filter];
            }, $types),
        ]);
    }

    public function buildPageXML($page, $page_types)
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
                $oPage->appendChild($this->buildPageXML($c, $page_types));
            }
        }

        return $oPage;
    }

    public function execute(array &$param_pool = null)
    {
        $result = new XMLElement($this->dsParamROOTELEMENT);

        // Build the query
        $stm = Symphony::Database()
            ->select()
            ->distinct()
            ->from('tbl_pages', 'p')
            ->leftJoin('tbl_pages_types', 'pt')
            ->on(['p.id' => '$pt.page_id'])
            ->orderBy('p.sortorder');
        // Create sub query from query
        $childrenStm = $stm
            ->select()
            ->count('id')
            ->from('tbl_pages', 'c')
            ->where(['c.parent' => '$p.id']);
        // Add projection to query, including the sub query
        $stm = $stm->projection(['p.id', 'p.title', 'p.handle', 'p.sortorder', 'children' => $childrenStm]);

        // Add type filters
        if (trim($this->dsParamFILTERS['type']) != '') {
            $this->processNavigationTypeFilter($this->dsParamFILTERS['type'], $stm);
        }
        // Add parent filters
        if (trim($this->dsParamFILTERS['parent']) != '') {
            $this->processNavigationParentFilter($this->dsParamFILTERS['parent'], $stm);
        } else {
            $stm->where(['p.parent' => null]);
        }

        // Execute
        $pages = $stm->execute()->rows();

        if (empty($pages)) {
            if ($this->dsParamREDIRECTONEMPTY === 'yes') {
                throw new FrontendPageNotFoundException;
            }
            $result->appendChild($this->noRecordsFound());
        } else {
            // Build an array of all the types so that the page's don't have to do
            // individual lookups.
            $page_types = PageManager::fetchAllPagesPageTypes();

            foreach ($pages as $page) {
                $result->appendChild($this->buildPageXML($page, $page_types));
            }
        }

        return $result;
    }
}
