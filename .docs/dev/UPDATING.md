# Updating from 2.7.x LTS to 3.0.0

* [Version-specific notes](#version-specific-notes)
* [Updating via Git](#updating-via-git)
* [Updating via the old-fashioned way](#updating-via-the-old-fashioned-way)

## Version-specific notes

### Versions prior to 2.7.0

Symphony `3.0.0` officially only supports updating from a `2.7.x` LTS release.
There are various changes between `2.7.x` and `3.0.0` that make this update impossible.
You first need to update to [2.7.x LTS](UPDATING-LTS.md) before doing this migration.

### Versions 2.7.x LTS

Symphony `3.0.0` introduces numerous improvements that may affect extension compatibility.
Before updating, be sure to consult the [extension compatibility table](https://www.getsymphony.com/download/extensions/compatibility/) to verify that the extensions you’re using have all been updated for Symphony `3.0.0`.

## Updating via Git

1. Pull from the `master` branch at `git://github.com/symphonycms/symphonycms.git`

1. Update all extensions to their latest versions.

1. Delete the vendor directory and run `composer install -o`

1. Point your web browser at the `install` subdirectory (e.g., `http://example.com/install/`) to complete the update process.

1. Manually add those lines to your `.htaccess` file under the security group

```
RewriteRule ^vendor/(.*)$ - [F]
RewriteRule ^extensions/(.+)/vendor/(.*)$ - [F]
```

1. If running on a remote server, make sure all those changes are replicated on the server.

1. You and your website are now in the future. Buy yourself a silver jumpsuit.

## Updating via the old-fashioned way

Follow the instructions below if you are updating not from git.

1. Download the [latest release tar ball](https://github.com/symphonycms/symphonycms/releases).

1. Unzip it and copy the content in your current project.

1. Update all extensions to their latest versions.

1. Delete the vendor directory and run `composer install -o`

1. If you run it on a remote server, upload `/extensions`, `/symphony`, `/install`, `/vendor` & `index.php`, replacing what is already on your server (Ideally, delete the folders/files first).

1. Point your web browser at the `install` subdirectory (e.g., `http://example.com/install/`) to complete the update process.

1. Call a friend and brag that your copy of Symphony is newer than theirs.
