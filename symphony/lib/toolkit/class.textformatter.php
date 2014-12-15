<?php
/**
 * @package toolkit
 */
/**
 * The abstract TextFormatter classes defines two methods
 * that must be implemented by any Symphony text formatter.
 */
abstract class TextFormatter
{
    /**
     * The about method allows a text formatter to provide
     * information about itself as an associative array. eg.
     * `array(
     *      'name' => 'Name of Formatter',
     *      'version' => '1.8',
     *      'release-date' => 'YYYY-MM-DD',
     *      'author' => array(
     *          'name' => 'Author Name',
     *          'website' => 'Author Website',
     *          'email' => 'Author Email'
     *      ),
     *      'description' => 'A description about this formatter'
     * )`
     * @return array
     *  An associative array describing the text formatter.
     */
    abstract public function about();

    /**
     * Given an input, apply the formatter and return the result
     *
     * @param string $string
     * @return string
     */
    abstract public function run($string);
}
