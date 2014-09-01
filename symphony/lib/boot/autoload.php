<?php

    /**
     * @package boot
     */
    /**
     * Handles autoloading of Symphony's objects. For now, this is not
     * really 'autoloading', it's just collecting all the require statements
     * scattered through the application and puts them in one spot
     */

    require_once DOCROOT . '/symphony/lib/boot/func.utilities.php';
    require_once DOCROOT . '/symphony/lib/boot/defines.php';

    require_once FACE . '/interface.provider.php';
    require_once FACE . '/interface.singleton.php';
    require_once FACE . '/interface.exportablefield.php';
    require_once FACE . '/interface.importablefield.php';
    require_once FACE . '/interface.cache.php';
    require_once FACE . '/interface.fileresource.php';

    require_once CORE . '/class.errorhandler.php';
    require_once CORE . '/class.configuration.php';
    require_once CORE . '/class.datetimeobj.php';
    require_once CORE . '/class.log.php';
    require_once CORE . '/class.cookie.php';
    require_once CORE . '/class.session.php';
    require_once CORE . '/class.log.php';
    require_once CORE . '/class.cacheable.php';
    require_once CORE . '/class.symphony.php';

    require_once TOOLKIT . '/class.page.php';
    require_once TOOLKIT . '/class.textpage.php';
    require_once TOOLKIT . '/class.jsonpage.php';
    require_once TOOLKIT . '/class.xmlpage.php';
    require_once TOOLKIT . '/class.ajaxpage.php';
    require_once TOOLKIT . '/class.htmlpage.php';
    require_once TOOLKIT . '/class.xsltpage.php';
    require_once TOOLKIT . '/class.administrationpage.php';
    require_once TOOLKIT . '/class.frontendpage.php';
    require_once TOOLKIT . '/class.resourcespage.php';

    require_once TOOLKIT . '/class.mutex.php';
    require_once TOOLKIT . '/class.xmlelement.php';
    require_once TOOLKIT . '/class.widget.php';
    require_once TOOLKIT . '/class.general.php';
    require_once TOOLKIT . '/class.lang.php';
    require_once TOOLKIT . '/class.cryptography.php';
    require_once TOOLKIT . '/class.xsrf.php';
    require_once TOOLKIT . '/class.profiler.php';
    require_once TOOLKIT . '/class.author.php';
    require_once TOOLKIT . '/class.email.php';
    require_once TOOLKIT . '/class.mysql.php';
    require_once TOOLKIT . '/class.gateway.php';
    require_once TOOLKIT . '/class.alert.php';
    require_once TOOLKIT . '/class.extensionmanager.php';
    require_once TOOLKIT . '/class.pagemanager.php';
    require_once TOOLKIT . '/class.authormanager.php';
    require_once TOOLKIT . '/class.emailgatewaymanager.php';
    require_once TOOLKIT . '/class.entrymanager.php';
    require_once TOOLKIT . '/class.fieldmanager.php';
    require_once TOOLKIT . '/class.sectionmanager.php';
    require_once TOOLKIT . '/class.textformattermanager.php';
    require_once TOOLKIT . '/class.datasourcemanager.php';
    require_once TOOLKIT . '/class.eventmanager.php';
    require_once TOOLKIT . '/class.resourcemanager.php';
    require_once TOOLKIT . '/class.xsltprocess.php';

    require_once TOOLKIT . '/cache/cache.database.php';
    require_once TOOLKIT . '/class.field.php';
    require_once TOOLKIT . '/fields/field.date.php';
    require_once TOOLKIT . '/class.event.php';
    require_once TOOLKIT . '/class.datasource.php';
    require_once TOOLKIT . '/class.emailgateway.php';
    require_once TOOLKIT . '/class.emailhelper.php';
    require_once TOOLKIT . '/class.smtp.php';
    require_once TOOLKIT . '/class.author.php';
    require_once TOOLKIT . '/class.entry.php';
    require_once TOOLKIT . '/class.datasource.php';
    require_once TOOLKIT . '/data-sources/class.datasource.author.php';
    require_once TOOLKIT . '/data-sources/class.datasource.section.php';
    require_once TOOLKIT . '/data-sources/class.datasource.static.php';
    require_once TOOLKIT . '/data-sources/class.datasource.dynamic_xml.php';
    require_once TOOLKIT . '/data-sources/class.datasource.navigation.php';
    require_once TOOLKIT . '/class.event.php';
    require_once TOOLKIT . '/events/class.event.section.php';
    require_once TOOLKIT . '/class.textformatter.php';
    require_once TOOLKIT . '/class.textformattermanager.php';

    require_once TOOLKIT . '/util.validators.php';
    require_once TOOLKIT . '/cryptography/class.md5.php';
    require_once TOOLKIT . '/cryptography/class.sha1.php';
    require_once TOOLKIT . '/cryptography/class.pbkdf2.php';

    require_once CONTENT . '/class.sortable.php';
    require_once CONTENT . '/content.ajaxeventdocumentation.php';


