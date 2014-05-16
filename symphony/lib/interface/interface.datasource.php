<?php

/**
 * @package interface
 */
/**
 * This interface describes the minimum a new Datasource type needs to
 * provide to be able to be used by Symphony
 *
 * @since Symphony 2.3
 */
interface iDatasource
{
    /**
     * Returns the human readable name of this Datasource type. This is
     * displayed in the datasource selection options.
     *
     * @return string
     */
    public static function getName();

    /**
     * Returns the absolute path to the template that this template will
     * use to save instances of this datasource in the `DATASOURCES` folder.
     *
     * @return string
     */
    public static function getTemplate();

    /**
     * This function return the source of this datasource. It's an artefact
     * of old core objects and for the moment it should return the same
     * value as `getClass`.
     *
     * @return string
     */
    public function getSource();

    /**
     * This function returns all the settings of the current Datasource
     * instance.
     *
     * @return array
     *  An associative array of settings for this datasource where the
     *  key is `getClass` and the value is an associative array of settings,
     *  key being the setting name, value being, the value
     */
    public function settings();

    /**
     * This function is invoked by the Datasource Editor and allows this
     * Datasource to provide HTML so that it can be created or edited.
     * It is expected that this function will also handle the display
     * of error messages.
     *
     * @see settings()
     * @param XMLElement $wrapper
     *  An XMLElement for the HTML to be appended to. This is usually
     *  `AdministrationPage->Form`.
     * @param array $errors
     *  If there are any errors, this variable will be an associative
     *  array, key being the setting handle.
     * @param array $settings
     *  An associative array of settings. This may be null on create, but
     *  will be populated with the Datasource's settings on edit using
     *  `settings()`.
     * @param string $handle
     *  If the datasource already exists (so it's being edited), the handle
     *  of the datasource will be passed to this function.
     * @return
     */
    public static function buildEditor(XMLElement $wrapper, array &$errors = array(), array $settings = null, $handle = null);

    /**
     * Given an array of settings, validate them, adding any errors
     * to the `$errors` variable which is passed by reference. `$errors`
     * should be formatted as an associative array
     *
     * @param array $settings
     *  An associative array of settings
     * @param array $errors
     *  Passed as an empty array, can be populated with any validation errors
     * @return boolean
     *  True if the datasource is valid, false otherwise.
     *  If false it is expected that `$errors` are populated.
    */
    public static function validate(array &$settings, array &$errors);

    /**
     * Given the settings and any existing datasource parameters, return
     * the contents of this datasource so that can be saved to the file system.
     *
     * @param array $fields
     *  An associative array of settings for this datasource, where the key
     *  is the name of the setting. These are user defined through the Datasource
     *  Editor.
     * @param array $parameters
     *  An associative array of parameters for this datasource, where the key
     *  is the name of the parameter.
     * @param string $template
     *  The template file, which has already been altered by Symphony to remove
     *  any named tokens (ie. `<!-- CLASS NAME -->`).
     * @return string
     *  The completed template, ready to be saved.
     */
    public static function prepare(array $fields, array $parameters, $template);

    /**
     * This function is responsible for returning an `XMLElement` so that the
     * `FrontendPage` class can add to a page's XML. It is executed and passed
     * the current `$param_pool` array.
     *
     * @param array $param_pool
     *  An associative array of parameters that have been evaluated prior to
     *  this Datasource's execution.
     * @return XMLElement
     *  This Datasource should return an `XMLElement` object.
     */
    public function execute(array &$param_pool = NULL);
}
