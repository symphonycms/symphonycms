# Changes in Symphony CMS 3.0

All notable changes of the Symphony CMS 3.0 release series are documented in this file using the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [3.0.0] - 2020-03-27

#### Added
  - [API] PDO Support
  - [API] Database Abstraction Layer
  - [API] Split error handling from error rendering
  - [config] InnoDB by default
  - [test] Unit and integration tests
  - [test] CI Integration
  - [class] XMLDocument
  - [class] DateRangeParser
  - [class] ArrayReducer

### Changed
 - [BC] Use APP_MODE variable to load the proper Engine instance
 - [BC] Default parameter value for for parameter $context of TextPage#build() changed from NULL to array (
)
 - [BC] The parameter $context of TextPage#build() changed from no type to a non-contravariant array
 - [BC] The parameter $context of TextPage#build() changed from no type to array
 - [BC] Property AdministrationPage#$_context changed default value from NULL to array (
 - [BC] Property AdministrationPage#$_context visibility reduced from public to protected
 - [BC] Property contentSystemPreferences#$_errors visibility reduced from public to protected
 - [BC] Property contentBlueprintsEvents#$_errors visibility reduced from public to protected
 - [BC] Property contentPublish#$_errors visibility reduced from public to protected
 - [BC] Property contentBlueprintsDatasources#$_errors visibility reduced from public to protected
 - [BC] Property contentSystemAuthors#$_errors visibility reduced from public to protected
 - [BC] Default parameter value for for parameter $context of contentLogin#build() changed from NULL to array (
 - [BC] The parameter $context of contentLogin#build() changed from no type to a non-contravariant array
 - [BC] The parameter $context of contentLogin#build() changed from no type to array
 - [BC] Property contentBlueprintsPages#$_errors visibility reduced from public to protected
 - [BC] Property contentBlueprintsSections#$_errors visibility reduced from public to protected
 - [BC] Method providerOf() of class iProvider changed scope from static to instance
 - [BC] Property Symphony::$Cookie visibility reduced from public to private
 - [BC] Property Symphony::$Author visibility reduced from public to private
 - [BC] Method renderHtml() of class GenericExceptionHandler visibility reduced from public to protected
 - [BC] Method createCookieSafePath() of class Session changed scope from static to instance
 - [BC] Method getDomain() of class Session changed scope from static to instance
 - [BC] Method open() of class Session changed scope from static to instance
 - [BC] Method close() of class Session changed scope from static to instance
 - [BC] Method write() of class Session changed scope from static to instance
 - [BC] Method read() of class Session changed scope from static to instance
 - [BC] Method destroy() of class Session changed scope from static to instance
 - [BC] Method gc() of class Session changed scope from static to instance
 - [BC] Property DateTimeObj::$date_mappings changed default value from array (
  'Y/m/d' => 'YYYY/MM/DD',
  'd/m/Y' => 'DD/MM/YYYY',
  'm/d/Y' => 'MM/DD/YYYY',
  'm/d/y' => 'MM/DD/YY',
  'Y-m-d' => 'YYYY-MM-DD',
  'm-d-Y' => 'MM-DD-YYYY',
  'm-d-y' => 'MM-DD-YY',
  'd.m.Y' => 'DD.MM.YYYY',
  'j.n.Y' => 'D.M.YYYY',
  'j.n.y' => 'D.M.YY',
  'd.m.y' => 'DD.MM.YYYY',
  'd F Y' => 'DD MMMM YYYY',
  'd. F Y' => 'DD. MMMM YYYY',
  'd M Y' => 'DD MMM YYYY',
  'd. M Y' => 'DD. MMM. YYYY',
  'j. F Y' => 'D. MMMM YYYY',
  'j. M. Y' => 'D. MMM. YYYY',
) to array (
  'Y/m/d' => 'YYYY/MM/DD',
  'd/m/Y' => 'DD/MM/YYYY',
  'm/d/Y' => 'MM/DD/YYYY',
  'm/d/y' => 'MM/DD/YY',
  'Y-m-d' => 'YYYY-MM-DD',
  'm-d-Y' => 'MM-DD-YYYY',
  'm-d-y' => 'MM-DD-YY',
  'j.n.Y' => 'D.M.YYYY',
  'j.n.y' => 'D.M.YY',
  'd.m.Y' => 'DD.MM.YYYY',
  'd.m.y' => 'DD.MM.YYYY',
  'd F Y' => 'DD MMMM YYYY',
  'd. F Y' => 'DD. MMMM YYYY',
  'd M Y' => 'DD MMM YYYY',
  'd. M Y' => 'DD. MMM. YYYY',
  'j. F Y' => 'D. MMMM YYYY',
  'j. M. Y' => 'D. MMM. YYYY',
) NOTE: There was a duplicated key, which does not shows up
 - [BC] Method getMimeType() of class General changed scope from instance to static
 - [BC] Method providerOf() of class Extension changed scope from static to instance
 - [BC] The parameter $Database of CacheDatabase#__construct() changed from MySQL to a non-contravariant Database
 - [BC] The parameter $Database of CacheDatabase#__construct() changed from MySQL to Database
 - [BC] Default parameter value for for parameter $salt of PBKDF2::hash() changed from NULL to array (
 - [BC] The parameter $salt of PBKDF2::hash() changed from no type to a non-contravariant array
 - [BC] The parameter $salt of PBKDF2::hash() changed from no type to array
 - [BC] Type documentation for property XSLTPage#$Proc changed from \XsltProcess to \XSLTProcess
 - [BC] Method queryCount() of class MySQL changed scope from static to instance
 - [BC] Method enableCaching() of class MySQL changed scope from static to instance
 - [BC] Method disableCaching() of class MySQL changed scope from static to instance
 - [BC] Method isCachingEnabled() of class MySQL changed scope from static to instance
 - [BC] Method enableLogging() of class MySQL changed scope from static to instance
 - [BC] Method disableLogging() of class MySQL changed scope from static to instance
 - [BC] Method isLoggingEnabled() of class MySQL changed scope from static to instance
 - [BC] Method isConnected() of class MySQL changed scope from static to instance
 - [BC] Method cleanValue() of class MySQL changed scope from static to instance
 - [BC] Method cleanFields() of class MySQL changed scope from static to instance
 - [BC] The parameter $fields of MySQL#insert() changed from array to no type
 - [BC] Class Datasource became abstract
 - [BC] The number of required arguments for XMLElement#setElementStyle() increased from 0 to 1
 - [BC] The number of required arguments for XMLElement#setSelfClosingTag() increased from 0 to 1
 - [BC] The number of required arguments for XMLElement#setAllowEmptyAttributes() increased from 0 to 1
 - [BC] The number of required arguments for XMLElement#setAttributeArray() increased from 0 to 1
 - [BC] The parameter $attributes of XMLElement#setAttributeArray() changed from ?array to a non-contravariant array
 - [BC] The parameter $attributes of XMLElement#setAttributeArray() changed from ?array to array
 - [BC] The number of required arguments for XMLElement#setChildren() increased from 0 to 1
 - [BC] The parameter $children of XMLElement#setChildren() changed from ?array to a non-contravariant array
 - [BC] The parameter $children of XMLElement#setChildren() changed from ?array to array
 - [BC] The number of required arguments for XMLElement#appendChildArray() increased from 0 to 1
 - [BC] The parameter $children of XMLElement#appendChildArray() changed from ?array to a non-contravariant array
 - [BC] The parameter $children of XMLElement#appendChildArray() changed from ?array to array
 - [BC] The number of required arguments for XMLElement#insertChildAt() increased from 1 to 2
 - [BC] The parameter $child of XMLElement#insertChildAt() changed from ?XMLElement to a non-contravariant XMLElement
 - [BC] The parameter $child of XMLElement#insertChildAt() changed from ?XMLElement to XMLElement
 - [BC] The number of required arguments for XMLElement#replaceChildAt() increased from 1 to 2
 - [BC] The parameter $child of XMLElement#replaceChildAt() changed from ?XMLElement to a non-contravariant XMLElement
 - [BC] The parameter $child of XMLElement#replaceChildAt() changed from ?XMLElement to XMLElement
 - [BC] Method __reduceType() of class SectionEvent changed scope from instance to static
 - [BC] Class SectionDatasource became abstract
 - [BC] The number of required arguments for XsltProcess#process() increased from 0 to 2
 - [BC] The number of required arguments for XsltProcess#validate() increased from 1 to 2
 - [BC] The number of required arguments for PageManager::fetchPageByType() increased from 0 to 1
 - [BC] The number of required arguments for PageManager::hasChildPages() increased from 0 to 1
 - [BC] Property $existing_version of Migration changed scope from static to instance
 - [BC] Method run() of class Migration changed scope from static to instance
 - [BC] Method getVersion() of class Migration changed scope from static to instance
 - [BC] Method getReleaseNotes() of class Migration changed scope from static to instance
 - [BC] Method upgrade() of class Migration changed scope from static to instance
 - [BC] Method downgrade() of class Migration changed scope from static to instance
 - [BC] Method preUpdateNotes() of class Migration changed scope from static to instance
 - [BC] Method postUpdateNotes() of class Migration changed scope from static to instance

### Removed
 - [BC] Property Page#$_status was removed
 - [BC] Method Page#__renderHeaders() was removed
 - [BC] Method contentLogin#__loginFromToken() was removed
 - [BC] Method contentBlueprintsPages#__actionTemplate() was removed
 - [BC] Property Symphony::$Cookie was removed
 - [BC] Property Symphony::$Author was removed
 - [BC] Class SymphonyErrorPageHandler has been deleted
 - [BC] Class SymphonyErrorPage has been deleted
 - [BC] Class DatabaseExceptionHandler has been deleted
 - [BC] Method Cacheable#check() was removed
 - [BC] Method Cacheable#forceExpiry() was removed
 - [BC] Method Cacheable#clean() was removed
 - [BC] Property GenericExceptionHandler::$enabled was removed
 - [BC] Method GenericExceptionHandler::initialise() was removed
 - [BC] Method GenericExceptionHandler::__nearbyLines() was removed
 - [BC] Method GenericExceptionHandler::isValidThrowable() was removed
 - [BC] Method GenericExceptionHandler::handler() was removed
 - [BC] Method GenericExceptionHandler::shutdown() was removed
 - [BC] These ancestors of FrontendPageNotFoundExceptionHandler have been removed: ["SymphonyErrorPageHandler","GenericExceptionHandler"]
 - [BC] Class GenericExceptionHandler has been deleted
 - [BC] Class GenericErrorHandler has been deleted
 - [BC] Constant Configuration::TAB was removed
 - [BC] Method Configuration#serializeArray() was removed
 - [BC] Method General::checkFile() was removed
 - [BC] Method General::hash() was removed
 - [BC] Method Field#fetchAssociatedEntryIDs() was removed
 - [BC] Class MD5 has been deleted
 - [BC] Class SHA1 has been deleted
 - [BC] Class AjaxPage has been deleted
 - [BC] Method Lang::isUnicodeCompiled() was removed
 - [BC] Method AuthorManager::activateAuthToken() was removed
 - [BC] Method AuthorManager::deactivateAuthToken() was removed
 - [BC] Method EntryManager::__buildEntries() was removed
 - [BC] Method XSRF::getSession() was removed
 - [BC] Property XSLTPage#$_registered_php_functions was removed
 - [BC] Method XSLTPage#setRuntimeParam() was removed
 - [BC] Method XSLTPage#registerPHPFunction() was removed
 - [BC] Method FieldTagList#fetchAssociatedEntryIDs() was removed
 - [BC] Method FieldTagList#findAllTags() was removed
 - [BC] Method MySQL::flushLog() was removed
 - [BC] Method MySQL#close() was removed
 - [BC] Method MySQL::getConnectionResource() was removed
 - [BC] Method MySQL#setCharacterEncoding() was removed
 - [BC] Method MySQL#setCharacterSet() was removed
 - [BC] Method Datasource#grab() was removed
 - [BC] Method Datasource#__noRecordsFound() was removed
 - [BC] Method Datasource#__negateResult() was removed
 - [BC] Method Datasource#__processParametersInString() was removed
 - [BC] Method Datasource#__determineFilterType() was removed
 - [BC] Method XMLElement#addProcessingInstruction() was removed
 - [BC] Method XMLElement#setDTD() was removed
 - [BC] Method XMLElement#setEncoding() was removed
 - [BC] Method XMLElement#setVersion() was removed
 - [BC] Method XMLElement#setIncludeHeader() was removed
 - [BC] Method NavigationDatasource#__processNavigationParentFilter() was removed
 - [BC] Method NavigationDatasource#__processNavigationTypeFilter() was removed
 - [BC] Method NavigationDatasource#__buildPageXML() was removed
 - [BC] Class DynamicXMLDatasource has been deleted
 - [BC] Method SectionDatasource#setSource() was removed
 - [BC] Method AuthorDatasource#__processAuthorFilter() was removed
 - [BC] Property Entry#$creationDate was removed
 - [BC] Property XSLTPage#$_param was removed
 - [BC] Method XsltProcess#__construct() was removed
 - [BC] Method Author#createAuthToken() was removed
 - [BC] Class migration_268 has been deleted
 - [BC] Class migration_269 has been deleted
 - [BC] Class migration_2610 has been deleted
 - [BC] Class migration_2611 has been deleted
 - [BC] Class migration_253 has been deleted
 - [BC] Class migration_235 has been deleted
 - [BC] Class migration_234 has been deleted
 - [BC] Class migration_252 has been deleted
 - [BC] Class migration_270 has been deleted
 - [BC] Class migration_250 has been deleted
 - [BC] Class migration_236 has been deleted
 - [BC] Class migration_251 has been deleted
 - [BC] Class migration_233 has been deleted
 - [BC] Class migration_232 has been deleted
 - [BC] Class migration_230 has been deleted
 - [BC] Class migration_231 has been deleted
 - [BC] Class migration_240 has been deleted
 - [BC] Class migration_262 has been deleted
 - [BC] Class migration_263 has been deleted
 - [BC] Class migration_225 has been deleted
 - [BC] Class migration_261 has been deleted
 - [BC] Class migration_260 has been deleted
 - [BC] Class migration_224 has been deleted
 - [BC] Class migration_220 has been deleted
 - [BC] Class migration_264 has been deleted
 - [BC] Class migration_265 has been deleted
 - [BC] Class migration_221 has been deleted
 - [BC] Class migration_223 has been deleted
 - [BC] Class migration_267 has been deleted
 - [BC] Class migration_266 has been deleted
 - [BC] Class migration_222 has been deleted
 - [BC] Method Installer::__abort() was removed
 - [BC] Method Installer::__render() was removed
 - [BC] Property InstallerPage#$_params was removed
 - [BC] Property InstallerPage#$_page_title was removed

### Deprecated
- [delegate] AdjustPublishFiltering$context.where
- [delegate] AdjustPublishFiltering$context.joins
- [class] FrontendPageNotFoundExceptionHandler
- [API] AuthorManager::fetch()
- [class] MySQL
- [API] Symphony::setDatabase()
- [API] Symphony::Database()->insert($table, $values)
- [API] Symphony::Database()->update($table, $values)
- [API] Symphony::Database()->delete($table, $where)
- [API] Symphony::Database()->quote()
- [API] Symphony::Database()->quoteFields()
- [API] Symphony::Database()->import($sql, $force_engine)
- [API] Symphony::Database()->quoteFields()
- [API] Symphony::Database()->debug()
- [API] Symphony::Database()->cleanValue()
- [API] Symphony::Database()->cleanFields()
- [API] Symphony::Database()->determineQueryType()
- [API] Symphony::Database()->query()
- [API] Symphony::Database()->fetch()
- [API] Symphony::Database()->fetchRow()
- [API] Symphony::Database()->fetchCol()
- [API] Symphony::Database()->fetchVar()
- [API] EntryManager::fetch()
- [API] EntryManager::fetchCount()
- [API] EntryManager::fetchByPage()
- [API] EntryManager::setFetchSortingDirection()
- [API] EntryManager::setFetchSortingField()
- [API] EntryManager::setFetchSorting()
- [API] EntryManager::getFetchSorting()
- [API] Field::isFilterRegex()
- [API] Field::buildRegexSQL()
- [API] Field::isFilterSQL()
- [API] Field::buildFilterSQL()
- [API] Field::buildDSRetrievalSQL()
- [API] Field::isRandomOrder()
- [API] Field::buildSortingSQL()
- [API] Field::buildSortingSelectSQL()
- [API] FieldManager::fetch()
- [API] XMLElement::convertFromXMLString()
- [API] XMLElement::convertFromDOMDocument()
- [API] PageManager::fetch()
- [API] SectionManager::fetch()
- [API] FieldDate::SIMPLE
- [API] FieldDate::REGEXP
- [API] FieldDate::RANGE
- [API] FieldDate::ERROR
- [API] FieldDate::parseDate()
- [API] FieldDate::isEqualTo
- [API] FieldDate::parseFilter
- [API] FieldDate::parseFilter
- [API] FieldDate::buildRangeFilterSQL
