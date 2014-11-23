<?php
/**
 * @package toolkit
 */
/**
 * The EventManager class is responsible for managing all Event objects
 * in Symphony. Event's are stored on the file system either in the
 * /workspace/events folder or provided by an extension in an /events folder.
 * Events run from the Frontend usually to add new entries to the system, but
 * they are not limited to that facet.
 */

class EventManager implements FileResource
{
    /**
     * Given the filename of an Event return it's handle. This will remove
     * the Symphony convention of `event.*.php`
     *
     * @param string $filename
     *  The filename of the Event
     * @return string
     */
    public static function __getHandleFromFilename($filename)
    {
        return preg_replace(array('/^event./i', '/.php$/i'), '', $filename);
    }

    /**
     * Given a name, returns the full class name of an Event. Events
     * use an 'event' prefix.
     *
     * @param string $handle
     *  The Event handle
     * @return string
     */
    public static function __getClassName($handle)
    {
        return 'event' . $handle;
    }

    /**
     * Finds an Event by name by searching the events folder in the workspace
     * and in all installed extension folders and returns the path to it's folder.
     *
     * @param string $handle
     *  The handle of the Event free from any Symphony conventions
     *  such as `event.*.php`
     * @return string|boolean
     *  If the Event is found, the function returns the path it's folder, otherwise false.
     */
    public static function __getClassPath($handle)
    {
        if (is_file(EVENTS . "/event.$handle.php")) {
            return EVENTS;
        } else {
            $extensions = Symphony::ExtensionManager()->listInstalledHandles();

            if (is_array($extensions) && !empty($extensions)) {
                foreach ($extensions as $e) {
                    if (is_file(EXTENSIONS . "/$e/events/event.$handle.php")) {
                        return EXTENSIONS . "/$e/events";
                    }
                }
            }
        }

        return false;
    }

    /**
     * Given a name, return the path to the Event class
     *
     * @see toolkit.EventManager#__getClassPath()
     * @param string $handle
     *  The handle of the Event free from any Symphony conventions
     *  such as event.*.php
     * @return string
     */
    public static function __getDriverPath($handle)
    {
        return self::__getClassPath($handle) . "/event.$handle.php";
    }

    /**
     * Finds all available Events by searching the events folder in the workspace
     * and in all installed extension folders. Returns an associative array of Events.
     *
     * @see toolkit.Manager#about()
     * @return array
     *  Associative array of Events with the key being the handle of the Event
     *  and the value being the Event's `about()` information.
     */
    public static function listAll()
    {
        $result = array();
        $structure = General::listStructure(EVENTS, '/event.[\\w-]+.php/', false, 'ASC', EVENTS);

        if (is_array($structure['filelist']) && !empty($structure['filelist'])) {
            foreach ($structure['filelist'] as $f) {
                $f = self::__getHandleFromFilename($f);

                if ($about = self::about($f)) {
                    $classname = self::__getClassName($f);
                    $env = array();
                    $class = new $classname($env);

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
                if (!is_dir(EXTENSIONS . "/$e/events")) {
                    continue;
                }

                $tmp = General::listStructure(EXTENSIONS . "/$e/events", '/event.[\\w-]+.php/', false, 'ASC', EXTENSIONS . "/$e/events");

                if (is_array($tmp['filelist']) && !empty($tmp['filelist'])) {
                    foreach ($tmp['filelist'] as $f) {
                        $f = self::__getHandleFromFilename($f);

                        if ($about = self::about($f)) {
                            $about['can_parse'] = false;
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

        require_once $path;

        $handle = self::__getHandleFromFilename(basename($path));
        $env = array();
        $class = new $classname($env);

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
     *  The handle of the Event to create
     * @param array $env
     *  The environment variables from the Frontend class which includes
     *  any params set by Symphony or Datasources or by other Events
     * @throws Exception
     * @return Event
     */
    public static function create($handle, array $env = null)
    {
        $classname = self::__getClassName($handle);
        $path = self::__getDriverPath($handle);

        if (!is_file($path)) {
            throw new Exception(
                __('Could not find Event %s.', array('<code>' . $handle . '</code>'))
                . ' ' . __('If it was provided by an Extension, ensure that it is installed, and enabled.')
            );
        }

        if (!class_exists($classname)) {
            require_once $path;
        }

        return new $classname($env);
    }
}
