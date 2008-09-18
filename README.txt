
Symphony 2 Beta
------------------------------------

Version: 2.0 Beta Revision 5
Build Date: 18th March 2008
Change Log: http://beta.overture21.com/forum/comments.php?DiscussionID=114


[INSTALLATION]

## TODO: Instructions for checking out of GitHub

** See further down for updating instructions **

1. Upload the following files and directories to the root directory of your
   website:

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

1. Backup /symphony/.htaccess

2. Replace /symphony, update.php & index.php with those contained 
   in this archive.

3. Put the backed up .htaccess file into the new /symphony folder

4. If necessary, run the following SQL ensuring to change tbl_ to 
   match your table prefix. This will get your database up to date.


	UPDATING FROM REVISION 1:
	-----------------

		ALTER TABLE `tbl_sections` 
		CHANGE `entry_order_direction` `entry_order_direction` 
		ENUM('asc', 'desc') NULL DEFAULT 'asc',
		CHANGE `hidden` `hidden` ENUM('yes', 'no') DEFAULT 'no';
	
	
	UPDATING FROM REVISION 2 OR EARLIER:
	-----------------

		ALTER TABLE `tbl_sections_association` 
		ADD `cascading_deletion` ENUM('yes', 'no') NOT NULL DEFAULT 'no';
	
	
	UPDATING FROM REVISION 4 OR EARLIER:
	-----------------
			
		Browse to http://yousite.com/update.php to ensure your database 
		is updated for Revision 5.	
			
	
5. Dance like it's 1999!
