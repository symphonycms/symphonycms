---
id: chapter-03
title: Getting Started
layout: docs
---

# Chapter 3: Getting Started

It doesn’t take very much to get Symphony up and running. In fact, if you’re already comfortable with basic server-related tasks, five minutes should be about all you need (not including time spent reading this chapter, of course). All you have to do is prepare the server (by uploading Symphony and creating a database) and then run the installer. That's it.

Of course, this process might involve a little lead time depending on how much you already know about servers and databases and the like. Whatever your knowledge level, though, this chapter will get you up to speed quickly and then walk you through the setup and installation process step-by-step. If you get stuck anywhere along the way, I’ll also let you know where you can look for help.

Because I don't know how much you know, we’ll take a sort of “choose your own adventure” approach here. If you’re a total beginner, just read the entire chapter straight through. We’ll start by stepping back to make sure you’ve got a basic understanding of how Symphony works before getting into some of the more technical installation details. If you’ve worked with web software before but are still a little green when it comes to things like server and database configurations, then skip ahead to “Knowing What You Need” and read on from there—we’ll help you get set up. Finally, those of you who know everything there is to know about servers and databases and even version control can just skim the “Requirements Summary” and then jump ahead to “Preparing the Installation.”

Alright, so do you know where you’re going? Good. I’ll meet you there—just need to grab another cup of coffee first...

## Getting the Lay of the Land

If you're still fairly new to the world of web software, there are a handful of concepts you should be familiar with before we move on. This section will give you a crash course, and even if you don't fully grasp all the details right away, you'll at least have a better idea of what's going on during the installation process.

### How Symphony Works

Symphony, as you know, is a web application—a piece of software that runs on a web server. When someone visits a URL that points to a Symphony-powered site, the web server receives their request and passes it to Symphony. Based on how it's been set up, Symphony figures out what it's supposed to do with that request and, when it's finished, sends a response back to the user (Figure 3-1).

    Figure 3-1	[0301.png]

More often than not, these requests involve content. Either someone wants to get content from your site (like a visitor trying to read your article), or someone wants to post content to your site (like your ex leaving a snarky comment about your eating habits). In either of those cases, while processing the request, Symphony communicates with an underlying database, in which all its content is stored.

So, essentially, Symphony is sitting on a server and handling various kinds of interactions between content stored on the server and people who want to use that content. There's a lot more to it than this, of course, but we've got a dozen or so chapters to cover the details more thoroughly. For now, it's enough just to have this rough overview of how Symphony works. Now let's review some of the other concepts we've introduced in case they're new to you.

### Web Servers

A web server, put very simply, is a computer equipped with software that allows it to respond to web requests. This can be any kind of computer, from the laptop you have at home to the sleek, powerful machines that commercial web hosting companies use. Of course, when you've got a website that you actually want to share with the world, you'll use the latter. But most developers actually work with a combination of the two—they set up a local server (server software running on their own computer) to build and test their sites, and then push to a remotely hosted server when their work is ready to see the light of day.

> **Note**
> 
> I highly recommend setting up a web server locally on your own computer, both for the purposes of this book and for web development work in general. You'll be able to work faster and troubleshoot more easily. Later in this chapter, after we discuss Symphony's server requirements, I'll provide some pointers on getting this set up.

### Server Environments

Web server software is designed to handle requests and responses, but the applications that run on these servers have to do all kinds of other stuff too—they need to run code, store and retrieve data, and so on. So a server has to be configured to provide web applications with access to all kinds of other things, from programming language libraries to software packages to databases. The sum of all this—the web server software and everything it provides access to—is referred to as the server environment. Like animals and their habitats, web applications require specific kinds of environments in order to function.

> **Note**
> 
> #### Know Thy Server Environment
> 
> There are a lot of variables in a server environment: what software packages and libraries are available, what operating system is powering the machine, what versions of these things you're using and how each has been configured... Taken together, these variables can account for significant differences from one server environment to the next. Knowing the details of your server environment can save you a lot of time while troubleshooting, because often it's in these differences that your problems will lie.

### Databases

For most web applications, Symphony included, databases are a necessary part of the server environment. They provide an organized and efficient way to store and retrieve massive amounts of complex data. Database software usually runs alongside web server software, allowing web applications to connect and query their databases as needed.

### Summary

