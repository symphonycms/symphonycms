<?php
// This class exists in PHP7.3
if (class_exists('JSONException', false)) {
    return;
}
/**
 * @package toolkit
 */
/**
 * The `JSONException` class extends the base `Exception` class. It's only
 * difference is that it will translate the `$code` to a human readable
 * error.
 * The class won't be loaded on PHP7.3+, which provides its own.
 *
 * @since Symphony 2.3
 */
class JSONException extends Exception
{
    /**
     * Constructor takes a `$message`, `$code` and the original Exception, `$ex`.
     * Upon translating the `$code` into a more human readable message, it will
     * initialise the base `Exception` class. If the `$code` is unfamiliar, the original
     * `$message` will be passed.
     *
     * @param string $message
     * @param integer $code
     * @param Exception $ex
     */
    public function __construct($message, $code = -1, Exception $ex = null)
    {
        switch ($code) {
            case JSON_ERROR_NONE:
                $message = __('No errors.');
                break;
            case JSON_ERROR_DEPTH:
                $message = __('Maximum stack depth exceeded.');
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $message = __('Underflow or the modes mismatch.');
                break;
            case JSON_ERROR_CTRL_CHAR:
                $message = __('Unexpected control character found.');
                break;
            case JSON_ERROR_SYNTAX:
                $message = __('Syntax error, malformed JSON.');
                break;
            case JSON_ERROR_UTF8:
                $message = __('Malformed UTF-8 characters, possibly incorrectly encoded.');
                break;
            default:
                if (!$message) {
                    $message = __('Unknown JSON error');
                }
                break;
        }

        parent::__construct($message, $code, $ex);
    }
}
