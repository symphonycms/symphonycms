<?php

/**
 * @package boot
 */

require_once DOCROOT . '/symphony/lib/boot/func.utilities.php';
require_once DOCROOT . '/symphony/lib/boot/defines.php';
require_once TOOLKIT . '/util.validators.php';

/**
 * Handles autoloading of Symphony's objects. For now, this is not
 * really 'autoloading', it's utilising a classmap of all known
 * Symphony classes.
 */

Class SymphonyLoader
{
    protected static $classes = array();

    public static function init()
    {
        self::$classes = array(

            'administration'                => CORE    . '/class.administration.php',
            'administrationpage'            => TOOLKIT . '/class.administrationpage.php',
            'ajaxpage'                      => TOOLKIT . '/class.ajaxpage.php',
            'alert'                         => TOOLKIT . '/class.alert.php',
            'author'                        => TOOLKIT . '/class.author.php',
            'authordatasource'              => TOOLKIT . '/data-sources/class.datasource.author.php',
            'authormanager'                 => TOOLKIT . '/class.authormanager.php',
            'cacheable'                     => CORE    . '/class.cacheable.php',
            'cachedatabase'                 => TOOLKIT . '/cache/cache.database.php',
            'configuration'                 => CORE    . '/class.configuration.php',
            'contentajaxeventdocumentation' => CONTENT . '/content.ajaxeventdocumentation.php',
            'contentajaxfilters'            => CONTENT . '/content.ajaxfilters.php',
            'contentajaxhandle'             => CONTENT . '/content.ajaxhandle.php',
            'contentajaxlog'                => CONTENT . '/content.ajaxlog.php',
            'contentajaxparameters'         => CONTENT . '/content.ajaxparameters.php',
            'contentajaxquery'              => CONTENT . '/content.ajaxquery.php',
            'contentajaxreorder'            => CONTENT . '/content.ajaxreorder.php',
            'contentajaxsections'           => CONTENT . '/content.ajaxsections.php',
            'contentajaxtranslate'          => CONTENT . '/content.ajaxtranslate.php',
            'contentblueprintsdatasources'  => CONTENT . '/content.blueprintsdatasources.php',
            'contentblueprintsevents'       => CONTENT . '/content.blueprintsevents.php',
            'contentblueprintspages'        => CONTENT . '/content.blueprintspages.php',
            'contentblueprintssections'     => CONTENT . '/content.blueprintssections.php',
            'contentlogin'                  => CONTENT . '/content.login.php',
            'contentlogout'                 => CONTENT . '/content.logout.php',
            'contentpublish'                => CONTENT . '/content.publish.php',
            'contentsystemauthors'          => CONTENT . '/content.systemauthors.php',
            'contentsystemextensions'       => CONTENT . '/content.systemextensions.php',
            'contentsystemlog'              => CONTENT . '/content.systemlog.php',
            'contentsystempreferences'      => CONTENT . '/content.systempreferences.php',
            'cookie'                        => CORE    . '/class.cookie.php',
            'cryptography'                  => TOOLKIT . '/class.cryptography.php',
            'databaseexception'             => TOOLKIT . '/class.mysql.php',
            'databaseexceptionhandler'      => CORE    . '/class.symphony.php',
            'datasource'                    => TOOLKIT . '/class.datasource.php',
            'datasourcemanager'             => TOOLKIT . '/class.datasourcemanager.php',
            'datetimeobj'                   => CORE    . '/class.datetimeobj.php',
            'devkit'                        => TOOLKIT . '/class.devkit.php',
            'dynamicxmldatasource'          => TOOLKIT . '/data-sources/class.datasource.dynamic_xml.php',
            'email'                         => TOOLKIT . '/class.email.php',
            'emailexception'                => TOOLKIT . '/class.email.php',
            'emailgateway'                  => TOOLKIT . '/class.emailgateway.php',
            'emailgatewayexception'         => TOOLKIT . '/class.emailgateway.php',
            'emailgatewaymanager'           => TOOLKIT . '/class.emailgatewaymanager.php',
            'emailhelper'                   => TOOLKIT . '/class.emailhelper.php',
            'emailvalidationexception'      => TOOLKIT . '/class.emailgatewaymanager.php',
            'entry'                         => TOOLKIT . '/class.entry.php',
            'entrymanager'                  => TOOLKIT . '/class.entrymanager.php',
            'event'                         => TOOLKIT . '/class.event.php',
            'eventmanager'                  => TOOLKIT . '/class.eventmanager.php',
            'eventmessages'                 => TOOLKIT . '/events/class.event.section.php',
            'exportablefield'               => FACE    . '/interface.exportablefield.php',
            'extension'                     => TOOLKIT . '/class.extension.php',
            'extensionmanager'              => TOOLKIT . '/class.extensionmanager.php',
            'field'                         => TOOLKIT . '/class.field.php',
            'fieldauthor'                   => TOOLKIT . '/fields/field.author.php',
            'fieldcheckbox'                 => TOOLKIT . '/fields/field.checkbox.php',
            'fielddate'                     => TOOLKIT . '/fields/field.date.php',
            'fieldinput'                    => TOOLKIT . '/fields/field.input.php',
            'fieldmanager'                  => TOOLKIT . '/class.fieldmanager.php',
            'fieldselect'                   => TOOLKIT . '/fields/field.select.php',
            'fieldtaglist'                  => TOOLKIT . '/fields/field.taglist.php',
            'fieldtextarea'                 => TOOLKIT . '/fields/field.textarea.php',
            'fieldupload'                   => TOOLKIT . '/fields/field.upload.php',
            'fileresource'                  => FACE    . '/interface.fileresource.php',
            'frontend'                      => CORE    . '/class.frontend.php',
            'frontendpage'                  => TOOLKIT . '/class.frontendpage.php',
            'frontendpageexception'         => TOOLKIT . '/class.frontendpage.php',
            'frontendpageexceptionhandler'  => TOOLKIT . '/class.frontendpage.php',
            'gateway'                       => TOOLKIT . '/class.gateway.php',
            'general'                       => TOOLKIT . '/class.general.php',
            'genericerrorhandler'           => CORE    . '/class.errorhandler.php',
            'genericexceptionhandler'       => CORE    . '/class.errorhandler.php',
            'htmlpage'                      => TOOLKIT . '/class.htmlpage.php',
            'icache'                        => FACE    . '/interface.cache.php',
            'idatasource'                   => FACE    . '/interface.datasource.php',
            'ievent'                        => FACE    . '/interface.event.php',
            'importablefield'               => FACE    . '/interface.importablefield.php',
            'inamespacedcache'              => FACE    . '/interface.namespacedcache.php',
            'iprovider'                     => FACE    . '/interface.provider.php',
            'json'                          => TOOLKIT . '/class.json.php',
            'jsonexception'                 => TOOLKIT . '/class.json.php',
            'jsonpage'                      => TOOLKIT . '/class.jsonpage.php',
            'lang'                          => TOOLKIT . '/class.lang.php',
            'log'                           => CORE    . '/class.log.php',
            'md5'                           => TOOLKIT . '/cryptography/class.md5.php',
            'mutex'                         => TOOLKIT . '/class.mutex.php',
            'mysql'                         => TOOLKIT . '/class.mysql.php',
            'navigationdatasource'          => TOOLKIT . '/data-sources/class.datasource.navigation.php',
            'page'                          => TOOLKIT . '/class.page.php',
            'pagemanager'                   => TOOLKIT . '/class.pagemanager.php',
            'pbkdf2'                        => TOOLKIT . '/cryptography/class.pbkdf2.php',
            'profiler'                      => TOOLKIT . '/class.profiler.php',
            'resourcemanager'               => TOOLKIT . '/class.resourcemanager.php',
            'resourcespage'                 => TOOLKIT . '/class.resourcespage.php',
            'sectiondatasource'             => TOOLKIT . '/data-sources/class.datasource.section.php',
            'sectionevent'                  => TOOLKIT . '/events/class.event.section.php',
            'sectionmanager'                => TOOLKIT . '/class.sectionmanager.php',
            'session'                       => CORE    . '/class.session.php',
            'sha1'                          => TOOLKIT . '/cryptography/class.sha1.php',
            'singleton'                     => FACE    . '/interface.singleton.php',
            'smtp'                          => TOOLKIT . '/class.smtp.php',
            'sortable'                      => CONTENT . '/class.sortable.php',
            'staticxmldatasource'           => TOOLKIT . '/data-sources/class.datasource.static.php',
            'symphony'                      => CORE    . '/class.symphony.php',
            'symphonyerrorpage'             => CORE    . '/class.symphony.php',
            'symphonyerrorpageHandler'      => CORE    . '/class.symphony.php',
            'textformatter'                 => TOOLKIT . '/class.textformatter.php',
            'textformattermanager'          => TOOLKIT . '/class.textformattermanager.php',
            'textformattermanager'          => TOOLKIT . '/class.textformattermanager.php',
            'textpage'                      => TOOLKIT . '/class.textpage.php',
            'widget'                        => TOOLKIT . '/class.widget.php',
            'xmlelement'                    => TOOLKIT . '/class.xmlelement.php',
            'xmlpage'                       => TOOLKIT . '/class.xmlpage.php',
            'xsltpage'                      => TOOLKIT . '/class.xsltpage.php',
            'xsltprocess'                   => TOOLKIT . '/class.xsltprocess.php',
            'xsrf'                          => TOOLKIT . '/class.xsrf.php'
        );

        // Add in the installer classes if INSTALL is defined.

        if (defined('INSTALL')) {

            self::$classes = array_merge(self::$classes, array(

                // Installer

                'installer'     => INSTALL . '/lib/class.installer.php',
                'installerpage' => INSTALL . '/lib/class.installerpage.php',
                'updater'       => INSTALL . '/lib/class.updater.php',
                'updaterpage'   => INSTALL . '/lib/class.updaterpage.php',
                'migration'     => INSTALL . '/lib/class.migration.php',
            ));
        }

        spl_autoload_register(array('SymphonyLoader', 'load'));
    }

    public static function load($class)
    {
        $class = strtolower($class);

        if (isset(self::$classes[$class])) {

            require_once self::$classes[$class];
        }
    }
}

SymphonyLoader::init();
