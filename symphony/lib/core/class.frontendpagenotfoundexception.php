<?php

/**
 * `FrontendPageNotFoundException` extends a default Exception, it adds nothing
 * but allows a different ExceptionRenderer to be used to render the Exception
 *
 * @see core.FrontendPageNotFoundExceptionRenderer
 */
class FrontendPageNotFoundException extends Exception
{
    /**
     * The constructor for `FrontendPageNotFoundException` sets the default
     * error message and code for Logging purposes
     */
    public function __construct()
    {
        parent::__construct();
        $pagename = getCurrentPage();

        if (empty($pagename)) {
            $this->message = __('The page you requested does not exist.');
        } else {
            $this->message = __('The page you requested, %s, does not exist.', array('<code>' . $pagename . '</code>'));
        }

        $this->code = E_USER_NOTICE;
    }
}
