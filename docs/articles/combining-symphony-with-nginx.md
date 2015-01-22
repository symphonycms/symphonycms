---
id: combining-symphony-with-nginx
title: Combining Symphony with Nginx
layout: docs
---

##META
* Doc Version: 110710
* Author: Rowan Lewis
* Applies to: 2.x
* Based on: 2.1.0
* Production URL: http://symphonycms.com/learn/articles/view/combining-symphony-with-nginx/

#Combining Symphony with Nginx

[Nginx](http://wiki.nginx.org/Main) is a powerful, lightweight web server. Today I’ll show you how to rewrite your URLs for a typical Symphony installation.

First, create a new `location` rule in your Nginx configuration. This can be in the main server configuration or in an included configuration file in your web folder. If you didn't configure Nginx yourself, then your hosting provider may provide more detailed instructions on what file to edit.

I’m assuming that your Symphony installation is in a subfolder in your web space, such as `symphony/2.1.0`. If it isn’t, don’t worry, because I cover that later.

Add this line to your configuration:

	location ~ ^(?<path>/symphony/2.1.0)/(?<admin>symphony)?(?<page>.*)$ {

Looks a little confusing, I know, so here’s a quick breakdown:

* `location ~` Create a new location that matches this expression
* `^(?<path>/symphony…)/` Match any URL that starts with /symphony/2.1.0 and assign it to the variable `$path`
* `(?<admin>symphony)?` Optionally match symphony and assign it to the variable `$admin`
* `(?<page>.*)$` Match anything until the end of the URL and assign it to the variable `$page`

Essentially, this matches the basic structure of any Symphony URL and lets us run more tests on `$path`, `$admin` and `$page`.

##Ignore files

Next, prevent Nginx from rewriting files that actually exist in your Symphony installation.  If you don’t do this step,  you won’t be able to load any images, stylesheets or scripts on your website:

	# Allow access to files:
	if (-f $request_filename) {
    	break;
	}

This tells Nginx to stop `(break)` rewriting when the current URL matches a file that exists on the file system (-f).

##Image Manipulation

If you use [Alistair's JIT Image Manipulation extension](http://symphony-cms.com/download/extensions/view/20046/), then you should add the following rule:

	# Just In Time Image Manipulation:
	if ($page ~ ^image/(.+\.(jpg|gif|jpeg|png|bmp))$) {
    	rewrite . $path/extensions/jit_image_manipulation/lib/image.php?param=$1 last;
	}

##Trailing slashes

Some people prefer to force trailing slashes on the and of their URLs, so if you visited `http://yourhost.com/symphony/2.1.0/about` it would automatically send you to `about/`. This is easy enough to do:

	# Add trailing slashes:
	if ($request_filename !~ "/$") {
    	rewrite ^(.*)$ $1/ redirect;
	}

However, if you want to remove trailing slashes, then things get a little more interesting.  Because Nginx doesn’t support nested if statements, or allow testing two conditions in one if statement, we find ourselves doing this:

	# Remove trailing slashes:
	set $test "";

	if ($request_filename ~ "/$") {
    	set $test "${test}o";
	}

	if (!-d $request_filename) {
    	set $test "${test}k";
	}

	if ($test = "ok") {
    	rewrite ^(.*)/$ $1 redirect;
	}

What does this do?

1. Check to see if the current URL has a trailing slash, if it does, then add "`o`" to ``$test``,
2. Check to see if the current URL is not a directory, then add "`k`" to `$test`,
3. Check to make sure `$test` is equal to "`ok`", in which case, redirect to the current URL without a trailing slash.

##Deny access

Symphony stores some potentially sensitive files in the `manifest` folder, and you don't want anything inside of it to be publicly visible:

	# Block access to manifest:
	if ($page ~ "^manifest/") {
    	return 403;
	}
Yes, that matches `$path` when it starts with `manifest/` and returns a 403 Forbidden message to the browser.

##The main rewrite

Now all that’s left to do is send `$page` to Symphony:

	# Access Symphony backend:
	if ($admin) {
    	rewrite . $path/index.php?mode=administration&symphony-page=$page last;
	}

	# Access Symphony frontend:
	if ($page) {
    	rewrite . $path/index.php?symphony-page=$page last;
	}

In these two rewrites, we don’t actually care about matching anything, as all of the information we need is in the `$path`, `$admin` and `$page` varables.

So, what does the entire `location` look like?

	location ~ ^(?<path>/symphony/2.1.0)/(?<admin>symphony)?(?<page>.*)$ {
    	# Allow access to files:
    	if (-f $request_filename) {
        	break;
    	}
    
    	# Just In Time Image Manipulation:
    	if ($page ~ ^image/(.+\.(jpg|gif|jpeg|png|bmp))$) {
        	rewrite . $path/extensions/jit_image_manipulation/lib/image.php?param=$1 last;
    	}
    
    	# Add trailing slashes:
    	if ($request_filename !~ "/$") {
        	rewrite ^(.*)$ $1/ redirect;
    	}
    
    	# Block access to manifest:
    	if ($page ~ "^manifest/") {
        	return 403;
    	}
    
    	# Access Symphony backend:
    	if ($admin) {
        	rewrite . $path/index.php?mode=administration&symphony-page=$page last;
    	}
    
    	# Access Symphony frontend:
    	if ($page) {
        	rewrite . $path/index.php?symphony-page=$page last;
    	}
	}
	
Again, the above only works for a Symphony installation in the `/symphony/2.1.0` folder of your web server, if your installation is in the root folder, then change the first line to read:

	location ~ ^(?<path>)/(?<admin>symphony)?(?<page>.*)$ {

Yep, it’s that simple!