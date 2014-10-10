<?php

/**
 * @package interface
 */
/**
 * The `FileResource` interface defines the minimum functions required
 * by managers that manage Symphony's file based objects. This interface
 * is used by the `DatasourceManager`, `EventManager`, `EmailGatewayManager`,
 * `ExtensionManager`, `FieldManager` and `TextFormatterManager`.
 *
 * @since Symphony 2.3
 */
interface FileResource
{
    /**
     * Given a filename, return the handle. This will remove
     * any Symphony conventions such as `field.*.php`
     *
     * @param string $filename
     * @return string|boolean
     */
    public static function __getHandleFromFilename($filename);

    /**
     * Given a name, return the class name of that object. Symphony objects
     * often have conventions tied to an objects class name that prefix the
     * class with the type of the object. eg. field{Class}, formatter{Class}
     *
     * @param string $name
     * @return string|boolean
     */
    public static function __getClassName($name);

    /**
     * Given a name, return the path to the class of that object
     *
     * @param string $name
     * @return string|boolean
     */
    public static function __getClassPath($name);

    /**
     * Given a name, return the path to the driver of that object
     *
     * @param string $name
     * @return string|boolean
     */
    public static function __getDriverPath($name);

    /**
     * Returns an array of all the objects that this manager is responsible for.
     * This function is only use on the file based Managers in Symphony
     * such `DatasourceManager`, `EventManager`, `EmailGatewayManager`,
     * `ExtensionManager`, `FieldManager` and `TextformatterManager`.
     *
     * @return array
     */
    public static function listAll();

    /**
     * The about function returns information about a particular object
     * in this manager's pool. It is limited for use on objects provided by
     * Extensions.
     * The function uses the `getClassName()`, `getDriverPath()` and
     * `getHandleFromFilename()` functions to find the object of the Manager's
     * type on the filesystem.
     *
     * @param string $name
     *  The name of the object that has an `about()` function. This should be
     *  lowercase and free from any Symphony conventions. eg. `author`,
     *  not `field.author.php`.
     * @return array|boolean
     *  False is object doesn't exist or an associative array of information
     */
    public static function about($name);

    /**
     * Creates a new instance of an object by name and returns it by reference.
     *
     * @param string name
     *  The name of the Object to be created. Can be used in conjunction
     *  with the auto discovery methods to find a class.
     * @return object
     */
    public static function create($name);
}
