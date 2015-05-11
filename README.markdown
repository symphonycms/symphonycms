# Symphony 2

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/symphonycms/symphony-2/badges/quality-score.png?b=integration)](https://scrutinizer-ci.com/g/symphonycms/symphony-2/?branch=master)

- Version: 2.6.2
- Date: 11th May 2015
- [Release notes](http://getsymphony.com/download/releases/version/2.6.2/)
- [Github repository](https://github.com/symphonycms/symphony-2/tree/2.6.2)

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

Symphony is a `PHP` & `MySQL` based CMS that utilises `XML` and `XSLT` as its core technologies. This repository represents version `2.6.2` and is considered stable.

Useful places:

- [The Symphony website](http://getsymphony.com/)
- [The Symphony forum](http://getsymphony.com/discuss/)
- [Symphony Extensions](http://symphonyextensions.com/)
- [Contributing to Symphony](https://github.com/symphonycms/symphony-2/wiki/Contributing-to-Symphony)

## Server requirements

- PHP 5.3 or above
- PHP’s LibXML module, with the XSLT extension enabled (`--with-xsl`)
- MySQL 5.5 or above is recommended
- A webserver (known to be used with Apache, Litespeed, Nginx and Hiawatha)
- Apache’s `mod_rewrite` module or equivalent
- PHP’s built in `json` functions, which are enabled by default in PHP 5.2 and above; if they are missing, ensure PHP wasn’t compiled with `--disable-json`


## Installing

Before installation, see the [notes on file permissions](#file-permissions).

### Via Git

1. Clone the Symphony Git repository to the desired location:

		git clone git://github.com/symphonycms/symphony-2.git target-directory
		cd target-directory

	(Replace `target-directory` with your chosen new directory name.)

1.	_(Optional)_ If you would like to add the bundled optional extensions, run the following commands to checkout the `bundle` branch which contains the Git submodules references and update the submodules:

		git checkout --track origin/bundle
		git submodule update --init --recursive

	The extensions included in the optional bundle:

	- [Markdown](https://github.com/symphonycms/markdown)
	- [Maintenance Mode](https://github.com/symphonycms/maintenance_mode)
	- [Select Box Link Field](https://github.com/symphonycms/selectbox_link_field)
	- [JIT Image Manipulation](https://github.com/symphonycms/jit_image_manipulation)
	- [Export Ensemble](https://github.com/symphonycms/export_ensemble)
	- [Debug DevKit](https://github.com/symphonycms/debugdevkit)
	- [Profile DevKit](https://github.com/symphonycms/profiledevkit)
	- [XSS Filter](https://github.com/symphonycms/xssfilter)

1. _(Optional)_ If you would like to install the [example workspace](https://github.com/symphonycms/workspace), which aims to teach newcomers by showcasing basic features and functionalities using a typical blog set-up, run:

		git clone git://github.com/symphonycms/workspace.git

1. Point your web browser at the `install` subdirectory (e.g., `http://example.com/install/`) and provide details on establishing a database connection and your server environment.

1. Chuckle villainously and tap your fingertips together (or pet a cat) as your installation completes.

1. Remove installer files:

		rm -rf install/ workspace/install.sql

### Via the old-fashioned way

1. This step assumes you downloaded a zip archive from the [Symphony website](http://getsymphony.com). Upload the following files and directories to the root directory of your website:

	- `index.php`
	- `/extensions`
	- `/install`
	- `/symphony`
	- `/workspace` (leave out if you don’t require the example workspace)
	- `/vendor`

1. Point your web browser at the `install` subdirectory (e.g., `http://example.com/install/`) and provide details on establishing a database connection and your server environment.

1. Pose like you’re being filmed for a dramatic close-up while your installation completes.

1. Remove installer files:

	`rm -rf install/ workspace/install.sql`

### File permissions

1. Symphony’s installer will inform you if it needs write access to directories that it doesn’t already have, but you can ensure it has the access it needs by temporarily setting the root to world-writeable.

	`chmod 777 /your/site/root/`

1. Once Symphony is successfully installed, you should change file/directory permissions to something tighter for security reasons. Symphony recommends `755` for directories and `644` for files as a good default, but this may need to be changed depending on your server’s users and groups configuration. For example, you may need to change directories and files that Symphony needs to subsequently write to to `777` and `666` respectively.

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

We are now using [GitHub’s organisations feature](https://github.com/blog/674-introducing-organizations). As a result, all submodules—as well as the main Symphony 2 repo—are forks owned by the [Symphony CMS organisation](https://github.com/symphonycms/).

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

1. Use the following two commands to bring extensions up to date:

		git submodule update --init --recursive
		git submodule update --recursive

1. If updating from a version older than `2.0.5`, enable the [Debug DevKit](https://github.com/symphonycms/debugdevkit) and [Profile DevKit](https://github.com/symphonycms/profiledevkit) extensions.

1. Point your web browser at the `install` subdirectory (e.g., `http://example.com/install/`) to complete the update process.

1. You and your website are now in the future. Buy yourself a silver jumpsuit.

### Updating via the old-fashioned way

Follow the instructions below if you are updating from Symphony 2.0 (not from Git)

**Note:** As of 2.0.6, there is no longer a need to backup `/symphony/.htaccess`.

1. Upload `/symphony`, `/install`, `/vendor` & `index.php`, replacing what is already on your server.

1. If updating from a version older than `2.0.5`, enable the [Debug DevKit](https://github.com/symphonycms/debugdevkit) and [Profile DevKit](https://github.com/symphonycms/profiledevkit) extensions.

1. Point your web browser at the `install` subdirectory (e.g., `http://example.com/install/`) to complete the update process.

1. Call a friend and brag that your copy of Symphony is newer than theirs.

## Contributing

Symphony uses [Grunt](http://gruntjs.com/) to build concatenated and minified assets:

1. Install `grunt-cli` globally ([see the Grunt documentation](http://gruntjs.com/getting-started)):

		npm install -g grunt-cli

2. Install all dependencies from the repository’s root:

		npm install

3. Run the `watch` task:

		grunt watch

Symphony’s minified script and style files will be updated automatically when saving source files.

More information: [Contributing to Symphony](https://github.com/symphonycms/symphony-2/wiki/Contributing-to-Symphony).
