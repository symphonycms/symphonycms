<?php

if (!defined("__IN_SYMPHONY__")) die("You cannot directly access this file.");

/**
 * @package toolkit
 */

/**
 * The `XSRF` class provides protection for mitigating XRSF/CSRF attacks.
 *
 * @since Symphony 2.4
 * @author Rich Adams, http://richadams.me
 */
class XSRF
{
    /**
     * Return's the location of the XSRF tokens in the Session
     *
     * @return array
     */
    public static function getSession()
    {
        $tokens = $_SESSION[__SYM_COOKIE_PREFIX__]['xsrf-token'];

        return is_null($tokens) ? array() : $tokens;
    }

    /**
     * Adds a token to the Session
     *
     * @param array $token
     */
    public static function setSessionToken($token = array())
    {
        $_SESSION[__SYM_COOKIE_PREFIX__]['xsrf-token'] = $token;
    }

    /**
     * Removes the token from the Session
     *
     * @param string $token
     */
    public static function removeSessionToken($token = null)
    {
        if (is_null($token)) {
            return;
        }

        unset($_SESSION[__SYM_COOKIE_PREFIX__]['xsrf-token'][$token]);
    }

    /**
     * Generates nonce to a desired `$length` using `openssl` where available,
     * falling back to using `/dev/urandom` and a microtime implementation
     * otherwise
     *
     * @param integer $length
     * @return string
     */
    public static function generateNonce($length = 20)
    {
        // Base64 encode some random binary data, and strip the "=" if there are any.
        if (function_exists("openssl_random_pseudo_bytes")) {
            return str_replace("=", "", base64_encode(openssl_random_pseudo_bytes($length)));
        }

        // Fallback if openssl not available
        if (is_readable("/dev/urandom")) {
            if (($handle = @fopen("/dev/urandom", "rb")) !== false) {
                $bytes = fread($handle, $length);
                fclose($handle);
                return str_replace("=", "", base64_encode($bytes));
            }
        }

        // Fallback if /dev/urandom not readable.
        $state = microtime();

        for ($i = 0; $i < 1000; $i += 20) {
            $state = sha1(microtime() . $state);
        }

        return str_replace("=", "", base64_encode(substr($state, 0, $length)));
    }

    /**
     * Creates the form input to use to house the token
     *
     * @return XMLElement
     */
    public static function formToken()
    {
        // <input type="hidden" name="xsrf" value=" . self::getToken() . " />
        $obj = new XMLElement("input");
        $obj->setAttribute("type", "hidden");
        $obj->setAttribute("name", "xsrf");
        $obj->setAttribute("value", self::getToken());
        return $obj;
    }

    /**
     * This is the nonce used to stop CSRF/XSRF attacks. It's stored in the user session.
     *
     * @return string
     */
    public static function getToken()
    {
        $tokens = self::getSession();
        if (empty($tokens)) {
            $nonce = self::generateNonce(20);
            $tokens[$nonce] = 1;
            self::setSessionToken($tokens);
        } else {
            $nonce = key($tokens);
        }

        return $nonce;
    }

    /**
     * This will determine if a token is valid.
     *
     * @param string $xsrf
     *  The token to validate
     * @return boolean
     */
    public static function validateToken($xsrf)
    {
        $tokens = self::getSession();

        // Sanity check
        if (empty($tokens)) {
            return false;
        }

        // Check that the token exists
        foreach ($tokens as $key => $expires) {
            if ($key == $xsrf) {
                return true;
            } else {
                self::removeSessionToken($key);
            }
        }

        return false;
    }

    /**
     * This will validate a request has a good token.
     *
     * @throws SymphonyErrorPage
     * @param boolean $silent
     *  If true, this function will return false if the request fails,
     *  otherwise it will throw an Exception. By default this function
     *  will thrown an exception if the request is invalid.
     * @return false|void
     */
    public static function validateRequest($silent = false)
    {
        // Only care if we have a POST request.
        if (count($_POST) > 0) {
            if (!self::validateToken($_POST["xsrf"])) {
                // Token was invalid, show an error page.
                if (!$silent) {
                    self::throwXSRFException();
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * The error function that's thrown if the token is invalid.
     *
     * @throws SymphonyErrorPage
     */
    public static function throwXSRFException()
    {
        $msg =
            __('Request was rejected for having an invalid cross-site request forgery token.')
            . '<br/><br/>' .
            __('Please go back and try again.');
        throw new SymphonyErrorPage($msg, __('Access Denied'), 'generic', array(), Page::HTTP_STATUS_FORBIDDEN);
    }
}
