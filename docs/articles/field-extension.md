---
id: field-extension
title: Field Extension
layout: docs
---

## An Overview

Symphony extension development has always been seen with an aura of 'dark arts' surrounding it, and now that Symphony 2.2 is on our doorstep, it's a great time to take some steps to demystify this art. The purpose of this walkthrough is to provide an insight into how I built my [Meta Keys](http://github.com/brendo/field_metakeys) extension. For those who haven't used it, the Meta Keys field allows a user to save key/value information against an Entry, without having to follow a defined schema. This walkthrough assumes you have an understanding of Symphony concepts and feel comfortable writing PHP.

## Getting Started

### Directory Structure

[![Extension Directory Structure](http://farm6.static.flickr.com/5298/5423332863_2218f68335.jpg)](http://www.flickr.com/photos/bloodbone/5423332863/)

The basis of any Symphony extension requires a certain directory structure to enable your extension to be discovered, and loaded by the Extension Manager. This absolute minimum for an extension is a folder, `field_metakeys`, with a single `extension.driver.php` file inside it. As this is a Field extension, I have then created a new `fields` directory containing a single file, `field.metakeys.php` in my extension root. All fields in Symphony live in `fields` folders and follow a `field.{handle}.php` convention. Naming conventions are everywhere in Symphony and some others that you may be familiar with is `events` and `event.{handle}.php`, or `data-sources` and `data.{handle}.php`. These all serve as a way for the Symphony Managers to load objects from the filesystem.

## `extension.driver.php`

The extension driver contains your extension class that extends the Symphony base `Extension` class. It is vital that the name of your class starts with `extension_` followed by **the same handle as your extension directory**. So in Meta Key's case, this results in [`extension_field_metakeys`](https://github.com/brendo/field_metakeys/blob/master/extension.driver.php#L3). The [base `extension` class](https://github.com/symphonycms/symphony-2/blob/integration/symphony/lib/toolkit/class.extension.php) is probably the best place to start to get an idea of what functions your extension driver should contain. 

Generally, [`about()`](https://github.com/symphonycms/symphony-2/blob/integration/symphony/lib/toolkit/class.extension.php#L121-138) is the first to be implemented and as the PHPDoc comments suggest, the purpose is to provide meta data about your extension such as the name, version, release data and author information. Symphony 2.2 allows you to provide multiple authors in your `about()` function by having an array of Author arrays. The [Symphony website](http://symphony-cms.com/download/extensions/) allows Extensions to be searched by particular types, so it has become a bit of a pseudo convention for developers to create a comma separated list of these types for their [extension](https://github.com/brendo/field_metakeys/blob/master/extension.driver.php#L9).

[![About Definition](http://farm6.static.flickr.com/5252/5423935812_c6738295af.jpg)](http://www.flickr.com/photos/bloodbone/5423935812/)

### Installation

The next functions of your `extension.driver.php` relate to the installation, updating and removal of your extension. For a Field extension, the installation function allows you to create a table schema to store any additional Symphony settings for your Field. There is a set of default Symphony settings, which are stored in the `tbl_fields` table that all Fields will inherit (such as 'required', 'show_column' etc.), but you may wish to provide some additional settings for your Field. These settings can be customised by a developer when they create an instance of your Field in the Section Editor. 

Your settings table should follow the convention of a Field extension, `tbl_fields_{field-handle}` with the minimum columns of `id` and `field_id`. As a developer adds instances of your Field to their Symphony installation, a new row is created in this table for that instance of your extension (`field_id`) which allows a developer to add your extension to many sections, all with slightly different configurations. Remember, this table only stores setting information, as any entry data is stored in a separate table created by the Field's 'createTable()` function, but I'll get to that later. 

[![Example Installation Query](http://farm6.static.flickr.com/5299/5423382169_d1487602f0.jpg)](http://www.flickr.com/photos/bloodbone/5423382169/)

The `uninstall` function needs to do the opposite to the `install` function, so in this case, drop the table that was just created. Finally, the `update` function can be used to help migrate your extension as you release new versions. Meta Keys is young so at the moment this just returns `true` as it has no migration to perform.

The Meta Keys installation (and uninstallation) queries are wrapped in a `try/catch` block, so that if anything happens, Meta Keys can report the error to the user through a Symphony [`PageAlert`](https://github.com/symphonycms/symphony-2/blob/integration/symphony/lib/toolkit/class.alert.php).

You may notice the prefix `tbl_` on the table definition's. This prefix is an alias (or pseudonym) which is replaced by your actual database’s table prefix at runtime which allows Symphony to live on the same database as another application (even another Symphony install!)

### Assets

Meta Keys requires some custom Javascript and CSS so I've also created an `assets` directory in the extension folder. I only have two assets for Meta Keys, `field_metakeys.publish.js` and `field_metakeys.publish.css`, which follow another [Symphony naming convention](http://symphony-cms.com/discuss/blog/entry/a-guide-to-javascript-for-symphony-22/). This naming convention is purely aesthetics, but it an important one and can help developers when debugging their own extensions and looking for any potential conflicts.


## `field.metakeys.php`

Now for the fun part, defining your Field! I like to break up my extensions field into some logical sections to keep maintenance easy and makes the code readable, even if I'm lazy and omit comments. For Meta Keys, I've used Setup, Utilities, Settings, Input, Output and Filtering as my sections, but your free to use whatever you like.

Similar to the extension driver, your Field class will extend the base `Field` class and requires a [`field{handle}` naming convention](https://github.com/brendo/field_metakeys/blob/master/fields/field.metakeys.php#L5). This convention allows Symphony to load Meta Keys from the filesystem using the `FieldManager`.

### Setup

#### `__construct()`

The [`__construct()`](https://github.com/brendo/field_metakeys/blob/master/fields/field.metakeys.php#L7-15) function is your first chance to add some default settings, using the `set()` function of the Field class. I've set Meta Keys to (by default) not be required (but it has the ability to be), not be shown in the entries table and to default to the sidebar of Entry forms. The values from the `set()` function are stored in `tbl_fields_metakeys`, which we created in the `install()` function in the extension driver, and is executed when a developer enables your extension.

#### `createTable()`

The `createTable()` function is executed when a new instance of your Field is added to a Section and it is responsible for creating the entry data tables for the Field. The table name for the entry data should be `tbl_entries_data_%d`, where `%d` will be the id (`field_id`) of the current Field instance.

This table schema will hold the data that a user enters from the Entry creation page, and the only required columns are `id` and `entry_id`. You may like to take some time add a Field to a Section and inspect what happens in the `tbl_fields` table. It's important that you understand the distinction between your settings table, and the entry data table. Looking at the Field's [default `createTable()` function](https://github.com/symphonycms/symphony-2/blob/integration/symphony/lib/toolkit/class.field.php#1066-1083) may also prove useful.

After defining your entry table schema, it's commonplace to list some functions that act as feature toggles for your Field. For MetaKeys, I've said that it `canFilter()` and `allowDatasourceParamOutput()`, but I've turned off `prePopulate`. A complete listing of these 'features' can be found by reading through the [`Field` class](https://github.com/symphonycms/symphony-2/blob/integration/symphony/lib/toolkit/class.field.php#165-309). For example, you may wish to turn off Sorting for your Field (`isSortable()`), or not allow it to be Grouped (`allowDatasourceOutputGrouping`) or may it unique, aka, one Field per section (`mustBeUnique()`).

### Settings

#### `displaySettingsPanel()`

The Section Editor allows a user to customise your Field for their use in a Section. To do this, you can provide your own HTML through the `displaySettingsPanel()` function. It is recommended to call `parent::displaySettingsPanel()` first, which will build a wrapper for you to add your HTML in. Symphony abstracts HTML using the [`XMLElement`]((https://github.com/symphonycms/symphony-2/blob/integration/symphony/lib/toolkit/class.xmlelement.php) class, which mimics PHP's DOMDocument functionality to a degree. The Symphony [`Widget`](https://github.com/symphonycms/symphony-2/blob/integration/symphony/lib/toolkit/class.widget.php) class provides a set of convenience functions to allow you to build common HTML elements rapidly. 

Symphony has some [CSS conventions](http://symphony-cms.com/discuss/blog/entry/a-guide-to-css-and-layout-changes-in-symphony-22/) that allows your extensions to look as native as possible. Meta Keys makes use of one of the most common conventions, the two column layout using the `group` class, eg. `<div class='group'>` wrapper around two block DOM elements. There is also no need to replicate the logic to build some common setting interfaces, with functions such as `appendRequiredCheckbox()`, `appendShowColumnCheckbox()` and `buildValidationSelect()` all available to use. You can access the default settings of your Field by using `$this->get({setting-name})`. This is especially important for when a developer will come to edit your Field in the Section Editor, as it will prepopulate the field values with the current settings.

Meta Keys doesn't do anything unusual in the settings panel but add an additional input field so that a user can specify some default keys that will populate the Field on new entry creation, and provide a validator so that developers can ensure keys/values are entered that match a particular format. Because these setting's are specific to Meta Keys, it will not be stored in the `tbl_fields` table, and instead will be in my Field's own settings table, `tbl_field_metakeys`. These default keys are saved in the `default_keys` column, with the validator rule saved in `validator`.

#### `commit()`

Following `displaySettingsPanel()` is the `commit()` function, which is responsible for saving the field settings in the Database. Any core Symphony settings will be saved as a new row in the `tbl_fields` table by simply calling the parent commit function, `parent::commit()`. If this is successful, you can use the rest of the `commit()` function to save the custom settings into your settings table, in this case, `tbl_fields_metakeys`. The `field_id` of your Field instance is generated from the `auto_increment` index in the `tbl_fields` table, and is accessible via `$this->get('id')` after the parent `commit()` function is called.

### Input

Now that you've allowed your Field to be added to a Section, you may want to experiment and have a look at the database tables to see the above functions in action. The next focus should be allowing a user to add data into your Field, and how your field is rendered in the Entry Creation form. Enter `displayPublishPanel()`..

#### `displayPublishPanel()`

This function requires you to build out form elements using `XMLElement`. The first parameter of `displayPublishPanel()` is a `XMLElement` wrapper, which by default is `<div class='field field-{field-handle}>`. If the field is marked as required, a `required` class is also added. The `field-handle` is the handlized version of the `$field->name` (which we set in the Field constructor ;)), which is available via `$this->handle()`. These classes allow you to apply CSS only to your field, and not every field in the section. Additional parameters passed to the function include an associative array of the entry `$data`, an `$error` (that have occurred while trying to save), `$prefix` and `$postfix` (which I have no idea about, it seems to stem from early editions of Symphony and is commonly left as `null` these days) and in the instance of an Edit Entry form, the current `$entry_id`.

You may want to have a browse over the [Meta Keys `displayPublishPanel()` function](https://github.com/brendo/field_metakeys/blob/master/fields/field.metakeys.php#L157-197) to get a quick idea of how to interact with `XMLElement` and the `Widget` class. Something to note here is the use of `$this->get('element_name')` which provides a handlized version of the Label of your field (set by the Developer when they added it to the Section) and is used so the form knows what field the data is meant for. You can see this in action in my utility [`buildPair()` function](https://github.com/brendo/field_metakeys/blob/master/fields/field.metakeys.php#L66-85)

To inject the custom CSS and JS for Meta Keys, the `displayPublishPanel()` function calls the [`appendAssets()` function](https://github.com/brendo/field_metakeys/blob/master/extension.driver.php#L69), which is defined as a static utility function in the `extension.driver.php` file. Something to note here is the use of the `$duplicate` parameter with the `addScriptToHead()` and `addStylesheetToHead()` functions. By setting this to `false`, this will prevent Meta Key's assets from being injected into the head for every instance of the field in the current section. You may want to note the explicit check for the presence of the `Administration` class, and that `$Page` is an instance of the `HTMLPage` class before trying to append the resources to the page. We can only add Javascript and CSS in the Administration context (and it's only possible in this context as the `addScriptToHead()` and `addStylesheetToHead()` functions don't exist in the Frontend context), hence the check. This is check ensures your function will not throw an error if it is called in the `Frontend` context (Section Schemas does this to generate a representation of your Field for use on Frontend fields). 

#### `checkFieldPostData()`

Once you have displayed your interface, the next thing you'll want to do is check any data that the user tries to save using `checkFieldPostData()`. This function is given an associative array of the data for this field instance only, so essentially `$_POST['fields'][$this->get('element_name')`, to perform validation logic on. If the data passed is correct, this function should return `Field::OK`, otherwise there is a selection of Field constants that can be returned, which will slightly affect the error message returned. The most common are `Field::__MISSING_FIELDS__` or `Field::__INVALID_FIELDS__`. Check the [Field class](https://github.com/symphonycms/symphony-2/blob/integration/symphony/lib/toolkit/class.field.php#15-58) for further error constants that you can use. Meta Keys checks to see if the current instance is required, that data has been received, and if any validator rules have been set before returning Field status.

#### `processRawFieldData()`

Should the data be ok, `processRawFieldData()` gives you the chance to map the `$_POST` data to the Field's database table, (`tbl_entries_data_%d`). While this function is pretty self explanatory, the `$simulate` parameter often is a point of confusion for developers. The idea behind this parameter is to allow your function to 'almost' save the raw data, but not make any lasting changes (ie. doesn't commit the data to the database). Meta Keys doesn't need to simulate anything (and the majority of fields don't), so the parameter is just ignored within the function. The expected result from this function is an associative array, with the key's being the table columns described in the `Field->createTable()` function. Should your field like Meta Keys need to add multiple rows, you can use a multidimensional array, with numerical indexes as shown below.

[![processRawFieldData](http://farm6.static.flickr.com/5300/5423557575_426a4afe0a.jpg)](http://www.flickr.com/photos/bloodbone/5423557575/)

#### `getExampleFormMarkup()`

The last function for this block, `getExampleFormMarkup()` is a way for you to provide some documentation about how the markup for Frontend forms should be created if a user wants an Event to populate your field. You can add anything in here, using the `XMLElement` class, which will be shown to a user when they save their Event in the backend.

### Output

So the user has saved some data for your extension, now it's up to you to format it so that it can be useful on the Frontend of the site!

#### `fetchIncludableElements()`

Some field extensions can offer multiple ways for the data to be formatted in a datasource. This concept is known as 'output modes', and is usually shown by `Field Name : {mode}`. The `fetchIncludableElements()` function allows your extension to show the different modes that is has to offer. Meta Keys offers two modes for output, so this function returns an array of `$this->get('element-name')` and `$this->get('element-name'): named-keys`.

[![Included Elements](http://farm6.static.flickr.com/5299/5424213638_8db5311caf.jpg)](http://www.flickr.com/photos/bloodbone/5424213638/)

#### `appendFormattedElement()`

The `appendFormattedElement()` is called every time your Field is included in a Datasource and is executed on the Frontend. It is given a `$wrapper` parameter, which is an XMLElement for this datasource, the `$data` for this datasource, a boolean `$encode` parameter, the `$mode` and the current `$entry_id`. It's important to note that `$data` contains data for a single entry, and that this function is called `x` times for the number of entries in the datasource. The `$mode` parameter will be the string after the `:` from `fetchIncludableElements()`. In the case where your Field only provides one output mode, this will be `null`. Meta Keys uses the `$mode` parameter to switch between the two output modes. Below is an example of the two output modes that Meta Keys provides.

[![Two Output Modes](http://farm6.static.flickr.com/5012/5423612695_8f5c4bd6db.jpg)](http://www.flickr.com/photos/bloodbone/5423612695/)

#### `getParameterPoolValue()`

Almost there! `getParameterPoolValue()` allows you to format your entry specifically for use as a Datasource Output param. It is given an array of data for the entire datasource as an associative array that represents your table schema (from the entry table, `tbl_entries_data_%d`). At this stage, Meta Keys is just going to output the keys that are used in entry. The only real thing to note here is that a string must be returned.

[![Example array for getParameterPoolValue](http://farm6.static.flickr.com/5060/5423654631_73f7830968.jpg)](http://www.flickr.com/photos/bloodbone/5423654631/)

#### `prepareTableValue()`

`prepareTableValue()` is used in the Symphony backend context only. The result of this function is what will be displayed on the entries table should your field be shown (Show Column in the Section Editor). Meta Keys makes use of `parent::prepareTableValue()` to do most of the heavy lifting here. This function differs from `getParameterPoolValue()` in that the `$data` array is for one entry, not every entry otherwise the same logic applies in that a string of data needs to be created to be passed onto the parent function. The parent function makes use of Symphonys `cell_truncation_length` value in the `config.php`, which extracts a certain number of characters from your value. By default, Symphony installs with this is set to 75. 

The `$link` parameter will contain an `XMLElement` wrapper, which allows a user to edit the current entry, should your Field be the first visible column in the entries table, otherwise it will default to null.

### Filtering

The final function in the Meta Keys field class is `buildDSRetrivalSQL()` and this one is responsible for generating the SQL to filter the entries for a datasource. The `$data` parameter is an array of the filters for your Field. Meta Keys allows developers to filter the field with a couple of prefixes, `value:` and `key-equals`, so the logic looks for the filters to start with either of these, before falling back to a generic filter SQL. For Meta Keys, the `value` prefix allows a developer to filter by the value of a Key, ie. `value: red` will get any entries where one of the Meta Keys contains the value 'red'. `key-equals` is more specific, allowing a developer to specify that Key and the Value that should be filtered on, ie. `key-equals: colour=red`. No prefix just falls back to looking for any `key` that is `red`.

To build the SQL required, this function is given `$joins` and `$where` parameters by reference, which allow you to add on your SQL. The goal of this SQL is to return the entry id's of that match all the datasource filters, so your join will be on `tbl_entries`, which has been prefixed to `e`.

<code>
	$joins .= "
		LEFT JOIN
			`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
		ON
			(e.id = t{$field_id}_{$this->_key}.entry_id)
	";
</code>

Your SQL should take into account the fact that there maybe multiple filters on this field, so using `$this->_key` will allow a new table alias to be created everytime this occurs. `$this->key` is a integer, and is generally incremented each time the `buildDSRetrivalSQL()` function is called. Should you wish to sanitize the filters, and you should, the Field class provides the `cleanValue()` function to do that :)

## Additional Notes

### Github Repository naming 
Occasionally, people will run into some problems when cloning extensions from github because the repository is named slightly differently to the extension's directory. If your github repository is named differently to your extension directory, a default clone will checkout the repo name, which will cause the extension to not be loaded, and the user to have to manually rename the directory to match the extension driver's class. This is necessary because Symphony loads some objects from the filesystem which relys on the following naming conventions:

* Repo: **field_metakeys**
* Folder: **field_metakeys**
* Extension Driver: extension_**field_metakeys**

### Why don't I use `Symphony::Engine()`?

Symphony 2.2 introduces `Symphony::Engine()`, which automatically provides an instance of the currently available context, whether that be Frontend or Administration. In the `appendAssets()` function, one might considered updating their code to use this new accessor, however in this situation, it is not possible to inject CSS and JS into the Frontend of a Symphony installation (imagine if that happened?!), so extension developers should always check explicitly for the Administration instance. [This article](http://symphony-cms.com/discuss/blog/entry/a-guide-to-accessor-changes-in-symphony-22/) provides some detailed information about some accessor changes in Symphony 2.2.

### How to access the Symphony Database?

The correct way for any extension to access the Symphony Database instance is through the accessor `Symphony::Database()`. It has been like this since Symphony 2.0.6.

## That's it!

That's it for Meta Keys, I hope that's helped clean up some confusion and unravel a bit of mystery of Symphony Fields and extensions. There are still many functions left for you to discover that Meta Keys doesn't need, such as Sorting and Grouping, but I'll leave that as an exercise for you :) The Symphony 2.2 codebase is now fully documented using the PHPDoc syntax, so by all means, go forth, discover and learn!
