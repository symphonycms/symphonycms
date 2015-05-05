<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Accept\Language;

use Aura\Accept\AbstractValue;

/**
 *
 * Represents an acceptable language value.
 *
 * @package Aura.Accept
 *
 */
class LanguageValue extends AbstractValue
{
    /**
     *
     * The language type.
     *
     * @var string
     *
     */
    protected $type = '*';

    /**
     *
     * The language subtype, if any.
     *
     * @var string
     *
     */
    protected $subtype;

    /**
     *
     * Finishes construction of the value object.
     *
     * @return null
     *
     */
    protected function init()
    {
        list($this->type, $this->subtype) = array_pad(explode('-', $this->value), 2, false);
    }

    /**
     *
     * Returns the language type.
     *
     * @return string
     *
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     *
     * Returns the language subtype.
     *
     * @return string
     *
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     *
     * Checks if an available language value matches this acceptable value.
     *
     * @param Language $avail An available language value.
     *
     * @return True on a match, false if not.
     *
     */
    public function match(LanguageValue $avail)
    {
        if ($avail->getValue() == '*') {
            return true;
        }

        // is it a full match?
        if (strtolower($this->value) == strtolower($avail->getValue())) {
            return $this->matchParameters($avail);
        }

        // is it a type-without-subtype match?
        return ! $this->subtype
            && strtolower($this->type) == strtolower($avail->getType())
            && $this->matchParameters($avail);
    }
}
