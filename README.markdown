## Symphony 2 ##

- Version: 2.0.3 (build 515)
- Date: 24th June 2009
- Github Repository: <http://github.com/symphony/symphony-2/tree/master>


### SYNOPSIS

Symphony is a `PHP` & `MySQL` based CMS that utilises `XML` and `XSLT` as its core 
technologies. This repository represents version 2.0.3 and is considered stable.

Visit the forum at <http://symphony-cms.com/forum/>


### UPDATING VIA GIT

1. Use the following command to get Extensions up to date:

	git submodule init
	git submodule update

2. Follow normal updating procedure below from step 4.


### UPDATING

Follow the instructions below if you are updating from Symphony version 2.0 (non Git)

1. Backup `/symphony/.htaccess`.

2. Upload `/symphony`, `index.php` & `update.php`, replacing what is already on your server.

3. Put the backed-up `.htaccess` file into the new `/symphony` folder.

4. Go to `http://yoursite.com/update.php` to complete the update process.

5. Follow the instruction under "Adding Navigation Group to sections".

6. For those who have an upload field, follow the instructions under "Update Upload Field".

7. Dance like it's 1999!


#### Adding Navigation Group to sections

Be sure to run the following MySQL commands to get the new section navigation group functionality. Change `sym_` to match your table prefix value

	ALTER TABLE  `sym_sections` ADD  `navigation_group` VARCHAR( 50 ) NOT NULL DEFAULT  'Content';
	
	ALTER TABLE  `sym_sections` ADD INDEX (  `navigation_group` ) ;

#### Update Upload Field

Update your corresponding entries_data_xx table with the following:

	ALTER TABLE `tbl_entries_data_XX` CHANGE `mimetype` `mimetype` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL

The table number, 'XX' should be whatever ID of your upload field. If you have more than one upload field, run the above query for each field.


### INSTALLING VIA GIT

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

3. Run the following commands to ensure the submodules are cloned:

		git submodule init
		git submodule update

4. _(Optional)_ If you would like the [default theme](http://github.com/symphony/workspace/tree) installed as well, 
you will need to use the following command from within the Symphony 2 folder you just created:

		git clone git://github.com/symphony/workspace.git
		
5. Follow normal installation procedure below from step 2.


### INSTALLATION

**Note: You can leave `/workspace` out if you do not want the default theme.**

1. This step assumes you downloaded a zip archive from the [Symphony website](http://symphony21.com). 
Upload the following files and directories to the root directory of your website:

	- index.php
	- install.php
	- /symphony
	- /workspace
	- /extensions

2. Point your web browser at <http://yourwebsite.com/install.php> and provide
details for establishing a database connection and about your server environment.

3. Celebrate!


### SECURITY

**Secure Production Sites: Change permissions and remove installer files.**

1. For a smooth install process, change permissions for the `root`, `symphony` and `workspace` directories.

	cd /your/site/root
	chmod 777 symphony .
	chmod -R 777 workspace

2. Once successfully installed, change permissions as per your server preferences:

	chmod 755 symphony .

3. Remove installer files (unless you're fine with revealing all your trade secrets):

	rm install.php install.sql workspace/install.sql update.php

4. Dance again like it's 1999!
