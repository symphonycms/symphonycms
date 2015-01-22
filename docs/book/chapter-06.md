---
id: chapter-06
title: Front-end
layout: docs
---

# Chapter 6: Front-end

## What’s in This Chapter

- About front-end architecture
- Understanding Symphony’s front end
- Working with Symphony’s front end

Now that you’ve got your content modeled, the next step is to begin thinking about your blog’s front end—you know, the part that everyone sees. What interfaces do you want to provide for your visitors? Where will these interfaces live? How do you want their URLs to be structured?

Front ends come in all kinds of shapes and sizes, from traditional websites that mimic page-based architectures to applications with modular, responsive interfaces or even specialized setups like APIs. With so many possibilities, it’d be incredibly limiting for a system to predetermine how your front end should be structured.

Thankfully, Symphony doesn’t. That task is left entirely up to you. This means, though, that the blog you’ve been building doesn’t even have a front end yet. (Just like there was no content structure at all until you defined one in Chapter 5). Don’t believe me? Try pointing your browser to it: http://yoursite.com/.

    Figure 6-1	[f0601.png]

The error message you see there (Figure 6-1) is thrown because you haven’t yet created any interfaces, so Symphony doesn’t know how you want it to handle requests. Until there are structures in place defining where and how people should be able to access your site, Symphony will simply shrug its shoulders like this and say “sorry.”

The next step in building your blog, then, is to start mapping these structures out, developing what we’ll call a front-end architecture. We’ll begin by talking about what front-end architecture looks like and what it entails. Then I’ll explain how a Symphony front end works, and we’ll review all the important concepts you need to know before you get started.

After that, all that’s left is to do the work. You’ll see that the process of building out a front end is actually pretty simple once you know what you’re doing. By the time we finish this chapter, you’ll be well on your way to mastering the art of front-end architecture, and your blog will be beginning to take visible, tangible shape.

To get you oriented, let’s start with another quick exercise:

1. Navigate to Blueprints > Views
2. Click the green “Create New” button
3. You’ll see the view editor (Figure 6-2). Enter Home as the title, and / (a forward slash) as the handle. For now, leave the rest of the fields blank.
4. Click “Create View”

    Figure 6-2	[f0602.png]

Now, if you return to your front end, you’ll see that the error is gone. By creating this view, you’ve defined the first point of interface for your blog, one that sits at the root (/) of your site. The view is just displaying some simple placeholder content right now (Figure 6-3), because you haven’t written its template yet (something we’ll take care of in Chapter 8). But it’s a start. 

    Figure 6-3	[f0603.png]

Before we press on, let’s take a moment to assess the task at hand.

## What is Front-End Architecture?

As I pointed out above, Symphony doesn’t make any assumptions about how and where you want to deliver content to your users. All of these decisions are left entirely up to you, which means that your site doesn’t have any front end interfaces at all until you build them. It’s the classic Symphony trade-off: complete freedom and flexibility for a little bit of extra work.

So what does developing a front-end architecture entail? Let’s start with a real-world example.

Imagine for a moment that you’ve been asked to help design and build a new museum. The fundamental content questions have already been sorted—what collections the museum will house, what kinds of exhibitions it’ll host, how its holdings will be stored. What you’ve been asked to do is to sit down with the architect and begin planning a physical layout for the museum—its great halls and exhibit rooms, its entrances and exits, its restrooms, ticket counters, fire escapes, and so on. In short, your task is to define how and where the museum’s visitors will be able to view its content and accomplish important tasks. 

Websites and web applications require the same sort of planning. They need to determine where various kinds of content will be on display, where visitors will go when they need more information, what happens when people get lost, etc. Their front ends require an architecture—a logic for handling visitors and their requests, and a structure for organizing and facilitating interactions.

At its core, developing a front-end architecture involves three interrelated tasks:

- Identifying the interfaces that you’ll make available to your visitors
- Figuring out how those interfaces will be organized
- Deciding what interactions each interface will need to facilitate

In a simple, page-based architecture, each page corresponds to one URL and one set of contents, so mapping out a front end is no more difficult than organizing a bunch of files into folders. In Symphony, however, things are a bit different. A single view can power dozens or even hundreds of pages and URLs. 

It’s a far more powerful and flexible approach, and though it’s slightly more challenging conceptually, in the end developing the architecture is almost as easy. You just have to know how it all works.

## Understanding Symphony’s Front End

When you visit a Symphony site, every page that gets loaded in your browser is the result of a transaction: you submit a request to the server, and Symphony formulates a response. I explained roughly how all this works back in Chapter 4, but it bears elaborating here. 

