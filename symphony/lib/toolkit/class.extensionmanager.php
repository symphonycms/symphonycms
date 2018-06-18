<?php

/**
 * @package toolkit
 */
/**
 * The ExtensionManager class is responsible for managing all extensions
 * in Symphony. Extensions are stored on the file system in the `EXTENSIONS`
 * folder. They are auto-discovered where the Extension class name is the same
 * as it's folder name (excluding the extension prefix).
 */

class ExtensionManager implements FileResource
{
    /**
     * An array of all the objects that the Manager is responsible for.
     * Defaults to an empty array.
     * @var array
     */
    protected static $_pool = array();

    /**
     * An array of all extensions whose status is enabled
     * @var array
     */
    private static $_enabled_extensions = array();

    /**
     * An array of all the subscriptions to Symphony delegates made by extensions.
     * @var array
     */
    private static $_subscriptions = array();

    /**
     * An associative array of all the extensions in `tbl_extensions` where
     * the key is the extension name and the value is an array
     * representation of it's accompanying database row.
     * @var array
     */
    private static $_extensions = array();

    /**
     * An associative array of all the providers from the enabled extensions.
     * The key is the type of object, with the value being an associative array
     * with the name, classname and path to the object
     *
     * @since Symphony 2.3
     * @var array
     */
    private static $_providers = array();

    /**
     * The constructor will populate the `$_subscriptions` variable from
     * the `tbl_extension` and `tbl_extensions_delegates` tables.
     */
    public function __construct()
    {
        if (empty(self::$_subscriptions) && Symphony::Database() && Symphony::Database()->isConnected()) {
            $subscriptions = $this->getDelegateSubscriptions();

            while ($subscription = $subscriptions->next()) {
                self::$_subscriptions[$subscription['delegate']][] = $subscription;
            }
        }
    }

    public static function __getHandleFromFilename($filename)
    {
        return false;
    }

    /**
     * Given a name, returns the full class name of an Extension.
     * Extension use an 'extension' prefix.
     *
     * @param string $name
     *  The extension handle
     * @return string
     */
    public static function __getClassName($name)
    {
        return 'extension_' . $name;
    }

    /**
     * Finds an Extension by name by searching the `EXTENSIONS` folder and
     * returns the path to the folder.
     *
     * @param string $name
     *  The extension folder
     * @return string
     */
    public static function __getClassPath($name)
    {
        return EXTENSIONS . strtolower("/$name");
    }

    /**
     * Given a name, return the path to the driver of the Extension.
     *
     * @see toolkit.ExtensionManager#__getClassPath()
     * @param string $name
     *  The extension folder
     * @return string
     */
    public static function __getDriverPath($name)
    {
        return self::__getClassPath($name) . '/extension.driver.php';
    }

    /**
     * This function returns an instance of an extension from it's name
     *
     * @param string $name
     *  The name of the Extension Class minus the extension prefix.
     * @throws SymphonyException
     * @throws Exception
     * @return Extension
     */
    public static function getInstance($name)
    {
        return (isset(self::$_pool[$name]) ? self::$_pool[$name] : self::create($name));
    }

    /**
     * Populates the `ExtensionManager::$_extensions` array with all the
     * extensions stored in `tbl_extensions`. If `ExtensionManager::$_extensions`
     * isn't empty, passing true as a parameter will force the array to update
     *
     * @param boolean $update
     *  Updates the `ExtensionManager::$_extensions` array even if it was
     *  populated, defaults to false.
     * @throws DatabaseException
     */
    protected static function buildExtensionList($update = false)
    {
        if (empty(self::$_extensions) || $update) {
            self::$_extensions = (new ExtensionManager)
                ->select()
                ->execute()
                ->rowsIndexedByColumn('name');
        }
    }

