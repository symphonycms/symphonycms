<?php

    /**
     * @package core
     */

    /**
     * The `DatabaseExceptionHandler` provides a render function to provide
     * customised output for database exceptions. It displays the exception
     * message as provided by the Database.
     */
    class DatabaseExceptionHandler extends GenericExceptionHandler
    {
        /**
         * The render function will take a `DatabaseException` and output a
         * HTML page.
         *
         * @param Exception $e
         *  The Exception object
         * @return string
         *  An HTML string
         */
        public static function render(Exception $e)
        {
            $trace = $queries = null;

            foreach ($e->getTrace() as $t) {
                $trace .= sprintf(
                    '<li><code><em>[%s:%d]</em></code></li><li><code>&#160;&#160;&#160;&#160;%s%s%s();</code></li>',
                    $t['file'],
                    $t['line'],
                    (isset($t['class']) ? $t['class'] : null),
                    (isset($t['type']) ? $t['type'] : null),
                    $t['function']
                );
            }

            if (is_object(Symphony::Database())) {
                $debug = Symphony::Database()->debug();

                if (!empty($debug)) {
                    foreach ($debug as $query) {
                        $queries .= sprintf(
                            '<li><em>[%01.4f]</em><code> %s;</code> </li>',
                            (isset($query['execution_time']) ? $query['execution_time'] : null),
                            htmlspecialchars($query['query'])
                        );
                    }
                }
            }

            $html = sprintf(
                file_get_contents(self::getTemplate('fatalerror.database')),
                $e->getDatabaseErrorMessage(),
                $e->getQuery(),
                $trace,
                $queries
            );

            $html = str_replace('{ASSETS_URL}', ASSETS_URL, $html);
            $html = str_replace('{SYMPHONY_URL}', SYMPHONY_URL, $html);
            $html = str_replace('{URL}', URL, $html);

            return $html;
        }
    }