I hope that reviewing these key concepts will make it easier for you to follow along with the rest of this chapter. If you find any of the above unclear or confusing, though, it's probably a good idea to do a little more basic research on your own before continuing. I'll do my best to fill in the gaps, but if you're going to be working with Symphony, you'll want to have a pretty good handle on this stuff beforehand.

> **Note**
> 
> For more information on servers, databases, and other web technologies, try:
> 
> - How Web Servers Work: http://computer.howstuffworks.com/web-server.htm
> - Google Code University: http://code.google.com/edu
> - W3 Schools Tutorials: www.w3schools.com/
> - Nettuts Plus Basix: http://net.tutsplus.com/tag/basix/
> 
> Once you feel comfortable enough to begin getting into server environment details, read on.

## Knowing What You Need

It doesn't take very much to run Symphony, and most commercial hosts easily meet the system's requirements.

### Server Requirements

The first thing you'll need, of course, is a web server. Symphony requires Apache or Litespeed (though it's worth noting that people have had success run-ning Symphony on other kinds of servers as well, like NGINX and even Micro-soft's IIS). Whatever server software you use, it'll need to be able to rewrite URLs, so Apache's mod_rewrite (or whatever your server's equivalent is) will have to be enabled.

Symphony is written in PHP, so your server environment will need to have PHP 5.2 or above installed in order to run it. In addition, your build of PHP will have to have XML and XSLT support enabled because, as we've seen, Symphony leans on these technologies heavily. Both extensions are already included with PHP, but while the LibXML extension is enabled by default, the XSL extension needs to be enabled explicitly. Again, most commercial hosts have this turned on, but if you're setting up a local server, you'll have to remember to activate the XSL extension yourself.

Finally, Symphony stores its content in a MySQL database. MySQL is one of the most widely used database systems available and you'd be hard-pressed to find a web host that doesn't support it. You'll want to have a recent version, ideally, but Symphony can work with versions as far back as MySQL 4.1.

That's all it takes. I’m not kidding.

### Helpful PHP Extensions

Although this is all that's required to run Symphony, there are a few additional PHP extensions that are helpful to have enabled.

One of Symphony's core extensions, which we mentioned in Chapter 1, enables you to zip up a Symphony build into an installable package, like your own custom CMS. In order to take advantage of this functionality, though, PHP's Zip extension needs to be enabled on your server. This is fairly common, but if you're setting up your own environment, you'll need to remember to enable it yourself.

Another core extension, one we've also mentioned, enables the system to process and manipulate images on-the-fly, which can save designers lots of time and headaches. This functionality requires that the GD library is installed and enabled. Again, this shouldn't be difficult to find on commercial hosts, but make sure you enable it if you’re setting up your own server.

> **Note**
> 
> #### Enabling PHP Extensions
> 
> When you're using a commercial host and a required or recommended PHP ex-tension is not enabled, a simple support request is often enough to get them to enable it for you. The extensions used by Symphony are common enough that, if your web host is not willing to switch them on, you're probably better off hosting your site elsewhere.

When you're setting up your own server, you'll have to take care of stuff like this yourself. Most Linux distributions make this easy—you just install the extension in your software package manager. To enable the XSL extension on Ubuntu, for example, you just install the php5-xsl package in the software center (or run sudo apt-get install php5-xsl) and you're all set. For Windows, you can actually download a PHP installer from http://php.net that allows you to select your extensions during setup. Mac users aren't as lucky, I’m afraid, but as we'll see later in this chapter, there are some helpful tools for setting up server environments on OS X and Windows that make all of this a bit easier.

#### Requirements Summary

- An Apache or Litespeed web server, with mod_rewrite module or equivalent
- PHP 5.2 or above, with the LibXML and XSL extensions enabled
- MySQL 4.1 or above

> **Note**
> 
> ##### Setting up your own server
> 
> Walking you through the process of setting up and configuring a server is well beyond the scope of this modest book, but if you're going to take my advice and set up a development server on your own computer, I want to get you pointed in the right direction.

Linux users should be able to use their package manager to install Apache 2, MySQL 5, and PHP 5 and its extensions (along with phpMyAdmin or whatever other tools you need). You’ll have to do a little bit of configuration, but it's fairly easy to find tutorials for every major Linux distribution on how to configure a LAMP (Linux, Apache, MySQL, PHP) stack.

