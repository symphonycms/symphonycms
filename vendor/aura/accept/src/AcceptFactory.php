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
 * A factory to create an Accept objects.
 *
 * @package Aura.Accept
 *
 */
class AcceptFactory
{
    /**
     *
     * A copy of $_SERVER.
     *
     * @var array
     *
     */
    protected $server = array();

    /**
     *
     * A map of file .extensions to media types.
     *
     * @var array
     *
     */
    protected $types = array();

    /**
     *
     * Contains the ValueFactory.
     *
     * @var ValueFactory
     *
     */
    protected $value_factory;

    /**
     *
     * Constructor.
     *
     * @param array $server A copy of $_SERVER.
     *
     * @param array $types A map of file .extensions to media types.
     *
     */
    public function __construct(array $server = array(), array $types = array())
    {
        $this->server = $server;
        $this->types = $types;
        $this->value_factory = new ValueFactory;
    }

    /**
     *
     * Returns an Accept object with all negotiators.
     *
     * @return Accept
     *
     */
    public function newInstance()
    {
        return new Accept(
            $this->newCharsetNegotiator(),
            $this->newEncodingNegotiator(),
            $this->newLanguageNegotiator(),
            $this->newMediaNegotiator()
        );
    }

    /**
     *
     * Returns a charset negotiator.
     *
     * @return Charset\CharsetNegotiator
     *
     */
    public function newCharsetNegotiator()
    {
        return new Charset\CharsetNegotiator($this->value_factory, $this->server);
    }

    /**
     *
     * Returns an encoding negotiator.
     *
     * @return Encoding\EncodingNegotiator
     *
     */
    public function newEncodingNegotiator()
    {
        return new Encoding\EncodingNegotiator($this->value_factory, $this->server);
    }

    /**
     *
     * Returns a language negotiator.
     *
     * @return Language\LanguageNegotiator
     *
     */
    public function newLanguageNegotiator()
    {
        return new Language\LanguageNegotiator($this->value_factory, $this->server);
    }

    /**
     *
     * Returns a media type negotiator.
     *
     * @return Media\MediaNegotiator
     *
     */
    public function newMediaNegotiator()
    {
        return new Media\MediaNegotiator($this->value_factory, $this->server, $this->types);
    }
}
