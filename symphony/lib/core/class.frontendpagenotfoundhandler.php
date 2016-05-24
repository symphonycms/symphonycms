<?php

    /**
     * @package core
     */

    /**
     * The `FrontendPageNotFoundExceptionHandler` attempts to find a Symphony
     * page that has been given the '404' page type to render the SymphonyErrorPage
     * error, instead of using the Symphony default.
     */
    class FrontendPageNotFoundExceptionHandler extends SymphonyErrorPageHandler
    {
        /**
         * The render function will take a `FrontendPageNotFoundException` Exception and
         * output a HTML page. This function first checks to see if their is a page in Symphony
         * that has been given the '404' page type, otherwise it will just use the default
         * Symphony error page template to output the exception
         *
         * @param Exception $e
         *  The Exception object
         * @throws FrontendPageNotFoundException
         * @throws SymphonyErrorPage
         * @return string
         *  An HTML string
         */
        public static function render(Exception $e)
        {
            $page = PageManager::fetchPageByType('404');
            $previous_exception = Frontend::instance()->getException();

            // No 404 detected, throw default Symphony error page
            if (is_null($page['id'])) {
                parent::render(new SymphonyErrorPage(
                    $e->getMessage(),
                    __('Page Not Found'),
                    'generic',
                    array(),
                    Page::HTTP_STATUS_NOT_FOUND
                ));

                // Recursive 404
            } elseif (isset($previous_exception)) {
                parent::render(new SymphonyErrorPage(
                    __('This error occurred whilst attempting to resolve the 404 page for the original request.') . ' ' . $e->getMessage(),
                    __('Page Not Found'),
                    'generic',
                    array(),
                    Page::HTTP_STATUS_NOT_FOUND
                ));

                // Handle 404 page
            } else {
                $url = '/' . PageManager::resolvePagePath($page['id']) . '/';

                Frontend::instance()->setException($e);
                $output = Frontend::instance()->display($url);
                echo $output;

                exit;
            }
        }
    }
