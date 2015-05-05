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
 * Represents an acceptable value.
 *
 * @package Aura.Accept
 *
 */
abstract class AbstractValue
{
    /**
     *
     * The acceptable value, not including any parameters.
     *
     * @var string
     *
     */
    protected $value;

    /**
     *
     * The quality parameter.
     *
     * @var float
     *
     */
    protected $quality = 1.0;

    /**
     *
     * Parameters additional to the acceptable value.
     *
     * @var array
     *
     */
    protected $parameters = array();

    /**
     *
     * Constructor.
     *
     * @param string $value The acceptable value, not including any parameters.
     *
     * @param float $quality The quality parameter.
     *
     * @param array $parameters Other parameters additional to the value.
     *
     */
    public function __construct(
        $value,
        $quality,
        array $parameters
    ) {
        $this->value = $value;
        $this->quality = $quality;
        $this->parameters = $parameters;
        $this->init();
    }

    /**
     *
     * Finishes construction of the value object.
     *
     * @return null
     *
     */
    protected function init()
    {
    }

    /**
     *
     * Match against the parameters of an available value.
     *
     * @param AbstractValue $avail The available value.
     *
     * @return bool True on a match, false if not.
     *
     */
    protected function matchParameters(AbstractValue $avail)
    {
        foreach ($avail->getParameters() as $label => $value) {
            if ($this->parameters[$label] != $value) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * Is the acceptable value a wildcard?
     *
     * @return bool
     *
     */
    public function isWildcard()
    {
        return $this->value == '*';
    }

    /**
     *
     * Returns the acceptable value.
     *
     * @return string
     *
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     *
     * Returns the quality level.
     *
     * @return float
     *
     */
    public function getQuality()
    {
        return (float) $this->quality;
    }

    /**
     *
     * Returns the additional parameters.
     *
     * @return array
     *
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
