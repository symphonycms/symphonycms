<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Accept\Encoding;

use Aura\Accept\AbstractNegotiator;

/**
 *
 * Represents a collection of `Accept-Encoding` header values, sorted in
 * quality order.
 *
 * @package Aura.Accept
 *
 */
class EncodingNegotiator extends AbstractNegotiator
{
    /**
     *
     * The $_SERVER key to use when populating acceptable values.
     *
     * @var string
     *
     */
    protected $server_key = 'HTTP_ACCEPT_ENCODING';

    /**
     *
     * The type of value object to create using the ValueFactory.
     *
     * @var string
     *
     */
    protected $value_type = 'encoding';
}
