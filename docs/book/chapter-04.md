---
id: chapter-04
title: Symphony Anatomy
layout: docs
---

# Chapter 4: Symphony Anatomy

## What’s in This Chapter

- Functional overview of the system
- Tour of the admin interface
- Symphony file structure
- Differences between Symphony 2 and 3

Now that you’ve got Symphony installed, you face a familiar, ages-old dilemma. Do you continue reading along studiously? Or do you roll up your sleeves and venture off to explore this new territory on your own, armed with nothing but your wits, your keyboard, and what I can only assume is a childlike sense of wonderment? After all, you’ve just logged in to Symphony’s back end, and as you can see in Figure 4-1, the cleanliness, the simple elegance, it’s all… strangely alluring.

    Figure 4-1	[f0401.png]

So if the urge to poke around a bit is too much for you to bear, feel free to do so. I’ll wait here.

Whether you decided to explore on your own or not, it won’t be long before you realize that you can save yourself a lot of time if you get to know the lay of the land first. This may seem like a tedious step, but then again it’s always better to have a map in your pocket than not, right? Well this chapter is that map, your bird’s-eye view on all things Symphony.

We’ll begin with a cursory glance at the admin interface, or back end, just to give you an initial point of reference—something to anchor your mind as we batter it with new ideas and information. Then we’ll set about exploring the system from three angles:

1.	For starters, we'll review what the system looks like functionally—the working elements that allow you to define, manage, and deliver content.
2.	Then, we'll dive in visually to explore Symphony's admin interface in detail.
3.	Finally, we'll have a look at how Symphony is represented physically on your server—the directories and files that comprise it and what they do.

These three overviews should provide you with a strong foundation for the rest of the book—whether you’re a total noob or an experienced web developer, a designer or a programmer, a visual thinker or a conceptual one, or none of these things.

## Dipping Your Toes In

Symphony’s back end is deceptively simple. Gone are the days (I hope) when we assumed that the most powerful interfaces had to be the most complex. When you look at Symphony, know this: there’s a whole lot of power in that minimalist package.

Let’s quickly run through what you see when you log in. The dark header at the top displays your website’s name, the name of the currently logged-in user (which is you, unless you’re impersonating someone else and learning valuable web development skills on their behalf, which would be weird), and a logout button.

Beneath the header is a gray navigation menu. This is how you’ll move around Symphony’s back end to manage your website and its content. Figure 4-2 shows a fully-expanded sample menu:

    Figure 4-2	[f0402.png]

The left side of the navigation menu is where you manage all the content that will power your website—be it blog posts, portfolio items, products… whatever. Because Symphony allows you to define those content types yourself, this part of the menu will vary from site to site. That’s why I referred to the image above as a sample menu.
The right side of the navigation menu is where you manage all the elements that structure your website and its behavior (its “blueprints,” if you will), and anything else related to administering the system. The two submenus you see here—Blueprints and System—form the backbone of Symphony’s admin interface. Everything about your website, from the shape of its content and interactions to its URL schema and design, will be defined and determined here.
Let’s quickly walk through those submenus now. If you’re looking at the Symphony admin interface in your browser as you read this, hover over the Blueprints item. Otherwise, you can refer back to Figure 4-2. Either way, you see the following submenu items under Blueprints:

- **Views** are used to build out your website’s front end. They determine where visitors can go to access your website, and what they’ll see when they get there.
- **Sections** are used to define and outline the types of content you’ll be managing.
- **Data Sources** are used to filter and channel content to your front end.
- **Events** are used to capture and save input submitted from the front end.
- **Utilities** are used to manage reusable bits of template code.

Now, hover over the System menu item (or glance at the System submenu in Figure 4-2). It contains the following items:

- **Users** are accounts that have access to the admin interface.
- **Settings** allows you to adjust various system settings, such as date and language preferences.
- **Extensions** can add important functionality to your system, and can be enabled, disabled, and uninstalled here.

Don’t worry if all of this is a bit of a blur at the moment. What’s important is that your mind has something visual to anchor it as we move on to discuss all of these new concepts in greater detail.

## The Form of Symphony’s Function

The first thing you need to know about Symphony is how it works. Just as we study areas of the human anatomy based on what they do—circulate blood, digest food, control breathing—likewise Symphony’s anatomy is more easily understood when broken down into functional groupings.

What would those look like? Well, in the very broadest sense, creating and managing websites with Symphony boils down to three basic functions:

