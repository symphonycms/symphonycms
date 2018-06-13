# Installing

Before installation, read the [notes on file permissions](#file-permissions).

* [Via Git](#via-git)
* [Via the old-fashioned way](#via-the-old-fashioned-way)
* [File permissions](#file-permissions)

## Via Git

1. Clone the Symphony Git repository to the desired location:

		git clone git://github.com/symphonycms/symphonycms.git target-directory
		cd target-directory

	(Replace `target-directory` with your chosen new directory name.)

1. Run composer to install the dependencies and generate the auto-loader:

		composer install --no-dev -o

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

1. _(Optional)_ You can also provide the `manifest/unattended.php` file to pre-fill information.
The is an empty unattended.php file [in the code source](https://github.com/symphonycms/symphonycms/blob/master/install/includes/unattend.php).

1. Point your web browser at the `install` subdirectory (e.g., `http://example.com/install/`) and provide details on establishing a database connection and your server environment.

1. Chuckle villainously and tap your fingertips together (or pet a cat) as your installation completes.

1. Remove installer files:

		rm -rf install/ workspace/install.sql

	Make sure you remove them from your remote server also. If not, we may nag you.

## Via the old-fashioned way

1. This step assumes you downloaded a zip archive from the [Symphony website](https://www.getsymphony.com). Upload the following files and directories to the root directory of your website:

	- `index.php`
	- `/extensions`
	- `/install`
	- `/symphony`
	- `/workspace` (leave out if you don’t require the example workspace)
	- `/vendor`

1. _(Optional)_ You can also provide the `manifest/unattended.php` file to pre-fill information.
The is an empty unattended.php file [in the code source](https://github.com/symphonycms/symphonycms/blob/master/install/includes/unattend.php).

1. Point your web browser at the `install` subdirectory (e.g., `http://example.com/install/`) and provide details on establishing a database connection and your server environment.

1. Pose like you’re being filmed for a dramatic close-up while your installation completes.

1. Remove installer files:

		`rm -rf install/ workspace/install.sql`

	Make sure you remove them from your remote server also. If not, we may troll you.

## File permissions

1. Symphony’s installer will inform you if it needs write access to directories that it doesn’t already have, but you can ensure it has the access it needs by temporarily setting the root to world-writable.

	`chmod 777 /your/site/root/`

1. Once Symphony is successfully installed, you should change file/directory permissions to something tighter for security reasons. Symphony recommends `755` for directories and `644` for files as a good default, but this may need to be changed depending on your server’s users and groups configuration. For example, you may need to change directories and files that Symphony needs to subsequently write to to `775` and `664` respectively.

#### Useful commands

You may find these commands useful when adjusting file and directory permissions.

To recursively chmod directories only:

	find /your/site/root -type d -exec chmod 755 {} \;

To recursively chmod files only:

	find /your/site/root -type f -exec chmod 644 {} \;
