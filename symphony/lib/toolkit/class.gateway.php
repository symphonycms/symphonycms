<?php

/**
 * @package toolkit
 */
/**
 * The Gateway class provides a standard way to interact with other pages.
 * By default it is essentially a wrapper for CURL, but if that is not available
 * it falls back to use sockets.
 * @example
 *  `
 * $ch = new Gateway;
 * $ch->init('http://www.example.com/');
 * $ch->setopt('POST', 1);
 * $ch->setopt('POSTFIELDS', array('fred' => 1, 'happy' => 'yes'));
 * print $ch->exec();
 * `
 */
class Gateway
{
    /**
     * Constant used to explicitly bypass CURL and use Sockets to
     * complete the request.
     * @var string
     */
    const FORCE_SOCKET = 'socket';

    /**
     * An associative array of some common ports for HTTP, HTTPS
     * and FTP. Port cannot be null when using Sockets
     * @var array
     */
    private static $ports = array(
        'http' => 80,
        'https' => 443,
        'ftp' => 21
    );

    /**
     * The URL for the request, as string. This may be a full URL including
     * any basic authentication. It will be parsed and applied to CURL using
     * the correct options.
     * @var string
     */
    private $_url = null;

    /**
     * The hostname of the request, as parsed by parse_url
     *
     * @link http://php.net/manual/en/function.parse-url.php
     * @var string
     */
    private $_host = null;

    /**
     * The protocol of the URL in the request, as parsed by parse_url
     * Defaults to http://
     *
     * @link http://php.net/manual/en/function.parse-url.php
     * @var string
     */
    private $_scheme = 'http://';

    /**
     * The port of the URL in the request, as parsed by parse_url
     *
     * @link http://php.net/manual/en/function.parse-url.php
     * @var integer
     */
    private $_port = null;

    /**
     * The path of the URL in the request, as parsed by parse_url
     *
     * @link http://php.net/manual/en/function.parse-url.php
     * @var string
     */
    private $_path = null;

    /**
     * The method to request the URL. By default, this is GET
     * @var string
     */
    private $_method = 'GET';

    /**
     * The content-type of the request, defaults to application/x-www-form-urlencoded
     * @var string
     */
    private $_content_type = 'application/x-www-form-urlencoded; charset=utf-8';

    /**
     * The user agent for the request, defaults to Symphony.
     * @var string
     */
    private $_agent = 'Symphony';

    /**
     * A URL encoded string of the `$_POST` fields, as built by
     * http_build_query()
     *
     * @link http://php.net/manual/en/function.http-build-query.php
     * @var string
     */
    private $_postfields = '';

    /**
     * Whether to the return the Header with the result of the request
     * @var boolean
     */
    private $_returnHeaders = false;

    /**
     * The timeout in seconds for the request, defaults to 4
     * @var integer
     */
    private $_timeout = 4;

    /**
     * An array of custom headers to pass with the request
     * @var array
     */
    private $_headers = array();

    /**
     * An array of custom options for the CURL request, this
     * can be any option as listed on the PHP manual
     *
     * @link http://php.net/manual/en/function.curl-setopt.php
     * @var array
     */
    private $_custom_opt = array();

    /**
     * An array of information about the request after it has
     * been executed. At minimum, regardless of if CURL or Sockets
     * are used, the HTTP Code, URL and Content Type will be returned
     *
     * @link http://php.net/manual/en/function.curl-getinfo.php
     */
    private $_info_last = array();

    /**
     * Mimics curl_init in that a URL can be provided
     *
     * @param string $url
     * A full URL string to use for the request, this can include
     * basic authentication which will automatically set the
     * correct options for the CURL request. Defaults to null
     */
    public function init($url = null)
    {
        if (!is_null($url)) {
            $this->setopt('URL', $url);
        }
    }

    /**
     * Checks to the see if CURL is available, if it isn't, false will
     * be returned, and sockets will be used
     *
     * @return boolean
     */
    public static function isCurlAvailable()
    {
        return function_exists('curl_init');
    }

    /**
     * Resets `$this->_postfields` variable to an empty string
     */
    public function flush()
    {
        $this->_postfields = '';
    }