1. Defining and managing various kinds of web content
2. Setting up interfaces that enable visitors to interact with your site
3. Crafting a system of templates to present things to your visitors

You can think of these as functional “layers.” At the core of your website is a content layer, where all of your data is catalogued and stored. If you were publishing blog posts on your website, for example, they’d be defined and stored in the content layer. An interaction layer sits above that, responding to visitors' requests and delivering whatever it is that they're looking for. So if your visitor tries to view a single blog post, or to browse through your blog archive, those things would be handled by the interaction layer. Finally, on the surface, a presentation layer takes the data generated by the interaction and formats it for display or consumption.

> ###### Note
> 
> Programmers might recognize this as a reflection of the classic Model-View-Controller (MVC) paradigm. Widely regarded as a best practice in programming, MVC emphasizes separation between an application's data logic (the model layer), its presentation logic (the view layer), and its interaction logic (the controller layer), resulting in more flexible code that is easier to extend and maintain. Symphony is designed to allow its users to take advantage of this design pattern and reap these same benefits when building websites.

This structure of distinct functional layers is what makes Symphony so incredibly flexible, because each “layer” is actually comprised of a set of independent, fully-configurable components, as shown in Figure 4-3. These modular building blocks can be molded and assembled in an infinite number of  ways, making it possible to craft highly specialized and efficient systems.

    Figure 4-3	[f0403.png]

Let’s take a look at how Symphony’s internal structure maps onto these broader, functional layers:

- The **content layer** consists of sections and fields. Together, these enable you to define very precisely what kinds of content you'll be managing and how you'll capture and store it.
- The **interaction layer** consists of views, data sources, and events. These allow you to specify what requests your site will respond to (i.e. its URL schema), what it will do with them, and what data it will send back.
- The **presentation layer** consists of view templates and utilities. These are used to format the data your site delivers to the user.

Whenever a visitor lands on your website, these three layers work together in harmony:

1.	A view responds to the visitor's request, kicking into action any data sources and events that are attached to it.
2.	The data sources then fetch content from your sections (or from elsewhere, for example an RSS feed), and send it back to the view.
3.	The view then uses its template (and any attached utilities) to format the data and display it.

You can see a flowchart of this process in Figure 4-4:

    Figure 4-4	[f0404.png]

Now let’s take a quick look at each of these layers and their components in turn.

### The Content Layer

In Symphony, you define the content you’d like to manage using sections and fields. Sections are like containers for your content, and fields determine their shape. Figure 4-5 shows the basic structure of Symphony’s content layer.

    Figure 4-5	[f0405.png]

As you can see, every section is comprised of one or more fields, and each individual piece of content (each entry in a section) is made up of data corresponding to those fields.

Let’s say you’re using Symphony to launch a web-based magazine. You’d start by asking yourself, “What kinds of content do I need to be able to create and manage?” Things like issues, articles, and authors might come to mind. These would be your sections. Then, for each of these, you’d ask, “What do these things look like? What pieces of data do they contain?” For something like issues, your answer might be: each issue will have a title, an issue number, a description, a cover image… These would be your fields. Having defined that structure, every new issue you created—or, in Symphony terms, each new entry in the issues section—would be made up of these same fields.

> ###### Note
> 
> If you've ever worked with databases, sections would be analogous to tables, fields to columns, and each entry would be a row. For object-oriented programmers, sections would be like classes and fields like class properties, and each entry would be an object instance.

As we’ve mentioned several times, Symphony gives you a completely blank slate when it comes to your content layer; it doesn't make any assumptions at all. Even if you download a copy of Symphony that has some default sections set up, there’s nothing stopping you from deleting all of them and starting fresh. With sections and fields, you can create and manage any kind of content you like—from articles and blog posts to… yarns and zoo animals.

There’s a bit more to all of this, of course. For example, you’ll often need to create relationships among your sections (e.g. giving your magazine articles an author and placing them in a particular issue). You’ll also need to change the structure of your sections from time to time, even after they’ve been created and populated with entries. And there are many different types of fields, each with its own configuration options and its own ways of storing and retrieving data. But we’ll get into all that in Chapter 5. First, let’s talk about what Symphony allows you to do with your content once you’ve got some.

### The Interaction Layer

There’s a whole lot that can happen in your website’s interaction layer. Let’s say, using our magazine example from above, that you want to provide your visitors with archives of your past issues and articles. What URL do they visit? What content will they see? How is it sorted and organized? How can it be browsed? All of this is determined in the interaction layer.

