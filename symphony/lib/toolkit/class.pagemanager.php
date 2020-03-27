<?php

/**
 * @package toolkit
 */
/**
 * The `PageManager` class is responsible for providing basic CRUD operations
 * for Symphony frontend pages. These pages are stored in the database in
 * `tbl_pages` and are resolved to an instance of `FrontendPage` class from a URL.
 * Additionally, this manager provides functions to access the Page's types,
 * and any linked datasources or events.
 *
 * @since Symphony 2.3
 */
class PageManager
{
    /**
     * Given an associative array of data, where the key is the column name
     * in `tbl_pages` and the value is the data, this function will create a new
     * Page and return a Page ID on success.
     *
     * @param array $fields
     *  Associative array of field names => values for the Page
     * @throws DatabaseException
     * @return integer|boolean
     *  Returns the Page ID of the created Page on success, false otherwise.
     */
    public static function add(array $fields)
    {
        if (!isset($fields['sortorder'])) {
            $fields['sortorder'] = self::fetchNextSortOrder();
        }

        // Force parent to be null if empty
        if (empty($fields['parent'])) {
            $fields['parent'] = null;
        }

        $inserted = Symphony::Database()
            ->insert('tbl_pages')
            ->values($fields)
            ->execute()
            ->success();

        return $inserted ? Symphony::Database()->getInsertID() : 0;
    }

    /**
     * Return a Page title by the handle
     *
     * @param string $handle
     *  The handle of the page
     * @return string
     *  The Page title
     */
    public static function fetchTitleFromHandle($handle)
    {
        return Symphony::Database()
            ->select(['title'])
            ->from('tbl_pages')
            ->where(['handle' => $handle])
            ->limit(1)
            ->execute()
            ->string('title');
    }

    /**
     * Return a Page ID by the handle
     *
     * @param string $handle
     *  The handle of the page
     * @return int
     *  The Page ID
     */
    public static function fetchIDFromHandle($handle)
    {
        return (int)Symphony::Database()
            ->select(['id'])
            ->from('tbl_pages')
            ->where(['handle' => $handle])
            ->limit(1)
            ->execute()
            ->integer('id');
    }

    /**
     * Given a Page ID and an array of types, this function will add Page types
     * to that Page. If a Page types are stored in `tbl_pages_types`.
     *
     * @param integer $page_id
     *  The Page ID to add the Types to
     * @param array $types
     *  An array of page types
     * @throws DatabaseException
     * @return boolean
     */
    public static function addPageTypesToPage($page_id = null, array $types)
    {
        if (!$page_id) {
            return false;
        }

        PageManager::deletePageTypes($page_id);

        foreach ($types as $type) {
            Symphony::Database()
                ->insert('tbl_pages_types')
                ->values([
                    'page_id' => $page_id,
                    'type' => $type
                ])
                ->execute();
        }

        return true;
    }

    /**
     * Returns the path to the page-template by looking at the
     * `WORKSPACE/template/` directory, then at the `TEMPLATES`
     * directory for `$name.xsl`. If the template is not found,
     * false is returned
     *
     * @param string $name
     *  Name of the template
     * @return mixed
     *  String, which is the path to the template if the template is found,
     *  false otherwise
     */
    public static function getTemplate($name)
    {
        $format = '%s/%s.xsl';

        if (file_exists($template = sprintf($format, WORKSPACE . '/template', $name))) {
            return $template;
        } elseif (file_exists($template = sprintf($format, TEMPLATE, $name))) {
            return $template;
        } else {
            return false;
        }
    }