Mac users can try to use OS X's built-in server, but you'll have to install MySQL yourself, and you'll have to do some heavy lifting to enable certain PHP extensions. For these reasons, an easier route might be to install a full-stack solution like MAMP (http://mamp.info), or to use Macports (http://macports.org) to set up your server environment.

Windows users can download dedicated installers for each element of their server stack—Apache, MySQL, and PHP. You might actually find it easier, though, to just use full-stack tools like Wampserver (http://wampserver.com) or XAMPP (http://apachefriends.org/en/xampp.html).

Whatever route you choose, somewhere along the way you'll have to set up a root MySQL user. Be sure to note the username and password, because you'll need them later in the installation process.

## Preparing the Installation

Once you're sure you've got all the requirements met, you need to make a few decisions about how and where you're going to install.

### Decisions, Decisions

#### Local Versus Hosted

The first thing you need to decide is where you're going to install Symphony. For production sites, of course, you'll use a web host. But for development and testing, and certainly for the purposes of this book, I recommend you take the extra time to set up a local server environment on your own computer (if you don't already have one). It'll make things much easier for you in the long run. I provided some pointers in the last section to get you started.

If you do choose to install on a remote server, there are a few things you should be aware of.

If you need to transfer files from your computer to the server, you’ll need an FTP client. Thankfully, there are lots of free FTP clients available for all the major operating systems—the cross-platform Filezilla, Cyberduck on Mac, SmartFTP on Windows, gFTP on Linux, and many others.
If you need to execute commands using the command line, you’ll need secure shell (SSH) access to your server, which isn’t always available in shared hosting environments.

#### Package Versus Git

The second decision you need to make is how to get Symphony, and how to get it onto your server.

If you’ve used other web-based software before, you're probably accustomed to package-based installations. This method simply entails loading the contents of a software package onto your server (usually with an FTP client if you’re uploading to a remote server). The benefits of package-based installations are that they're usually easier for beginners and they don’t introduce any additional server requirements. Unfortunately, installing via package also means you’ll have to re-download and replace package files whenever you update Symphony.

Your other option is to use Git. Git is a popular version control system that is used to manage Symphony’s codebase. Using Git to install Symphony only takes a few commands, and has the added benefit of keeping your installation linked to the official repository (which makes updating Symphony a breeze). To use this method, though, your server needs to have Git enabled, and you need command line access (usually via SSH on remote servers).
If you’re comfortable with Git, or at least willing to learn, using it to manage your Symphony installation can be very helpful in the long-run. If in doubt, though, or if you're not sure whether your server has Git installed, just go ahead with a package-based installation for now.

#### Clean System Versus Starter Package

Finally, whenever you install Symphony you have the option to start with a completely clean system—with no content or front-end setup at all—or to use one of several pre-configured starter packages. For the purposes of this book, you'll need to go with the former, a completely clean install, so you can see what it's like to build out a Symphony site bit-by-bit. But when you're working on your own projects, starter packages can be a helpful way to bootstrap the development process.

### Getting Symphony onto Your Server

Now that you’ve decided on an installation method, let’s get Symphony onto your server. Follow the set of instructions that corresponds to the installation method you’ve chosen. The first two are for performing a package-based install—one for those who’d like to use a file browser or FTP client, and one for those who’d like to use the command line. The third set is for those who’ve opted to perform a Git-based install.

#### Using a .Zip Package and a File Browser/FTP Client

1.	Grab the current release package from http://symphony-cms.com/download/releases/current/ and save it to a local directory.
2.	Extract the package.
3.	Optional. When starting with a completely clean system (which we will be for this book), you should delete the workspace/ directory.
4.	Move the contents of the extracted directory to the desired location on your server. Note that you don’t want to include the package directory itself (the one named `symphony-n.n.n` where `n.n.n` is the version number); you only want to include its contents. In other words, you want the index.php file right in the directory where you want to run Symphony.
5.	Use your file browser or FTP client to temporarily set permissions on the root directory (the one you just installed to) and the `symphony/` directory to `777` (read, write, and execute for all). Then set permissions on the `workspace/` directory to `777` and tell your client to apply the changes recursively (to all subfolders). Don't worry, we’ll undo this step and tighten up permissions after installing.

#### Using a .Zip Package and the Command Line

1. cd into the directory where you’d like to install Symphony (usually the server’s web root).
2. Execute one of the following commands, depending on whether your server supports wget or curl:  

        wget http://symphony-cms.com/download/releases/current && unzip symphony-n.n.n.zip && rm symphony-n.n.n.zip && mv symphony-n.n.n/* . && rmdir symphony-n.n.n
        curl -L http://symphony-cms.com/download/releases/current > symphony.zip && unzip symphony.zip && rm symphony.zip && mv symphony-n.n.n/* . && rmdir symphony-n.n.n
        
    Where `n.n.n` is the version of Symphony you're installing.

3. Optional. When starting with a completely clean system (which we are for this book), you should delete the workspace/ directory:

        rm -R workspace

4. Set temporary permissions for the install script:

        chmod 777 symphony .
        chmod -R 777 workspace
    
    Don't worry. We’ll undo this step and tighten up permissions after installing.

#### Using Git

1.	cd into parent directory of the directory where you’d like to install Symphony (for example, if you’re installing into your server’s root at public/html, you want to cd into public/).
2.	If the directory you want to install into exists, you have two options:
A) Make sure you’ve safely backed up its contents and emptied it, and then remove the directory (in the example above, you’d execute the command rmdir html).
B) If there are contents inside it that you cannot delete, you can clone the repository into a subdirectory, and then move all the files and directories (including the hidden .git directory) back up into the main directory.
3.	Clone the Symphony Git repository using the following command:

        git clone git://github.com/symphonycms/symphony-3.git directory

    Where directory is the name of the directory into which you’d like to install Symphony. For example, if you’re installing to your server’s web root at public/html, you’ll want to use html in the above command.

