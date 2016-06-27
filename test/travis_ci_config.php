<?php
return $settings = array (
  'admin' =>
  array (
    'max_upload_size' => '5242880',
    'upload_blacklist' => '/\\.(?:php[34567s]?|phtml)$/i',
  ),
  'symphony' =>
  array (
    'admin-path' => 'symphony',
    'pagination_maximum_rows' => '20',
    'association_maximum_rows' => '5',
    'lang' => 'en',
    'pages_table_nest_children' => 'no',
    'version' => '3.0.0-alpha.1',
    'cell_truncation_length' => '75',
    'enable_xsrf' => 'no',
  ),
  'log' =>
  array (
    'handler' =>
    array (
      'class' => '\\Monolog\\Handler\\StreamHandler',
      'args' =>
      array (
        0 => '{vars.filename}',
        1 => 100,
      ),
    ),
    'formatter' =>
    array (
      'class' => '\\Monolog\\Formatter\\LineFormatter',
      'args' =>
      array (
        0 => '%datetime% > %level_name%: %message% %context% %extra%' . PHP_EOL,
        1 => '{region.date_format}{region.datetime_separator}{region.time_format}',
        2 => false,
        3 => true,
      ),
    ),
  ),
  'database' =>
  array (
    'host' => 'localhost',
    'port' => '3306',
    'user' => 'root',
    'password' => '',
    'db' => 'symphony_test',
    'tbl_prefix' => 'sym_',
    'query_caching' => 'on',
    'query_logging' => 'on',
  ),
  'session' =>
  array (
    'admin_session_name' => 'symphony_admin',
    'public_session_name' => 'symphony_public',
    'admin_session_expires' => '2 weeks',
    'public_session_expires' => '2 weeks',
    'session_gc_probability' => '1',
    'session_gc_divisor' => '10',
  ),
  'public' =>
  array (
    'display_event_xml_in_source' => 'no',
  ),
  'general' =>
  array (
    'sitename' => 'Symphony CMS',
    'useragent' => 'Symphony/3.0.0-alpha.1',
  ),
  'file' =>
  array (
    'write_mode' => '0644',
  ),
  'directory' =>
  array (
    'write_mode' => '0755',
  ),
  'region' =>
  array (
    'time_format' => 'g:i a',
    'date_format' => 'm/d/Y',
    'datetime_separator' => ' ',
    'timezone' => 'Australia/Brisbane',
  ),
  'cache_driver' =>
  array (
    'default' => 'database',
  )
);
