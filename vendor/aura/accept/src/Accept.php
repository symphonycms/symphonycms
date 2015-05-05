<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Accept;

use Aura\Accept\Charset\CharsetNegotiator;
use Aura\Accept\Encoding\EncodingNegotiator;
use Aura\Accept\Language\LanguageNegotiator;
use Aura\Accept\Media\MediaNegotiator;

/**
 *
 * A collection of negotiator objects.
 *
 * @package Aura.Accept
 *
 */
class Accept
{
    /**
     *
     * The media-type negotiator.
     *
     * @var MediaNegotiator
     *
     */
    protected $media;

    /**
     *
     * The charset negotiator.
     *
     * @var CharsetNegotiator
     *
     */
    protected $charset;

    /**
     *
     * The encoding negotiator.
     *
     * @var EncodingNegotiator
     *
     */
    protected $encoding;

    /**
     *
     * The language negotiator.
     *
     * @var LanguageNegotiator
     *
     */
    protected $language;

	/**
	 *
	 * Constructor.
	 *
	 * @param CharsetNegotiator $charset A charset negotiator.
	 *
	 * @param EncodingNegotiator $encoding An encoding negotiator.
	 *
	 * @param LanguageNegotiator $language A language negotiator.
	 *
	 * @param MediaNegotiator $media A media-type negotiator.
	 *
	 */
    public function __construct(
        CharsetNegotiator $charset,
        EncodingNegotiator $encoding,
        LanguageNegotiator $language,
        MediaNegotiator $media
    ) {
        $this->charset  = $charset;
        $this->encoding = $encoding;
        $this->language = $language;
        $this->media    = $media;
    }

    /**
     *
     * Negotiate between acceptable and available charsets.
     *
     * @param array $available The list of available charsets, ordered by most
     * preferred to least preferred.
     *
     * @return Charset\CharsetValue
     *
     */
    public function negotiateCharset(array $available)
    {
        return $this->parseResult($this->charset->negotiate($available));
    }

    /**
     *
     * Negotiate between acceptable and available encodings.
     *
     * @param array $available The list of available encodings, ordered by most
     * preferred to least preferred.
     *
     * @return Encoding\EncodingValue|false
     *
     */
    public function negotiateEncoding(array $available)
    {
        return $this->parseResult($this->encoding->negotiate($available));
    }

    /**
     *
     * Negotiate between acceptable and available languages.
     *
     * @param array $available The list of available languages, ordered by most
     * preferred to least preferred.
     *
     * @return Language\LanguageValue|false
     *
     */
    public function negotiateLanguage(array $available)
    {
        return $this->parseResult($this->language->negotiate($available));
    }

    /**
     *
     * Negotiate between acceptable and available media types.
     *
     * @param array $available The list of available media types, ordered by
     * most preferred to least preferred.
     *
     * @return Media\MediaValue|false
     *
     */
    public function negotiateMedia(array $available)
    {
        return $this->parseResult($this->media->negotiate($available));
    }

    /**
     *
     * Given a negotiation result, return either the acceptable value or the
     * available value (or false if negotiation failed).
     *
     * Sometimes the acceptable value is a wildcard, or none was specified, so
     * we have to go with the available value.
     *
     * @param mixed $result A negotiation result.
     *
     * @return AbstractValue|false
     *
     */
    protected function parseResult($result)
    {
        if (! $result) {
            return false;
        }

        if ($result->acceptable && ! $result->acceptable->isWildcard()) {
            return $result->acceptable;
        }

        return $result->available;
    }
}