Symphony provides several finely tuned components to handle these sorts of tasks, as you can see in Figure 4-6:

    Figure 4-6	[f0406.png]

It starts on the front end, with views. In Symphony, you use views to build an interface that will respond to visitors’ URL requests (whether they come from a web browser or via some other sort of client application like a feed reader). In other words, views are the answer to the question, “Where do I want my visitors to be able to go, and what do I want to show them when they get there?”

Each view is configured to respond to a certain request (or set of requests), at which point it executes any data sources and events that are attached to it (more on that in a moment). The data sources (and sometimes events) will return a bunch of content, which the view will then template for display in the browser (or for consumption in some other format). So the view is a middleman of sorts, like a waiter taking and delivering orders in a restaurant.
Data sources, in that scenario, would be the dishes. They may have a very technical-sounding name, but they are exactly what you’d think: sources of data for a view. Data sources can grab content from all sorts of places (your sections, other websites, your system’s user info), and can have any number of conditions, filters, and other directives specified. Every data source, in other words, has its own unique recipe for delivering content to your front end.

> ###### Note
> 
> If you’ve ever worked with databases, you’ll probably find it helpful to think of data sources as being akin to queries. You’re just asking Symphony to fetch you a set of data from a particular source that meets certain requirements, and to limit and sort the results as requested.

This may sound a bit overwhelming, especially if you’re accustomed to using systems that make these kinds of decisions for you, but hopefully by now you’re beginning to see some of the tremendous potential inherent in this structure. Because Symphony doesn’t make assumptions about how you want your visitors to interact with your content, you’re free to set up your views and data sources to do anything you’d like. Want your visitors to be able to go to http://yoursite.com/archive and see a reverse-chronological list of your magazine’s issues, grouped by year? No problem. Rather have them grouped thematically and then sorted by issue? You can do that too. Want to serve an RSS feed at that URL instead? Again, easy as pie.

Events are the flip side of the interactive coin. If data sources enable content to be fetched and displayed on the front end, events allow data submitted from the front end to be saved into your sections. If your magazine permitted comments to be submitted by visitors, for instance, an event would do the job. Member-driven Symphony websites like Connosr.com and the Symphony website itself rely heavily on events because users can submit everything from forum posts and reviews to extension packages, all from the front end.

As you might suspect, there’s a lot more to all of this, and at the moment you’ve probably got more questions than you know what to do with. Don’t worry. We’ll have an in-depth look at views in Chapter 6, and then at all the different kinds of data sources and events—and the options available for each—in Chapter 7. For now, if you’ve got a rough sense of how the interaction layer works in Symphony, you’re in good shape.

### The Presentation Layer

The presentation layer, by comparison, is fairly straightforward, as you can see in Figure 4-7:

    Figure 4-7	[f0407.png]

Every view in Symphony has a corresponding view template. Because the data that’s delivered to your views is raw XML, what the view template needs to do is transform that XML into a format that your visitors can use. Most often, it’s turned into (X)HTML for display in a browser, but your view templates can actually produce almost any format at all—RSS or Atom-flavored XML, plain text, comma-separated values (CSV), PDFs… even JavaScript or CSS.

View templates can accomplish this sorcery because they’re written in XSLT (Extensible Stylesheet Language Transformations), which you’ll recall is a templating language developed precisely for the purpose of transforming XML. Fortuitous, no? Rather than inventing some system of arcane pseudo-tags, Symphony’s presentation layer allows you to leverage a widely-used, powerful, open standard.

Among the many, many benefits of XSLT is that it makes it easy for you to organize and reuse code. Programmers know how important it can be to pull out snippets of code that perform common tasks and to reuse them. And designers know how important it is to produce markup that’s consistent and easy to maintain. Utilities are independent XSLT stylesheets that can be dynamically included by view templates and thus reused, remixed, and recycled anywhere on your website.

Perhaps the most common use of utilities is the creation of a master layout stylesheet, responsible for outputting all the markup that will be used universally throughout your front end—header, navigation, and footer, for example. Another common usage is for utilities to contain snippets of template code that can be used over and over in many different contexts. Let’s say, for instance, that you wanted your magazine website to make heavy use of Microformats.[^1] You could create a Microformats utility that would used to output hCalendars wherever you were displaying a date, hCards wherever you were displaying author profiles, and so on.

