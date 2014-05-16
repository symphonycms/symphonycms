<?php
/**
 * @package content
 */
/**
 * Displays the contents of the Symphony `ACTIVITY_LOG`
 * log to any user who is logged in. If a user is not logged
 * in, or the log file is unreadable, they will be directed
 * to a 404 page
 */
class contentSystemLog
{
    public function build()
    {
        if (!is_file(ACTIVITY_LOG) || !$log = @file_get_contents(ACTIVITY_LOG)) {
            Administration::instance()->errorPageNotFound();
        }

        header('Content-Type: text/plain');

        print $log;
        exit;
    }

}
