# Symphony 2

- Version: 2.3.2
- Date: 24th March 2013
- Release Notes: <http://getsymphony.com/download/releases/version/2.3.2/>
- Github Repository: <http://github.com/symphonycms/symphony-2/tree/2.3.2>



## Contents

* [Overview](#overview)
* [Server requirements](#server-requirements)
* [Installing](#installing)
	* [Via Git](#via-git)
	* [Via the old-fashioned way](#via-the-old-fashioned-way)
	* [File permissions](#file-permissions)
* [Updating from an older version](#updating-from-an-older-version)
	* [Version-specific notes](#version-specific-notes)
	* [Updating via Git](#updating-via-git)
	* [Updating via the old-fashioned way](#updating-via-the-old-fashioned-way)


## Overview

Symphony is a `PHP` & `MySQL` based CMS that utilises `XML` and `XSLT` as its core technologies. This repository represents version `2.3.2 Release Candidate 2` and is considered stable.

Visit [the Symphony website](http://getsymphony.com/), [the forum](http://getsymphony.com/discuss/) or learn how you can [contribute to Symphony](https://github.com/symphonycms/symphony-2/wiki/Contributing-to-Symphony).

## Server requirements

- PHP 5.2 or above (PHP 5.3 recommended)
- PHP’s LibXML module, with the XSLT extension enabled (`--with-xsl`)
- MySQL 5.0 or above
- A webserver (known to be used with Apache, Litespeed, Nginx and Hiawatha)
- Apache’s `mod_rewrite` module or equivalent
- PHP’s built in `json` functions, which are enabled by default in PHP 5.2 and above; if they are missing, ensure PHP wasn’t compiled with `--disable-json`

### A note for Windows developers

While Windows is not officially supported for production, we understand many developers use WAMP for Symphony development before deploying to a production server. The Symphony team recommends that while using WAMP, developers use the latest PHP 5.3.x version during development to minimise any potential issues. PHP 5.3 provides numerous fixes and improvements to help minimise and standardise the result of several functions that behave slightly differently depending on the OS.



## Installing

Before installation, see the [notes on file permissions](#file-permissions).

### Via Git

1. Clone the git repository to the location you desire using:

		git clone git://github.com/symphonycms/symphony-2.git

	The following repositories are included as submodules:

	[Markdown](http://github.com/symphonycms/markdown)  
	[Maintenance Mode](http://github.com/symphonycms/maintenance_mode)  
	[Select Box Link Field](http://github.com/symphonycms/selectbox_link_field)  
	[JIT Image Manipulation](http://github.com/symphonycms/jit_image_manipulation)  
	[Export Ensemble](http://github.com/symphonycms/export_ensemble)  
	[Debug DevKit](http://github.com/symphonycms/debugdevkit/tree/master)  
	[Profile DevKit](http://github.com/symphonycms/profiledevkit/tree/master)  
	[XSS Filter](http://github.com/symphonycms/xssfilter/tree/master)

1. Run the following command to ensure the submodules are cloned:

		git submodule update --init --recursive

1. _(Optional)_ If you would like the [default ensemble](http://github.com/symphonycms/workspace/tree) installed as well,
you will need to use the following command from within the Symphony 2 folder you just created:

		git clone git://github.com/symphonycms/workspace.git

1. Point your web browser at <http://example.com/install/> and provide
details for establishing a database connection and about your server environment.

1. Chuckle villainously and tap your fingertips together (or pet a cat) as your installation completes.

1. Remove installer files:

	`rm -rf install/ workspace/install.sql`

### Via the old-fashioned way

**Note:** You can leave `/workspace` out if you do not want the default theme.

1. This step assumes you downloaded a zip archive from the [Symphony website](http://getsymphony.com). Upload the following files and directories to the root directory of your website:

	`index.php`  
	`/install`  
	`/symphony`  
	`/workspace`  
	`/extensions`

1. Point your web browser at <http://example.com/install/> and provide
details on establishing a database connection and your server environment.

1. Pose like you’re being filmed for a dramatic close-up while your installation completes.

1. Remove installer files:

	`rm -rf install/ workspace/install.sql`

### File permissions

1. Symphony’s installer will inform you if it needs write access to files that it doesn’t already have, but you can ensure it has the access it needs by temporarily setting files to world-writeable.

	`cd /your/site/root`  
	`chmod -R 777 .`

1. Once Symphony is successfully installed, you should change file permissions to something tighter for security reasons. Symphony recommends `755` for directories and `644` for files as a good default, but this may need to be changed depending on your server’s users and groups configuration. For example, you may need to change directories and files that Symphony needs to subsequently write to to `775` and `664` respectively.

#### Useful commands

You may find these commands useful when adjusting file and directory permissions.

To recursively chmod directories only:

	find /your/site/root -type d -exec chmod 755 {} \;

To recursively chmod files only:

	find /your/site/root -type f -exec chmod 644 {} \;



## Updating from an older version

### Version-specific notes

#### Versions prior to 2.3

Symphony `2.3` officially only supports updating from a `2.2.x` release. There are various changes between `2.1` and `2.3` that make this update unlikely to be successful. Symphony `2.3` also enforces that all authors have unique email addresses, so please ensure that this constraint is met before updating.

#### Versions prior to 2.2

Symphony `2.2` introduces numerous improvements that may affect extension compatibility. Before updating, be sure to consult the [extension compatibility table](http://getsymphony.com/download/extensions/compatibility/) to verify that the extensions you’re using have all been updated for Symphony `2.2`.

#### Versions prior to 2.1

As of version `2.1`, Symphony stores passwords using the more secure [SHA1](http://php.net/sha1) algorithm (previous versions used MD5). When updating to `2.1`, the primary user’s login password will be reset (the new password will be displayed by the updater—please note it).

Please also note that all other users’ passwords will no longer be valid and will require a manual reset through Symphony’s forgotten password feature. Alternatively, as an administrator, you can also change your users’ passwords on their behalf.

We are now using [GitHub’s organisations feature](http://github.com/blog/674-introducing-organizations). As a result, all submodules—as well as the main Symphony 2 repo—are forks owned by the [Symphony CMS organisation](http://github.com/symphonycms/).

To fully update your Git-based installation, please edit your `.git/config` and the `.git/config` of each core extension (`debugdevkit`, `profiledevkit`, `markdown`, `maintenance_mode`, `selectbox_link_field`, `jit_image_manipulation` and `export_ensemble`) and change the URL of the remote repo from `symphony` or `pointybeard` to be `symphonycms`.

For example:

	[remote "origin"]
		fetch = +refs/heads/*:refs/remotes/origin/*
		url = git://github.com/pointybeard/markdown.git

Change `git://github.com/pointybeard/markdown.git` to `git://github.com/symphonycms/markdown.git`

#### Versions prior to 2.0.5

Version `2.0.5` introduced multiple includable elements in the Data Source Editor for a single field. After updating from `2.0.5` or lower, the DS editor will seem to “forget” about any `Textarea` fields selected when you are editing existing Data Sources. After updating, you must ensure you re-select them before saving. Note, this will only effect Data Sources that you edit and were created prior to `2.0.5`. Until that point, the field will still be included in any front-end XML.

### Updating via Git

1. Pull from the master branch at `git://github.com/symphonycms/symphony-2.git`

1. Use the following command to bring extensions up to date:

		git submodule update --init --recursive

1. If updating from a version older than `2.0.5`, enable the [Debug DevKit](http://github.com/symphonycms/debugdevkit/tree/master) and [Profile DevKit](http://github.com/symphonycms/profiledevkit/tree/master) extensions.

1. Go to `http://example.com/install/` to complete the update process.

1. You and your website are now in the future. Buy yourself a silver jumpsuit.

### Updating via the old-fashioned way

Follow the instructions below if you are updating from Symphony 2.0 (not from Git)

**Note:** As of 2.0.6, there is no longer a need to backup `/symphony/.htaccess`.

1. Upload `/symphony`, `/install` & `index.php`, replacing what is already on your server.

1. If you are updating from a version older than 2.0.5, download and install the Debug DevKit and Profile DevKit:

	[Debug DevKit](http://github.com/symphonycms/debugdevkit/tree/master)  
	[Profile DevKit](http://github.com/symphonycms/profiledevkit/tree/master)

1. Go to `http://example.com/install/` to complete the update process.

1. Call a friend and brag that your copy of Symphony is newer than theirs.