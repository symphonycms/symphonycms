<?php

	Class migration_205 extends Migration{

		static function upgrade(){

			// Rebuild the .htaccess here
			$rewrite_base = trim(dirname($_SERVER['PHP_SELF']), DIRECTORY_SEPARATOR);

			if(strlen($rewrite_base) > 0){
				$rewrite_base .= '/';
			}

			$htaccess = '
### Symphony 2.2.x ###
Options +FollowSymlinks -Indexes

<IfModule mod_rewrite.c>

	RewriteEngine on
	RewriteBase /'.$rewrite_base.'

	### SECURITY - Protect crucial files
	RewriteRule ^manifest/(.*)$ - [F]
	RewriteRule ^workspace/utilities/(.*).xsl$ - [F]
	RewriteRule ^workspace/pages/(.*).xsl$ - [F]
	RewriteRule ^(.*).sql$ - [F]

	### DO NOT APPLY RULES WHEN REQUESTING "favicon.ico"
	RewriteCond %{REQUEST_FILENAME} favicon.ico [NC]
	RewriteRule .* - [S=14]

	### IMAGE RULES
	RewriteRule ^image\/(.+\.(jpg|gif|jpeg|png|bmp))$ extensions/jit_image_manipulation/lib/image.php?param=$1 [L,NC]

	### CHECK FOR TRAILING SLASH - Will ignore files
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_URI} !/$
	RewriteCond %{REQUEST_URI} !(.*)/$
	RewriteRule ^(.*)$ $1/ [L,R=301]

	### URL Correction
	RewriteRule ^(symphony/)?index.php(/.*/?) $1$2 [NC]

	### ADMIN REWRITE
	RewriteRule ^symphony\/?$ index.php?mode=administration&%{QUERY_STRING} [NC,L]

	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^symphony(\/(.*\/?))?$ index.php?symphony-page=$1&mode=administration&%{QUERY_STRING}	[NC,L]

	### FRONTEND REWRITE - Will ignore files and folders
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*\/?)$ index.php?symphony-page=$1&%{QUERY_STRING}	[L]

</IfModule>
######
';

			file_put_contents(DOCROOT . '/.htaccess', $htaccess);

			// No longer need symphony/.htaccess
			if(file_exists(DOCROOT . '/symphony/.htaccess') && is_writable(DOCROOT . '/symphony/.htaccess')){
				unlink(DOCROOT . '/symphony/.htaccess');
			}

		}

		static function post_notes(){
			$notes = array();

			if(file_exists(DOCROOT . '/symphony/.htaccess')){
				$notes[] = (
					'<strong>' . __('WARNING') . '</strong>' . __('The updater tried, but failed, to remove the file %s. It is vitally important that this file be removed, otherwise the administration area will not function. If you have customisations to this file, you should be able to just remove the Symphony related block, but there are no guarantees.', array('<code>symphony/.htaccess</code>'))
				);
			}

			$notes = array_merge($notes, array(
				__('Version %1$s introduced multiple includable elements, in the Data Source Editor, for a single field. After updating from %1$ or lower, the DS editor will seem to forget about any %2$s fields selected when you are editing existing Data Sources. After updating, you must ensure you re-select them before saving.', array('<code>2.0.5</code>', '<code>' . __('Textarea') . '</code>'))
				. ' <strong>' . __('Note, this will only effect Data Sources that you edit and were created prior to %s.', array('<code>2.0.5</code>')) . '</strong> '
				. __('Until that point, the field will still be included in any front-end XML'),

				__('As of %1$s, Symphony comes pre-packaged with the ‘Debug Dev Kit’ and ‘Profile Dev Kit’ extensions, which replace the built-in functionality. Prior to using them, you must ensure the folder %2$s is writable by PHP.', array('<code>2.0.5</code>', '<code>extensions/debugdevkit/lib/bitter/caches/</code>'))
			));

			return $notes;
		}

	}
