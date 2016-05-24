<?php

    /**
     * @package core
     */

    /**
     * The `SymphonyErrorPageHandler` extends the `GenericExceptionHandler`
     * to allow the template for the exception to be provided from the `TEMPLATES`
     * directory
     */
    class SymphonyErrorPageHandler extends GenericExceptionHandler
    {
        /**
         * The render function will take a `SymphonyErrorPage` exception and
         * output a HTML page. This function first checks to see if their is a custom
         * template for this exception otherwise it reverts to using the default
         * `usererror.generic.php`
         *
         * @param Exception $e
         *  The Exception object
         * @return string
         *  An HTML string
         */
        public static function render(Exception $e)
        {
            if ($e->getTemplate() === false) {
                Page::renderStatusCode($e->getHttpStatusCode());

                if (isset($e->getAdditional()->header)) {
                    header($e->getAdditional()->header);
                }

                echo '<h1>Symphony Fatal Error</h1><p>' . $e->getMessage() . '</p>';
                exit;
            }

            include $e->getTemplate();
        }
    }
