# Updating from an older version to 2.7.x LTS

* [Version-specific notes](#version-specific-notes)
* [Updating via Git](#updating-via-git)
* [Updating via the old-fashioned way](#updating-via-the-old-fashioned-way)

## Version-specific notes

### Versions prior to 2.3

Symphony `2.3` officially only supports updating from a `2.2.x` release. There are various changes between `2.1` and `2.3` that make this update unlikely to be successful. Symphony `2.3` also enforces that all authors have unique email addresses, so please ensure that this constraint is met before updating.

### Versions prior to 2.2

Symphony `2.2` introduces numerous improvements that may affect extension compatibility. Before updating, be sure to consult the [extension compatibility table](https://www.getsymphony.com/download/extensions/compatibility/) to verify that the extensions you’re using have all been updated for Symphony `2.2`.

### Versions prior to 2.1

As of version `2.1`, Symphony stores passwords using the more secure [SHA1](http://php.net/sha1) algorithm (previous versions used MD5). When updating to `2.1`, the primary user’s login password will be reset (the new password will be displayed by the updater—please note it).

Please also note that all other users’ passwords will no longer be valid and will require a manual reset through Symphony’s forgotten password feature. Alternatively, as an administrator, you can also change your users’ passwords on their behalf.

We are now using [GitHub’s organisations feature](https://github.com/blog/674-introducing-organizations). As a result, all submodules—as well as the main Symphony 2 repo—are forks owned by the [Symphony CMS organisation](https://github.com/symphonycms/).

To fully update your Git-based installation, please edit your `.git/config` and the `.git/config` of each core extension (`debugdevkit`, `profiledevkit`, `markdown`, `maintenance_mode`, `selectbox_link_field`, `jit_image_manipulation` and `export_ensemble`) and change the URL of the remote repo from `symphony` or `pointybeard` to be `symphonycms`.

For example:

	[remote "origin"]
		fetch = +refs/heads/*:refs/remotes/origin/*
		url = git://github.com/pointybeard/markdown.git

Change `git://github.com/pointybeard/markdown.git` to `git://github.com/symphonycms/markdown.git`

### Versions prior to 2.0.5

Version `2.0.5` introduced multiple includable elements in the Data Source Editor for a single field. After updating from `2.0.5` or lower, the DS editor will seem to “forget” about any `Textarea` fields selected when you are editing existing Data Sources. After updating, you must ensure you re-select them before saving. Note, this will only effect Data Sources that you edit and were created prior to `2.0.5`. Until that point, the field will still be included in any front-end XML.

## Updating via Git

1. Pull from the `lts` branch at `git://github.com/symphonycms/symphonycms.git`

1. Use the following two commands to bring extensions up to date:

		git submodule update --init --recursive
		git submodule update --recursive

1. If updating from a version older than `2.0.5`, enable the [Debug DevKit](https://github.com/symphonycms/debugdevkit) and [Profile DevKit](https://github.com/symphonycms/profiledevkit) extensions.

1. Point your web browser at the `install` subdirectory (e.g., `http://example.com/install/`) to complete the update process.

1. You and your website are now in the future. Buy yourself a silver jumpsuit.

## Updating via the old-fashioned way

Follow the instructions below if you are updating from Symphony 2.0 (not from Git)

**Note:** As of 2.0.6, there is no longer a need to backup `/symphony/.htaccess`.

1. Upload `/symphony`, `/install`, `/vendor` & `index.php`, replacing what is already on your server.

1. If updating from a version older than `2.0.5`, enable the [Debug DevKit](https://github.com/symphonycms/debugdevkit) and [Profile DevKit](https://github.com/symphonycms/profiledevkit) extensions.

1. Point your web browser at the `install` subdirectory (e.g., `http://example.com/install/`) to complete the update process.

1. Call a friend and brag that your copy of Symphony is newer than theirs.
