Symphony 2
------------------------------------

Version: 2.0
Status: Stable
Build Date: 8th December 2008
Github Repository: http://github.com/symphony/symphony-2/tree/master


[SYNOPSIS]

Symphony is a PHP & MySQL based CMS that utilises XML and XSLT as it's core 
technologies. This repository represents version 2.0 and is considered stable.

Visit the forum at http://overture21.com/forum/ 


[INSTALLING FROM GIT]

1. Clone the git repository to the location you desire using:

		git clone git://github.com/symphony/symphony-2.git
		
	Should you wish to make contributions back to the project, feel free to fork the
	master tree, instead of cloning, and issue pull requests via github.


	The following repositories are included as submodules:

		- Maintenance Mode: http://github.com/pointybeard/maintenance_mode/tree
		
		- Select Box Link Field: https://github.com/pointybeard/selectbox_link_field/tree
		
		- Export Ensemble: http://github.com/pointybeard/export_ensemble/tree
		
		- Markdown: https://github.com/pointybeard/markdown/tree
		
		- Default Workspace: http://github.com/symphony/workspace/tree


2. Follow from step 2 below



[INSTALLATION]

** See further down for updating instructions **

1. This step assumes you downloaded a zip archive from the Symphony website 
   (http://symphony21.com). Upload the following files and 
   directories to the root directory of your website:

     - index.php
     - install.php
     - /symphony
     - /workspace
     - /extensions

   (Alternatively, you can upload the .zip archive to the same location and
   run 'unzip' from the command line.)
	
   Note: You can leave /workspace out if you do not want the default theme.

2. Point your web browser at http://yourwebsite.com/install.php and provide
   details for establishing a database connection and about your server
   environment.

3. Celebrate!



[UPDATING]

Updating requires you are running Symphony 2 Beta, revision 5.

1. Backup /symphony/.htaccess and /symphony/lib/toolkit/fields/field.sectionlink.php

2. Replace /symphony, index.php with those contained in this archive.

3. Put the backed up .htaccess file into the new /symphony folder

4. Put the backed up fields.sectionlink.php file into /symphony/lib/toolkit/fields

5. If necessary, edit /manifest/config.php, changing any build numbers to 2000 E.G.

	$settings['symphony']['build'] = '2000';
	$settings['general']['useragent'] = 'Symphony/2000';

6. Dance like it's 1999!