Views are the engines of a Symphony front end, fielding requests and orchestrating the system’s responses. Every view has resources attached to it—data sources for fetching content and/or events for processing interactions (we’ll cover both in the next chapter). When a view is called, these resources get executed, and they return XML content to the view. The view then uses its template to transform that content into a digestible response. Figure 6-4 illustrates the basic process:

    Figure 6-4	[f0604.png]

Put very simply, each view constitutes a point of interface between a site and its visitors.

What makes views so powerful is that their URLs can have dynamic parts, or parameters. These get passed through to the resources and the template, meaning everything that a view needs to take care of, from content retrieval to presentation, can be dynamic and almost infinitely extensible.

Here’s a simple example. Let’s say you’re building an online children’s store. You create a view called “Shop,” available at the URL handle shop, and configure it to accept three parameters in its URL: department, age group, and gender. So if someone were to visit http://yoursite.com/shop/toys/toddlers/girls, all three parameters would be set:

- department = toys
- age-group = toddlers
- gender = girls

A data source attached to this view could fetch products from your catalog using these dynamic values to filter the results. Assuming your Products section has the appropriate fields, the above URL would return products from the “toys” department, in the “toddlers” age group, targeted for “girls.” 

Imagine the breadth of possible variations:

- http://yoursite.com/shop/clothing/
- http://yoursite.com/shop/clothing/infants/
- http://yoursite.com/shop/books/
- http://yoursite.com/shop/books/adolescents/boys

… and so on, ad infinitum.

You could use these values in your templates, too, allowing you to adjust your shop’s theme for different departments, age groups, or genders. 

In short, you could power an entire shop interface with this single view!

As powerful as views are, you needn’t be intimidated. Planning your blog’s front-end architecture actually isn’t all that daunting a task. Once I’ve reviewed a few important concepts, you’ll see so for yourself.

### Views

Views are points of interface for a website. Everything your visitors can do, from browsing content to submitting forms, is powered by a view. A view can be as simple as a old-fashioned static page, or robust enough to power a dynamic web application interface.

Views define a website’s URL schema. Every view has a URL handle and is able to accept URL parameters. These two attributes, along with the ability to nest views hierarchically (we’ll discuss this below), mean views give you full control over even the most intricate URL structures.

Views are the context in which data exchange and templating take place. This will be important to remember over the next two chapters. When you’re planning interactions or sketching out your presentation layer, the contextual information (like parameters and event output) and the content that will be available to you are both determined by the view.

> ###### Note
> 
> Those familiar with MVC architecture might find all this a bit confusing, because from an MVC perspective, Symphony’s views would actually be part of the controller layer rather than the view layer. If you’re accustomed to a framework like Ruby on Rails and tend to think in MVC terms, it’ll help you to know that Symphony’s views combine the functions of a classic MVC controller with a web framework’s URL routing system.
> 
> You can manage the views you’ve created at Blueprints > Views.

### View Types

There are circumstances in which you want some views to behave differently than others. For this reason, Symphony allows for different types of views.

View types define special behaviors and handling options. For example, there is a view type that restricts access to authenticated users, and one that designates a view to handle 404 errors. View types give you an added measure of control over not only the structure but also the behavior of your front end interfaces. What’s more, view types can be provided by extensions, making Symphony’s front end endlessly flexible.

### URL Parameters

URL parameters allow views to accept dynamic values in their URL. These values can then be used by the view (and its resources and templates) as it orchestrates the system’s response.

> ###### Note
> 
> Views are able to use values passed via a URL’s query string as well. This makes it possible to work with optional values that shouldn’t affect a URL’s basic structure, for instance:
> 
>     http://yoursite.com/shop?sort=price&order=asc&limit=50

This URL would set three query string parameters—price, order, and limit—which could then be used just like a URL parameter. Rather than having the view define these explicitly, though, they can be tacked on to the URL on an ad-hoc basis.

### View Resources

View resources enable a view to deliver content or process interactions. There are two types of view resources:

- Data sources deliver content to a view, either from within the system or from an external source. As we’ve seen, the view is a dynamic environment, and data sources can inherit this dynamism (for instance by filtering their results using a URL parameter).
- Events process interactions. Most commonly, they allow data submitted to a view (via a web form, for instance) to be saved into the system. Commenting and contact forms are good examples of this sort of behavior.

We’ll discuss data sources and events in greater depth in Chapter 7.

### Planning Your Blog’s Front End

