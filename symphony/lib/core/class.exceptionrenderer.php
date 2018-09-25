<?php

class ExceptionRenderer
{
    /**
     * The render function will take an Throwable and output a formatted message.
     *
     * @since Symphony 2.7.0
     *  This function works with both Exception and Throwable
     * @since Symphony 3.0.0
     *  This function support both html (default) and text (cli) format
     *
     * @param Throwable $e
     *  The Throwable object
     * @return string
     *  An formatted message string
     */
    public static function render($e)
    {
        if (php_sapi_name() === 'cli') {
            return static::renderText($e);
        }
        return static::renderHtml($e);
    }

    /**
     * Retrieves a window of lines before and after the line where the error
     * occurred so that a developer can help debug the exception
     *
     * @param integer $line
     *  The line where the error occurred.
     * @param string $file
     *  The file that holds the logic that caused the error.
     * @param integer $window
     *  The number of lines either side of the line where the error occurred
     *  to show
     * @return array
     */
    protected static function getNearbyLines($line, $file, $window = 5)
    {
        if (!file_exists($file)) {
            return array();
        }

        return array_slice(file($file), ($line - 1) - $window, $window * 2, true);
    }

    /**
     * Returns the path to the error-template by looking at the `WORKSPACE/template/`
     * directory, then at the `TEMPLATES`  directory for the convention `*.tpl`. If
     * the template is not found, `false` is returned
     *
     * @since Symphony 2.3
     * @param string $name
     *  Name of the template
     * @return string|false
     *  String, which is the path to the template if the template is found,
     *  false otherwise
     */
    public static function getTemplate($name)
    {
        $format = '%s/%s.tpl';

        if (!ExceptionHandler::$enabled && php_sapi_name() !== 'cli') {
            if (!file_exists($template = sprintf($format, TEMPLATE, 'fatalerror.disabled'))) {
                return false;
            }
            return $template;
        } elseif (file_exists($template = sprintf($format, WORKSPACE . '/template', $name))) {
            return $template;
        } elseif (file_exists($template = sprintf($format, TEMPLATE, $name))) {
            return $template;
        }
        return false;
    }

    /**
     * Sends out the proper HTTP headers when rendering an error page.
     * It sets the page status to the proper code, depending on the Throwable received.
     * If the Throwable is a SymphonyException, additional headers are also sent.
     *
     * @uses SymphonyException::getAdditional()
     * @uses SymphonyException::getHttpStatusCode()
     * @param Throwable $e
     *  The Throwable object
     * @return void
     */
    public static function sendHeaders($e)
    {
        if (!headers_sent()) {
            cleanup_session_cookies();

            // Inspect the exception to determine the best status code
            $httpStatus = null;
            if ($e instanceof SymphonyException) {
                $httpStatus = $e->getHttpStatusCode();
                if (isset($e->getAdditional()->header)) {
                    header($e->getAdditional()->header);
                }
            } elseif ($e instanceof FrontendPageNotFoundException) {
                $httpStatus = Page::HTTP_STATUS_NOT_FOUND;
            }

            if (!$httpStatus || $httpStatus == Page::HTTP_STATUS_OK) {
                $httpStatus = Page::HTTP_STATUS_ERROR;
            }

            Page::renderStatusCode($httpStatus);
            header('Content-Type: text/html; charset=utf-8');
        }
    }

