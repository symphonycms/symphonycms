<?php

/**
 * @package toolkit
 */
/**
 * The ResourcesManager is a class used to collect some methods for both
 * Datasources and Events.
 *
 * @since Symphony 2.3
 */

class ResourceManager
{
    /**
     * The integer value for event-type resources.
     * @var integer
     */
    const RESOURCE_TYPE_EVENT = 20;

    /**
     * The integer value for datasource-type resources.
     * @var integer
     */
    const RESOURCE_TYPE_DS = 21;

    /**
     * A private method used to return the `tbl_pages` column related to
     * the given resource type.
     *
     * @param integer $type
     *  The resource type, either `RESOURCE_TYPE_EVENT` or `RESOURCE_TYPE_DS`
     * @return string
     *  A string representing the `tbl_pages` column to target.
     */
    private static function getColumnFromType($type)
    {
        switch($type) {
            case ResourceManager::RESOURCE_TYPE_EVENT:
                return 'events';
            case ResourceManager::RESOURCE_TYPE_DS:
                return 'data_sources';
        }
    }

    /**
     * A method used to return the Manager for the given resource type.
     *
     * @param integer $type
     *  The resource type, either `RESOURCE_TYPE_EVENT` or `RESOURCE_TYPE_DS`
     * @return string
     *  An string representing the name of the Manager class that handles the resource.
     */
    public static function getManagerFromType($type)
    {
        switch($type) {
            case ResourceManager::RESOURCE_TYPE_EVENT:
                return 'EventManager';
            case ResourceManager::RESOURCE_TYPE_DS:
                return 'DatasourceManager';
        }
    }

    /**
     * Returns the axis a given resource type will be sorted by.
     * The following handles are available: `name`, `source`, `release-date`
     * and `author`. Defaults to 'name'.
     *
     * @param integer $type
     *  The resource type, either `RESOURCE_TYPE_EVENT` or `RESOURCE_TYPE_DS`
     * @return string
     *  The axis handle.
     */
    public static function getSortingField($type)
    {
        $result = Symphony::Configuration()->get(self::getColumnFromType($type) . '_index_sortby', 'sorting');

        return (is_null($result) ? 'name' : $result);
    }

    /**
     * Returns the sort order for a given resource type. Defaults to 'asc'.
     *
     * @param integer $type
     *  The resource type, either `RESOURCE_TYPE_EVENT` or `RESOURCE_TYPE_DS`
     * @return string
     *  Either 'asc' or 'desc'.
     */
    public static function getSortingOrder($type)
    {
        $result = Symphony::Configuration()->get(self::getColumnFromType($type) . '_index_order', 'sorting');

        return (is_null($result) ? 'asc' : $result);
    }

    /**
     * Saves the new axis a given resource type will be sorted by.
     *
     * @param integer $type
     *  The resource type, either `RESOURCE_TYPE_EVENT` or `RESOURCE_TYPE_DS`
     * @param string $sort
     *  The axis handle.
     * @param boolean $write
     *  If false, the new settings won't be written on the configuration file.
     *  Defaults to true.
     */
    public static function setSortingField($type, $sort, $write = true)
    {
        Symphony::Configuration()->set(self::getColumnFromType($type) . '_index_sortby',  $sort , 'sorting');

        if ($write) {
            Symphony::Configuration()->write();
        }
    }

    /**
     * Saves the new sort order for a given resource type.
     *
     * @param integer $type
     *  The resource type, either `RESOURCE_TYPE_EVENT` or `RESOURCE_TYPE_DS`
     * @param string $order
     *  Either 'asc' or 'desc'.
     * @param boolean $write
     *  If false, the new settings won't be written on the configuration file.
     *  Defaults to true.
     */
    public static function setSortingOrder($type, $order, $write = true)
    {
        Symphony::Configuration()->set(self::getColumnFromType($type) . '_index_order', $order, 'sorting');

        if ($write) {
            Symphony::Configuration()->write();
        }
    }

