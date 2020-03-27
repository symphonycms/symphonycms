<?php

/**
 * @package core
 */
/**
 * The `FrontendPageNotFoundExceptionRenderer` attempts to find a Symphony
 * page that has been given the '404' page type to render the Exception,
 * instead of using the Symphony default.
 */
class FrontendPageNotFoundExceptionRenderer extends ExceptionRenderer
{
    /**
     * The renderHtml function will take a `FrontendPageNotFoundException` Exception and
     * output a HTML page. This function first checks to see if their is a page in Symphony
     * that has been given the '404' page type, otherwise it will just use the default
     * Symphony error page template to output the exception
     *
     * @param Throwable $e
     *  The Throwable object
     * @throws FrontendPageNotFoundException
     * @throws SymphonyException
     * @return string
     *  An HTML string
     */
    protected static function renderHtml($e)
    {
        $page = PageManager::fetchPageByType('404');
        $previous_exception = Frontend::instance()->getException();

        // No 404 detected, throw default Symphony error page
        if (is_null($page['id'])) {
            static::sendHeaders($e);
            $e = new SymphonyException(
                $e->getMessage(),
                __('Page Not Found'),
                'generic',
                array(),
                Page::HTTP_STATUS_NOT_FOUND
            );
            include $e->getTemplate();

        // Recursive 404
        } elseif (isset($previous_exception)) {
            static::sendHeaders($e);
            $e = new SymphonyException(
                __('This error occurred whilst attempting to resolve the 404 page for the original request.') . ' ' . $e->getMessage(),
                __('Page Not Found'),
                'generic',
                array(),
                Page::HTTP_STATUS_NOT_FOUND
            );
            include $e->getTemplate();

        // Handle 404 page
        } else {
            $url = '/' . PageManager::resolvePagePath($page['id']) . '/';

            Frontend::instance()->setException($e);
            $output = Frontend::instance()->display($url);
            echo $output;
        }
        exit;
    }
}

/**
 * Compat Layer
 *
 * @deprecated @since Symphony 3.0.0
 *  Use FrontendPageNotFoundExceptionRenderer instead
 */
class FrontendPageNotFoundExceptionHandler extends FrontendPageNotFoundExceptionRenderer
{
    public static function render($e)
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog(
                'FrontendPageNotFoundExceptionHandler::render()',
                'FrontendPageNotFoundExceptionRenderer::render()'
            );
        }
        parent::render($e);
    }
}