    /**
     * Returns the status of an Extension given an associative array containing
     * the Extension `handle` and `version` where the `version` is the file
     * version, not the installed version. This function returns an array
     * which may include a maximum of two statuses.
     *
     * @param array $about
     *  An associative array of the extension meta data, typically returned
     *  by `ExtensionManager::about()`. At the very least this array needs
     *  `handle` and `version` keys.
     * @return array
     *  An array of extension statuses, with the possible values being
     * `EXTENSION_ENABLED`, `EXTENSION_DISABLED`, `EXTENSION_REQUIRES_UPDATE`
     *  or `EXTENSION_NOT_INSTALLED`. If an extension doesn't exist,
     *  `EXTENSION_NOT_INSTALLED` will be returned.
     */
    public static function fetchStatus($about)
    {
        $return = array();
        static::buildExtensionList();

        if (isset($about['handle']) && array_key_exists($about['handle'], self::$_extensions)) {
            if (self::$_extensions[$about['handle']]['status'] == 'enabled') {
                $return[] = Extension::EXTENSION_ENABLED;
            } else {
                $return[] = Extension::EXTENSION_DISABLED;
            }
        } else {
            $return[] = Extension::EXTENSION_NOT_INSTALLED;
        }

        if (isset($about['handle'], $about['version']) && static::requiresUpdate($about['handle'], $about['version'])) {
            $return[] = Extension::EXTENSION_REQUIRES_UPDATE;
        }

        return $return;
    }

    /**
     * A convenience method that returns an extension version from it's name.
     *
     * @param string $name
     *  The name of the Extension Class minus the extension prefix.
     * @return string
     */
    public static function fetchInstalledVersion($name)
    {
        static::buildExtensionList();

        return (isset(self::$_extensions[$name]) ? self::$_extensions[$name]['version'] : null);
    }

    /**
     * A convenience method that returns an extension ID from it's name.
     *
     * @param string $name
     *  The name of the Extension Class minus the extension prefix.
     * @return integer
     */
    public static function fetchExtensionID($name)
    {
        static::buildExtensionList();

        return self::$_extensions[$name]['id'];
    }

    /**
     * Return an array all the Provider objects supplied by extensions,
     * optionally filtered by a given `$type`.
     *
     * @since Symphony 2.3
     * @todo Add information about the possible types
     * @param string $type
     *  This will only return Providers of this type. If null, which is
     *  default, all providers will be returned.
     * @throws Exception
     * @throws SymphonyException
     * @return array
     *  An array of objects
     */
    public static function getProvidersOf($type = null)
    {
        // Loop over all extensions and build an array of providable objects
        if (empty(self::$_providers)) {
            self::$_providers = array();

            foreach (self::listInstalledHandles() as $handle) {
                $obj = self::getInstance($handle);

                if (!method_exists($obj, 'providerOf')) {
                    continue;
                }

                $providers = $obj->providerOf();

                if (empty($providers)) {
                    continue;
                }

                // For each of the matching objects (by $type), resolve the object path
                self::$_providers = array_merge_recursive(self::$_providers, $obj->providerOf());
            }
        }

        // Return an array of objects
        if (is_null($type)) {
            return self::$_providers;
        }

        if (!isset(self::$_providers[$type])) {
            return array();
        }

        return self::$_providers[$type];
    }

    /**
     * This function will return the `Cacheable` object with the appropriate
     * caching layer for the given `$key`. This `$key` should be stored in
     * the Symphony configuration in the caching group with a reference
     * to the class of the caching object. If the key is not found, this
     * will return a default `Cacheable` object created with the Database driver.
     *
     * @since Symphony 2.4
     * @param string $key
     *  Should be a reference in the Configuration file to the Caching class
     * @param boolean $reuse
     *  By default true, which will reuse an existing Cacheable object of `$key`
     *  if it exists. If false, a new instance will be generated.
     * @return Cacheable
     */
    public static function getCacheProvider($key = null, $reuse = true)
    {
        $cacheDriver = Symphony::Configuration()->get($key, 'caching');

        if (in_array($cacheDriver, array_keys(Symphony::ExtensionManager()->getProvidersOf('cache')))) {
            $cacheable = new $cacheDriver;
        } else {
            $cacheable = Symphony::Database();
            $cacheDriver = 'CacheDatabase';
        }

        if ($reuse === false) {
            return new Cacheable($cacheable);
        } elseif (!isset(self::$_pool[$cacheDriver])) {
            self::$_pool[$cacheDriver] = new Cacheable($cacheable);
        }

        return self::$_pool[$cacheDriver];
    }

    /**
     * Determines whether the current extension is installed or not by checking
     * for an id in `tbl_extensions`
     *
     * @param string $name
     *  The name of the Extension Class minus the extension prefix.
     * @return boolean
     */
    protected static function requiresInstallation($name)
    {
        static::buildExtensionList();
        $id = self::$_extensions[$name]['id'];

        return (is_numeric($id) ? false : true);
    }