4.	cd into your installation directory:

        cd directory

    Where directory is the name of the directory into which you just cloned the repo

5.	Grab the default extensions:

        git submodule init
        git submodule update

6.	Optional. If you’ve decided to include a starter package rather than start with a clean system, clone the package into your workspace directory:

        git clone git://package-git-url workspace

    Where package-git-url is the location of the starter package’s Git repository

7.	Set temporary permissions for the install script:

        chmod 777 symphony .
        chmod -R 777 workspace
    
    Don't worry. We’ll undo this step and tighten up permissions after installing.

> **Note**
> 
> #### Examining your server environment
> 
> At this stage, if you have any doubts or questions about your server environment, you can use the Symphony installer’s ?info function to see the output of phpinfo() (a PHP function that displays details about your server environment and configurations). You can access this by visiting http://yourdomain.com/install?info in your browser.
> 
> If you have any trouble during the installation itself, it’d be a good idea to note the output of this function so you can include your server details when asking for help.

### Creating a Database

Creating a database for Symphony to use is a fairly simple step, but in order to proceed you’ll need to know your MySQL username and password. If you're installing on a local server, you will have created these yourself. If you're installing on a web host, they may have been assigned by your hosting provider, or you may have created them using your server’s control panel.

You'll probably have several different options for creating a MySQL data-base, including phpMyAdmin, the command line MySQL client, and web host control panels. The latter are usually fairly straightforward and well-documented, so I'll just cover the former two here.

#### Using phpMyAdmin

1.	Log into your phpMyAdmin interface.
2.	You’ll see a field on the home page labeled “Create new database.” En-ter a database name (note this for the next step), and from the “Collation” dropdown, select `utf8_unicode_ci`.
3.	Click the “Create” button.

#### Using the Command Line MySQL Client

1.	Connect to the MySQL client:

        mysql -u username -p

    Replace username with your MySQL username. You’ll be prompted for your MySQL password. Enter it.

2.	In the MySQL prompt (mysql>), type:  

        CREATE DATABASE db_name CHARACTER SET utf8 COLLATE utf8_unicode_ci;

    Be sure to replace `db_name` with a suitable database name, like symphony. You should see a message telling you that the query was executed. Type EXIT.

## Running the Installer

