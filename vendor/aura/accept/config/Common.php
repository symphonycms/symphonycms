<?php
namespace Aura\Accept\_Config;

use Aura\Di\Config;
use Aura\Di\Container;

class Common extends Config
{
    public function define(Container $di)
    {
        /**
         * Aura\Accept\Accept
         */
        $di->params['Aura\Accept\Accept'] = array(
            'charset'  => $di->lazyNew('Aura\Accept\Charset\CharsetNegotiator'),
            'encoding' => $di->lazyNew('Aura\Accept\Encoding\EncodingNegotiator'),
            'language' => $di->lazyNew('Aura\Accept\Language\LanguageNegotiator'),
            'media'    => $di->lazyNew('Aura\Accept\Media\MediaNegotiator'),
        );

        /**
         * Aura\Accept\AbstractNegotiator
         */
        $di->params['Aura\Accept\AbstractNegotiator'] = array(
            'value_factory' => $di->lazyNew('Aura\Accept\ValueFactory'),
            'server' => $_SERVER,
        );
    }
}