    /**
     * This function will fetch the `fatalerror.fatal` template, and output the
     * Throwable in a user friendly way.
     *
     * @since Symphony 2.4
     * @since Symphony 2.6.4 the method is protected
     * @since Symphony 2.7.0
     *  This function works with both Exception and Throwable
     * @since Symphony 3.0.0
     *  This function enforces the protected visibility.
     *  This function has a new signature.
     *
     * @param Throwable $e
     *  The Throwable object
     * @return string
     *  The HTML of the formatted error message.
     */
    protected static function renderHtml($e)
    {
        $heading = $e instanceof ErrorException ? ErrorHandler::$errorTypeStrings[$e->getSeverity()] : 'Fatal Error';
        $message = $e->getMessage() . ($e->getPrevious()
            ? '<br />' . __('Previous exception: ') . $e->getPrevious()->getMessage()
            : '');
        $lines = null;

        foreach (self::getNearbyLines($e->getLine(), $e->getFile()) as $line => $string) {
            $lines .= sprintf(
                '<li%s><strong>%d</strong> <code>%s</code></li>',
                (($line + 1) == $e->getLine() ? ' class="error"' : null),
                ++$line,
                str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', htmlspecialchars($string))
            );
        }

        $trace = null;

        foreach ($e->getTrace() as $t) {
            $trace .= sprintf(
                '<li><code><em>[%s:%d]</em></code></li><li><code>&#160;&#160;&#160;&#160;%s%s%s();</code></li>',
                (isset($t['file']) ? $t['file'] : null),
                (isset($t['line']) ? $t['line'] : null),
                (isset($t['class']) ? $t['class'] : null),
                (isset($t['type']) ? $t['type'] : null),
                $t['function']
            );
        }

        $queries = null;

        if (is_object(Symphony::Database())) {
            $debug = Symphony::Database()->getLogs();

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

        $template = 'fatalerror.generic';
        if (is_callable([$e, 'getTemplate'])) {
            $template = $e->getTemplate();
        }

        $html = sprintf(
            file_get_contents(self::getTemplate($template)),
            $heading,
            !ExceptionHandler::$enabled ? 'Something unexpected occurred.' : General::unwrapCDATA($message),
            !ExceptionHandler::$enabled ? '' : $e->getFile(),
            !ExceptionHandler::$enabled ? '' : $e->getLine(),
            !ExceptionHandler::$enabled ? null : $lines,
            !ExceptionHandler::$enabled ? null : $trace,
            !ExceptionHandler::$enabled ? null : $queries
        );

        $html = str_replace('{ASSETS_URL}', ASSETS_URL, $html);
        $html = str_replace('{SYMPHONY_URL}', SYMPHONY_URL, $html);
        $html = str_replace('{URL}', URL, $html);
        $html = str_replace('{PHP}', PHP_VERSION, $html);
        $html = str_replace(
            '{MYSQL}',
            !Symphony::Database() ? 'N/A' : Symphony::Database()->getVersion(),
            $html
        );

        return $html;
    }

    /**
     * This function will output the Throwable in a user friendly way.
     *
     * @since Symphony 3.0.0
     * @param Throwable $e
     *  The Throwable object
     * @return string
     *  The formatted error message.
     */
    protected static function renderText($e)
    {
        $message = $e->getMessage() . ($e->getPrevious()
            ? PHP_EOL . __('Previous exception: ') . $e->getPrevious()->getMessage()
            : '');
        $lines = null;

        foreach (self::getNearbyLines($e->getLine(), $e->getFile()) as $line => $string) {
            $lines .= sprintf(
                '%s %d %s',
                (($line + 1) == $e->getLine() ? '>' : ' '),
                ++$line,
                $string
            );
        }

        $trace = null;

        foreach ($e->getTrace() as $t) {
            $trace .= sprintf(
                '[%s:%d]%s  %s%s%s();%s',
                (isset($t['file']) ? $t['file'] : null),
                (isset($t['line']) ? $t['line'] : null),
                PHP_EOL,
                (isset($t['class']) ? $t['class'] : null),
                (isset($t['type']) ? $t['type'] : null),
                $t['function'],
                PHP_EOL
            );
        }

        $queries = null;

        if (is_object(Symphony::Database())) {
            $debug = Symphony::Database()->getLogs();

            if (!empty($debug)) {
                foreach ($debug as $query) {
                    $queries .= sprintf(
                        '[%01.4f] %s;' . PHP_EOL,
                        (isset($query['execution_time']) ? $query['execution_time'] : null),
                        $query['query']
                    );
                }
            }
        }

        $template = 'fatalerror.cli';

        $text = sprintf(
            file_get_contents(self::getTemplate($template)),
            $message,
            $e->getFile(),
            $e->getLine()
        );

        $text = str_replace('{LINES}', $lines, $text);
        $text = str_replace('{TRACE}', $trace, $text);
        $text = str_replace('{QUERIES}', $queries, $text);
        $text = str_replace('{PHP}', PHP_VERSION, $text);
        $text = str_replace(
            '{MYSQL}',
            !Symphony::Database() ? 'N/A' : Symphony::Database()->getVersion(),
            $text
        );

        return $text . PHP_EOL;
    }
}