    /**
     * A basic wrapper that simulates the curl_setopt function. Any
     * options that are not recognised by Symphony will fallback to
     * being added to the `$custom_opt` array. Any options in `$custom_opt`
     * will be applied on executed using curl_setopt. Custom options are not
     * available for Socket requests. The benefit of using this function is for
     * convienience as it performs some basic preprocessing for some options
     * such as 'URL', which will take a full formatted URL string and set any
     * authentication or SSL curl options automatically
     *
     * @link http://php.net/manual/en/function.curl-setopt.php
     * @param string $opt
     *  A string representing a CURL constant. Symphony will intercept the
     *  following, URL, POST, POSTFIELDS, USERAGENT, HTTPHEADER,
     *  RETURNHEADERS, CONTENTTYPE and TIMEOUT. Any other values
     *  will be saved in the `$custom_opt` array.
     * @param mixed $value
     *  The value of the option, usually boolean or a string. Consult the
     *  setopt documentation for more information.
     */
    public function setopt($opt, $value)
    {
        switch ($opt) {
            case 'URL':
                $this->_url = $value;
                $url_parsed = parse_url($value);
                $this->_host = $url_parsed['host'];

                if (isset($url_parsed['scheme']) && strlen(trim($url_parsed['scheme'])) > 0) {
                    $this->_scheme = $url_parsed['scheme'];
                }

                if (isset($url_parsed['port'])) {
                    $this->_port = $url_parsed['port'];
                }

                if (isset($url_parsed['path'])) {
                    $this->_path = $url_parsed['path'];
                }

                if (isset($url_parsed['query'])) {
                    $this->_path .= '?' . $url_parsed['query'];
                }

                // Allow basic HTTP authentiction
                if (isset($url_parsed['user']) && isset($url_parsed['pass'])) {
                    $this->setopt(CURLOPT_USERPWD, sprintf('%s:%s', $url_parsed['user'], $url_parsed['pass']));
                    $this->setopt(CURLOPT_HTTPAUTH, CURLAUTH_ANY);
                }

                // Better support for HTTPS requests
                if ($url_parsed['scheme'] == 'https') {
                    $this->setopt(CURLOPT_SSL_VERIFYPEER, false);
                }
                break;
            case 'POST':
            case 'GET':
            case 'PUT':
            case 'DELETE':
                $this->_method = ($value == 1 ? $opt : 'GET');
                break;
            case 'POSTFIELDS':
                if (is_array($value) && !empty($value)) {
                    $this->_postfields = http_build_query($value);
                } else {
                    $this->_postfields = $value;
                }
                break;
            case 'USERAGENT':
                $this->_agent = $value;
                break;
            case 'HTTPHEADER':
                // merge the values, so multiple calls won't erase other values
                if (is_array($value)) {
                    $this->_headers = array_merge($this->_headers, $value);
                } else {
                    $this->_headers[] = $value;
                }
                break;
            case 'RETURNHEADERS':
                $this->_returnHeaders = (intval($value) == 1 ? true : false);
                break;
            case 'CONTENTTYPE':
                $this->_content_type = $value;
                break;
            case 'TIMEOUT':
                $this->_timeout = max(1, intval($value));
                break;
            default:
                $this->_custom_opt[$opt] = $value;
                break;
        }
    }

