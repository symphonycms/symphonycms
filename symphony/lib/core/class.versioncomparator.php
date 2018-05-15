<?php

/**
 * @package core
 */

use \Composer\Semver\Comparator;

/**
 * The VersionComparator provides functions to compare semver compatible versions.
 * It uses \Composer\Semver\Comparator under the hood but translate the `.x`
 * notation used in Symphony for semver.
 * @since Symphony 3.0.0
 */
class VersionComparator
{
    /**
     * The base version used to compare
     *
     * @var string
     */
    private $base;

    /**
     * Creates a new version comparator object, based on $version.
     *
     * @param string $version
     */
    public function __construct($version)
    {
        General::ensureType([
            'version' => ['var' => $version, 'type' => 'string'],
        ]);
        $this->base = $version;
    }

    /**
     * Formats the $version to make lessThan and greaterThan work with how
     * Symphony handles the .x notation.
     *
     * @param string $version
     * @return string
     */
    public function fixVersion($version)
    {
        $version = str_replace('.x', '.999999999', $version);
        return $version;
    }

    /**
     * Checks if base version is less than $version.
     *
     * @param string $version
     * @return boolean
     *  true if base is less than $version, false otherwise.
     */
    public function lessThan($version)
    {
        return Comparator::lessThan($this->base, $this->fixVersion($version));
    }

    /**
     * Checks if base version is greater than $version.
     *
     * @param string $version
     * @return boolean
     *  true if base is greater than $version, false otherwise.
     */
    public function greaterThan($version)
    {
        return Comparator::greaterThan($this->base, $this->fixVersion($version));
    }

    /**
     * Compares base version with $version.
     *
     * @param string $version
     * @return int
     *  If both version are equals, it returns 0.
     *  If not, it returns -1 if base is less than $version.
     *  Returns 1 otherwise.
     */
    public function compareTo($version)
    {
        $version = $this->fixVersion($version);
        if ($this->base === $version) {
            return 0;
        }
        return $this->lessThan($version) ? -1 : 1;
    }

    /**
     * Static version of VersionComparator::compareTo().
     * Usable the same way PHP's version_compare works.
     *
     * @param string $a
     *  The base version
     * @param string $b
     *  The comparable version
     * @return int
     *  If both version are equals, it returns 0.
     *  If not, it returns -1 if base is less than $version.
     *  Returns 1 otherwise.
     */
    public static function compare($a, $b)
    {
        return (new VersionComparator($a))->compareTo($b);
    }
}
