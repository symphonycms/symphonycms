<?php

/**
 * @package toolkit
 */

/**
 * A helper class for various email functions.
 */
abstract Class EmailHelper
{
    /**
     * Folding an email header field body as required by RFC2822.
     *
     * @param string $input
     *  header field body string
     * @param integer $max_length
     *  defaults to 75
     * @return string
     *  folded output string
     */
    public static function fold($input, $max_length = 75)
    {
        return @wordwrap($input, $max_length, "\r\n ");
    }

    /**
     * Q-encoding of a header field 'text' token or 'word' entity
     * within a 'phrase', according to RFC2047. The output is called
     * an 'encoded-word'; it must not be longer than 75 characters.
     *
     * This might be achieved with PHP's `mbstring` functions, but
     * `mbstring` is a non-default extension.
     *
     * For simplicity reasons this function encodes every character
     * except upper and lower case letters and decimal digits.
     *
     * RFC: 'While there is no limit to the length of a multiple-line
     * header field, each line of a header field that contains one or
     * more 'encoded-word's is limited to 76 characters.'
     * The required 'folding' will not be done here, but in another
     * helper function.
     *
     * This function must be 'multi-byte-sensitive' in a way that it
     * must never scatter a multi-byte character representation across
     * multiple encoded-words. So a 'lookahead' has been implemented,
     * based on the fact that for UTF-8 encoded characters any byte
     * except the first byte will have a leading '10' bit pattern,
     * which means an ASCII value >=128 and <=191.
     *
     * @author Elmar Bartel
     * @author Michael Eichelsdoerfer
     * @param string $input
     *  string to encode
     * @param integer $max_length
     *  maximum line length (default: 75 chars)
     * @return string $output
     *  encoded string
     */
    public static function qEncode($input, $max_length = 75)
    {
        // Don't encode empty strings
        if (empty($input)) {
            return $input;
        }

        $qpHexDigits  = '0123456789ABCDEF';
        $input_length = strlen($input);
        // Substract delimiters, character set and encoding
        $line_limit   = $max_length - 12;
        $line_length  = 0;

        $output = '=?UTF-8?Q?';

        for ($i=0; $i < $input_length; $i++) {
            $char = $input[$i];
            $ascii = ord($char);

            // No encoding for all 62 alphanumeric characters
            if (48 <= $ascii && $ascii <= 57 || 65 <= $ascii && $ascii <= 90 || 97 <= $ascii && $ascii <= 122) {
                $replace_length = 1;
                $replace_char = $char;

                // Encode space as underscore (means better readability for humans)
            } elseif ($ascii == 32) {
                $replace_length = 1;
                $replace_char = '_';

                // Encode
            } else {
                $replace_length = 3;
                // Bit operation is around 10 percent faster
                // than 'strtoupper(dechex($ascii))'
                $replace_char = '='
                              . $qpHexDigits[$ascii >> 4]
                              . $qpHexDigits[$ascii & 0x0f];

                // Account for following bytes of UTF8-multi-byte
                // sequence (max. length is 4 octets, RFC3629)
                $lookahead_limit = min($i+4, $input_length);

                for ($lookahead = $i+1; $lookahead < $lookahead_limit; $lookahead++) {
                    $ascii_ff = ord($input[$lookahead]);
                    if (128 <= $ascii_ff && $ascii_ff <= 191) {
                        $replace_char .= '='
                                       . $qpHexDigits[$ascii_ff >> 4]
                                       . $qpHexDigits[$ascii_ff & 0x0f];
                        $replace_length += 3;
                        $i++;
                    } else {
                        break;
                    }
                }
            }

            // Would the line become too long?
            if ($line_length + $replace_length > $line_limit) {
                $output .= "?= =?UTF-8?Q?";
                $line_length = 0;
            }

            $output .= $replace_char;
            $line_length += $replace_length;
        }

        $output .= '?=';
        return $output;
    }

    /**
     * Quoted-printable encoding of a message body (part),
     * according to RFC2045.
     *
     * This function handles <CR>, <LF>, <CR><LF> and <LF><CR> sequences
     * as 'user relevant' line breaks and encodes them as RFC822 line
     * breaks as required by RFC2045.
     *
     * @author Elmar Bartel
     * @author Michael Eichelsdoerfer
     * @param string $input
     *  string to encode
     * @param integer $max_length
     *  maximum line length (default: 76 chars)
     * @return string $output
     *  encoded string
     */
    public static function qpContentTransferEncode($input, $max_length = 76)
    {
        $qpHexDigits  = '0123456789ABCDEF';
        $input_length = strlen($input);
        $line_limit   = $max_length;
        $line_length  = 0;
        $output       = '';
        $blank        = false;

        for ($i=0; $i < $input_length; $i++) {
            $char = $input[$i];
            $ascii = ord($char);

            // No encoding for spaces and tabs
            if ($ascii == 9 || $ascii == 32) {
                $blank = true;
                $replace_length = 1;
                $replace_char = $char;

                // CR and LF
            } elseif ($ascii == 13 || $ascii == 10) {
                // Use existing offset only.
                if ($i+1 < $input_length) {
                    if (($ascii == 13 && ord($input[$i+1]) == 10) || ($ascii == 10 && ord($input[$i+1]) == 13)) {
                        $i++;
                    }
                }

                if ($blank) {
                    /**
                     * Any tab or space characters on an encoded line MUST
                     * be followed on that line by a printable character.
                     * This character may as well be the soft line break
                     * indicator.
                     *
                     * So if the preceding character is a space or a
                     * tab, we may simply insert a soft line break
                     * here, followed by a literal line break.
                     * Basically this means that we are appending
                     * an empty line (nada).
                     */
                    $output .= "=\r\n\r\n";
                } else {
                    $output .= "\r\n";
                }

                $blank = false;
                $line_length = 0;
                continue;

                // No encoding within ascii range 33 to 126 (exception: 61)
            } elseif (32 < $ascii && $ascii < 127 && $char !== '=') {
                $replace_length = 1;
                $replace_char = $char;
                $blank = false;

                // Encode
            } else {
                $replace_length = 3;
                // bit operation is around 10 percent faster
                // than 'strtoupper(dechex($ascii))'
                $replace_char = '='
                              . $qpHexDigits[$ascii >> 4]
                              . $qpHexDigits[$ascii & 0x0f];
                $blank = false;
            }
            // Would the line become too long?
            if ($line_length + $replace_length > $line_limit - 1) {
                $output .= "=\r\n";
                $line_length = 0;
            }

            $output .= $replace_char;
            $line_length += $replace_length;
        }

        return $output;
    }

    /**
     * Content-Transfer-Encoding for attachments
     *
     * This function will encode attachments according to RFC2045.
     * Line length must not exceed the default (76 characters).
     *
     * @author Michael Eichelsdoerfer
     * @param string $data
     * @param integer $length
     * @return string
     */
    public static function base64ContentTransferEncode($data, $length = 76)
    {
        return chunk_split(base64_encode($data), $length);
    }

    /**
     * Implodes an associative array or straight array to a
     * comma-separated string
     *
     * @param array $array
     * @return string
     */
    public static function arrayToList(array $array = array())
    {
        $return = array();
        foreach ($array as $name => $email) {
            $return[] = empty($name) || General::intval($name) > -1
                        ? $email
                        : $name . ' <' . $email . '>';
        }

        return implode(', ', $return);
    }
}