Though fairly simple conceptually, Symphony’s presentation layer is remarkably powerful thanks to the capabilities it inherits from XSLT. That said, for many people, it’s also the toughest part of the Symphony learning curve. Have no fear, though, because Chapter 8 is going to tell you everything you need to know about templating in Symphony, and may even turn you into an XSLT master of unparalleled guile and charm (if not actual skill).

[^1]: Microformats are a set of simple, open formats that allow you to embed meaningful data in markup that otherwise couldn’t convey it (like HTML). See http://microformats.org/.

### Summary: Symphony’s Functional Anatomy

In these brief overviews of Symphony’s three functional layers, we’ve learned the following:

- Sections and fields allow you to define the various kinds of content that you’ll be managing.
- Views allow you to set up a front end for your website’s visitors.
- Data sources allow you create recipes for delivering content to your visitors.
- Events allow you to accept input from your visitors.
- View templates and utilities allow you to format the content you present to your visitors in any way you like.

We’ll cover each of these elements in much greater depth in Chapters 5 through 8.

## The Admin Interface

Having taken the time to familiarize yourself with Symphony’s basic moving pieces, you’re now ready for a more meaningful walkthrough of the admin interface we looked at so briefly earlier. Assuming you installed Symphony on your computer or on a hosted server in Chapter 3, open the admin interface in your browser so you can follow along.

### Composition and Layout

Figure 4-8 shows a breakdown of Symphony’s back-end layout:

    Figure 4-8	[f0408.png]

The admin interface is designed to be easy to navigate and easy to use. Wherever you happen to be in Symphony’s back end, and whatever you happen to be doing, you’ll see the same basic layout. A title bar (1) identifies the project and the current user. A simple, horizontal menu (2) allows you to navigate through the back end. And the main content area (3) is used to display the various tables and forms that you’ll use to manage your website and its content.

Functionally speaking, the admin interface provides access to three basic areas. We’ll review those quickly now.

### Content

The content area, on the left side of the navigation menu, can consist of any number of submenus that allow you to manage your content entries. When you create a section, you assign it to a navigation group. Each navigation group becomes a submenu in the content area, and each Section belonging to that group becomes an item in that submenu (unless it’s hidden, which we’ll discuss in Chapter 5).

Confused? Well let’s go back to our magazine example. Imagine you were to create sections for issues and articles, and assign both sections to a navigation group called “Content.” Then imagine you created three more sections—writers, photographers, and editors—and assigned each of them to a navigation group called “Contributors.” What you’d end up with is a menu that looked like Figure 4-9:

    Figure 4-9	[f0409.png]

Clicking any item in one of these content submenus will take you to the index page for that section. A section’s index will display a paginated table of all content entries in that section. So, using the example in Figure 4-9, clicking Content and then Articles would allow you to browse all article entries in the system. Each column in the table will correspond to one of the section’s fields (you get to decide which fields display in the table).

You’ll notice that each entry in the index table is linked. Clicking the link will bring you to the entry editor, a form that enables you to create and edit entries. What this form looks like, of course, depends entirely on the fields you’ve added to your section, and how you’ve chosen to lay them out. We’ll explain how all that happens in Chapter 5.

> ###### Note
> 
> 
> The index/editor paradigm is a convention used throughout the Symphony admin interface. All of your content sections, and all system components (like views and data sources), have indexes—where you can view all the items in paginated tables—and editors—where you can fill out forms to create and update individual items.

### Blueprints

The Blueprints submenu allows you to manage all of the functional building blocks discussed above: views, sections, data sources, events, and utilities. Together, these determine the structure and behavior of your entire website, hence the term blueprints. We’re going to cover each of these elements—along with the interfaces for managing them—in great detail in Chapters 5 through 8, so for now let’s move on.

### System

The System submenu contains items that allow you to customize and fine-tune your copy of Symphony. By default, it consists of three items: Users, Settings, and Extensions.

As mentioned above, a user in Symphony is someone who has access to the system and is allowed to log in to the back end. Going to System > Users will take you to the user index, where you’ll see your own user account and any others you’ve created. Clicking your name will take you to the user editor, where you can adjust your user details, change your password, and so on. We’ll discuss users in more detail in Chapter 9.