    /**
     * This function creates the initial `.xsl` template for the page, whether
     * that be from the `TEMPLATES/blueprints.page.xsl` file, or from an existing
     * template with the same name. This function will handle the renaming of a page
     * by creating the new files using the old files as the templates then removing
     * the old template. If a template already exists for a Page, it will not
     * be overridden and the function will return true.
     *
     * @see toolkit.PageManager#resolvePageFileLocation()
     * @see toolkit.PageManager#createHandle()
     * @param string $new_path
     *  The path of the Page, which is the handles of the Page parents. If the
     *  page has multiple parents, they will be separated by a forward slash.
     *  eg. article/read. If a page has no parents, this parameter should be null.
     * @param string $new_handle
     *  The new Page handle, generated using `PageManager::createHandle`.
     * @param string $old_path (optional)
     *  This parameter is only required when renaming a Page. It should be the 'old
     *  path' before the Page was renamed.
     * @param string $old_handle (optional)
     *  This parameter is only required when renaming a Page. It should be the 'old
     *  handle' before the Page was renamed.
     * @throws Exception
     * @return boolean
     *  true when the page files have been created successfully, false otherwise.
     */
    public static function createPageFiles($new_path, $new_handle, $old_path = null, $old_handle = null)
    {
        $new = PageManager::resolvePageFileLocation($new_path, $new_handle);
        $old = PageManager::resolvePageFileLocation($old_path, $old_handle);

        // Nothing to do:
        if (file_exists($new) && $new == $old) {
            return true;
        }

        // Old file doesn't exist, use template:
        if (!file_exists($old)) {
            $data = file_get_contents(self::getTemplate('blueprints.page'));
        } else {
            $data = file_get_contents($old);
        }

        /**
         * Just before a Page Template is about to be created & written to disk
         *
         * @delegate PageTemplatePreCreate
         * @since Symphony 2.2.2
         * @param string $context
         * '/blueprints/pages/'
         * @param string $file
         *  The path to the Page Template file
         * @param string $contents
         *  The contents of the `$data`, passed by reference
         */
        Symphony::ExtensionManager()->notifyMembers('PageTemplatePreCreate', '/blueprints/pages/', array('file' => $new, 'contents' => &$data));

        if (PageManager::writePageFiles($new, $data)) {
            // Remove the old file, in the case of a rename
            General::deleteFile($old);

            /**
             * Just after a Page Template is saved after been created.
             *
             * @delegate PageTemplatePostCreate
             * @since Symphony 2.2.2
             * @param string $context
             * '/blueprints/pages/'
             * @param string $file
             *  The path to the Page Template file
             */
            Symphony::ExtensionManager()->notifyMembers('PageTemplatePostCreate', '/blueprints/pages/', array('file' => $new));

            return true;
        }

        return false;
    }

    /**
     * A wrapper for `General::writeFile`, this function takes a `$path`
     * and a `$data` and writes the new template to disk.
     *
     * @param string $path
     *  The path to write the template to
     * @param string $data
     *  The contents of the template
     * @return boolean
     *  true when written successfully, false otherwise
     */
    public static function writePageFiles($path, $data)
    {
        return General::writeFile($path, $data, Symphony::Configuration()->get('write_mode', 'file'));
    }

    /**
     * This function will update a Page in `tbl_pages` given a `$page_id`
     * and an associative array of `$fields`. A third parameter, `$delete_types`
     * will also delete the Page's associated Page Types if passed true.
     *
     * @see toolkit.PageManager#addPageTypesToPage()
     * @param integer $page_id
     *  The ID of the Page that should be updated
     * @param array $fields
     *  Associative array of field names => values for the Page.
     *  This array does need to contain every value for the Page, it
     *  can just be the changed values.
     * @param boolean $delete_types
     *  If true, this parameter will cause the Page Types of the Page to
     *  be deleted. By default this is false.
     * @return boolean
     */
    public static function edit($page_id, array $fields, $delete_types = false)
    {
        if (!is_numeric($page_id)) {
            return false;
        }

        if (isset($fields['id'])) {
            unset($fields['id']);
        }

        // Force parent to be null if empty
        if (isset($fields['parent']) && empty($fields['parent'])) {
            $fields['parent'] = null;
        }

        if (Symphony::Database()
                ->update('tbl_pages')
                ->set($fields)
                ->where(['id' => General::intval($page_id)])
                ->execute()
                ->success()
        ) {
            // If set, this will clear the page's types.
            if ($delete_types) {
                PageManager::deletePageTypes($page_id);
            }

            return true;
        }
        return false;
    }

