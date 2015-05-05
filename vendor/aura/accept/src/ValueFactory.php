<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Accept;

/**
 *
 * A factory to create value objects.
 *
 * @package Aura.Accept
 *
 */
class ValueFactory
{
    /**
     *
     * A map of value types to value classes.
     *
     * @var array
     *
     */
    protected $map = array(
        'charset' => 'Aura\Accept\Charset\CharsetValue',
        'encoding' => 'Aura\Accept\Encoding\EncodingValue',
        'language' => 'Aura\Accept\Language\LanguageValue',
        'media' => 'Aura\Accept\Media\MediaValue',
    );

    /**
     *
     * Returns a new value object instance.
     *
     * @param string $type The value type.
     *
     * @param string $value The acceptable value.
     *
     * @param float $quality The quality parameter.
     *
     * @param array $parameters Additional parameters.
     *
     * @return AbstractValue
     *
     */
    public function newInstance($type, $value, $quality, array $parameters)
    {
        $class = $this->map[$type];
        return new $class($value, $quality, $parameters);
    }
}
