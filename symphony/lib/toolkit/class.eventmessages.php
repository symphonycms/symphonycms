<?php

/**
 * @package toolkit
 */
/**
 * Basic lookup class for Event messages, allows for frontend developers
 * to localise and change event messages without relying on string
 * comparision.
 *
 * @since Symphony 2.4
 */
class EventMessages
{
    const UNKNOWN_ERROR = 0;

    const ENTRY_CREATED_SUCCESS = 100;
    const ENTRY_EDITED_SUCCESS = 101;
    const ENTRY_ERRORS = 102;
    const ENTRY_MISSING = 103;
    const ENTRY_NOT_UNIQUE = 104;
    const ENTRY_UNKNOWN = 105;

    const SECTION_MISSING = 200;

    const FIELD_MISSING = 301;
    const FIELD_INVALID = 302;
    const FIELD_NOT_UNIQUE = 303;

    const FILTER_FAILED = 400;

    const SECURITY_XSRF = 500;
}
