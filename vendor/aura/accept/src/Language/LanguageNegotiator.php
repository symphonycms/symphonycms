<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Accept\Language;

use Aura\Accept\AbstractNegotiator;

/**
 *
 * Represents a collection of `Accept-Language` header values, sorted in
 * quality order.
 *
 * @package Aura.Accept
 *
 */
class LanguageNegotiator extends AbstractNegotiator
{
    /**
     *
     * The $_SERVER key to use when populating acceptable values.
     *
     * @var string
     *
     */
    protected $server_key = 'HTTP_ACCEPT_LANGUAGE';

    /**
     *
     * The type of value object to create using the ValueFactory.
     *
     * @var string
     *
     */
    protected $value_type = 'language';
}
