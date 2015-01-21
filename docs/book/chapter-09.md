---
id: chapter-09
title: System Management
layout: docs
---

# Chapter 9: System Management

## What’s in This Chapter

- System Settings
- Users and Roles
- Working with Extensions
- Backups and Maintenance

You’ve only just finished learning the basics, and already Symphony’s enabled you to build a custom website and CMS, essentially from scratch. Pretty impressive, no? And while you might expect managing such a capable system to be a hopelessly complex endeavor—involving all sorts of arcane menus and page after page of switches to flip and boxes to tick—Symphony actually packs all of its punch into a remarkably lightweight package.

All of which is to say that the work of tending a Symphony installation is much more straightforward than you might think. This is partly due to the fact that you’re not deploying sites pieced together from cookie-cutter features. So you don’t need loads of extra preferences to tell your site what you want it to do. It does what you designed it to do. The other reason is that Symphony is engineered from top to bottom with simplicity as a guiding principle:

> “The simplest answer is almost always right, because it has the greatest number of other answers behind it. Opting for simplicity makes it just as easy to turn back or change direction as it is to keep going.”
> 
> — The Tao of Symphony, <http://symphony-cms.com/learn/articles/view/the-tao-of-symphony/>

This approach permeates the entire system, from its core architecture right through to its admin interface and the user experiences and workflows it provides.

This chapter will be a quick one, then. It won’t take long for you to learn everything you need to know about managing your Symphony installation. 

We’ll begin with the general settings that define the system’s internal behavior—things like the default language and date formats for your admin interface. Then we’ll go on to discuss users and roles so that you’ll be able to manage back-end access and permissions. After that, we’ll briefly explore the world of Symphony extensions. Extensions are a crucial part of any Symphony project because the core of the system is built to be so lean—almost every kind of specialized functionality is provided by an extension, even things like field types and data source types. Finally, we’ll wrap up our discussion by talking about site maintenance, backups, packaging, and so on.

When we’re finished, you’ll be fully equipped to manage, tweak, and extend your new site to your heart’s content.

## Managing Settings

Settings allow you to configure various aspects of the system’s internal behavior—from simple display options to things like file permissions and caching. There are two ways to manage your settings in Symphony: using the admin interface or by editing the settings files themselves.

The most commonly used settings can be accessed right in the admin interface, at System > Settings (Figure 9-1). Any settings provided by extensions are made available here as well, in a separate tab.

    Figure 9-1	[f0901.png]

Let’s take a moment to review the settings available here:

- Website Name. This is the name of your website. It’s used to title the admin interface, and is provided in the context XML that is made available to your front end.
- Language. The default language for the admin interface. You have to have language extensions installed to enable selection of languages other than English. This setting is only a default. Individual users will be able to choose their own preferred language.
- Administration Path. This is the URL path for the admin interface. By default, this is ‘symphony’, but you can change that here.
- Date Format, Time Format, and Timezone. These settings allow you to specify how dates will appear in the admin interface, and whether times should be adjusted to a particular timezone.
- [[Additional settings not yet defined... caching, logging]]

All of Symphony’s settings—both these common ones and several more advanced settings—are stored in XML files in your manifest/settings/ folder. Extensions will save their own settings files to this directory too. The second way to manage settings, then, is to edit these files directly. Because you’re now familiar with XML syntax, you should be able to find your way around the files pretty easily. Here’s a sample:

    PASTE

You can always change any setting by editing these settings files directly, but make sure you know what you’re doing. If a setting requires more than a simple yes/no value, you need to know what will work and what won’t.

Let’s review the remaining default settings we haven’t already discussed, so that you’ll be able to adjust these if necessary.

#### Website settings

- **Version.** This will be updated automatically when Symphony is installed or updated. You shouldn’t change this or you might risk throwing off future upgrades.

#### Email settings

- Provided by gateway extensions?

#### Front End Settings

- **Display Event XML in Source.** This setting specifies whether the system will append to its output, in an XML comment, any data returned by an event. Expects yes or no.

#### System Settings