    /**
     * Executes the request using Curl unless it is not available
     * or this function has explicitly been told not by providing
     * the `Gateway::FORCE_SOCKET` constant as a parameter. The function
     * will apply all the options set using `curl_setopt` before
     * executing the request. Information about the transfer is
     * available using the `getInfoLast()` function. Should Curl not be
     * available, this function will fallback to using Sockets with `fsockopen`
     *
     * @see toolkit.Gateway#getInfoLast()
     * @param string $force_connection_method
     *  Only one valid parameter, `Gateway::FORCE_SOCKET`
     * @return string|boolean
     *  The result of the transfer as a string. If any errors occur during
     *  a socket request, false will be returned.
     */
    public function exec($force_connection_method = null)
    {
        if ($force_connection_method !== self::FORCE_SOCKET && self::isCurlAvailable()) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, sprintf(
                "%s://%s%s%s",
                $this->_scheme,
                $this->_host,
                (!is_null($this->_port) ? ':' . $this->_port : null),
                $this->_path
            ));
            curl_setopt($ch, CURLOPT_HEADER, $this->_returnHeaders);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->_agent);
            curl_setopt($ch, CURLOPT_PORT, $this->_port);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);

            if (ini_get('safe_mode') == 0 && ini_get('open_basedir') == '') {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            }

            switch($this->_method) {
                case 'POST':
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_postfields);
                    break;
                case 'PUT':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_postfields);
                    $this->setopt('HTTPHEADER', array('Content-Length:' => strlen($this->_postfields)));
                    break;
                case 'DELETE':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_postfields);
                    break;
            }

            if (is_array($this->_headers) && !empty($this->_headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_headers);
            }

            if (is_array($this->_custom_opt) && !empty($this->_custom_opt)) {
                foreach ($this->_custom_opt as $opt => $value) {
                    curl_setopt($ch, $opt, $value);
                }
            }

            // Grab the result
            $result = curl_exec($ch);

            $this->_info_last = curl_getinfo($ch);
            $this->_info_last['curl_error'] = curl_errno($ch);

            // Close the connection
            curl_close($ch);

            return $result;
        }

        $start = precision_timer();

        if (is_null($this->_port)) {
            $this->_port = (!is_null($this->_scheme) ? self::$ports[$this->_scheme] : 80);
        }

        // No CURL is available, use attempt to use normal sockets
        $handle = @fsockopen($this->_host, $this->_port, $errno, $errstr, $this->_timeout);

        if ($handle === false) {
            return false;
        }

        $query = $this->_method . ' ' . $this->_path . ' HTTP/1.1' . PHP_EOL;
        $query .= 'Host: '.$this->_host . PHP_EOL;
        $query .= 'Content-type: '.$this->_content_type . PHP_EOL;
        $query .= 'User-Agent: '.$this->_agent . PHP_EOL;
        $query .= @implode(PHP_EOL, $this->_headers);
        $query .= 'Content-length: ' . strlen($this->_postfields) . PHP_EOL;
        $query .= 'Connection: close' . PHP_EOL . PHP_EOL;

        if (in_array($this->_method, array('PUT', 'POST', 'DELETE'))) {
            $query .= $this->_postfields;
        }

        // send request
        if (!@fwrite($handle, $query)) {
            return false;
        }

        stream_set_blocking($handle, false);
        stream_set_timeout($handle, $this->_timeout);

        $status = stream_get_meta_data($handle);
        $response = $dechunked = '';

        // get header
        while (!preg_match('/\\r\\n\\r\\n$/', $header) && !$status['timed_out']) {
            $header .= @fread($handle, 1);
            $status = stream_get_meta_data($handle);
        }

        $status = socket_get_status($handle);

        // Get rest of the page data
        while (!feof($handle) && !$status['timed_out']) {
            $response .= fread($handle, 4096);
            $status = stream_get_meta_data($handle);
        }

        @fclose($handle);

        $end = precision_timer('stop', $start);

        if (preg_match('/Transfer\\-Encoding:\\s+chunked\\r\\n/', $header)) {
            $fp = 0;

            do {
                $byte = '';
                $chunk_size = '';

                do {
                    $chunk_size .= $byte;
                    $byte = substr($response, $fp, 1);
                    $fp++;
                } while ($byte !== "\r" && $byte !== "\\r");

                $chunk_size = hexdec($chunk_size); // convert to real number

                if ($chunk_size == 0) {
                    break(1);
                }

                $fp++;

                $dechunked .= substr($response, $fp, $chunk_size);
                $fp += $chunk_size;

                $fp += 2;
            } while (true);

            $response = $dechunked;

        }

        // Following code emulates part of the function curl_getinfo()
        preg_match('/Content-Type:\s*([^\r\n]+)/i', $header, $match);
        $content_type = $match[1];

        preg_match('/HTTP\/\d+.\d+\s+(\d+)/i', $header, $match);
        $status = $match[1];

        $this->_info_last = array(
            'url' => $this->_url,
            'content_type' => $content_type,
            'http_code' => (int)$status,
            'total_time' => $end
        );

        return ($this->_returnHeaders ? $header : null) . $response;
    }


    /**
     * Returns some information about the last transfer, this
     * the same output array as expected when calling the
     * `curl_getinfo()` function. If Sockets were used to complete
     * the request instead of CURL, the resulting array will be
     * the HTTP Code, Content Type, URL and Total Time of the resulting
     * request
     *
     * @link http://php.net/manual/en/function.curl-getinfo.php
     * @return array
     */
    public function getInfoLast()
    {
        return $this->_info_last;
    }
}