All the files are in place, your database is ready, and all that remains is to run the installer. This step is simple. Point your browser to the Symphony install script (http://yourdomain.com/install/). You’ll be presented with a nice graphical installer. Completing it should be fairly straightforward, but if there’s anything you’re unsure about, see the section-by-section breakdown below.

#### Your Website

    Figure 3-2	[0302.png]

- Website Name: Enter a name for your website.

#### Your Server

    Figure 3-3	[0303.png]

- Root Path: This should be automatically detected and pre-filled for you.
- File Permissions: Select the desired permissions settings for files created by the system.
- Directory Permissions: Select the desired permissions settings for directories created by the system.

#### Your Locale

    Figure 3-4	[0304.png]

- Region: Select the region of the world your website is based in.
- Date Format: Select a date format to be used in the admin interface.
- Time Format: Select a time format to be used in the admin interface.

#### Your Database

    Figure 3-5	[0305.png]

- Database: Enter the name of the database you created earlier.
- Username: Enter your MySQL username.
- Password: Enter your MySQL password.
- Host: Enter the MySQL host name, if applicable. Otherwise, leave the default value (localhost).
- Port: Enter the MySQL port, if applicable. Otherwise, leave the default value (3306).
- Table Prefix: Enter the desired table prefix, if applicable. Otherwise, leave the default value (sym_).
- Use compatibility mode: Tick this box if your host doesn’t allow character sets and collations to be specified for tables. Otherwise, leave unticked.

#### Your First User

    Figure 3-6	[0306.png]

- Username: Enter the username you’d like to use to access Symphony’s admin interface.
- Password (and Confirm Password): Enter the password you’d like to use to access Symphony’s admin interface.
- First Name: Enter your given name.
- Last Name: Enter your family name or surname.
- Email Address: Enter your email address.

#### Install Symphony

    Figure 3-7	[0307.png]

Click the button!

Congratulations, you’ve just installed Symphony! Before you jump out of your chair for a celebratory dance, we’ve got a little cleaning up to do:

First, delete the install script (install.php). If you’re using a file browser or an FTP client to manage your files, just select the file and delete it. If you used the command line, cd to the root of your Symphony installation and do:

    rm install.php

Second, be sure to tighten up the folder permissions you adjusted during setup. Exact settings will depend on your server environment, but the permissions on your root directory and your symphony/ directory should be fairly restrictive (try something like 755). Then set manifest/ and workspace/ to 775 (make the workspace/ permissions recursive, so they apply to its subdirectories as well).

    xchmod 755 symphony .
    chmod -R 775 manifest
    chmod -R 775 workspace

Now you can dance (try not to embarrass yourself).

## What to do when you need help

Everybody makes mistakes—some of us more than others. If my guidance in this chapter has somehow led you astray, or I've forgotten to account for your specific server environment or some other peculiar circumstance, don't fret. When it comes to getting the answers you need, this little tome is just the tip of a very large (and welcoming) iceberg.
First and foremost, you'll want to consult the errata for this book (URL?). If I've made any glaring errors or omissions, you'll find them listed there (alongside some text exonerating me and telling you who's really to blame). You'll also want to familiarize yourself with Symphony's official documentation (http://symphony-cms.com/learn/). The docs address many specific or advanced scenarios that are beyond the scope of this book. And when you've got a problem or question that you can't find the answer to, there are always real live people willing to help.

### Reaching out to the Symphony community

By far the fastest and most reliable way to seek help is on the Symphony forum (http://symphony-cms.com/discuss/). There are lots of folks around who know the ins and outs of Symphony as well as I do (or better), and the forum is where you'll find them. Bear in mind that there's a good chance your question has already been asked and answered before, so try do a thorough search before posting. But when you do have something you need help with, ask away. I don't think you'll find a more friendly or helpful group of people anywhere. Many Symphonists are also on Twitter, and if you can connect with the community there using the #symphonycms hashtag.

### Reaching out to the Symphony team

As amazing as our community is, there may be times when you need to contact the Symphony team directly—for instance when you're interested in commercial-level support, or when you want to offer us all-expenses-paid trips to places like Bali and Saint-Tropez. In these sorts of scenarios, the best way to reach us is via the contact form on the Symphony website (http://symphony-cms.com/get-support/contact/). You can also email us directly (team@symphony-cms.com) or give us a shout on Twitter (@symphonycms). However you reach out, we'll do our best to get back to you as quickly as possible.

## Summary: Rolling up your sleeves

In this chapter, we've reviewed what it takes to get Symphony up and running, and we've walked through the setup and installation process step-by-step. We've also taken a moment to talk about what you should do if you run into any problems.

With all that out of the way, and with Symphony purring along happily on your server, it's time to log in to your admin interface (http://yourdomain.com/symphony/) and have a look around. The real fun is about to begin…