Now that you have a good sense of how Symphony will run your front end, we can go ahead and plan the interfaces you’ll need for your blog. This should be fairly easy because, for the most part, blogs have developed a pretty standard format.

For each interface we plan, we’ll need to answer a handful of questions: 

- Where will it live?
- What content will it need to provide?
- Will it need to process any interactions?
- What parameters will it need to accept?

In the interest of keeping things simple, your blog will be powered by just three interfaces: a home view, a view for reading individual posts, and an archive.

We got a head start on the first of these earlier. Your Home view is going to live at the root (/) of your site. Like most blogs’ home pages, it’ll just display a stream of your most recent posts. The wrinkle is that we’re going to allow it to be filtered by category. So we’ll add a URL parameter for category.

The Posts view, a dedicated interface for reading individual posts, will have a handle of /posts, and since we’ll need to fetch each post by its title, should have a single URL parameter for title. That’ll give us nice, friendly URLs like http://yoursite.com/posts/how-to-become-a-luchador. This view will also need to process comments, something we’ll take up in the following chapter.

The Archive view will provide an interface for browsing through your blog’s history. It’ll be located at /archive, and will fetch posts based on their publish date. That means it’ll need to accept parameters for year and month (allowing for URLs like http://yoursite.com/archive/2010 or http://yoursite.com/archive/2011/01).

We could add a few more interfaces, but let’s leave it at this for now. 

Bear in mind, this is only one of many, many possible front-end architectures we could have whipped up for your blog. You might already be thinking of ways you’d want to improve things, or additional interfaces you’d want to offer. But since our goal here is just to familiarize you with the system and illustrate some important concepts, you’ll have to be patient for the time being. You can always go back and adjust things later if you like.

With this plan in place, scaffolding your front end is going to be a walk in the park. Let’s get started.

## Working with Symphony’s Front End

There are two ways to manage views: using the admin interface (under Blueprints > Views), or by working with the files directly (in workspace/views). 

> ###### Note
> 
> Like sections, views’ configurations and templates are stored as files in your workspace. Each view gets a dedicated folder, which is named using the view’s handle. The hierarchy of these folders mirrors the hierarchy of the views themselves.

Inside the view’s folder are two files: an XML file containing the its configuration, and an XSL file containing its template. We’ll discuss templates in Chapter 8, but here’s a peek at a (slightly simplified) sample view configuration:

    <?xml version="1.0" encoding="UTF-8"?>
    <view>
      <title>Inventory</title>
      <content-type>text/html;charset=utf-8</content-type>
      <url-parameters>
        <item>department</item>
      </url-parameters>
      <data-sources>
        <item>inventory-items</item>
      </data-sources>
      <events>
        <item>update-inventory</item>
      </events>
      <types>
        <item>admin</item>
      </types>
    </view>

Using the file system to manage views can be efficient, especially when you’re doing large-scale restructuring, but you don’t get the same combination of ease-of-use and fine-grained control as you would with the admin interface.

### Creating and Editing Views

Let’s start by revisiting your Home view and taking a closer look at the view editor.

1. Navigate to Blueprints > Views
2. Click “Home”

The one adjustment we need to make here is to add the URL parameter for category filtering:

1. In URL Parameters, enter category
2. Click “Save Changes”

Your view should now look like Figure 6-5:

    Figure 6-5	[f0605.png]

The view editor, as you can see, is organized into three parts: “Essentials,” where you identify the view and its type; “URL Settings,” where you define the various properties that will determine the view’s URL(s); and “Resources,” where you can attach data sources and events to the view. Let’s take a closer look at each field:

- Title is a simple, human-readable title for the view—something like “Home,” “About,” or “Browse Products”
- View Type allows you to select a type for the view. By default the system provides four view types:
    - “Normal” views have no special behaviors or conditions associated with them.
    - “Admin” views can only be accessed by users who are logged in to the system.
    - A “403” view (you should only specify one) is used when a visitor requests a forbidden URL.
    - A “404” view is used when a visitor requests a non-existent URL.
- Handle is the primary URL identifier for a view. If no handle is specified during view creation, one is automatically generated using the view’s title.
- Parent allows you to nest views hierarchically. The view’s URL will then include its parent’s handle: http://yoursite.com/parent-handle/handle.
- URL Parameters allows you to define one or more parameters that can be accepted by the view. Parameters are slash-delimited: parameter1/parameter2.
- Data Sources allows you to attach data sources to deliver content to a view.
- Events allows you to attach events to a view for processing interactions.

Let’s quickly create the two remaining views. First, your Posts view:

1. Navigate to Blueprints > Views
2. Click “Create New”
3. For title, enter Posts
4. For handle, enter posts
5. For URL parameters, enter title
6. Click “Save Changes”

Now the archive view:

1. In the notification bar, click “Create another”
2. For title, enter Archive
3. For handle, enter archive
4. For URL parameters, enter year/month
5. Click “Save Changes”

Just like that, you’ve defined the three interfaces that are going to power your blog. Of course, we’ve got a ways to go before they’ll actually do anything useful, but the structure is already in place. Didn’t I tell you it was going to be easy?

In fact, it’s been a bit too easy, so we’ll make this slightly more interesting. Let’s say, as very often happens when you’re working on a web project, that you’ve changed your mind about something. You’ve decided that you want your posts’ URLs to include the category in which you’ve placed them. So instead of, say:

    http://yoursite.com/posts/choosing-a-stripper

You want:

    http://yoursite.com/painting-tips/posts/choosing-a-stripper

Seems like a sound decision. Let’s go ahead and implement it.

### Crafting a URL Schema

It’s beyond the scope of this book to discuss what makes a good URL schema, and really, it depends on the site. But whatever your needs it’s important that you understand the formula that goes into determining a view’s URL, so let’s quickly review that now.

If you don’t specify a parent, your view will be located directly at the site’s root:

    http://yoursite.com/handle

Any parameters your view accepts can be tacked on to the end of that URL:

    http://yoursite.com/handle/parameter1/parameter2

When your view does have a parent, naturally its URL nests beneath that of the parent:

    http://yoursite.com/parent-handle/handle

And if the parent accepts parameters, you can nest behind those too:

    http://yoursite.com/parent-handle/parent-parameter/handle

If the parent itself has a parent, the same rules apply. I’m going to assume that, by now, you can use your powers of deduction to figure out what those URLs would look like, so let’s move on.

### Organizing Views

With all of that in mind, let’s make that change we talked about above. There are two ways to nest views: using the parent field in the view editor, or by physically nesting the view directories in the file system. The latter is less flexible (you can’t nest behind a parent view’s parameters without opening and editing the view configuration), but it’s useful nonetheless, so we’ll look briefly at both methods:

1. Using a file browser, or if necessary the command line, browse to the location on your server or computer where you installed Symphony.
2. Descend down into the workspace/ directory, and then into views/. You’ll see directories there for each of the views you’ve created.
3. Move the posts/ directory into the home/ directory, so that the directory hierarchy looks like Figure 6-6:

		 Figure 6-6	[f0606.png]

That’s it. Your Posts view is now nested beneath your Home view. Symphony instantly recognizes the change:

1. Back in the admin interface, navigate to Blueprints > Views

You’ll see that the views index now reflects the change you made in the file system, and Posts is nested beneath Home. Neat, huh?

    Figure 6-7	[f0607.png]

But we’re not finished yet. Remember, the whole point was to nest our Posts view behind the Home view’s category parameter, and that’s a level of control we can only get using the admin interface:

1. Click “Posts”
2. The parent dropdown now lists all the views you’ve created, along with various permutations including their parameters (Figure 6-7). Select “/:category”
3. Click “Save Changes”

        Figure 6-8	[f0608.png]

As you can see, the parent dropdown gives you the ability to choose very precisely where you want to nest a view, whether directly beneath the parent page or beneath one or more of its parameters. When you’re building complex websites or web applications, this kind of flexibility can come in very handy.

Also, the fact that views can be so easily rearranged, updated, and reorganized means that when you’re developing with Symphony it’s absolutely trivial to experiment with different architectures, tweaking and adjusting until you’ve got it just right. 

## Summary

And with that, you’re finished architecting your blog’s front end.

As with content modeling, I’ve tried to demonstrate here that front-end architecture really isn’t all that difficult once you understand what it entails. If you’re a seasoned developer, of course, most of this was elementary to you, but if not, I hope you’re beginning to feel empowered. This is serious web development work you’re doing, the sort that many systems try to hide from their users. Symphony not only hands you the reins, it actually makes the task easy and enjoyable.

In this chapter, we talked about what it means to develop a front-end architecture, and we reviewed how exactly a Symphony front end works. We then thoroughly dissected views and their various properties and accoutrements. Finally, we planned a structure for your blog’s front end and then built it, fairly quickly and unceremoniously, in just a handful of steps.

Though we restrained from doing anything too complex, I hope you’ve gotten a sense of how powerful views really are. They give you the ability to craft almost any kind of front end you can imagine. Just how this is so might not be entirely clear yet, but as we pull everything together over the next two chapters, I think you’ll begin to see for yourself.