    /**
     * Determines whether an extension needs to be updated or not.
     * This function will return the
     * installed version if the extension requires an update, or
     * false otherwise.
     *
     * @param string $name
     *  The name of the Extension Class minus the extension prefix.
     * @param string $file_version
     *  The version of the extension from the **file**, not the Database.
     * @return boolean
     *  true if the given extension (by $name) requires updating.
     *  If the extension doesn't require updating, false.
     */
    protected static function requiresUpdate($name, $file_version)
    {
        $installed_version = static::fetchInstalledVersion($name);

        if (!$installed_version) {
            return false;
        }

        $vc = new VersionComparator($installed_version);
        return $vc->lessThan($file_version);
    }

    /**
     * Enabling an extension will re-register all it's delegates with Symphony.
     * It will also install or update the extension if needs be by calling the
     * extensions respective install and update methods. The enable method is
     * of the extension object is finally called.
     *
     * @see toolkit.ExtensionManager#registerDelegates()
     * @see toolkit.ExtensionManager#canUninstallOrDisable()
     * @param string $name
     *  The name of the Extension Class minus the extension prefix.
     * @throws SymphonyException
     * @throws Exception
     * @return boolean
     */
    public static function enable($name)
    {
        $obj = self::getInstance($name);

        // If not installed, install it
        if (static::requiresInstallation($name) && $obj->install() === false) {
            // If the installation failed, run the uninstall method which
            // should rollback the install method. #1326
            $obj->uninstall();
            return false;

            // If the extension requires updating before enabling, then update it
        } elseif (($about = self::about($name)) && static::requiresUpdate($name, $about['version'])) {
            $obj->update(static::fetchInstalledVersion($name));
        }

        if (!isset($about)) {
            $about = self::about($name);
        }

        $id = self::fetchExtensionID($name);

        $fields = array(
            'name' => $name,
            'status' => 'enabled',
            'version' => $about['version']
        );

        // If there's no $id, the extension needs to be installed
        if (is_null($id)) {
            Symphony::Database()
                ->insert('tbl_extensions')
                ->values($fields)
                ->execute();
            static::buildExtensionList(true);

        // Extension is installed, so update!
        } else {
            Symphony::Database()
                ->update('tbl_extensions')
                ->set($fields)
                ->where(['id' => $id])
                ->execute();
        }

        self::registerDelegates($name);

        // Now enable the extension
        $obj->enable();

        return true;
    }

    /**
     * Disabling an extension will prevent it from executing but retain all it's
     * settings in the relevant tables. Symphony checks that an extension can
     * be disabled using the `canUninstallorDisable()` before removing
     * all delegate subscriptions from the database and calling the extension's
     * `disable()` function.
     *
     * @see toolkit.ExtensionManager#removeDelegates()
     * @see toolkit.ExtensionManager#canUninstallOrDisable()
     * @param string $name
     *  The name of the Extension Class minus the extension prefix.
     * @throws DatabaseException
     * @throws SymphonyException
     * @throws Exception
     * @return boolean
     */
    public static function disable($name)
    {
        $obj = self::getInstance($name);

        static::canUninstallOrDisable($obj);

        $info = self::about($name);
        $id = self::fetchExtensionID($name);

        $disabled = Symphony::Database()
            ->update('tbl_extensions')
            ->set([
                'name' => $name,
                'status' => 'disabled',
                'version' => $info['version']
            ])
            ->where(['id' => $id])
            ->execute()
            ->success();

        $obj->disable();

        self::removeDelegates($name);

        return $disabled;
    }

    /**
     * Uninstalling an extension will unregister all delegate subscriptions and
     * remove all extension settings. Symphony checks that an extension can
     * be uninstalled using the `canUninstallorDisable()` before calling
     * the extension's `uninstall()` function. Alternatively, if this function
     * is called because the extension described by `$name` cannot be found
     * it's delegates and extension meta information will just be removed from the
     * database.
     *
     * @see toolkit.ExtensionManager#removeDelegates()
     * @see toolkit.ExtensionManager#canUninstallOrDisable()
     * @param string $name
     *  The name of the Extension Class minus the extension prefix.
     * @throws Exception
     * @throws SymphonyException
     * @throws DatabaseException
     * @throws Exception
     * @return boolean
     */
    public static function uninstall($name)
    {
        // If this function is called because the extension doesn't exist,
        // then catch the error and just remove from the database. This
        // means that the uninstall() function will not run on the extension,
        // which may be a blessing in disguise as no entry data will be removed
        try {
            $obj = self::getInstance($name);
            static::canUninstallOrDisable($obj);
            $obj->uninstall();
        } catch (SymphonyException $ex) {
            // Create a consistant key
            $key = str_replace('-', '_', $ex->getTemplateName());

            if ($key !== 'missing_extension') {
                throw $ex;
            }
        }

        self::removeDelegates($name);
        return Symphony::Database()
            ->delete('tbl_extensions')
            ->where(['name' => $name])
            ->execute()
            ->success();
    }