Symphony’s Settings page is a simple form that allows you to manage a few basic system configurations like your website’s name, the default language for the admin interface, and so on. If you install any extensions that have settings of their own, you’ll be able to manage those here as well. Symphony only exposes the most commonly used settings in the admin interface; many more are available in a configuration file, which we’ll talk about in the next section.

The System menu is also where you’ll find the area for managing extensions. Because a central tenet of Symphony’s philosophy is to keep the core of the system lean and efficient, extensions are a vital part of any Symphony website. Much of the functionality that will make your website unique and useful will be provided by the rich ecosystem of extensions built by the Symphony team and by the community. All of your field types and your data source types, for example, are provided by extensions.

At System > Extensions, you can enable, disable, and uninstall extensions, and monitor the status of the extensions you’re using at any given time. We’ll cover how to work with extensions more fully in Chapter 9.

## Physical Footprint

Depending on how technically inclined you are, you may not spend very much time at all exploring the various directories and files that live on your server and keep Symphony humming along smoothly. But, whether you’re doing periodic backups, fine-tuning advanced configuration options, or troubleshooting a problem, knowing how Symphony is structured physically could turn out to be a lifesaver someday.

### Folder Structure

When you upload and install Symphony, you create on your server a hierarchy of directories and files that looks like Figure 4-10:

    Figure 4-10	[f0410.png]

Many of the files at the root of your installation are self-explanatory. The index.php file, as you might guess, is the main switchboard for your Symphony site. All requests, front-end and back-end, are routed through it. The update.php file has an equally obvious use: you run it when you want to update from one version of Symphony to another. When Symphony is installed it also creates a file called .htaccess (depending on your operating system, it might be hidden from view). This file contains server-level directives that help with routing URL requests and is a common place to look when your site isn’t behaving as expected.

In addition to these top-level files, Symphony will create several directories. Let’s look at those now.

#### Extensions

This one is simple enough. It’s where your extensions live. Each individual extension has its own directory inside the extensions/ folder. When you want to install a new extension, simply place it in this directory, and you’ll be able to enable it from within the admin interface.

> ###### Note
> 
> Symphony auto-includes extensions by predicting their folder names based on the names of the extension classes in PHP. If you’re ever trying to install an extension and it’s not appearing in the back-end, one of the first things to check is that the extension’s folder is named correctly.

#### Install

The install/ folder contains the files necessary to run Symphony’s installer. Once you’ve successfully installed Symphony, this folder should be removed.

#### The Manifest

In shipping, the manifest is a document that contains a log and description of all the cargo and passengers on board a ship. The idea is that most of what you need to know about a ship can be gleaned from looking over its manifest. Symphony’s manifest/ directory is named in that same spirit. Most of what you need to know about a particular installation of Symphony can be found in the various files in its manifest.

The manifest contains your site’s cache, its configuration files, its system logs, and its temporary directory. It also contains your extension configuration. In other words, all the system information specific to your website can be found here (as opposed to build and content information, which can be found in the Workspace).

Inside the manifest folder is a config directory. This directory contains XML configuration files, which you can edit directly in lieu of, or in addition to, using the Settings page in the admin interface. Many configuration options are available in these files that are not accessible from the Symphony back end. We’ll talk more about those in Chapter 9.

Finally, whenever you’re troubleshooting problems with your Symphony installation, a logical first place to look is in manifest/logs/. There you’ll find system logs detailing all manner of server-level events. You’ll want to have the info handy whenever you ask for help with Symphony.

> ###### Note
> 
> If you ever want to customize Symphony’s internal templates (like the default view template XSLT, or the default system error pages), you just need to create a directory within manifest/ called templates/ and copy into it duplicates of the files you find in symphony/templates/. The files in manifest/ will override the default system versions, and you customize them to your heart’s content.

#### The Symphony Directory

The symphony/ directory is where the core of the system is located. You shouldn’t muddle about in there, but if you know what you’re doing and are interested in seeing how Symphony works, feel free to explore. Just note that modifying anything in here will void all warranties, as they say.

#### The Workspace

The workspace/ directory contains all of your project-specific files. By default, the workspace will store data sources, events, sections, views, and utilities. Because all of your website’s blueprints are file-based and stored in the workspace, Symphony projects can be very elegantly version-controlled, and integrated structures can be easily shared, reused, and remixed between websites. Symphony’s admin interface will dynamically accommodate changes to files in the workspace, even if those changes aren’t propagated from within the back end itself.

