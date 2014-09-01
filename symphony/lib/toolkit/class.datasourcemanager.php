<?php
/**
 * @package toolkit
 */
/**
 * The DatasourceManager class is responsible for managing all Datasource objects
 * in Symphony. Datasources's are stored on the file system either in the
 * `WORKSPACE . /data-sources` folder or provided by an extension in `EXTENSIONS . /./data-sources` folder.
 * Datasources are run from the Frontend to commonly output Entries and parameters,
 * however there any different types of Datasource that query external sources for data.
 * Typically, a Datasource returns XML.
 */

class DatasourceManager implements FileResource
{
    /**
     * Given the filename of a Datasource, return its handle. This will remove
     * the Symphony convention of `data.*.php`
     *
     * @param string $filename
     *  The filename of the Datasource
     * @return string
     */
    public static function __getHandleFromFilename($filename)
    {
        return preg_replace(array('/^data./i', '/.php$/i'), '', $filename);
    }

    /**
     * Given a name, returns the full class name of an Datasources. Datasources
     * use a 'datasource' prefix.
     *
     * @param string $handle
     *  The Datasource handle
     * @return string
     */
    public static function __getClassName($handle)
    {
        return 'datasource' . $handle;
    }

    /**
     * Finds a Datasource by name by searching the data-sources folder in the
     * workspace and in all installed extension folders and returns the path
     * to it's folder.
     *
     * @param string $handle
     *  The handle of the Datasource free from any Symphony conventions
     *  such as `data.*.php`
     * @return string|boolean
     *  If the datasource is found, the function returns the path it's folder, otherwise false.
     */
    public static function __getClassPath($handle)
    {
        if (is_file(DATASOURCES . "/data.$handle.php")) {
            return DATASOURCES;
        } else {
            $extensions = Symphony::ExtensionManager()->listInstalledHandles();

            if (is_array($extensions) && !empty($extensions)) {
                foreach ($extensions as $e) {
                    if (is_file(EXTENSIONS . "/$e/data-sources/data.$handle.php")) {
                        return EXTENSIONS . "/$e/data-sources";
                    }
                }
            }
        }

        return false;
    }

    /**
     * Given a name, return the path to the Datasource class
     *
     * @see DatasourceManager::__getClassPath()
     * @param string $handle
     *  The handle of the Datasource free from any Symphony conventions
     *  such as `data.*.php`
     * @return string
     */
    public static function __getDriverPath($handle)
    {
        return self::__getClassPath($handle) . "/data.$handle.php";
    }

    /**
     * Finds all available Datasources by searching the data-sources folder in
     * the workspace and in all installed extension folders. Returns an
     * associative array of data-sources.
     *
     * @see toolkit.Manager#about()
     * @return array
     *  Associative array of Datasources with the key being the handle of the
     *  Datasource and the value being the Datasource's `about()` information.
     */
    public static function listAll()
    {
        $result = array();
        $structure = General::listStructure(DATASOURCES, '/data.[\\w-]+.php/', false, 'ASC', DATASOURCES);

        if (is_array($structure['filelist']) && !empty($structure['filelist'])) {
            foreach ($structure['filelist'] as $f) {
                $f = self::__getHandleFromFilename($f);

                if ($about = self::about($f)) {
                    $classname = self::__getClassName($f);
                    $env = array();
                    $class = new $classname($env, false);

                    $about['can_parse'] = method_exists($class, 'allowEditorToParse')
                        ? $class->allowEditorToParse()
                        : false;

                    $about['source'] = method_exists($class, 'getSource')
                        ? $class->getSource()
                        : null;

                    $result[$f] = $about;
                }
            }
        }

        $extensions = Symphony::ExtensionManager()->listInstalledHandles();

        if (is_array($extensions) && !empty($extensions)) {
            foreach ($extensions as $e) {
                if (!is_dir(EXTENSIONS . "/$e/data-sources")) {
                    continue;
                }

                $tmp = General::listStructure(EXTENSIONS . "/$e/data-sources", '/data.[\\w-]+.php/', false, 'ASC', EXTENSIONS . "/$e/data-sources");

                if (is_array($tmp['filelist']) && !empty($tmp['filelist'])) {
                    foreach ($tmp['filelist'] as $f) {
                        $f = self::__getHandleFromFilename($f);

                        if ($about = self::about($f)) {
                            $about['can_parse'] = false;
                            $about['source'] = null;
                            $result[$f] = $about;
                        }
                    }
                }
            }
        }

        ksort($result);
        return $result;
    }

    public static function about($name)
    {
        $classname = self::__getClassName($name);
        $path = self::__getDriverPath($name);

        if (!@file_exists($path)) {
            return false;
        }

        require_once($path);

        $handle = self::__getHandleFromFilename(basename($path));
        $env = array();
        $class = new $classname($env, false);

        try {
            $method = new ReflectionMethod($classname, 'about');
            $about = $method->invoke($class);
        } catch (ReflectionException $e) {
            $about = array();
        }

        return array_merge($about, array('handle' => $handle));
    }

    /**
     * Creates an instance of a given class and returns it.
     *
     * @param string $handle
     *  The handle of the Datasource to create
     * @param array $env
     *  The environment variables from the Frontend class which includes
     *  any params set by Symphony or Events or by other Datasources
     * @param boolean $process_params
     * @throws Exception
     * @return Datasource
     */
    public static function create($handle, array $env = null, $process_params = true)
    {
        $classname = self::__getClassName($handle);
        $path = self::__getDriverPath($handle);

        if (!is_file($path)) {
            throw new Exception(
                __('Could not find Data Source %s.', array('<code>' . $handle . '</code>'))
                . ' ' . __('If it was provided by an Extension, ensure that it is installed, and enabled.')
            );
        }

        if (!class_exists($classname)) {
            require_once $path;
        }

        return new $classname($env, $process_params);
    }

}
