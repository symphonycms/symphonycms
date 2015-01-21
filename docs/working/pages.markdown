##META
* Doc Version: 1
* Author: Giel Berkers
* Applies to: 2.x
* Based on: 2.x
* [Production URL]()

# A view on static pages #

First of all: let me say that "all roads lead to Rome", meaning that this article is a single view of a single person
on how to combine static and dynamic content in Symphony. It summarizes the things I've learned about this from when I
started with Symphony until today.

## First timers, beware! ##

A common feature of many content management systems is the ability to build a tree-like structure of pages. This allows you to create pages with a parent-child relationship, and define some basic content fields such as a title and body.

First-time users of Symphony often try to mimic this with their first websites (I know I did!) For example, you might create a section called _Pages_ and add some fields like _Title_, _Body_ and _Parent_ (_Parent_ is a dynamic selectbox, with which you can select other entries of the Pages section to use as parent). Next, you'd create a single page (called _Content_ for example), with a parameter _handle_ or _id_ to get the right entry out of your Pages section. This might work, but it has some serious drawbacks:

* The URLs of your static pages will always end up like:
    * www.your-website.com/content/12/
    * www.your-website.com/content/my-handle/
* You have to do some extra logic to create navigation, especially when you have mixed static and dynamic pages.
* If you want to hide a page from the navigation, you would have to add an extra field to your Pages section.
* If you have multiple navigations (such as a top menu, a utilities menu and a footer menu) this also requires some extra fields in your Pages section.
* It's particularly hard to make exceptions; for example:
    * If your client wants a different picture on each page you would have to add an extra field to the Pages section.
    * If you want to change the order of the pages you would have to depend on an [extension](https://github.com/nickdunn/order_entries).
  * ... and you would have to deal with all this logic in your XSL template.

## Forget pages, think content! ##

A page in Symphony is nothing more than a container for encapsulating content (data sources) and occasionally handling some logic (events).

The best way to structure your site is in **Blueprints > Pages**. This is fairly obvious for dynamic pages — you would create a unique page for a News section, and provide that with a parameter and the right data source to show the latest news items.

But here's the trick: all pages should be added here, also static pages! So, if you have a site structure like:

* Home
* About us
* Our Vision
* Products
* News
    * Detail
* Contact

You would need 7 pages, which result in 7 XSL documents:

* home.xsl
* about-us.xsl
* our-vision.xsl
* products.xsl
* news.xsl
* news-detail.xsl
* contact.xsl

## But that would mean I would end up with a lot of XSL templates, which are mostly identical! ##

That's true. But that isn't a problem, is it? In fact, it's ideal! Think about all the features that come straight out of the box:

* You will have nice URLs.
* You can make use of Symphony's built-in page logic:
    * Use types to create smart navigation data sources.
    * Have correct parent-child relationships.
    * Set the order of the pages
* Exceptions are no longer hard to do, after all you have a seperate XSL template for each page!

## What about the content? ##

Create a data source called 'Page Content'. This can have your common fields like _title_, _body_, and one field provided by an extension: a [_pages_ field](https://github.com/symphonycms/pagesfield). This allows you to link your entry to a page. Now when you create your data source, filter the pages field by `{$current-page}` to get the correct entry.

The XSLT could look something like this:

    <xsl:template match="data">
        <h1>
            <xsl:value-of select="page-content/entry/title" />
        </h1>
        <xsl:copy-of select="page-content/entry/content/*" />
    </xsl:template>

To fully support the DRY principle for code reuse, you could make a utility for static pages:

Utility:

    <xsl:template match="page-content" mode="static">
        <h1>
            <xsl:value-of select="entry/title" />
        </h1>
        <xsl:copy-of select="entry/content/*" />
    </xsl:template>

Page:

    <xsl:import href="../utilities/static-content.xsl" />
    <xsl:template match="data">
        <xsl:apply-templates select="page-content" mode="static" />
    </xsl:template>

This makes it quite easy to manage your static content, and provides a simple way to handle exceptions.

## Hold the phone! All of this means my client will not be able to add new pages by himself! ##

That's also true. But is adding pages really what your client wants? In my experience as a web developer, I've found that 99% of my clients don't need to add new pages to their site on a regular basis. What they do add are:

* News items
* Projects
* Publications
* Downloads
* Links
* FAQs
* Pictures

All these items will have their own section and their own page, resulting in URLs like:

* www.your-website.com/news/handle/
* www.your-website.com/projects/handle/
* www.your-website.com/publications/handle/
* www.your-website.com/downloads/category/

Now if you look at _projects_ or _publications_, it would appear that each project or publication would have its own page. But in reality, you only have one XSL template that deals with the logic. An even better approach is to add a detail sub-page; this keeps your XSL clean and reduces page rendering time spent on extraneous data sources that  will not be used. In the above example, the URLs would become:

* www.your-website.com/news/ _= news page, perhaps showing the latest 5 items._
* www.your-website.com/news/detail/handle/ _= a complete news item chosen by the visitor._
* www.your-website.com/projects/ _= projects homepage, showing all the projects with a picture and a short summary._
* www.your-website.com/projects/detail/handle/ _= the project detail page, showing the complete project._

So you see, it's more a way of thinking. As I said before, all roads lead to Rome, and this is just one of many ways on how you could approach pages in Symphony. I hope this article has given you some insight on how to do this.

Now, let's build some static pages, shall we?