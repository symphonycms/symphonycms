# Symphony 2

[![Join the chat at https://gitter.im/symphonycms/symphony-2](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/symphonycms/symphony-2?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/symphonycms/symphony-2/badges/quality-score.png?b=integration)](https://scrutinizer-ci.com/g/symphonycms/symphony-2/?branch=integration)

- Version: 3.0.0-alpha.1
- Date: unreleased
- [Release notes](http://getsymphony.com/download/releases/version/3.0.0/)
- [Github repository](https://github.com/symphonycms/symphony-2/tree/integration)
- [MIT Licence](https://github.com/symphonycms/symphony-2/blob/master/LICENCE)

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

Symphony is a `PHP` & `MySQL` based CMS that utilises `XML` and `XSLT` as its core technologies. This repository represents version `3.0.0-alpha.1` and is considered unstable. Do not use this in production.

Useful places:

- [The Symphony website](http://getsymphony.com/)
- [The Symphony forum](http://getsymphony.com/discuss/)
- [Symphony Extensions](http://symphonyextensions.com/)
- [Contributing to Symphony](https://github.com/symphonycms/symphony-2/wiki/Contributing-to-Symphony)

## Server requirements

- PHP 5.5.9 or above
- PHP’s LibXML module, with the XSLT extension enabled (`--with-xsl`)
- PHP’s built in `json` functions, which are enabled by default in PHP 5.2 and above; if they are missing, ensure PHP wasn’t compiled with `--disable-json`
- [Composer](https://getcomposer.org/)
- MySQL 5.5 or above is recommended
- A webserver (known to be used with Apache, Litespeed, Nginx and Hiawatha)
- Apache’s `mod_rewrite` module or equivalent

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

1. Initalise composer which will pull in dependencies and generate the autoloader:

		composer install

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

1. Initalise composer which will pull in dependencies and generate the autoloader:

		composer install

1. Point your web browser at the `install` subdirectory (e.g., `http://example.com/install/`) and provide details on establishing a database connection and your server environment.

1. Pose like you’re being filmed for a dramatic close-up while your installation completes.

1. Remove installer files:

		rm -rf install/ workspace/install.sql

### File permissions

1. Symphony’s installer will inform you if it needs write access to directories that it doesn’t already have, but you can ensure it has the access it needs by temporarily setting the root to world-writeable.

		chmod 777 /your/site/root/

1. Once Symphony is successfully installed, you should change file/directory permissions to something tighter for security reasons. Symphony recommends `755` for directories and `644` for files as a good default, but this may need to be changed depending on your server’s users and groups configuration. For example, you may need to change directories and files that Symphony needs to subsequently write to to `777` and `666` respectively.

#### Useful commands

You may find these commands useful when adjusting file and directory permissions.

To recursively chmod directories only:

	find /your/site/root -type d -exec chmod 755 {} \;

To recursively chmod files only:

	find /your/site/root -type f -exec chmod 644 {} \;

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