    /**
     * This function will update all children of a particular page (if any)
     * by renaming/moving all related files to their new path and updating
     * their database information. This is a recursive function and will work
     * to any depth.
     *
     * @param integer $page_id
     *  The ID of the Page whose children need to be updated
     * @param string $page_path
     *  The path of the Page, which is the handles of the Page parents. If the
     *  page has multiple parents, they will be separated by a forward slash.
     *  eg. article/read. If a page has no parents, this parameter should be null.
     * @throws Exception
     * @return boolean
     */
    public static function editPageChildren($page_id = null, $page_path = null)
    {
        if (!is_int($page_id)) {
            return false;
        }

        $page_path = trim($page_path, '/');
        $children = PageManager::fetchChildPages($page_id);

        foreach ($children as $child) {
            $child_id = (int)$child['id'];
            $fields = array(
                'path' => $page_path
            );

            if (!PageManager::createPageFiles($page_path, $child['handle'], $child['path'], $child['handle'])) {
                $success = false;
            }

            if (!PageManager::edit($child_id, $fields)) {
                $success = false;
            }

            $success = PageManager::editPageChildren($child_id, $page_path . '/' . $child['handle']);
        }

        return $success;
    }

    /**
     * This function takes a Page ID and removes the Page from the database
     * in `tbl_pages` and it's associated Page Types in `tbl_pages_types`.
     * This function does not delete any of the Page's children.
     *
     * @see toolkit.PageManager#deletePageTypes
     * @see toolkit.PageManager#deletePageFiles
     * @param integer $page_id
     *  The ID of the Page that should be deleted.
     * @param boolean $delete_files
     *  If true, this parameter will remove the Page's templates from the
     *  the filesystem. By default this is true.
     * @throws DatabaseException
     * @throws Exception
     * @return boolean
     */
    public static function delete($page_id = null, $delete_files = true)
    {
        if (!is_int($page_id)) {
            return false;
        }

        $can_proceed = true;

        // Delete Files (if told to)
        if ($delete_files) {
            $page = PageManager::fetchPageByID($page_id, array('path', 'handle'));

            if (empty($page)) {
                return false;
            }

            $can_proceed = PageManager::deletePageFiles($page['path'], $page['handle']);
        }

        // Delete from tbl_pages/tbl_page_types
        if ($can_proceed) {
            PageManager::deletePageTypes($page_id);
            Symphony::Database()
                ->delete('tbl_pages')
                ->where(['id' => General::intval($page_id)])
                ->execute();
            Symphony::Database()
                ->update('tbl_pages')
                ->set(['sortorder' => '$sortorder - 1'])
                ->where(['sortorder' => ['>' => $page['sortorder']]])
                ->execute();
        }

        return $can_proceed;
    }

    /**
     * Given a `$page_id`, this function will remove all associated
     * Page Types from `tbl_pages_types`.
     *
     * @param integer $page_id
     *  The ID of the Page that should be deleted.
     * @throws DatabaseException
     * @return boolean
     */
    public static function deletePageTypes($page_id = null)
    {
        if (!$page_id) {
            return false;
        }

        return Symphony::Database()
            ->delete('tbl_pages_types')
            ->where(['page_id' => General::intval($page_id)])
            ->execute()
            ->success();
    }

    /**
     * Given a Page's `$path` and `$handle`, this function will remove
     * it's templates from the `PAGES` directory returning boolean on
     * completion
     *
     * @param string $page_path
     *  The path of the Page, which is the handles of the Page parents. If the
     *  page has multiple parents, they will be separated by a forward slash.
     *  eg. article/read. If a page has no parents, this parameter should be null.
     * @param string $handle
     *  A Page handle, generated using `PageManager::createHandle`.
     * @throws Exception
     * @return boolean
     */
    public static function deletePageFiles($page_path, $handle)
    {
        $file = PageManager::resolvePageFileLocation($page_path, $handle);

        // Nothing to do:
        if (!file_exists($file)) {
            return true;
        }

        // Delete it:
        if (General::deleteFile($file)) {
            return true;
        }

        return false;
    }