    /**
     * Retrieves all subscribed delegates from the database.
     *
     * @return DatabaseQueryResult
     */
    public function getDelegateSubscriptions()
    {
        $projection = ['t1.name', 't2.page', 't2.delegate', 't2.callback', 't2.order'];
        $orderBy = ['t2.delegate', 't2.order', 't1.name'];
        $removeOrder = false;
        try {
            $removeOrder = !(Symphony::Database()
                ->showColumns()
                ->from('tbl_extensions_delegates')
                ->like('order')
                ->execute()
                ->next());
        } catch (DatabaseException $ex) {
            $removeOrder = true;
            // Ignore for now
            // This catch and check will be removed in Symphony 5.0.0
        }

        // Remove order col from projection and order by
        if ($removeOrder) {
            array_pop($projection);
            $orderBy = ['t2.delegate', 't1.name'];
        }

        return Symphony::Database()
            ->select($projection)
            ->from('tbl_extensions', 't1')
            ->innerJoin('tbl_extensions_delegates', 't2')
            ->on(['t1.id' => '$t2.extension_id'])
            ->where(['t1.status' => 'enabled'])
            ->orderBy($orderBy, 'ASC')
            ->execute();
    }

    /**
     * This functions registers an extensions delegates in `tbl_extensions_delegates`.
     *
     * @param string $name
     *  The name of the Extension Class minus the extension prefix.
     * @throws Exception
     * @throws SymphonyException
     * @return integer
     *  The Extension ID
     */
    public static function registerDelegates($name)
    {
        $obj = self::getInstance($name);
        $id = self::fetchExtensionID($name);

        if (!$id) {
            return false;
        }

        Symphony::Database()
            ->delete('tbl_extensions_delegates')
            ->where(['extension_id' => $id])
            ->execute();

        $delegates = $obj->getSubscribedDelegates();

        if (is_array($delegates) && !empty($delegates)) {
            foreach ($delegates as $delegate) {
                Symphony::Database()
                    ->insert('tbl_extensions_delegates')
                    ->values([
                        'extension_id' => $id,
                        'page' => $delegate['page'],
                        'delegate' => $delegate['delegate'],
                        'callback' => $delegate['callback'],
                        'order' => isset($delegate['order']) ? (int)$delegate['order'] : 0
                    ])
                    ->execute();
            }
        }

        // Remove the unused DB records
        self::cleanupDatabase();

        return $id;
    }

    /**
     * This function will remove all delegate subscriptions for an extension
     * given an extension's name. This triggers `cleanupDatabase()`
     *
     * @see toolkit.ExtensionManager#cleanupDatabase()
     * @param string $name
     *  The name of the Extension Class minus the extension prefix.
     * @return boolean
     */
    public static function removeDelegates($name)
    {
        $delegates = Symphony::Database()
            ->select(['ted.id'])
            ->from('tbl_extensions_delegates', 'ted')
            ->leftJoin('tbl_extensions')
            ->on(['tbl_extensions.id' => '$ted.extension_id'])
            ->where(['tbl_extensions.name' => $name])
            ->execute()
            ->column('id');

        if (!empty($delegates)) {
            Symphony::Database()
                ->delete('tbl_extensions_delegates')
                ->where(['id' => ['in' => $delegates]])
                ->execute();
        }

        // Remove the unused DB records
        self::cleanupDatabase();

        return true;
    }

