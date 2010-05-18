# Symphony 2 #

- Version: 2.0.8 Release Candidate 3
- Date: 19th May 2010
- Release Notes: Please see commit log on github
- Github Repository: <http://github.com/symphony/symphony-2/tree/2.0.8RC3>


## Synopsis

Symphony is a `PHP` & `MySQL` based CMS that utilises `XML` and `XSLT` as 
its core technologies. This repository represents version "2.0.8 RC3" and is 
considered stable.

Visit the forum at <http://symphony-cms.com/community/>

### Symphony Server Requirements

- PHP 5.2 or above
- PHP's LibXML module, with the XSLT extension enabled (--with-xsl)
- MySQL 4.1 or above
- An Apache or Litespeed webserver
- Apache's mod_rewrite module or equivalent

## Updating From an Older Version

### Important Information

Version `2.0.5` introduced multiple includable elements, in the Data Source 
Editor, for a single field. After updating from `2.0.5` or lower, the DS 
editor will seem to "forget" about any `Textarea` fields selected when you 
are editing existing Data Sources. After updating, you must ensure you 
re-select them before saving. Note, this will only effect Data Sources that 
you edit and were created prior to `2.0.5`. Until that point, the field will 
still be included in any front-end XML

### Via Git

1. Pull from the master branch at `git://github.com/symphony/symphony-2.git`

2. Use the following command to get Extensions up to date:

	git submodule init
	git submodule update

3. If updating from a version older than `2.0.5`, enable [Debug DevKit](http://github.com/symphony/debugdevkit/tree/master) and [Profile DevKit](http://github.com/symphony/profiledevkit/tree/master) extensions.

4. Follow normal updating procedure below from step 2.

### Via the old fashioned way

Follow the instructions below if you are updating from Symphony version 2.0 (not from Git)

**Note:** As of 2.0.6, there is no longer a need to backup `/symphony/.htaccess`.

1. Upload `/symphony`, `index.php` & `update.php`, replacing what is already on your server.

2. If you are updating from a version older than 2.0.5, download and install the Debug DevKit and Profile DevKit:

	- [Debug DevKit](http://github.com/symphony/debugdevkit/tree/master)
	- [Profile DevKit](http://github.com/symphony/profiledevkit/tree/master)

3. Go to `http://yoursite.com/update.php` to complete the update process.

4. Celebrate by shaving your friend's head for charity!


## Installing Symphony

### Via Git

1. Clone the git repository to the location you desire using:

		git clone git://github.com/symphony/symphony-2.git
		
	Should you wish to make contributions back to the project, fork the master tree rather than cloning, and issue pull requests via github.

	The following repositories are included as submodules:

	- [Markdown](http://github.com/pointybeard/markdown)
	- [Maintenance Mode](http://github.com/pointybeard/maintenance_mode)
	- [Select Box Link Field](http://github.com/pointybeard/selectbox_link_field)
	- [JIT Image Manipulation](http://github.com/pointybeard/jit_image_manipulation)
	- [Export Ensemble](http://github.com/pointybeard/export_ensemble)
	- [Debug DevKit](http://github.com/symphony/debugdevkit/tree/master)
	- [Profile DevKit](http://github.com/symphony/profiledevkit/tree/master)

3. Run the following command to ensure the submodules are cloned:

		git submodule update --init

4. _(Optional)_ If you would like the [default theme](http://github.com/symphony/workspace/tree) installed as well, 
you will need to use the following command from within the Symphony 2 folder you just created:

		git clone git://github.com/symphony/workspace.git
		
5. Follow normal installation procedure below from step 2.


### Via the old fashioned way

**Note: You can leave `/workspace` out if you do not want the default theme.**

1. This step assumes you downloaded a zip archive from the [Symphony website](http://symphony-cms.com). 
Upload the following files and directories to the root directory of your website:

	- index.php
	- install.php
	- install.sql
	- /symphony
	- /workspace
	- /extensions

2. Point your web browser at <http://yourwebsite.com/install.php> and provide
details for establishing a database connection and about your server environment.

3. Jump with both arms up like you're in a car commercial!


## Security

**Secure Production Sites: Change permissions and remove installer files.**

1. For a smooth install process, change permissions for the `root` and `workspace` directories.

	cd /your/site/root
	chmod -R 777 workspace

2. Once successfully installed, change permissions as per your server preferences, E.G.

	chmod 755 .

3. Remove installer files (unless you're fine with revealing all your trade secrets):

	rm install.php install.sql workspace/install.sql update.php

4. Dance like it's 1999!