    /**
     * This function will return an associative array of Page information. The
     * information returned is defined by the `$include_types` and `$select`
     * parameters, which will return the Page Types for the Page and allow
     * a developer to restrict what information is returned about the Page.
     * Optionally, `$where` and `$order_by` parameters allow a developer to
     * further refine their query.
     *
     * @deprecated @since Symphony 3.0.0
     *  Use select() instead
     * @param boolean $include_types
     *  Whether to include the resulting Page's Page Types in the return array,
     *  under the key `type`. Defaults to true.
     * @param array $select (optional)
     *  Accepts an array of columns to return from `tbl_pages`. If omitted,
     *  all columns from the table will be returned.
     * @param array $where (optional)
     *  Accepts an array of WHERE statements that will be appended with AND.
     *  If omitted, all pages will be returned.
     * @param string $order_by (optional)
     *  Allows a developer to return the Pages in a particular order. The string
     *  passed will be appended to `ORDER BY`. If omitted this will return
     *  Pages ordered by `sortorder`.
     * @param boolean $hierarchical (optional)
     *  If true, builds a multidimensional array representing the pages hierarchy.
     *  Defaults to false.
     * @return array|null
     *  An associative array of Page information with the key being the column
     *  name from `tbl_pages` and the value being the data. If requested, the array
     *  can be made multidimensional to reflect the pages hierarchy. If no Pages are
     *  found, null is returned.
     */
    public static function fetch($include_types = true, array $select = array(), array $where = array(), $order_by = null, $hierarchical = false)
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog('PageManager::fetch()', 'PageManager::select()');
        }

        if (empty($select)) {
            $select = ['*'];
        }

        if (!$order_by) {
            $order_by = ['sortorder' => 'ASC'];
        }

        $query = (new PageManager)->select($select);

        if ($hierarchical && !in_array('*', $select)) {
            $query->projection(['id', 'parent']);
        }
        if (is_array($where)) {
            foreach ($where as $w) {
                $where = $query->replaceTablePrefix($w);
                $op = $query->containsSQLParts('where') ? 'AND' : 'WHERE';
                $query->unsafe()->unsafeAppendSQLPart('where', "$op ($w)");
            }
        }
        if (is_array($order_by)) {
            $query->orderBy($order_by);
        } elseif (is_string($order_by)) {
            $order_by = $query->replaceTablePrefix($order_by);
            $query->unsafe()->unsafeAppendSQLPart('order by', "ORDER BY $order_by");
        }

        // Fetch the Page Types for each page, if required
        if ($include_types) {
            $query->includeTypes();
        }

        $pages = $query->execute();

        if ($hierarchical) {
            return $pages->tree();
        }

        return $pages->rows();
    }

    /**
     * Returns Pages that match the given `$page_id`. Developers can optionally
     * choose to specify what Page information is returned using the `$select`
     * parameter.
     *
     * @param integer|array $page_id
     *  The ID of the Page, or an array of ID's
     * @param array $select (optional)
     *  Accepts an array of columns to return from `tbl_pages`. If omitted,
     *  all columns from the table will be returned.
     * @return array|null
     *  An associative array of Page information with the key being the column
     *  name from `tbl_pages` and the value being the data. If multiple Pages
     *  are found, an array of Pages will be returned. If no Pages are found
     *  null is returned.
     */
    public static function fetchPageByID($page_id = null, array $select = array())
    {
        if (!$page_id) {
            return null;
        }

        if (!is_array($page_id)) {
            $page_id = [$page_id];
        }

        if (empty($select)) {
            $select = ['*'];
        }

        $page = (new PageManager)
            ->select($select)
            ->includeTypes()
            ->pages($page_id)
            ->execute()
            ->rows();

        return count($page) == 1 ? current($page) : $page;
    }

    /**
     * Returns the first Page that match the given `$type`.
     *
     * @since Symphony 3.0.0
     *  It returns only the first page of the specified type.
     * @param string $type
     *  Where the type is one of the available Page Types.
     * @return array|null
     *  An associative array of Page information with the key being the column
     *  name from `tbl_pages` and the value being the data. If multiple Pages
     *  are found, an array of Pages will be returned. If no Pages are found
     *  null is returned.
     */
    public static function fetchPageByType($type)
    {
        General::ensureType([
            'type' => ['var' => $type, 'type' => 'string'],
        ]);
        $pageQuery = (new PageManager)
            ->select()
            ->innerJoin('tbl_pages_types')
            ->alias('pt')
            ->on(['p.id' => '$pt.page_id'])
            ->where(['pt.type' => $type])
            ->limit(1);
        return $pageQuery->execute()->next();
    }

    /**
     * Returns the child Pages (if any) of the given `$page_id`.
     *
     * @param integer $page_id
     *  The ID of the Page.
     * @param array $select (optional)
     *  Accepts an array of columns to return from `tbl_pages`. If omitted,
     *  all columns from the table will be returned.
     * @return array|null
     *  An associative array of Page information with the key being the column
     *  name from `tbl_pages` and the value being the data. If multiple Pages
     *  are found, an array of Pages will be returned. If no Pages are found
     *  null is returned.
     */
    public static function fetchChildPages($page_id = null, array $select = array())
    {
        if (!$page_id) {
            return null;
        }

        if (empty($select)) {
            $select = ['*'];
        }

        return (new PageManager)
            ->select($select)
            ->where(['id' => ['!=' => $page_id]])
            ->where(['parent' => $page_id])
            ->execute()
            ->rows();
    }

    /**
     * This function returns a Page's Page Types. If the `$page_id`
     * parameter is given, the types returned will be for that Page.
     *
     * @param integer $page_id
     *  The ID of the Page.
     * @return array
     *  An array of the Page Types
     */
    public static function fetchPageTypes($page_id = null)
    {
        $sql = Symphony::Database()
            ->select(['pt.type'])
            ->from('tbl_pages_types')
            ->alias('pt')
            ->groupBy(['pt.type'])
            ->orderBy(['pt.type' => 'ASC']);

        if ($page_id) {
            $sql->where(['pt.page_id' => $page_id]);
        }

        return $sql->execute()->column('type');
    }

    /**
     * Returns all the page types that exist in this Symphony install.
     * There are 6 default system page types, and new types can be added
     * by Developers via the Page Editor.
     *
     * @since Symphony 2.3 introduced the JSON type.
     * @return array
     *  An array of strings of the page types used in this Symphony
     *  install. At the minimum, this will be an array with the values
     * 'index', 'XML', 'JSON', 'admin', '404' and '403'.
     */
    public static function fetchAvailablePageTypes()
    {
        $system_types = array('index', 'XML', 'JSON', 'admin', '404', '403');

        $types = PageManager::fetchPageTypes();

        return (!empty($types) ? General::array_remove_duplicates(array_merge($system_types, $types)) : $system_types);
    }

    /**
     * Work out the next available sort order for a new page
     *
     * @return integer
     *  Returns the next sort order
     */
    public static function fetchNextSortOrder()
    {
        return Symphony::Database()
            ->select(['MAX(sortorder)'])
            ->from('tbl_pages')
            ->execute()
            ->integer(0) + 1;
    }

    /**
     * Fetch an associated array with Page ID's and the types they're using.
     *
     * @throws DatabaseException
     * @return array
     *  A 2-dimensional associated array where the key is the page ID.
     */
    public static function fetchAllPagesPageTypes()
    {
        $types = Symphony::Database()
            ->select(['page_id', 'type'])
            ->from('tbl_pages_types')
            ->execute()
            ->rows();

        $page_types = [];

        if (is_array($types)) {
            foreach ($types as $type) {
                $page_types[$type['page_id']][] = $type['type'];
            }
        }

        return $page_types;
    }

    /**
     * Given a name, this function will return a page handle. These handles
     * will only contain latin characters
     *
     * @param string $name
     *  The Page name to generate a handle for
     * @return string
     */
    public static function createHandle($name)
    {
        return Lang::createHandle($name, 255, '-', false, true, array(
            '@^[^a-z\d]+@i' => '',
            '/[^\w\-\.]/i' => ''
        ));
    }

    /**
     * This function takes a `$path` and `$handle` and generates a flattened
     * string for use as a filename for a Page's template.
     *
     * @param string $path
     *  The path of the Page, which is the handles of the Page parents. If the
     *  page has multiple parents, they will be separated by a forward slash.
     *  eg. article/read. If a page has no parents, this parameter should be null.
     * @param string $handle
     *  A Page handle, generated using `PageManager::createHandle`.
     * @return string
     */
    public static function createFilePath($path, $handle)
    {
        return trim(str_replace('/', '_', $path . '_' . $handle), '_');
    }

    /**
     * This function will return the number of child pages for a given
     * `$page_id`. This is a recursive function and will return the absolute
     * count.
     *
     * @param integer $page_id
     *  The ID of the Page.
     * @return integer
     *  The number of child pages for the given `$page_id`
     */
    public static function getChildPagesCount($page_id = null)
    {
        if (is_null($page_id)) {
            return null;
        }

        $children = (new PageManager)
            ->select()
            ->where(['parent' => $page_id])
            ->execute()
            ->rows();

        $count = count($children);

        if ($count > 0) {
            foreach ($children as $c) {
                $count += self::getChildPagesCount($c['id']);
            }
        }

        return $count;
    }

    /**
     * Returns boolean if a the given `$type` has been used by Symphony
     * for a Page that is not `$page_id`.
     *
     * @param integer $page_id
     *  The ID of the Page to exclude from the query.
     * @param string $type
     *  The Page Type to look for in `tbl_page_types`.
     * @return boolean
     *  true if the type is used, false otherwise
     */
    public static function hasPageTypeBeenUsed($page_id = null, $type)
    {
        return count(Symphony::Database()
            ->select(['pt.id'])
            ->from('tbl_pages_types', 'pt')
            ->where(['pt.page_id' => ['!=' => $page_id]])
            ->where(['pt.type' => $type])
            ->limit(1)
            ->execute()
            ->rows()) === 1;
    }

    /**
     * Given a `$page_id`, this function returns boolean if the page
     * has child pages.
     *
     * @param integer $page_id
     *  The ID of the Page to check
     * @return boolean
     *  true if the page has children, false otherwise
     */
    public static function hasChildPages($page_id)
    {
        return count(Symphony::Database()
            ->select(['p.id'])
            ->from('tbl_pages', 'p')
            ->where(['p.parent' => $page_id])
            ->limit(1)
            ->execute()
            ->rows()) === 1;
    }

    /**
     * Resolves the path to this page's XSLT file. The Symphony convention
     * is that they are stored in the `PAGES` folder. If this page has a parent
     * it will be as if all the / in the URL have been replaced with _. ie.
     * /articles/read/ will produce a file `articles_read.xsl`
     *
     * @see toolkit.PageManager#createFilePath()
     * @param string $path
     *  The URL path to this page, excluding the current page. ie, /articles/read
     *  would make `$path` become articles/
     * @param string $handle
     *  The handle of the page.
     * @return string
     *  The path to the XSLT of the page
     */
    public static function resolvePageFileLocation($path, $handle)
    {
        return PAGES . '/' . PageManager::createFilePath($path, $handle) . '.xsl';
    }

    /**
     * Given the `$page_id` and a `$column`, this function will return an
     * array of the given `$column` for the Page, including all parents.
     *
     * @param mixed $page_id
     *  The ID of the Page that currently being viewed, or the handle of the
     *  current Page
     * @param string $column
     *  The column to return
     * @return array
     *  An array of the current Page, containing the `$column`
     *  requested. The current page will be the last item the array, as all
     *  parent pages are prepended to the start of the array
     */
    public static function resolvePage($page_id, $column)
    {
        $query = (new PageManager)
            ->select(['p.parent', "p.$column"])
            ->limit(1);

        if (General::intval($page_id) > 0) {
            $query->page($page_id);
        } else {
            $query->handle($page_id);
        }

        $page = $query->execute()->next();

        if (empty($page)) {
            return $page;
        }

        $path = array($page[$column]);

        if (!empty($page['parent'])) {
            $next_parent = $page['parent'];

            while ($next_parent &&
                $parent = (new PageManager)
                    ->select(['p.parent', "p.$column"])
                    ->page($next_parent)
                    ->limit(1)
                    ->execute()
                    ->next()
            ) {
                array_unshift($path, $parent[$column]);
                $next_parent = $parent['parent'];
            }
        }

        return $path;
    }

    /**
     * Given the `$page_id`, return the complete title of the
     * current page. Each part of the Page's title will be
     * separated by ': '.
     *
     * @param mixed $page_id
     *  The ID of the Page that currently being viewed, or the handle of the
     *  current Page
     * @return string
     *  The title of the current Page. If the page is a child of another
     *  it will be prepended by the parent and a colon, ie. Articles: Read
     */
    public static function resolvePageTitle($page_id)
    {
        $path = PageManager::resolvePage($page_id, 'title');

        return implode(': ', $path);
    }

    /**
     * Given the `$page_id`, return the complete path to the
     * current page. Each part of the Page's path will be
     * separated by '/'.
     *
     * @param mixed $page_id
     *  The ID of the Page that currently being viewed, or the handle of the
     *  current Page
     * @return string
     *  The complete path to the current Page including any parent
     *  Pages, ie. /articles/read
     */
    public static function resolvePagePath($page_id)
    {
        $path = PageManager::resolvePage($page_id, 'handle');

        return implode('/', $path);
    }

    /**
     * Resolve a page by it's handle and path
     *
     * @param string $handle
     *  The handle of the page
     * @param boolean $path
     *  The path to the page
     * @return array
     *  array if found, null if not
     */
    public static function resolvePageByPath($handle, $path = false)
    {
        return (new PageManager)
            ->select()
            ->handle($handle)
            ->path(!$path ? null : $path)
            ->limit(1)
            ->execute()
            ->next();
    }

    /**
     * Check whether a data source is used or not
     *
     * @param string $handle
     *  The data source handle
     * @return boolean
     *  true if used, false if not
     */
    public static function isDataSourceUsed($handle)
    {
        return (new PageManager)
            ->select()
            ->count()
            ->where(['p.data_sources' => ['regexp' => "[[:<:]]{$handle}[[:>:]]"]])
            ->execute()
            ->integer(0) > 0;
    }

    /**
     * Check whether a event is used or not
     *
     * @param string $handle
     *  The event handle
     * @return boolean
     *  true if used, false if not
     */
    public static function isEventUsed($handle)
    {
        return (new PageManager)
            ->select()
            ->count()
            ->where(['p.events' => ['regexp' => "[[:<:]]{$handle}[[:>:]]"]])
            ->execute()
            ->integer(0) > 0;
    }

    /**
     * Factory method that creates a new PageQuery.
     *
     * @since Symphony 3.0.0
     * @param array $projection
     *  The projection to select.
     *  If no projection gets added, it defaults to `PageQuery::getDefaultProjection()`.
     * @return PageQuery
     */
    public function select(array $projection = [])
    {
        return new PageQuery(Symphony::Database(), $projection);
    }
}