- **Cookie Prefix.** Prefix for cookies stored in the browser by Symphony. Defaults to sym- but any string will do.
- **File Write Mode.** Default permissions for files created by Symphony. Defaults to 664 but any mode writable by the server will work. Don’t change this unless you know what you’re doing, or you could create a security problem.
- **Directory Write Mode.** Default permissions for directories created by Symphony. Defaults to 775 but any mode writable by the server will work. Don’t change this unless you know what you’re doing, or you could create a security problem.
- **Archive.** Whether system logs should be archived. 1 for yes, 0 for no.
- **Max Size.** Maximum size of the logs archive. When the file reaches this size, old logs will be truncated. Enter a size in megabytes.
- **Cache Driver.** Method the system will use for caching. Either file or database. [[What’s the difference?]]

#### Admin Interface

- **Condense Scripts and Stylesheets.** This is an option to have the system condense back-end assets to improve performance. Accepts yes  or no.
- **Maximum Upload Size.** Maximum size for file uploads in the admin interface. Enter a size in megabytes.
- **Maximum Page Rows.** In the admin interface’s index views, the maximum number of items to show per page. Default is 17.
- **Default Data Source Type.** Default type selected when creating a new data source in the admin interface. Accepts handle-ized version of any available data source.
- **Default Event Type.** Default type selected when creating a new event in the admin interface. Accepts handle-ized version of any available event type.
- **Allow Page subscription.** ????

Most of these advanced settings you’ll never need to touch, but it’s good to know that they exist and how you can change them, just in case.

As I said above, extensions can also add their own settings. If they do so, their settings will be available at System > Settings under the Extensions tab, and in extension settings files in manifest/settings/.

X of Symphony’s default extensions provide settings of their own. Let’s review those now:

    [[Default extensions and their settings]]

## Managing Users

Users are people who can log into your Symphony site’s admin interface. When you installed Symphony you created the system’s first user.

### Creating and Editing Users

You can manage your system’s users at Users > Accounts. By now the interface conventions should feel like second nature—click a user’s name to edit that account, click the green “Create New” button to add a user. Let’s start by looking at your own account:

1. Click your name in the users index (you can also get to your account by clicking on your name in the title bar).
2. Your account information will be displayed in the user editor (Figure 9-2):

	   Figure 9-2	[f0902.png]

Let’s quickly review the information:

- First Name
- Last Name
- Email Address
- Username
- Password
- Start Page
- Language

All of this is pretty standard. You can use this form to update your details, change your password, or modify personal settings.

### User Roles

Symphony’s core allows for only one type of user with full permissions, meaning any user can access anything in the back end. This is great when you’re working on a site by yourself or with a trusted small team because it saves you the overhead of a complex permissions system.

Once you’re dealing with more than a few users, though, it’s likely that you’ll need fine-grained control over who has access to what. Symphony’s got your back here too. The system ships with an Access Control Layer (ACL) extension that, once enabled, allows you to create discrete user roles and specify role-based permissions.

To demonstrate how roles work, let’s imagine that you’ve decided to make your blog a collaborative endeavor. You’ve brought on a second author and

### Creating and Editing Roles

Creating and Editing roles...

Note about frontend users

## Managing Extensions

Because different people will require different things in different situations, Symphony is designed to allow you to swap pieces in and out as you need them. This is done via extensions.

A good deal of setting up and managing a Symphony installation, then, is 

### The Core Extensions

There are a handful of core extensions.

- **Field types:** text, date, select, upload, ?
- **Data source types:** entries, users, views, dynamic XML, dynamic JSON
- **Event types:** ??
- **View types:** ??
- **Devkits:** debug, profile
- Export ensemble, maintenance mode, ??

### Finding and Installing Extensions

Basic: locate extensions at getsymphony.com; download or clone; activate/enable
Advanced: install Extension Manager extension; configure git and/or curl permissions and details; browse extensions at Extensions; use search & filter;

### Managing Extensions

Update notifications

Updating extensions

Note about uninstalling extensions

### Backups, Updates, and System Maintenance

Blah

## Summary

Yeah text