In addition to your blueprints, you can use the workspace to store files uploaded via upload fields in your entries, and developers will often store other assets here as well, like CSS and JavaScript files or template images. As long as the default subdirectories are left intact, users are free to create any directory structure they like within the workspace/ folder.

> ###### Example
> 
> #### Common Workspace Setup
> 
> In addition to the default directories where your blueprints are stored, it’s common to have folders in your workspace like scripts/ (for your JavaScript files), styles/ (for your CSS and related assets), and uploads/ (for files uploaded via entry fields). It’s also fairly common to have subdirectories within uploads/ to separate each section’s files, e.g. uploads/issues/ for your magazine’s cover images and uploads/writers/ for your authors’ profile pictures. 

## Differences Between Symphony 2 and Symphony 3

This book covers the current version of Symphony, version 3. But many existing Symphony users will be most familiar with its predecessor, Symphony 2, and the majority of websites running Symphony at the time of this book’s publication will still be powered by Symphony 2. With that in mind, it probably makes sense to review some of the key differences between the two versions, in case you ever find yourself having to work with Symphony 2. If you don’t need to work with Symphony 2 at all, feel free to skip ahead to the summary at the end of this chapter.

### Nomenclature

In Symphony 2, views were known as pages. Pages was actually a misleading term, though, because one “page” in Symphony could actually power an entire front-end interface that rendered any number of web “pages” to a visitor. It turned out to be doubly confusing because most other content management systems have a “page” concept as well wherein “pages” are containers for static content. The fact that Symphony’s use of pages went against this widespread convention probably caused some confusion and frustration for new users, so with version 3 the term was changed to the more appropriate views.

### File Structures

In Symphony 2, sections and views were stored in the database rather than in XML files in the workspace. As a result, version control and collaboration was much more tricky with Symphony 2.

Also, in Symphony 2, the configuration was stored in a single PHP file in manifest/, rather than two separate XML files. Splitting the configuration into two files for version 3 makes it possible to move the database configuration to a more secure, inaccessible location on the server.

In Symphony 2 it was not possible to override Symphony’s default internal templates as described above.

### Database

Symphony 2 stored lots of structural data (section schemas, page configurations) in the database alongside actual content. Symphony 3, on the other hand, moves that structural data into files, as described above.

Also, Symphony 3 adopts a much more meaningful table-naming schema, sym_data_section-name_field-name rather than sym_entries_data_field-id. So where, in Symphony 2, you’d have a table called sym_entries_data_42, in Symphony 3 it’s called sym_data_articles_title. This makes working directly with the database much easier.

### Extensions

Many things became extensions in Symphony 3 that were built into the core in version 2:

Field types. In Symphony 2, there were core field types, a set of 6 field types that were bundled with the core and couldn’t be altered or removed. Additional field types could be added as extensions. In Symphony 3, though, all field types are extensions, meaning they’re all treated equally and can be swapped and replaced at will.

Data source types. In Symphony 2, there were only five data source types. Custom data sources could be written in PHP, and extensions could provide their own individual data sources, but it wasn’t possible to provide new types that users could use when creating data sources. In Symphony 3, all data source types are extensions, and extensions can provide new data source types.

Event types. In Symphony 2, there was only one event type: the section saving event. It was possible to author custom events, but not to create other kinds of events from within the admin interface. In Symphony 3, however, it is possible for extensions to provide additional event types.

### Admin Interface

Figure 4-11 shows Symphony 2’s admin interface:

    Figure 4-11	[f0411.png]

In Symphony 2, all navigation submenus were left-aligned, and Events, Data Sources, and Utilities were grouped together on a single back-end page called “Components,” which was accessible in the Blueprints menu. In Symphony 3, as we’ve seen, each of those is split out into its own separate index page.

## Summary

We’ve taken a whirlwind tour of Symphony, inside and out, to give you a sense of the structure and scope of the system before we start having fun and getting our hands dirty. We’ve reviewed the system’s functional anatomy, or the various elements that it uses to enable you to create, manage, and deliver content on the web. We’ve also walked through the back-end admin interface to give you an idea of where all the functional pieces are located and how you can find them and work with them. We looked quickly at the files and folders that comprise Symphony on your server, and explained what most of them do so that you know where to look when you want to back up your data or troubleshoot a problem. Finally, we covered the key differences between Symphony 2 and Symphony 3, in case you ever find yourself needing to work with the older version.

Now that you’ve seen the roadmap, and have a rough sense of how Symphony works, let’s go have some fun!