    /**
     * This function checks that if the given extension has provided Fields,
     * Data Sources or Events, that they aren't in use before the extension
     * is uninstalled or disabled. This prevents exceptions from occurring when
     * accessing an object that was using something provided by this Extension
     * can't anymore because it has been removed.
     *
     * @param Extension $obj
     *  An extension object
     * @throws SymphonyException
     * @throws Exception
     */
    protected static function canUninstallOrDisable(Extension $obj)
    {
        $extension_handle = strtolower(preg_replace('/^extension_/i', null, get_class($obj)));
        $about = self::about($extension_handle);

        // Fields:
        if (is_dir(EXTENSIONS . "/{$extension_handle}/fields")) {
            foreach (glob(EXTENSIONS . "/{$extension_handle}/fields/field.*.php") as $file) {
                $type = preg_replace(array('/^field\./i', '/\.php$/i'), null, basename($file));

                if (FieldManager::isFieldUsed($type)) {
                    throw new Exception(
                        __('The field ‘%s’, provided by the Extension ‘%s’, is currently in use.', array(basename($file), $about['name']))
                        . ' ' . __("Please remove it from your sections prior to uninstalling or disabling.")
                    );
                }
            }
        }

        // Data Sources:
        if (is_dir(EXTENSIONS . "/{$extension_handle}/data-sources")) {
            foreach (glob(EXTENSIONS . "/{$extension_handle}/data-sources/data.*.php") as $file) {
                $handle = preg_replace(array('/^data\./i', '/\.php$/i'), null, basename($file));

                if (PageManager::isDataSourceUsed($handle)) {
                    throw new Exception(
                        __('The Data Source ‘%s’, provided by the Extension ‘%s’, is currently in use.', array(basename($file), $about['name']))
                        . ' ' . __("Please remove it from your pages prior to uninstalling or disabling.")
                    );
                }
            }
        }

        // Events
        if (is_dir(EXTENSIONS . "/{$extension_handle}/events")) {
            foreach (glob(EXTENSIONS . "/{$extension_handle}/events/event.*.php") as $file) {
                $handle = preg_replace(array('/^event\./i', '/\.php$/i'), null, basename($file));

                if (PageManager::isEventUsed($handle)) {
                    throw new Exception(
                        __('The Event ‘%s’, provided by the Extension ‘%s’, is currently in use.', array(basename($file), $about['name']))
                        . ' ' . __("Please remove it from your pages prior to uninstalling or disabling.")
                    );
                }
            }
        }

        // Text Formatters
        if (is_dir(EXTENSIONS . "/{$extension_handle}/text-formatters")) {
            foreach (glob(EXTENSIONS . "/{$extension_handle}/text-formatters/formatter.*.php") as $file) {
                $handle = preg_replace(array('/^formatter\./i', '/\.php$/i'), null, basename($file));

                if (FieldManager::isTextFormatterUsed($handle)) {
                    throw new Exception(
                        __('The Text Formatter ‘%s’, provided by the Extension ‘%s’, is currently in use.', array(basename($file), $about['name']))
                        . ' ' . __("Please remove it from your fields prior to uninstalling or disabling.")
                    );
                }
            }
        }
    }

    /**
     * Given a delegate name, notify all extensions that have registered to that
     * delegate to executing their callbacks with a `$context` array parameter
     * that contains information about the current Symphony state.
     *
     * @param string $delegate
     *  The delegate name
     * @param string $page
     *  The current page namespace that this delegate operates in
     * @param array $context
     *  The `$context` param is an associative array that at minimum will contain
     *  the current Administration class, the current page object and the delegate
     *  name. Other context information may be passed to this function when it is
     *  called. eg.
     *
     * array(
     *        'parent' =>& $this->Parent,
     *        'page' => $page,
     *        'delegate' => $delegate
     *    );
     * @throws Exception
     * @throws SymphonyException
     * @return null|void
     */
    public static function notifyMembers($delegate, $page, array $context = array())
    {
        // Make sure $page is an array
        if (!is_array($page)) {
            $page = array($page);
        }

        // Support for global delegate subscription
        if (!in_array('*', $page)) {
            $page[] = '*';
        }

        $services = array();

        if (isset(self::$_subscriptions[$delegate])) {
            foreach (self::$_subscriptions[$delegate] as $subscription) {
                if (!in_array($subscription['page'], $page)) {
                    continue;
                }

                $services[] = $subscription;
            }
        }

        if (empty($services)) {
            return null;
        }

        $context += array('page' => $page, 'delegate' => $delegate);
        $profiling = Symphony::Profiler() instanceof Profiler;

        foreach ($services as $s) {
            if ($profiling) {
                // Initial seeding and query count
                Symphony::Profiler()->seed();
                $queries = Symphony::Database()->queryCount();
            }

            // Get instance of extension and execute the callback passing
            // the `$context` along
            $obj = self::getInstance($s['name']);

            if (is_object($obj) && method_exists($obj, $s['callback'])) {
                $obj->{$s['callback']}($context);
            }

            // Complete the Profiling sample
            if ($profiling) {
                $queries = Symphony::Database()->queryCount() - $queries;
                Symphony::Profiler()->sample($delegate . '|' . $s['name'], PROFILE_LAP, 'Delegate', $queries);
            }
        }
    }

