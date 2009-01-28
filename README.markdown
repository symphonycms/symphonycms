## Symphony 2 ##

- Version: 2.0.1 (build 267)
- Date: 28th January 2009
- Github Repository: <http://github.com/symphony/symphony-2/tree/master>


### SYNOPSIS

Symphony is a `PHP` & `MySQL` based CMS that utilises `XML` and `XSLT` as it's core 
technologies. This repository represents version 2.0.1 and is considered stable.

Visit the forum at <http://overture21.com/forum/>


### UPDATING VIA GIT

If you intend on pulling the latest changes to update your copy of Symphony, be forewarned.
There have been some major structural changes and doing so will remove parts of your 
workspace folder. To get around this, move your workspace folder some place safe, along with
any of the extensions mentioned below in the "INSTALLING FROM GIT" instructions. Once you have
pulled the latest version, move the workspace folder back in place and use the following command
to get those extensions back:

	git submodule init
	git submodule update
	
The workspace folder will no longer be under git version control, which allows the Symphony
team to make alterations to the default theme without risking your ability to pull changes.

Finally, follow from step 4 below.


### UPDATING

Follow the instructions below if you are updating from Symphony version 2.0 (non Git)

1. Backup `/symphony/.htaccess`

2. Upload `/symphony`, `index.php` & `update.php`, replacing what is already on your server.

3. Put the backed up `.htaccess` file into the new /symphony folder

4. Go to `http://yoursite.com/update.php` to complete the update process.

5. Dance like it's 1999!


### INSTALLING FROM GIT

1. Clone the git repository to the location you desire using:

		git clone git://github.com/symphony/symphony-2.git
		
	Should you wish to make contributions back to the project, feel free to fork the
	master tree, instead of cloning, and issue pull requests via github.


	The following repositories are included as submodules:

	- [Maintenance Mode](http://github.com/pointybeard/maintenance_mode)
	- [Select Box Link Field](http://github.com/pointybeard/selectbox_link_field)
	- [Export Ensemble](http://github.com/pointybeard/export_ensemble)
	- [Markdown](http://github.com/pointybeard/markdown)
		

3. Run the following commands to ensure the submodules are cloned:

		git submodule init
		git submodule update
		

4. _(Optional)_ If you would like the [default theme](http://github.com/symphony/workspace/tree) installed as well, 
you will need to use the following command from within the Symphony 2 folder you just created:

		git clone git://github.com/symphony/workspace.git
		

5. Follow from step 2 below


### INSTALLATION

** See further down for updating instructions **

1. This step assumes you downloaded a zip archive from the [Symphony website](http://symphony21.com). 
Upload the following files and directories to the root directory of your website:

	- index.php
	- install.php
	- /symphony
	- /workspace
	- /extensions

   _Alternatively, you can upload the `.zip` archive to the same location and
   run '`unzip`' from the command line._
	
   **Note: You can leave `/workspace` out if you do not want the default theme.**

2. Point your web browser at <http://yourwebsite.com/install.php> and provide
   details for establishing a database connection and about your server
   environment.

3. Celebrate!
