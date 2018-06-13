<?php

/**
 * @package core
 */
/**
 * The `SymphonyExceptionRenderer` extends the `ExceptionRenderer`
 * to allow the template for the exception to be provided from the `TEMPLATES`
 * directory.
 */
class SymphonyExceptionRenderer extends ExceptionRenderer
{
    /**
     * The renderHtml function will take a `SymphonyException` exception and
     * output a HTML page. This function first checks to see if the `ExceptionHandler`
     * is enabled and pass control to it if not. After that, the method checks if there is a custom
     * template for this exception otherwise it reverts to using the default
     * `usererror.generic.php`. If the template is not found, it will call
     * `ExceptionRenderer::renderHtml()`.
     *
     * @param Throwable $e
     *  The Throwable object
     * @return string
     *  An HTML string
     */
    protected static function renderHtml($e)
    {
        // Validate the type, resolve to a 404 if not valid
        if (!ExceptionHandler::isValidThrowable($e)) {
            $e = new FrontendPageNotFoundException();
        }

        if ($e->getTemplateName() !== 'generic' && !ExceptionHandler::$enabled) {
            return parent::renderHtml($e);
        } elseif (!$e->getTemplate()) {
            return parent::renderHtml($e);
        }

        self::sendHeaders($e);
        include $e->getTemplate();
        exit; // safety
    }
}
