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
     * @return string|null
     */
    public static function getSessionToken()
    {
        $token = $_SESSION[__SYM_COOKIE_PREFIX__]['xsrf-token'];

        if (is_array($token)) {
            $token = key($token);
        }

        return is_null($token) ? null : $token;
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

        $_SESSION[__SYM_COOKIE_PREFIX__]['xsrf-token'] = null;
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
        $token = self::getSessionToken();
        if (is_null($token)) {
            $nonce = self::generateNonce(20);
            self::setSessionToken($nonce);

        // Handle old tokens (< 2.6.0)
        } elseif (is_array($token)) {
            $nonce = key($token);
            self::setSessionToken($nonce);

        // New style tokens
        } else {
            $nonce = $token;
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
        $token = self::getSessionToken();

        return $token === $xsrf;
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

    /**
     * Return's the location of the XSRF tokens in the Session
     *
     * @deprecated This function will be removed in Symphony 3.0. Use
     *  `getSessionToken()` instead.
     * @return string|null
     */
    public static function getSession()
    {
        return self::getSessionToken();
    }
}
