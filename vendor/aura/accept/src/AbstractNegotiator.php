<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Accept;

use ArrayIterator;
use IteratorAggregate;

/**
 *
 * Represents a collection of `Accept*` header values, sorted in quality order.
 *
 * @package Aura.Accept
 *
 */
abstract class AbstractNegotiator implements IteratorAggregate
{
    /**
     *
     * An array of objects representing acceptable values from the header.
     *
     * @var array
     *
     */
    protected $acceptable = array();

    /**
     *
     * The $_SERVER key to use when populating acceptable values.
     *
     * @var string
     *
     */
    protected $server_key;

    /**
     *
     * A factory to create value objects.
     *
     * @var ValueFactory
     *
     */
    protected $value_factory;

    /**
     *
     * The type of value object to create using the ValueFactory.
     *
     * @var string
     *
     */
    protected $value_type;

    /**
     *
     * Constructor.
     *
     * @param ValueFactory $value_factory A factory for value objects.
     *
     * @param array $server A copy of $_SERVER for finding acceptable values.
     *
     */
    public function __construct(
        ValueFactory $value_factory,
        array $server = array()
    ) {
        $this->value_factory = $value_factory;
        if (isset($server[$this->server_key])) {
            $this->set($server[$this->server_key]);
        }
    }

    /**
     *
     * Returns a value object by its sorted quality position.
     *
     * @param int $key The sorted position.
     *
     * @return Value\AbstractValue
     *
     */
    public function get($key = null)
    {
        if ($key === null) {
            return $this->acceptable;
        }
        return $this->acceptable[$key];
    }

    /**
     *
     * Sets the collection to one or more acceptable values, overwriting all
     * previous values.
     *
     * @param string|array $acceptable An `Accept*` string value; e.g.,
     * `text/plain;q=0.5,text/html,text/*;q=0.1`.
     *
     * @return null
     *
     */
    public function set($acceptable = null)
    {
        $this->acceptable = array();
        $this->add($acceptable);
    }

    /**
     *
     * Adds one or more acceptable values to this collection.
     *
     * @param string|array $acceptable One or more `Accept*` string values;
     * e.g., the string `'text/plain;q=0.5,text/html,text/*;q=0.1'` and
     * `array('text/plain;q=0.5','text/html','text/*;q=0.1')` are
     * equivalent.
     *
     * @return null
     *
     * @todo Allow this to take an array so we can parse-and-sort in one pass.
     *
     */
    public function add($acceptable = null)
    {
        foreach ((array) $acceptable as $string) {
            $this->parse($string);
        }

        $this->sort();
    }

    /**
     *
     * Parses an acceptable string value into the `$acceptable` property.
     *
     * @param string $string An `Accept*` string value; e.g.,
     * `text/plain;q=0.5,text/html,text/*;q=0.1`.
     *
     * @return array
     *
     */
    protected function parse($string)
    {
        $acceptable = explode(',', $string);

        foreach ($acceptable as $value) {
            $pairs = explode(';', $value);
            $value = $pairs[0];
            unset($pairs[0]);

            $parameters = array();
            foreach ($pairs as $pair) {
                $param = array();
                preg_match(
                    '/^(?P<name>.+?)=(?P<quoted>"|\')?(?P<value>.*?)(?:\k<quoted>)?$/',
                    $pair,
                    $param
                );
                $parameters[$param['name']] = $param['value'];
            }

            $quality = 1.0;
            if (isset($parameters['q'])) {
                $quality = $parameters['q'];
                unset($parameters['q']);
            }

            $this->acceptable[] = $this->value_factory->newInstance(
                $this->value_type,
                trim($value),
                (float) $quality,
                $parameters
            );
        }
    }

    /**
     *
     * Sorts the `$acceptable` values according to quality levels.
     *
     * This is an unusual sort. Normally we'd think a reverse-sort would
     * order the array by q values from 1 to 0, but the problem is that
     * an implicit 1.0 on more than one value means that those values will
     * be reverse from what the header specifies, which seems unexpected
     * when negotiating later.
     *
     */
    protected function sort()
    {
        // q-value buckets
        $bucket = array();

        // sort into q-value buckets
        foreach ($this->acceptable as $value) {
            $bucket[$value->getQuality()][] = $value;
        }

        // reverse-sort the buckets so that q=1 is first and q=0 is last,
        // but the values in the buckets stay in the original order.
        krsort($bucket);

        // flatten the buckets back into the acceptable array
        $this->acceptable = array();
        foreach ($bucket as $q => $acceptable) {
            foreach ($acceptable as $value) {
                $this->acceptable[] = $value;
            }
        }
    }

    /**
     *
     * IteratorInterface: returns the iterator for this object.
     *
     * @return ArrayIterator
     *
     */
    public function getIterator()
    {
        return new ArrayIterator($this->acceptable);
    }

    /**
     *
     * Negotiates between acceptable and available values.  On success, the
     * return value is a plain old PHP object with the matching negotiated
     * `$acceptable` and `$available` value objects; these are to be inspected
     * by the calling code.
     *
     * @param array $available Available values in preference order, if any.
     *
     * @return mixed A plain-old PHP object with negotiated `$acceptable` and
     * `$available` value objects on success, or false on failure.
     *
     */
    public function negotiate(array $available = null)
    {
        // if none available, no possible match
        if (! $available) {
            return false;
        }

        // convert to object
        $clone = clone $this;
        $clone->set($available);
        $available = $clone;

        // if nothing acceptable specified, use first available
        if (! $this->acceptable) {
            return (object) array(
                'acceptable' => false,
                'available' => $available->get(0),
            );
        }

        // loop through acceptable values
        foreach ($this->acceptable as $accept) {

            // if the acceptable quality is zero, skip it
            if ($accept->getQuality() == 0) {
                continue;
            }

            // if acceptable value is "anything" return the first available
            if ($accept->isWildcard()) {
                return (object) array(
                    'acceptable' => $accept,
                    'available' => $available->get(0),
                );
            }

            // if acceptable value is available, use it
            foreach ($available as $avail) {
                if ($accept->match($avail)) {
                    return (object) array(
                        'acceptable' => $accept,
                        'available' => $avail,
                    );
                }
            }
        }

        return false;
    }
}