    /**
     * Returns an array of all the enabled extensions available
     *
     * @return array
     */
    public static function listInstalledHandles()
    {
        if (empty(self::$_enabled_extensions) && Symphony::Database()->isConnected()) {
            self::$_enabled_extensions = (new ExtensionManager)
                ->select(['name'])
                ->enabled()
                ->execute()
                ->column('name');
        }
        return self::$_enabled_extensions;
    }

    /**
     * Returns true if the extension is installed.
     *
     * @uses listInstalledHandles()
     * @since Symphony 3.0.0
     * @param string $handle
     *  The name of the extension
     * @return boolean
     */
    public static function isInstalled($handle)
    {
        return in_array($handle, self::listInstalledHandles());
    }

    /**
     * Will return an associative array of all extensions and their about information
     *
     * @param string $filter
     *  Allows a regular expression to be passed to return only extensions whose
     *  folders match the filter.
     * @throws SymphonyException
     * @throws Exception
     * @return array
     *  An associative array with the key being the extension folder and the value
     *  being the extension's about information
     */
    public static function listAll($filter = '/^((?![-^?%:*|"<>]).)*$/')
    {
        $result = array();
        $extensions = General::listDirStructure(EXTENSIONS, $filter, false, EXTENSIONS);

        if (is_array($extensions) && !empty($extensions)) {
            foreach ($extensions as $extension) {
                $e = trim($extension, '/');

                if ($about = self::about($e)) {
                    $result[$e] = $about;
                }
            }
        }

        return $result;
    }

    /**
     * Custom user sorting function used inside `fetch` to recursively sort authors
     * by their names.
     *
     * @param array $a
     * @param array $b
     * @param integer $i
     * @return integer
     */
    private static function sortByAuthor($a, $b, $i = 0)
    {
        $first = $a;
        $second = $b;

        if (isset($a[$i])) {
            $first = $a[$i];
        }

        if (isset($b[$i])) {
            $second = $b[$i];
        }

        if ($first == $a && $second == $b && $first['name'] == $second['name']) {
            return 1;
        } elseif ($first['name'] == $second['name']) {
            return self::sortByAuthor($a, $b, $i + 1);
        } else {
            return ($first['name'] < $second['name']) ? -1 : 1;
        }
    }

