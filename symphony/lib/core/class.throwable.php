<?php
/**
 * @package core
 */

if (class_exists('Throwable', false)) {
    return;
}

/**
 * Throwable is a new interface in PHP 7, from which both Errors and Exceptions
 * inherits. Since PHP 5 does not have this class, we fake it using the Exception
 * class, from which the PHP team derived the Throwable interface.
 * We made the class abstract to map more closely to the behavior of an interface.
 *
 * @link http://php.net/manual/en/class.throwable.php
 */
abstract class Throwable extends Exception
{
}
