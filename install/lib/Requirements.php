<?php

/**
 * @package install
 */
namespace SymphonyCms\Installer\Lib;

class Requirements
{
    /**
     * @return array
     */
    public function check()
    {
        $errors = array();

        // Check for PHP 5.4+
        if (version_compare(phpversion(), '5.5.9', '<=')) {
            $errors[] = array(
                'msg' => __('PHP Version is not correct'),
                'details' => __(
                    'Symphony requires %1$s or greater to work, however version %2$s was detected.',
                    array(
                        '<code><abbr title="PHP: Hypertext Pre-processor">PHP</abbr> 5.5.9</code>',
                        '<code>' . phpversion() . '</code>'
                    )
                )
            );
        }

        // Is PDO available?
        if (!extension_loaded('pdo')) {
            $errors[] = array(
                'msg' => __('PDO extension not present'),
                'details' => __('Symphony requires PHP to be configured with PDO to work.')
            );
        }

        // Is CURL available?
        if (!extension_loaded('curl')) {
            $errors[] = array(
                'msg' => __('CURL extension not present'),
                'details' => __('Symphony requires PHP to be configured with CURL for HTTP communication.')
            );
        }

        // Is libxml available?
        if (!extension_loaded('xml') && !extension_loaded('libxml')) {
            $errors[] = array(
                'msg' => __('XML extension not present'),
                'details' => __('Symphony needs the XML extension to pass data to the site frontend.')
            );
        }

        // Is libxslt available?
        if (!extension_loaded('xsl') && !extension_loaded('xslt') && !function_exists('domxml_xslt_stylesheet')) {
            $errors[] = array(
                'msg' => __('XSLT extension not present'),
                'details' => __(
                    'Symphony needs an XSLT processor such as %s or Sablotron to build pages.',
                    array('Lib<abbr title="eXtensible Stylesheet Language Transformation">XSLT</abbr>')
                )
            );
        }

        // Is json_encode available?
        if (!function_exists('json_decode')) {
            $errors[] = array(
                'msg' => __('JSON functionality is not present'),
                'details' => __('Symphony uses JSON functionality throughout the backend for translations and the interface.')
            );
        }

        // Cannot write to root folder.
        if (!is_writable(DOCROOT)) {
            $errors['no-write-permission-root'] = array(
                'msg' => 'Root folder not writable: ' . DOCROOT,
                'details' => __(
                    'Symphony does not have write permission to the root directory. Please modify permission settings on %s. This can be reverted once installation is complete.',
                    array('<code>' . DOCROOT . '</code>')
                )
            );
        }

        // Cannot write to workspace
        if (is_dir(DOCROOT . '/workspace') && !is_writable(DOCROOT . '/workspace')) {
            $errors['no-write-permission-workspace'] = array(
                'msg' => 'Workspace folder not writable: ' . DOCROOT . '/workspace',
                'details' => __(
                    'Symphony does not have write permission to the existing %1$s directory. Please modify permission settings on this directory and its contents to allow this, such as with a recursive %2$s command.',
                    array('<code>/workspace</code>', '<code>chmod -R</code>')
                )
            );
        }

        return $errors;
    }
}