    /**
     * This function will return an associative array of Extension information. The
     * information returned is defined by the `$select` parameter, which will allow
     * a developer to restrict what information is returned about the Extension.
     * Optionally, `$where` (not implemented) and `$order_by` parameters allow a developer to
     * further refine their query.
     *
     * @see listAll()
     * @param array $select (optional)
     *  Accepts an array of keys to return from the listAll() method. If omitted, all keys
     *  will be returned.
     * @param array $where (optional)
     *  Not implemented.
     * @param string $order_by (optional)
     *  Allows a developer to return the extensions in a particular order. The syntax is the
     *  same as other `fetch` methods. If omitted this will return resources ordered by `name`.
     * @throws Exception
     * @throws SymphonyException
     * @return array
     *  An associative array of Extension information, formatted in the same way as the
     *  listAll() method.
     */
    public static function fetch(array $select = array(), array $where = array(), $order_by = null)
    {
        $extensions = self::listAll();
        $data = array();

        if (empty($select) && empty($where) && is_null($order_by)) {
            return $extensions;
        }

        if (empty($extensions)) {
            return array();
        }

        if (!is_null($order_by)) {
            $author = $name = $label = array();
            $order_by = array_map('strtolower', explode(' ', $order_by));
            $order = ($order_by[1] == 'desc') ? SORT_DESC : SORT_ASC;
            $sort = $order_by[0];

            if ($sort == 'author') {
                foreach ($extensions as $key => $about) {
                    $author[$key] = $about['author'];
                }

                uasort($author, array('self', 'sortByAuthor'));

                if ($order == SORT_DESC) {
                    $author = array_reverse($author);
                }

                foreach ($author as $key => $value) {
                    $data[$key] = $extensions[$key];
                }

                $extensions = $data;
            } elseif ($sort == 'name') {
                foreach ($extensions as $key => $about) {
                    $name[$key] = strtolower($about['name']);
                    $label[$key] = $key;
                }

                array_multisort($name, $order, $label, $order, $extensions);
            }
        }

        foreach ($extensions as $i => $e) {
            $data[$i] = array();
            foreach ($e as $key => $value) {
                // If $select is empty, we assume every field is requested
                if (in_array($key, $select) || empty($select)) {
                    $data[$i][$key] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * This function will load an extension's meta information given the extension
     * `$name`. Since Symphony 2.3, this function will look for an `extension.meta.xml`
     * file inside the extension's folder. If this is not found, it will initialise
     * the extension and invoke the `about()` function. By default this extension will
     * return an associative array display the basic meta data about the given extension.
     * If the `$rawXML` parameter is passed true, and the extension has a `extension.meta.xml`
     * file, this function will return `DOMDocument` of the file.
     *
     * @param string $name
     *  The name of the Extension Class minus the extension prefix.
     * @param boolean $rawXML
     *  If passed as true, and is available, this function will return the
     *  DOMDocument of representation of the given extension's `extension.meta.xml`
     *  file. If the file is not available, the extension will return the normal
     *  `about()` results. By default this is false.
     * @throws Exception
     * @throws SymphonyException
     * @return array
     *  An associative array describing this extension
     */
    public static function about($name, $rawXML = false)
    {
        // See if the extension has the new meta format
        if (file_exists(self::__getClassPath($name) . '/extension.meta.xml')) {
            try {
                $meta = new DOMDocument;
                $meta->load(self::__getClassPath($name) . '/extension.meta.xml');
                $xpath = new DOMXPath($meta);
                $rootNamespace = $meta->lookupNamespaceUri($meta->namespaceURI);

                if (is_null($rootNamespace)) {
                    throw new Exception(__('Missing default namespace definition.'));
                } else {
                    $xpath->registerNamespace('ext', $rootNamespace);
                }
            } catch (Exception $ex) {
                Symphony::Engine()->throwCustomError(
                    __('The %1$s file for the %2$s extension is not valid XML: %3$s', array(
                        '<code>extension.meta.xml</code>',
                        '<code>' . $name . '</code>',
                        '<br /><code>' . $ex->getMessage() . '</code>'
                    ))
                );
            }

            // Load <extension>
            $extension = $xpath->query('/ext:extension')->item(0);

            // Check to see that the extension is named correctly, if it is
            // not, then return nothing
            if (self::__getClassName($name) !== self::__getClassName($xpath->evaluate('string(@id)', $extension))) {
                return array();
            }

            // If `$rawXML` is set, just return our DOMDocument instance
            if ($rawXML) {
                return $meta;
            }

            $about = array(
                'name' => $xpath->evaluate('string(ext:name)', $extension),
                'handle' => $name,
                'github' => $xpath->evaluate('string(ext:repo)', $extension),
                'discuss' => $xpath->evaluate('string(ext:url[@type="discuss"])', $extension),
                'homepage' => $xpath->evaluate('string(ext:url[@type="homepage"])', $extension),
                'wiki' => $xpath->evaluate('string(ext:url[@type="wiki"])', $extension),
                'issues' => $xpath->evaluate('string(ext:url[@type="issues"])', $extension),
                'status' => array()
            );

            // find the latest <release> (largest version number)
            $latest_release_version = '0.0.0';
            foreach ($xpath->query('//ext:release', $extension) as $release) {
                $version = $xpath->evaluate('string(@version)', $release);
                $vc = new VersionComparator($version);

                if ($vc->greaterThan($latest_release_version)) {
                    $latest_release_version = $version;
                }
            }

            // Load the latest <release> information
            if ($release = $xpath->query("//ext:release[@version='$latest_release_version']", $extension)->item(0)) {
                $about += array(
                    'version' => $xpath->evaluate('string(@version)', $release),
                    'release-date' => $xpath->evaluate('string(@date)', $release)
                );

                // If it exists, load in the 'min/max' version data for this release
                $required_min_version = $xpath->evaluate('string(@min)', $release);
                $required_max_version = $xpath->evaluate('string(@max)', $release);
                $symphonyVc = new VersionComparator(Symphony::Configuration()->get('version', 'symphony'));

                // Min version
                if (!empty($required_min_version) && $symphonyVc->lessThan($required_min_version)) {
                    $about['status'][] = Extension::EXTENSION_NOT_COMPATIBLE;
                    $about['required_version'] = $required_min_version;

                // Max version
                } elseif (!empty($required_max_version) && $symphonyVc->greaterThan($required_max_version)) {
                    $about['status'][] = Extension::EXTENSION_NOT_COMPATIBLE;
                    $about['required_version'] = $required_max_version;
                }
            }

            // Add the <author> information
            foreach ($xpath->query('//ext:author', $extension) as $author) {
                $a = array(
                    'name' => $xpath->evaluate('string(ext:name)', $author),
                    'website' => $xpath->evaluate('string(ext:website)', $author),
                    'github' => $xpath->evaluate('string(ext:name/@github)', $author),
                    'email' => $xpath->evaluate('string(ext:email)', $author)
                );

                $about['author'][] = array_filter($a);
            }

            $about['status'] = array_merge($about['status'], self::fetchStatus($about));
            return $about;
        } else {
            Symphony::Log()->pushToLog(sprintf('%s does not have an extension.meta.xml file', $name), E_DEPRECATED, true);

            return array();
        }
    }

    /**
     * Creates an instance of a given class and returns it
     *
     * @param string $name
     *  The name of the Extension Class minus the extension prefix.
     * @throws Exception
     * @throws SymphonyException
     * @return Extension
     */
    public static function create($name)
    {
        if (!isset(self::$_pool[$name])) {
            $classname = self::__getClassName($name);
            $path = self::__getDriverPath($name);

            if (!is_file($path)) {
                $errMsg = __('Could not find extension %s at location %s.', array(
                    '<code>' . $name . '</code>',
                    '<code>' . str_replace(DOCROOT . '/', '', $path) . '</code>'
                ));
                try {
                    Symphony::Engine()->throwCustomError(
                        $errMsg,
                        __('Symphony Extension Missing Error'),
                        Page::HTTP_STATUS_ERROR,
                        'missing_extension',
                        array(
                            'name' => $name,
                            'path' => $path
                        )
                    );
                } catch (Exception $ex) {
                    throw new Exception($errMsg, 0, $ex);
                }
            }

            // Load optional auto-loader
            $autoLoader = basename($path) . '/vendor/autoload.php';
            if (file_exists($autoLoader)) {
                require_once($autoLoader);
            }

            // Load missing file if class does not exists
            if (!class_exists($classname)) {
                require_once($path);
            }

            // Create the extension object
            self::$_pool[$name] = new $classname(array());
        }

        return self::$_pool[$name];
    }

    /**
     * A utility function that is used by the ExtensionManager to ensure
     * stray delegates are not in `tbl_extensions_delegates`. It is called when
     * a new Delegate is added or removed.
     */
    public static function cleanupDatabase()
    {
        // Grab any extensions sitting in the database
        $rows = Symphony::Database()
            ->select(['name', 'status'])
            ->from('tbl_extensions')
            ->execute()
            ->rows();

        // Iterate over each row
        if (!empty($rows)) {
            foreach ($rows as $r) {
                $name = $r['name'];
                $status = $r['status'];

                // Grab the install location
                $path = self::__getClassPath($name);
                $existing_id = self::fetchExtensionID($name);

                $removeDelegatesSQL = Symphony::Database()
                    ->delete('tbl_extensions_delegates')
                    ->where(['extension_id' => $existing_id]);

                // If it doesn't exist, remove the DB rows
                if (!@is_dir($path)) {
                    $removeDelegatesSQL->execute();
                    Symphony::Database()
                        ->delete('tbl_extensions')
                        ->wehere(['id' => $existing_id])
                        ->limit(1)
                        ->execute();
                } elseif ($status == 'disabled') {
                    $removeDelegatesSQL->execute();
                }
            }
        }
    }

    /**
     * Factory method that creates a new ExtensionQuery.
     *
     * @since Symphony 3.0.0
     * @param array $projection
     *  The projection to select.
     *  If no projection gets added, it defaults to `DatabaseQuery::getDefaultProjection()`.
     * @return ExtensionQuery
     */
    public function select(array $projection = [])
    {
        return new ExtensionQuery(Symphony::Database(), $projection);
    }
}
