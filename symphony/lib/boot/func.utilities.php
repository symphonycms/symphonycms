<?php

/**
 * @package boot
 */

/**
 * Redirects the browser to a specified location. Safer than using a
 * direct header() call
 *
 *  @param string $url
 */
function redirect ($url)
{
    // Just make sure.
    $url = str_replace('Location:', null, $url);

    if (headers_sent($filename, $line)) {
        echo "<h1>Error: Cannot redirect to <a href=\"$url\">$url</a></h1><p>Output has already started in $filename on line $line</p>";
        exit;
    }

    // convert idn back to ascii for redirect

    if (function_exists('idn_to_ascii')) {
        $root = parse_url(URL);
        $host = $root['host'];
        $url  = str_replace($host, idn_to_ascii($host), $url);
    }

    header('Status: 302 Found');
    header('Expires: Mon, 12 Dec 1982 06:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header("Location: $url");

    exit;
}

/**
 * Returns the current working directory, replacing any \
 *  with /. Use for Windows compatibility.
 *
 *  @return string
 */
function getcwd_safe()
{
    return str_replace('\\', '/', getcwd());
}

/**
 * Checks that a constant has not been defined before defining
 * it. If the constant is already defined, this function will do
 * nothing, otherwise, it will set the constant
 *
 * @param string $name
 *  The name of the constant to set
 * @param string $value
 *  The value of the desired constant
 */
function define_safe($name, $value)
{
    if (!defined($name)) {
        define($name, $value);
    }
}

/**
 * Returns the current URL string from within the Administration
 * context. It omits the Symphony directory from the current URL.
 *
 *  @return string
 */
function getCurrentPage()
{
    if (!isset($_GET['symphony-page'])) {
        return null;
    }

    return '/' . filter_var(trim($_GET['symphony-page'], '/'), FILTER_SANITIZE_STRING) . '/';
}

/**
 * Used as a basic stopwatch for profiling. The default `$action`
 * starts the timer. Setting `$action` to 'stop' and passing the
 * start time returns the difference between now and that time.
 *
 *  @param string $action (optional)
 *  @param integer $start_time (optional)
 *  @return integer
 */
function precision_timer($action = 'start', $start_time = null)
{
    $currtime = microtime(true);

    if ($action == 'stop') {
        return $currtime - $start_time;
    }

    return $currtime;
}

/**
 * Convert php.ini size format to bytes
 *
 *  @param string $val (optional)
 *  @return integer
 */
function ini_size_to_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);

    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

/**
 * Cleans up Session Cookies. When there is no data in the session the cookie will be unset.
 * If there is data, the cookie will be renewed, expiring it in two weeks from now.
 * This will improve the interoperability with caches like Varnish and Squid.
 *
 * @since 2.3.3
 * @author creativedutchmen (Huib Keemink)
 * @return void
 */
function cleanup_session_cookies()
{
    /*
    Unfortunately there is no way to delete a specific previously set cookie from PHP.
    The only way seems to be the method employed here: store all the cookie we need to keep, then delete every cookie and add the stored cookies again.
    Luckily we can just store the raw header and output them again, so we do not need to actively parse the header string.
    */
    $cookie_params = session_get_cookie_params();
    $list = headers_list();
    $custom_cookies = array();

    foreach ($list as $hdr) {
        if ((stripos($hdr, 'Set-Cookie') !== false) && (stripos($hdr, session_id()) === false)) {
            $custom_cookies[] = $hdr;
        }
    }

    header_remove('Set-Cookie');

    foreach ($custom_cookies as $custom_cookie) {
        header($custom_cookie);
    }

    $session_is_empty = is_session_empty();

    if ($session_is_empty && !empty($_COOKIE[session_name()])) {
        setcookie(
            session_name(),
            session_id(),
            time() - 3600,
            $cookie_params['path'],
            $cookie_params['domain'],
            $cookie_params['secure'],
            $cookie_params['httponly']
        );
    } elseif (!$session_is_empty) {
        setcookie(
            session_name(),
            session_id(),
            time() + TWO_WEEKS,
            $cookie_params['path'],
            $cookie_params['domain'],
            $cookie_params['secure'],
            $cookie_params['httponly']
        );
    }
}

/**
 * Function will loop over the $_SESSION and find out if it is empty or not
 *
 * @since Symphony 2.4
 * @return boolean
 */
function is_session_empty()
{
    $session_is_empty = true;
    if(is_array($_SESSION)) {
        foreach ($_SESSION as $contents) {
            if (!empty($contents)) {
                $session_is_empty = false;
            }
        }
    }

    return $session_is_empty;
}

/**
 * Responsible for picking the launcher function and starting it.
 *
 *  @param string $mode (optional)
 */
function symphony($mode)
{
    $launcher = SYMPHONY_LAUNCHER;
    $launcher($mode);
}

/**
 * Responsible for launching a standard symphony instance and
 * sending output to the browser.
 *
 *  @param string $mode (optional)
 *  @return integer
 */
function symphony_launcher($mode)
{
    if (strtolower($mode) == 'administration') {
        $renderer = Administration::instance();
    }

    else {
        $renderer = Frontend::instance();
    }

    $output = $renderer->display(getCurrentPage());

    // #1808
    if (isset($_SERVER['HTTP_MOD_REWRITE']))
    {
        $output = file_get_contents(GenericExceptionHandler::getTemplate('fatalerror.rewrite'));
        $output = str_replace('{ASSETS_URL}', ASSETS_URL, $output);
        $output = str_replace('{SYMPHONY_URL}', SYMPHONY_URL, $output);
        $output = str_replace('{URL}', URL, $output);
        echo $output;
        exit;
    }

    cleanup_session_cookies();

    echo $output;

    return $renderer;
}


/**
 * The translation function accepts an English string and returns its translation
 * to the active system language. If the given string is not available in the
 * current dictionary the original English string will be returned. Given an optional
 * `$inserts` array, the function will replace translation placeholders using `vsprintf()`.
 * Since Symphony 2.3, it is also possible to have multiple translation of the same string
 * according to the page namespace (i.e. the value returned by Symphony's `getPageNamespace()`
 * method). In your lang file, use the `$dictionary` key as namespace and its value as an array
 * of context-aware translations, as shown below:
 *
 * $dictionary = array(
 *        [...]
 *
 *        'Create new' => 'Translation for Create New',
 *
 *        '/blueprints/datasources' => array(
 *            'Create new' =>
 *            'If we are inside a /blueprints/datasources/* page, this translation will be returned for the string'
 *        ),
 *
 *        [...]
 *  );
 *
 * @see core.Symphony#getPageNamespace()
 * @param string $string
 *  The string that should be translated
 * @param array $inserts (optional)
 *  Optional array used to replace translation placeholders, defaults to NULL
 * @return string
 *  Returns the translated string
 */
function __($string, $inserts = null)
{
    return Lang::translate($string, $inserts);
}