    /**
     * This function will return an associative array of resource information. The
     * information returned is defined by the `$select` parameter, which will allow
     * a developer to restrict what information is returned about the resource.
     * Optionally, `$where` (not implemented) and `$order_by` parameters allow a developer to
     * further refine their query.
     *
     * @param integer $type
     *  The type of the resource (needed to retrieve the correct Manager)
     * @param array $select (optional)
     *  Accepts an array of keys to return from the manager's `listAll()` method. If omitted,
     *  all keys will be returned.
     * @param array $where (optional)
     *  Not implemented.
     * @param string $order_by (optional)
     *  Allows a developer to return the resources in a particular order. The syntax is the
     *  same as other `fetch` methods. If omitted this will return resources ordered by `name`.
     * @throws SymphonyErrorPage
     * @throws Exception
     * @return array
     *  An associative array of resource information, formatted in the same way as the resource's
     *  manager `listAll()` method.
     */
    public static function fetch($type, array $select = array(), array $where = array(), $order_by = null)
    {
        $manager = self::getManagerFromType($type);

        if (!isset($manager)) {
            throw new Exception(__('Unable to find a Manager class for this resource.'));
        }

        $resources = call_user_func(array($manager, 'listAll'));

        foreach ($resources as &$r) {
            // If source is numeric, it's considered to be a Symphony Section
            if (isset($r['source']) && $r['source'] > 0) {
                $section = SectionManager::fetch($r['source']);

                if ($section !== false) {
                    $r['source'] = array(
                        'name' => $section->get('name'),
                        'handle' => $section->get('handle'),
                        'id' => $r['source']
                    );
                } else {
                    unset($r['source']);
                }

                // If source is set but no numeric, it's considered to be a Symphony Type (e.g. authors or navigation)
            } elseif (isset($r['source'])) {
                $r['source'] = array(
                    'name' => ucwords($r['source']),
                    'handle' => $r['source']
                );

                // Resource provided by extension?
            } else {
                $extension = self::__getExtensionFromHandle($type, $r['handle']);

                if (!empty($extension)) {
                    $extension = Symphony::ExtensionManager()->about($extension);
                    $r['source'] = array(
                        'name' => $extension['name'],
                        'handle' => Lang::createHandle($extension['name'])
                    );
                }
            }
        }

        if (empty($select) && empty($where) && is_null($order_by)) {
            return $resources;
        }

        if (!is_null($order_by) && !empty($resources)) {
            $author = $label = $source = $name = array();
            $order_by = array_map('strtolower', explode(' ', $order_by));
            $order = ($order_by[1] == 'desc') ? SORT_DESC : SORT_ASC;
            $sort = $order_by[0];

            if ($sort == 'author') {
                foreach ($resources as $key => $about) {
                    $author[$key] = $about['author']['name'];
                    $label[$key] = $key;
                }

                array_multisort($author, $order, $label, SORT_ASC, $resources);
            } elseif ($sort == 'release-date') {
                foreach ($resources as $key => $about) {
                    $author[$key] = $about['release-date'];
                    $label[$key] = $key;
                }

                array_multisort($author, $order, $label, SORT_ASC, $resources);
            } elseif ($sort == 'source') {
                foreach ($resources as $key => $about) {
                    $source[$key] = $about['source']['handle'];
                    $label[$key] = $key;
                }

                array_multisort($source, $order, $label, SORT_ASC, $resources);
            } elseif ($sort == 'name') {
                foreach ($resources as $key => $about) {
                    $name[$key] = strtolower($about['name']);
                    $label[$key] = $key;
                }

                array_multisort($name, $order, $label, SORT_ASC, $resources);
            }

        }

        $data = array();

        foreach ($resources as $i => $res) {
            $data[$i] = array();

            foreach ($res as $key => $value) {
                // If $select is empty, we assume every field is requested
                if (in_array($key, $select) || empty($select)) {
                    $data[$i][$key] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Given the type and handle of a resource, return the extension it belongs to.
     *
     * @param integer $type
     *  The resource type, either `RESOURCE_TYPE_EVENT` or `RESOURCE_TYPE_DS`
     * @param string $r_handle
     *  The handle of the resource.
     * @throws Exception
     * @return string
     *  The extension handle.
     */
    public static function __getExtensionFromHandle($type, $r_handle)
    {
        $manager = self::getManagerFromType($type);

        if (!isset($manager)) {
            throw new Exception(__('Unable to find a Manager class for this resource.'));
        }

        $type = str_replace('_', '-', self::getColumnFromType($type));
        preg_match('/extensions\/(.*)\/' . $type . '/', call_user_func(array($manager, '__getClassPath'), $r_handle), $data);

        $data = array_splice($data, 1);

        if (empty($data)) {
            return null;
        } else {
            return $data[0];
        }
    }

    /**
     * Given the resource handle, this function will return an associative
     * array of Page information, filtered by the pages the resource is attached to.
     *
     * @param integer $type
     *  The resource type, either `RESOURCE_TYPE_EVENT` or `RESOURCE_TYPE_DS`
     * @param string $r_handle
     *  The handle of the resource.
     * @return array
     *  An associative array of Page information, according to the pages the resource is attached to.
     */
    public static function getAttachedPages($type, $r_handle)
    {
        $col = self::getColumnFromType($type);

        $pages = PageManager::fetch(false, array('id'), array(sprintf(
            '`%s` = "%s" OR `%s` REGEXP "%s"',
            $col,
            $r_handle,
            $col,
            '^' . $r_handle . ',|,' . $r_handle . ',|,' . $r_handle . '$'
        )));

        if (is_array($pages)) {
            foreach ($pages as $key => &$page) {
                $pages[$key] = array(
                    'id' => $page['id'],
                    'title' => PageManager::resolvePageTitle($page['id'])
                );
            }
        }

        return (is_null($pages) ? array() : $pages);
    }

    /**
     * Given a resource type, a handle and a page, this function will attach
     * the given handle (which represents either a datasource or event) to that page.
     *
     * @param integer $type
     *  The resource type, either `RESOURCE_TYPE_EVENT` or `RESOURCE_TYPE_DS`
     * @param string $r_handle
     *  The handle of the resource.
     * @param integer $page_id
     *  The ID of the page.
     * @return boolean
     */
    public static function attach($type, $r_handle, $page_id)
    {
        $col = self::getColumnFromType($type);

        $pages = PageManager::fetch(false, array($col), array(sprintf(
            '`id` = %d',
            $page_id
        )));

        if (is_array($pages) && count($pages) == 1) {
            $result = $pages[0][$col];

            if (!in_array($r_handle, explode(',', $result))) {

                if (strlen($result) > 0) {
                    $result .= ',';
                }

                $result .= $r_handle;

                return PageManager::edit($page_id, array(
                    $col => MySQL::cleanValue($result)
                ));
            }
        }

        return false;
    }

    /**
     * Given a resource type, a handle and a page, this function detaches
     * the given handle (which represents either a datasource or event) to that page.
     *
     * @param integer $type
     *  The resource type, either `RESOURCE_TYPE_EVENT` or `RESOURCE_TYPE_DS`
     * @param string $r_handle
     *  The handle of the resource.
     * @param integer $page_id
     *  The ID of the page.
     * @return boolean
     */
    public static function detach($type, $r_handle, $page_id)
    {
        $col = self::getColumnFromType($type);

        $pages = PageManager::fetch(false, array($col), array(sprintf(
            '`id` = %d',
            $page_id
        )));

        if (is_array($pages) && count($pages) == 1) {
            $result = $pages[0][$col];

            $values = explode(',', $result);
            $idx = array_search($r_handle, $values, false);

            if ($idx !== false) {
                array_splice($values, $idx, 1);
                $result = implode(',', $values);

                return PageManager::edit($page_id, array(
                    $col => MySQL::cleanValue($result)
                ));
            }
        }

        return false;
    }

    /**
     * Given a resource type, a handle and an array of pages, this function will
     * ensure that the resource is attached to the given pages. Note that this
     * function will also remove the resource from all pages that are not provided
     * in the `$pages` parameter.
     *
     * @since Symphony 2.4
     * @param integer $type
     *  The resource type, either `RESOURCE_TYPE_EVENT` or `RESOURCE_TYPE_DS`
     * @param string $r_handle
     *  The handle of the resource.
     * @param array $pages
     *  An array of Page ID's to attach this resource to.
     * @return boolean
     */
    public static function setPages($type, $r_handle, $pages = array())
    {
        if (!is_array($pages)) {
            $pages = array();
        }

        // Get attached pages
        $attached_pages = ResourceManager::getAttachedPages($type, $r_handle);
        $currently_attached_pages = array();

        foreach ($attached_pages as $page) {
            $currently_attached_pages[] = $page['id'];
        }

        // Attach this datasource to any page that is should be attached to
        $diff_to_attach = array_diff($pages, $currently_attached_pages);

        foreach ($diff_to_attach as $diff_page) {
            ResourceManager::attach($type, $r_handle, $diff_page);
        }

        // Remove this datasource from any page where it once was, but shouldn't be anymore
        $diff_to_detach = array_diff($currently_attached_pages, $pages);

        foreach ($diff_to_detach as $diff_page) {
            ResourceManager::detach($type, $r_handle, $diff_page);
        }

        return true;
    }
}
