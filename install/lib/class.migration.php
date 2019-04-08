<?php

/**
 * @package install
 */
/**
 * The Migration class is extended by updates files that contain the necessary
 * logic to update the current installation to the migration version. In the
 * future it is hoped Migrations will support downgrading as well.
 */
abstract class Migration
{
    /**
     * The current installed version of Symphony, before updating
     * @var string
     */
    public $existing_version = null;

    public function __construct($existing_version)
    {
        $this->existing_version = $existing_version;
    }

    /**
     * Run the update or downgrade function.
     *
     * @return boolean
     *  true if successful, false otherwise
     */
    public function run($function)
    {
        try {
            $canProceed = $this->{$function}();

            return ($canProceed === false) ? false : true;
        } catch (DatabaseException $e) {
            Symphony::Log()->pushToLog(
                'Could not complete upgrading. MySQL returned: ' .
                $e->getDatabaseErrorCode() . ': ' . $e->getMessage(),
                E_ERROR,
                true
            );

            return false;
        } catch (Exception $e) {
            Symphony::Log()->pushToLog(
                'Could not complete upgrading because of the following error: ' .
                $e->getMessage(),
                E_ERROR,
                true
            );

            return false;
        }
    }

    /**
     * Return's the most current version that this migration provides.
     * Note that just because the migration file is 2.3, the migration
     * might only cater for 2.3 Beta 1 at this stage, hence the function.
     *
     * @return string
     */
    public function getVersion()
    {
        return null;
    }

    /**
     * Return's the string to this migration's release notes. Like `getVersion()`,
     * this may not be the complete version, but rather the release notes for
     * the Beta/RC.
     *
     * @return string
     */
    public function getReleaseNotes()
    {
        $version = $this->getVersion();
        if (!$version) {
            return null;
        }
        return "https://www.getsymphony.com/download/releases/version/$version/";
    }

    /**
     * This function will upgrade Symphony from the `self::$existing_version`
     * to `getVersion()`.
     *
     * @return boolean
     */
    public function upgrade()
    {
        Symphony::Configuration()->set('version', $this->getVersion(), 'symphony');
        Symphony::Configuration()->set('useragent', 'Symphony/' . $this->getVersion(), 'general');

        if (Symphony::Configuration()->write() === false) {
            throw new Exception('Failed to write configuration file, please check the file permissions.');
        } else {
            return true;
        }
    }

    /**
     * This function is not implemented yet. It will take the `self::$existing_version`
     * and downgrade the Symphony install to `getVersion`.
     *
     * @return boolean
     */
    public function downgrade()
    {
        return false;
    }

    /**
     * Called before an upgrade has started, this function allows migrations to
     * include notices to display the user. These may be warnings about what is
     * about to happen, or a description of what this upgrade provides.
     *
     * @return
     *  An array of strings, where each string will become a list item.
     */
    public function preUpdateNotes()
    {
        return [];
    }

    /**
     * Called after an upgrade has started, this function allows migrations to
     * include notices to display the user. These may be post upgrade steps such
     * as new extensions that are available or required by the current version
     *
     * @return
     *  An array of strings, where each string will become a list item.
     */
    public function postUpdateNotes()
    {
        return [];
    }
}
