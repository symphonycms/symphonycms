# XSLT Chapter

...

In this chapter, you’ll learn how to template a Symphony website—in other words, how to define the output that will be returned to your visitors. Most often, you’ll be outputting HTML pages, but not always. You can also output Atom feeds, JSON documents, raw XML, or pretty much any other text-based format you can imagine. For the sake of your modest blog project, though, we’ll stick to plain old XHTML (XML-flavored HTML), at least for now.

This chapter’s going to be a little more challenging than the previous few. Although Symphony’s templating layer is structurally very simple, conceptually it’s different than systems you may have encountered elsewhere. On top of that, it’s powered by XSLT, which is a language unto itself (and one you may know nothing about). But just because it’s going to be challenging doesn’t mean it can’t be fun. In fact, if I do my job well, you’ll leave this chapter excited about XSLT and eager to learn more.

We’ll start by talking about that conceptual difference, then, in order to explain the unique approach that Symphony takes to templating. After that, I’ll outline the components involved in Symphony’s templating layer (which should be easy—there are only two), and then we’ll dive right into XSLT itself. That’s a book-length topic in its own right, but I’d like to teach you enough that you feel comfortable following along with the remainder of this book. A lot of Symphony’s punch comes straight from this wonderful templating language. Once we’ve gotten through all that, we’ll finish up by writing the templates that will give your new blog a face, even if it’s a fairly plain one.

> ###### Note
> 
> Until now, I’ve tried to avoid making too many assumptions about how much web development experience you have. This is the exception. I need to be able to trust that you know at least the basics of HTML. If you don’t, I worry that this chapter might be a bit overwhelming for you.
> 
> If you’re not confident in your HTML knowledge or think you could use a refresher (or if you’ve never even heard of HTML and at first glance figured it was a leftist political party in Honduras), then it’s probably a good idea to set this book aside for a day and read through a quick introduction to HTML. The folks at http://htmldog.com have an excellent HTML Beginner tutorial.

Before we get started exploring Symphony’s templating layer, though, let’s try a sample exercise to get your feet wet.

(1)	Go to Framework > Views

(2)	Click “Home” to edit your Home view

(3)	You’ll see two tabs in the view editor, “Configuration” and “Template” (Figure 8-1)

    Figure 8-1	[f0801.png]

(4)	Click “Template”

The view template editor is fairly simple (Figure 8-2). It contains a large textarea for your template code, and a list of available XSLT utilities (more on those later).

    Figure 8-2	[f0802.png]

By default, Symphony generates a simple starter template for each new view—just a placeholder that outputs an XHTML page with some basic info about the view and its data. Don’t panic if any of the code scares you. By the end of this chapter, you’ll be comfortable with all of it.

The starter template looks like this:

    <?xml version="1.0" encoding="UTF-8"?>
    <xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
      <xsl:output method="xml"
        doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
        doctype-system="http://www.w3.org/TR/xhtml1/DTD/ xhtml1-strict.dtd"
        omit-xml-declaration="yes"
        encoding="UTF-8"
        indent="yes" />
     
      <xsl:template match="/">
        <html>
          <head>
            <title><xsl:value-of select="context/website/name"/></title>
          </head>
          <body>
            <h1><xsl:value-of select="context/view/title"/></h1>
          </body>
        </html>
      </xsl:template>
    
    </xsl:stylesheet>

On the whole the syntax should feel vaguely familiar, and if you’ve got a keen eye you’ll notice some HTML in there. For now, we’re just going to make some very simple changes to this template so it will output the entry titles being returned by your Recent Posts data source.

(5)	Just below the line containing `<h1><xsl:value-of select=”data/context/view/title”/></h1>`, enter:

    <h2>Recent Posts</h2>
    <ul>
      <xsl:apply-templates select=”recent-posts/entry”/>
    </ul>
  
> ###### Translation”
> 
> “Output a second-level header (`h2`) element containing the text Recent Posts, followed by an unordered list (`ul`) element. Inside the unordered list, I want you to apply templates to the entry items being returned by my Recent Posts data source.”

(6)	Now, after `</xsl:template>` and before `</xsl:stylesheet>`, enter:

    <xsl:template match=”recent-posts/entry”>
      <li>
        <xsl:value-of select=”title”/>
      </li>
    </xsl:template>


> ###### Translation
> 
> “Here’s the template I want you to use for those entry items. Just create a list item (`li`) element and inside it output the entry’s title.”

Your final template should look like this (I’ve emphasized the bits I asked you to add):

    <?xml version="1.0" encoding="UTF-8"?>
    <xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
      <xsl:output method="xml"
        doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
        doctype-system="http://www.w3.org/TR/xhtml1/DTD/ xhtml1-strict.dtd"
        omit-xml-declaration="yes"
        encoding="UTF-8"
        indent="yes" />
     
      <xsl:template match="/">
        <html>
          <head>
            <title><xsl:value-of select=”context/website/name”/></title>
          </head>
          <body>
            <h1><xsl:value-of select=”context/view/title”/></h1>
            <h2>Recent Posts</h2>
            <ul>
              <xsl:apply-templates select=”recent-posts/entry”/>
            </ul>
          </body>
        </html>
      </xsl:template>
     
      <xsl:template match=”recent-posts/entry”>
        <li>
          <xsl:value-of select=”title”/>
        </li>
      </xsl:template>
    
    </xsl:stylesheet>

(7)	Click “Save Changes”

If you visit your Home view now, you should see something like Figure 8-3.

    Figure 8-3	[f0803.png]

It’s pretty barebones at the moment, I’ll admit, but it’s something. The content that you modeled and created yourself back in Chapter 5 has finally found its way to your adoring public.

Now, if you’ve never seen XML or XSLT before, that exercise might have been a little intimidating. Don’t worry. Like me, it’s actually far simpler than it looks. Before we get into figuring out how to crack all that code, though, let’s review exactly how templating works in Symphony.

## What is Templating?

Loosely speaking, templating is how a web application prepares the output that it will render to its users. There are lots of different kinds of web templating systems out there, each with its own virtues (and shortcomings), but the majority of them can probably be said to take one of two approaches:

- The first, which we'll call "parsed output," allows you to write your output directly (in HTML template files, for instance), adding in dynamic bits via specialized tags or bits of procedural code. The system then parses the template, evaluates the dynamic bits, and outputs the result.
- The other approach, which we'll call "programmed output," allows you to write code that will generate the output you're after using the system's native programming language.

Of course, this is an oversimplification, and there are certainly exceptions, but in my experience these do seem to be the dominant paradigms. If you've done any web development before, chances are you're familiar with one or both of them.

The parsed output approach has the benefit of being fairly simple and easier for beginners to grasp. If you already know how to write HTML, for example, you can can just whip up your templates as normal and then drop in a few copied-and-pasted tags where necessary.

The programmed output approach, on the other hand, is very powerful, because you can lean on the full capabilities of a robust programming language like PHP, Python, or Ruby. If you know the language well, you can do pretty much anything you want in the course of generating your output.

Both can and have been used to great effect, but they usually have significant drawbacks too. Parsed output, for example, tends to be very limited, both in terms of the data you can access and what you’re able to do with it. These systems often rely on a narrowly-defined custom syntax or set of functions that’s fairly shallow and not useful in other contexts.

Programmed output, on the other hand, has a pretty high barrier of entry for designers and other non-programmers. More importantly, though, it subjects you to the procedural constraints of languages that aren’t really designed for templating. These kinds of systems can easily get messy because often there is no clear distinction between the presentation logic and, well, everything else. 

Whichever approach they take, the vast majority of web templating systems make it difficult or impossible to intelligently organize and reuse code, and they nearly always leave their dirty little fingerprints all over your output. As a result, in many systems, templating on the whole can often feel like an afterthought—something pieced together and tacked onto a system just to allow its content to be turned into HTML and decorated.

Symphony’s approach doesn’t really fall on this spectrum. It’s a radically different tack, one you might call "transformed output." With it, you craft a standalone system of stylesheets with rules for turning the raw data delivered by the system into some sort of digestible format. So rather than having you write output laced with procedural code, or procedural code laced with output, Symphony's templating system is an entirely self-contained layer, powered by a language that was designed just for templating (XSLT).

This can be a little disorienting if you're accustomed to other kinds of systems, and it isn't without its own drawbacks. For starters, XSLT can involve a bigger learning curve than simple tag-based template syntaxes, and it's more verbose (although some people like that about it). There are also things that XSLT can't do as easily as a full-fledged programming language can.

That said, though, the benefits of a transformed output approach far outweigh these minor gripes. First of all, you have complete, unfettered access to all the raw data you need to craft your presentation. Also, as I said above, the templating layer is completely self-contained, which means it’s clean and lean, and the system can’t interfere with your output in any way. This self-containment also means that your code can be organized elegantly and reused. Finally, and perhaps most importantly, you get all the benefits of a dedicated, open templating language in XSLT. We’ll talk about what those benefits are in a moment.

So what does Symphony’s implementation of this approach look like?

## Understanding Templating in Symphony

As you’ve seen, when someone visits your Symphony site's front end, a view handles their request and goes on to build the XML data that will power the view and its interactions. But this isn't what gets delivered to your visitor. Instead, it’s left entirely up to the templating layer to transform that raw data into some sort of usable output.

The templating layer is made up of view templates and XSLT utilities. Every view has an accompanying view template that it uses to transform its source XML. XSLT utilities are more generic and can be included in these transformations at will. 

Figure 8-4 illustrates the templating process:

    Figure 8-4	[f0804.png]

Both view templates and XSLT utilities are simply vanilla XSLT stylesheets. No custom syntax or special processing required. So in order to understand how Symphony’s templating system works, you just need to understand how XSLT works.

To help you get there, I'm going to whisk you through a basic introduction to XSLT at neck-breaking speed. I hope you’re wearing a helmet. (Not because you need protection, though. It’s just funny to imagine you reading this book with a helmet on.)

Ok, ready?

## Understanding XSLT

XSLT is the keystone in a trio of languages developed to transform and format XML. Collectively, those languages are known as XSL, or Extensible Stylesheet Language.
Each member of the XSL family has a specific role:

- XSLT, or XSL Transformations, is used to transform XML data into other kinds of output.
- XPath, or XML Path Language, is used to identify nodes in an XML document for transformation or formatting.
- XSL-FO, or XSL Formatting Objects, is a presentational language used for formatting XML data, usually for print.
In the Symphony context, we only need to talk about the first two. XSL-FO can be useful for things like PDF-generation, but that sort of thing is a bit advanced for this book.

Though the usage of XSLT as a web templating language is not exactly commonplace, there are lots of factors that make it ideal for this exact purpose. Here’s a handful of them:

- **It’s an XML technology.** This means native handling of every web feed, every XHTML page, every RDF format, and nearly every API that exists on the web.
- **It’s an open standard.** Maintained by the world’s web standards body (W3C), XSLT is widely-used, widely-supported, and well-documented. You won’t have trouble finding resources or getting answers, and once you’ve learned XSLT it can be helpful anywhere XML is used (which is pretty much everywhere).
- **It’s content-driven.** Everything you output is directly tied to the data you need to present, meaning your presentation can always be lean and semantic.
- **It’s rule-based.** Rules are much more powerful than mixtures of markup and procedural code. They are distinct and self-contained, but can also have complex relationships and interdependencies.
- **It’s flexible.** XSLT can output nearly any text-based format there is, even ones that haven’t been invented yet.
- **It’s a complete templating language.** With XSLT you can craft an organized, coherent presentation system rather than cobbling pages together out of snippets and tags using languages like PHP.

In short, XSLT is what one might call “awesomecake,” and after reading about all the ways it can make your life easier, there’s a good chance you’re standing up doing fist pumps right now. With your helmet on. Which is fantastic.

So here’s how it all works...

XSLT defines sets of instructions that are used to transform source XML and create some kind of output (Figure 8-5).

    Figure 8-5	[f0805.png]

Let’s look at a few very simplistic examples of this process to give you an idea of what I mean. Just try to follow along as best you can, but don’t panic if anything confuses you—we’ll go through it all in more detail below.

Imagine that you have the following XML content:

    <book>
      <title>Symphony Start to Finish</title>
      <author>Craig Zheng</author>
    </book>

We’ll assume you don’t need to do anything fancy with it—you just want to output the book’s title. In your XSLT stylesheet, the first thing you’d do is write a template rule that matches that book element:

    <xsl:template match=”book”>
      … do stuff …
    </xsl:template>

Now anything you put inside this template will be used to generate output when that element gets processed. Since you just want to spit out the book’s title, you’d write:

    <xsl:template match=”book”>
      <xsl:value-of select=”title”/>
    </xsl:template>

That template, applied to the XML above, would just output the text from the title element:

    Symphony Start to Finish

And if you wanted to output XML or HTML, you would do something like this:

    <xsl:template match=”book”>
      <h1><xsl:value-of select=”title”/></h1>
    </xsl:template>

That transformation would get you this output:

    <h1>Symphony Start to Finish</h1>

Not too bad so far, right? Before we get into the nitty gritty details of the language, let’s step back and review what’s actually going on during an XSLT transformation.

### How Transformations Work

Imagine you’re my personal assistant (I’m liking this example already). I have several stacks of unorganized books, and I want you to help me create a catalog. So I ask you to sift through the books, one by one, and do the following:

- Enter each book’s details—title, author, publisher, year, and subject—into a row in a spreadsheet
- Scan the cover and save the resulting image to my hard drive

What I’ve done is I’ve asked you to process a bunch of content, and I’ve given you some instructions telling you what to do for each item you come across. When you’re finished, I’ll have a nifty spreadsheet and a stack of cover images.

This is more or less how XSLT works. A processor starts with some XML content, and as it parses that content, it uses instructions from an XSLT stylesheet to generate some other sort of output.

When you write XSLT stylesheets, then, you’re essentially creating systems of rules and instructions.

> ###### Note
> 
> If you’re familiar with other programming languages, this might take some getting used to. Many common programming languages are imperative—they issue lots of commands, one after the other. And as you saw above, lots of web developers are accustomed to systems that rely on imperative languages for templating—they might even begin to think about building output as a series of commands: *First include the head, then loop through entries, then include this other snippet...*
> 
> XSLT, on the other hand, is a declarative language. Instead of issuing commands, it simply states what should be done in a given context. It’s rather similar to CSS in that way. Neither language describes a sequence of events or functions. They just say, “Hey, when you come across this element, this is how you should style/transform it.”
> 
> Templating with this kind of rule-based language takes a different sort of mindset, but it’s actually a much more powerful and flexible approach. A list of commands can only be followed, but rules can have scope and interdependencies, they can cascade, they can override one another.
> 
> You might not fully understand what I mean here, and as I said above, this is really a book-length topic of its own. My hope is just that by the end of this book you’ll have seen enough of XSLT’s power as a web templating language that you’ll want to go and spend a little bit of time learning it in earnest.

Now that you’ve gotten an overview, let’s walk through what’s happening during one of these transformations. I’ll begin by explaining how an XSLT processor sees and interprets its source XML. Then we’ll talk about how a stylesheet’s instructions are applied and what they can do. We’ll finish up with a brief review of stylesheet structure and organization.

Parsing XML

The first thing the processor does during a transformation is load the source XML. It needs to parse all of the data into a document tree—a hierarchy of identifiable bits called nodes—so that the stylesheet can work with it. Let’s look at how XML data is broken down.

> ###### Note
> 
> We’ll stick to the basics here—if you want a detailed history of the language or a thorough breakdown, there are much better places to get it than this. We’re just going to concern ourselves with what you need to know in order to grasp the fundamentals of XSLT.

XML is a markup language, which basically means that you use it to describe content in some way. For example, look at this text:

    Amazon

That could be any of a half-dozen things—the internet company, the rainforest, the mythical tribe of female warriors. So let’s use XML to mark it up:

    <river>Amazon</river>

Ok, now the text has got some meaning attached to it. You’re no longer just looking at some ambiguous word but at a meaningful XML element.

At its very simplest, XML is made up of elements like this—just various things that exist in the universe of a particular XML document. A menu, for example, might have elements like dish and beverage, while a real estate listing might have elements like house or apartment.

Elements are identified by tags. In the example above, <river> and </river> are the tags. One opens the element, the other closes it—anything in between is the element’s content. Elements can have textual content, as above, or can contain child elements, like this:

    <river>
      <name>Amazon</name>
    </river>

If an element hasn’t got any content at all, the opening tag can simply close itself:

    <river />

Elements can also have attributes—key-value pairs that store additional information for the element:

<river continent=”South America” miles=”4200”>Amazon</river>

Taken all together, the basic ingredients of XML syntax are pretty straightforward:

<element-name attribute=”value”>text content</element-name>

Each of them—elements, attributes, and text—is a different kind of node that can be identified and processed during an XSLT transformation.

Now, if you’ve ever written any HTML in your life, all of this should look pretty familiar. If not, I’m fairly certain you won’t have any trouble catching on. After all, the syntax, by definition, is descriptive. Let’s imagine what a house might look like, for example, if we described it with XML:

    <house color=”yellow” square-feet=”2560” style=”colonial”>
      <room square-feet=”420” type=”kitchen”/>
      <room square-feet=”660” type=”bedroom”/>
      ...
    </house>

Not too difficult, right? We’ve got a house element with a few descriptive attributes and a couple of room elements for children.

Now let’s look at a snippet of the XML that your Recent Posts data source is providing to your Home view:

    <recent-posts>
      <entry id=”2”>
        <title handle=”my-second-post”>My Second Post</title>
        <category handle=”journal”>Journal</category>
        <publish-date>2011-01-21</publish-date>
      </entry>
      <entry id=”1”>
        <title handle=”my-first-post”>My First Post</title>
        <category handle=”journal”>Journal</category>
        <publish-date>2011-01-19</publish-date>
      </entry>
    </recent-posts>

If you can parse the above and identify the two entries and the various nodes that comprise them, then congratulations... you’re already conversant in XML! (You must have a really fantastic teacher.) And you’ll be glad to know that XSLT is itself an XML format, which means you already have a basic understanding of its syntax!

> ###### Note
> 
> This is all simplified, of course. There are lots of little rules and wrinkles that you’ll have to learn. For instance, XML is a very strict language and most use cases require it to be well-formed—meaning elements must be nested properly, closed properly, contain only valid characters, and so on. But we’ll cover most of those rules as we go along. No need to get bogged down right now.

So, as I was saying above, once the XSLT processor loads an XML source document, it begins stepping through its nodes, one by one. Let’s look at the XML for your Home view to see how this works. You can see the source for yourself at http://example.com/?debug, but I’ll paste a sample here for reference:

    <data>
      <context>
        <view>
          <title>Home</title>
          <handle>home</handle>
          <path>/</path>
          <current-url>http://example.com/</current-url>
        </view>
        <system>
          <site-name>Tales of a Highway Bandit Hacker</site-name>
          <site-url>http://example.com</site-url>
          <admin-url> http://example.com/symphony</admin-url>
          <symphony-version>3.0</symphony-version>
        </system>
        <date>
          <today>2011-01-25</today>
          <current-time>19:03</current-time>
          <this-year>2011</this-year>
          <this-month>01</this-month>
          <this-day>25</this-day>
          <timezone>America/New_York</timezone>
        </date>
        ...
      </context>
      <recent-posts section="blog-posts">
        <entry id="2">
          <title mode="formatted" handle="my-second-entry">My Second entry</title>
          <body mode="formatted">is flavored like potato.</body>
          <publish-date time="15:38" weekday="6">2011-02-12</publish-date>
          <category handle=”journal”>Journal</category>
        </entry>
        <entry id="1">
          <title mode="formatted" handle="my-first-entry">My First Entry</title>
          <body mode="formatted">Is flavored like awesomesauce</body>
          <publish-date time="15:38" weekday="3">2011-01-12</publish-date>
          <category handle=”journal”>Journal</category>
        </entry>
      </recent-posts>
    </data>

The processor starts at the root (or document) node, which is a node that contains the entire source tree. It then processes the top-most element (in this case, data), and begins working through its children one by one. First up would be the context element—the processor would crawl down through each of its descendants (view, title, handle, path… system, site-name, and so on). Then it would proceed to the data element and crawl through its descendants, and their attributes and contents.

As it’s stepping through XML like this, the processor checks the stylesheet for any instructions that it should apply to a particular node in order to generate output.

Let’s talk about how those instructions work.

### Templates

*Templates* are where all of the action happens, as you saw in some of the simple examples above. They are the real meat of an XSLT stylesheet, and they define most of what happens during a transformation—building output, specifying logic, routing the processor, and so on.

There are two types of templates, *template rules* (sometimes called “matching templates”) and named templates. Both are defined using xsl:template elements. The stylesheet you’re using to transform the XML above has two templates in it:

    <xsl:template match="/">
      <html>
        <head>
          <title><xsl:value-of select="data/context/website/name"/></title>
        </head>
        <body>
          <h1><xsl:value-of select="data/context/view/title"/></h1>
          <h2>Recent Posts</h2>
          <ul>
            <xsl:apply-templates select="data/recent-posts/entry"/>
          </ul>
        </body>
      </html>
    </xsl:template>

    <xsl:template match="recent-posts/entry">
      <li>
        <xsl:value-of select="title"/>
      </li>
    </xsl:template>

Both of these are template rules—they specify the nodes to which they should be applied using a pattern in their match attribute (more on those patterns in a moment). When the processor reaches nodes that match the pattern, it follows the instructions provided by the template. Template rules can have more than one match pattern (they’re separated by the pipe character, |), meaning the same template can be used to process different node-sets.

The other kind of template, the named template, isn’t matched to the source tree but rather is invoked explicitly. We’ll discuss these more in depth a bit later.

type="note"

If you’re accustomed to writing CSS rules, which have a selector and then a set of declarations, template rules are sort of an analogous structure. The match attribute contains the “selector,” and then the template contains details about how the selected node is to be handled:

    <xsl:template match=”recent-posts/entry”>
      … do stuff …
    </xsl:template>

Template rules are matched to source nodes using XPath patterns. Let’s look at the two that are used in the stylesheet above:

    /

and

    recent-posts/entry

Each of these patterns is used to match the template to a particular node or node-set in the source XML. The first matches the root node; the second matches entry elements that are children of a recent-posts element.

A great deal of what templates do depends on being able to select or match nodes in the source tree like this, so let’s take a quick detour into the world of XPath so you can get a sense of how these expressions work.

> ###### Note
> 
> The debug devkit actually allows you to test XPath expressions live on your source XML. If you go to http://example.com/?debug, you’ll see an XPath expression bar (it’s got //* in it by default). Any XPath expression you type into that bar will highlight the matching node or nodes in the source XML. It’s a tremendously helpful tool for learning XPath. As we walk through the various kinds of XPath expressions in this section, feel free to test them out. You can also experiment with expressions of your own to get a sense of what works and what doesn’t.

### XPath: A Crash Course

XPath is designed specifically to enable you to identify nodes or node-sets in an XML tree. There are several types of nodes, as you’ve seen: the root node, element nodes, attribute nodes, and text nodes (along with a few others that aren’t really important for us at the moment).

XSLT transformations rely on XPath extensively, because you have to identify nodes in the source tree in all kinds of contexts—when you’re defining template rules, when you’re grabbing and manipulating source content, when you’re giving instructions to the processor, and so on.

Thankfully, XPath provides a very powerful and versatile syntax for building all kinds of expressions. Expressions allow you to pinpoint nodes based on their location, their node type, and even their contents and values.

> ###### Note
> 
> Patterns, like the ones you saw above, are a subset of XPath expressions that are used in specific contexts (like template match attributes). While expressions as a whole are written to target particular nodes in a source tree, patterns allow you to specify conditions that you want nodes to meet in order to be considered a match. The difference is subtle, but it can be important. You’ll see why in a moment.

#### Selecting Nodes by Location

The simplest kind of expression is a path expression, which is used to select nodes based on where they are in the tree. These will look familiar to you because they work very much like filesystem paths and URLs.

If you look back at your home view’s XML, for example, you could select the current date with the following expression:

    /context/date/today

Easy enough, right? Each bit is the name of an element, and each slash indicates a level in the hierarchy.

> ###### Note
> 
> Path Expressions and Context
> 
> When you’re describing locations like this, context becomes an important factor.
> 
> Patterns are not evaluated relative to any context because they’re just general conditions that nodes need to match. So something like `match=”entry”` will apply to any entry element in the entire source tree, and match=”recent-posts/entry” will apply to all entry elements that are a child of a recent-posts element.
> 
> Expressions in general, though, are evaluated relative to the node being processed. So if you have a template rule that matches `/context/date`, any XPath you use inside that template has to be relative to the date element. Path expressions like `entry` and `recent-posts/entry` wouldn’t work because those elements don’t exist inside the date element.
When you’re dealing with relative paths like this, you have to be able to move around the source tree. XPath makes this possible with axes (this is an advanced topic, so I’ll just give you the highlights and you can read up on your own). There are 13 axes, among them `parent::`, `ancestor::`, `descendent::`, `following::`, and so on. Each allows you to navigate through the source tree in a different way.
> 
> The default axis in XPath is `child::` (meaning entry is the same as `child::entry`). The other axes enable you to select nodes that are not children or direct descendents of the current context node.
> 
> Many axes have shorthand equivalents, so `../context` is the same as `parent::context` (just like in a file system), and `//entry` is the same as `descendent-or-self::entry` (it’ll look for entry elements at any depth from the context node).
> 
> Aside from using axes, the other way to sidestep the current node’s context is to make an expression root-relative by preceding it with a forward slash: `/context`. You can then build your expression to target a node based on its location from the root.

These path expressions should be pretty intuitive, and I’m sure you’ll pick them up quickly, so I’ll leave it at that for now.

#### Selecting Nodes by Type

The expressions we’ve seen so far only match element nodes. You can select attribute nodes and text nodes just as easily, though. To target an attribute node, you just prepend `@` before its name: `@id`. In a path expression, an element’s attributes go at the same level in the hierarchy as its children:

    //entry/@id

There are also times when you don’t want to select an entire element, only its text content. In that case, you’d use text() to specifically target the text node:

    /context/view/title/text()

#### Selecting Nodes by Condition

You can also select nodes based on various conditions. Let’s say, for instance, that you only want to select the Recent Posts entry element whose id attribute is 2:

    /data/recent-posts/entry[@id = ‘2’]

Or you only want to select the last entry in the source:

    //entry[last()]

The part of the expression that appears in brackets is called a *predicate*. Predicates allow you to specify additional conditions for selecting a node. The predicate doesn’t always have to come at the end of the expression, either:

    /data/recent-posts/entry[@id = 2]/title/@handle

Cool, huh?

There’s a lot more to XPath than this—the * wildcard, for instance, and functions like `position()`, `count()`, and `substring()`, but we’ll continue to flesh that stuff out over the rest of the book. This should be enough for now.

If you’ve done much web development, hopefully XPath syntax feels sort of natural to you. It’s actually not unlike many other selector languages, and the very popular jQuery framework actually supports some XPath syntax itself. If you’re confused, though, you might want to read up a bit on your own before we get into more advanced XSLT techniques in later chapters.

Anyway, now that you’ve gotten a rundown of XPath syntax, let’s get back to the task at hand—explaining the role that templates play in an XSLT transformation.

### What Templates Can Do

A processor takes nearly all of its instructions from templates, so they’ve got to be able to do everything from establishing the structure of an output document and defining processing logic to grabbing, manipulating, and outputting data.

#### Control Processor Flow

One of the most important things a template can do is organize output and control processor flow during a transformation. You see this at work in your Home view template.

You’ll recall that, above, I said that an XSLT processor steps through a source tree node by node. That’s its default behavior. XSLT also specifies a default template (used when it can’t find a matching template for the node it’s processing). The default template essentially just spits out any text content it finds. 

If those are both default behaviors, why isn’t all the text in your Home view’s source XML just dumped out onto the page?

The key is that, when the processor does find a template to apply, it defers to the template to tell it what to do. So that first template rule in your stylesheet, the one that matches the root node (`match="/"`), effectively seizes control of the entire transformation from the outset. If you were to write an empty template rule matching the root node:

    <xsl:template match="/">
    </xsl:template>

the processor would simply stop there and output nothing at all. It wouldn’t step through any of remaining nodes unless you told it to.

In your Home view template, then, the first template rule matches the root node and stops the processor from going about its normal business. Then it builds the overall structure for an XHTML page, and explicitly tells the processor where and how to proceed using the `xsl:apply-templates` element:

    <xsl:template match="/">
      <html>
        <head>
          <title><xsl:value-of select="data/context/website/name"/></title>
        </head>
        <body>
          <h1><xsl:value-of select="data/context/view/title"/></h1>
          <h2>Recent Posts</h2>
          <ul>
            <xsl:apply-templates select="data/recent-posts/entry"/>
          </ul>
        </body>
      </html>
    </xsl:template>

If used without a select attribute (i.e. just `<xsl:apply-templates/>`), this element would just send the processor back on its merry way, to resume its normal processing flow. In the example above, though, you actually don’t want it crawling through all of the XML; you have a specific template you want it to apply in a very specific place, so you direct it to the nodes that match that template.

> ###### Note
> 
> `<xsl:apply-templates>` is only one way for a template to direct the processor. You can also iterate over nodes using `<xsl:for-each>`. In fact, the stylesheet we’ve been talking about could’ve been written with only one template:
> 
>     <xsl:template match="/">
>       <html>
>         <head>
>           <title><xsl:value-of select="data/context/website/name"/></title>
>         </head>
>         <body>
>           <h1><xsl:value-of select="data/context/view/title"/></h1>
>           <h2>Recent Posts</h2>
>           <ul>
>             <xsl:for-each select=”data/recent-posts/entry”>
>               <li>
>                 <xsl:value-of select="title"/>
>               </li>
>             </xsl:for-each>
>           </ul>
>         </body>
>       </html>
>     </xsl:template>
> 
> This would produce the same output, but it’s generally not as flexible. In most cases, it’s better to handle different kinds of source content using discrete templates. This way, your code is easier to maintain and reuse (even if the structure of the source XML changes).

This may seem like a roundabout way to achieve your desired output structure, but the point is that it’s content-centric and imminently modular. As you continue to learn and use XSLT, you’ll come to appreciate how powerful this system can be.

#### Write Output

As you’ve seen, templates are also responsible for building the output that will result from a transformation.
One of the most basic things you can do in a template is to specify output directly. If you’re building an XML document, this output can come in the form of literal result elements. All this means is that you actually just put the elements you want in your output directly into the template. This is what you’ve done in the example above—the `<html>`, `<head>`, `<body>`, `<h1>`, `<ul>`, and so on… all of these are literal result elements.

You can also write plain text content or build nodes for your output explicitly using instructions like `xsl:element`, `xsl:attribute`, and `xsl:text`, but we won’t get into that right now since your output is all fairly simple. You’ll see these in action in some of the later chapters.

#### Get Source Data

Of course, a major reason for using XSLT in the first place is to work with the XML source document, and one of the things you’ll need to do most often in your templates is pull data from that source document to add to your output. There are a few very simple ways to do this.

The first is using the `xsl:value-of` element, which you’ve already seen in action quite a bit. It requires a select attribute containing an XPath expression which points to a node (or set of nodes) in the source tree. It adds the text value of the selected node(s) to the output.

The `xsl:copy-of` instruction works almost identically, but instead of adding just the text value, it adds a copy of the node itself.

Let’s use a small snippet from your Home view’s XML to demonstrate the difference between the two:

    <view>
      <title>Home</title>
      <handle>home</handle>
      <path>/</path>
      <current-url>http://example.com/</current-url>
    </view>

Using `<xsl:value-of select=”view/handle”/>` would just output home, but `<xsl:copy-of select=”view/handle”/>` would output `<handle>home</handle>`.

Make sense? Now, what happens if you want to include data from your source tree in an attribute of a literal result element in your output? You certainly *cannot* write something like:

    <a href=”<xsl:value-of select=”view/current-url”/>”>Home</a>

That’s all kinds of broken, and you’d probably bring down the entire internet and wipe out a penguin colony if you tried it. You could use an xsl:attribute instruction, but that can be a pain for something so simple. Luckily, there’s another way to output source data in this scenario; it’s called the *attribute value template*. It allows you, in an attribute of a literal result element, to wrap an XPath expression in curly braces `{}`. The processor will then evaluate the expression, turn its value into a string, and add it to the output. So to build a link to the view above, you’d just do:

    <a href=”{view/current-url}”>Home</a>

Attribute value templates can be mixed with literal text, too:

    <a href=”http://example.com{view/path}”>Home</a>

There are other ways to grab source data, and lots things you can do with that data before you output it, but these fundamentals will suffice for now.

#### Other Advanced Mumbo-Jumbo

What you’ve seen so far are the basics, but XSLT and XPath can do much, much more. This is not the time or the place to try to go through all of the capabilities of each language, but I do want to give you a glimpse of the various kinds of things I’m leaving unsaid, for example:

- You can define and use variables and parameters. These can be local or global in scope and are able to store values or even entire node-sets. 
- You can also create various kinds of conditional logic (using elements like xsl:if and xsl:choose)
- You can define advanced operations for the processor like the sorting of node-sets
- You can take advantage of XPath’s many functions, which allow you to manipulate strings, perform math operations, compare values, and more.

For more examples of cool things you can do with XSLT templates, check out http://xsltea.com/.

===

So, to summarize what we’ve covered up to this point, templates contain instructions for the processor to follow. You can use templates to structure an output document, define processing logic, write output, grab and manipulate source data, and much more. They are pretty powerful things. 

A lot of their power, though, actually comes not from what they can do but from how they can be organized and applied.

### How Templates are Organized

Every template is a modular, self-contained set of instructions that’s crafted to handle a specific kind of content. But matching them directly to source nodes isn’t the only way they can be used. Templates can also be grouped functionally into separate stylesheets, given varying levels of priority, invoked selectively and recursively, applied based on arbitrary modes, and used and reused over and over again.

#### Multiple Stylesheets

Because templates are usually content- or function-specific, it’s often helpful to group them into separate stylesheets based on what they do, what content they work with, or how they’re used. Imagine, for example, that you’ve got several templates that you use to help you format dates and date ranges. You could put those into a dedicated stylesheet and then import or include that stylesheet whenever you need to use those templates:

    <xsl:import href=”path-to-stylesheet.xsl”/>

Or:

    <xsl:include href=”path-to-stylesheet.xsl”/>

This is how Symphony’s XSLT utilities are pulled into a transformation by a view template. The view template can contain any instructions specific to that view, and everything else can be abstracted into XSLT utilities.

> ###### Note
> 
> There’s a subtle difference between including and importing, but you don’t need to worry about it at the moment. We’re going to discuss the subject in much more detail in Chapters 12 and 14.

#### Named Templates

As I mentioned earlier, in addition to matching source nodes, templates can also be called explicitly by name. Named templates are often used for specific tasks that can be helpful in multiple contexts—things like formatting dates, building lists, truncating strings, and so on.

What makes named templates especially useful is that they can be passed parameters when they’re called. So you could have a generic template for truncating strings, for instance, and then you could call that template from any context and have it truncate some bit of text to a desired length:

    <xsl:call-template name=”truncate-string”>
      <xsl:with-param name=”string” select=”path/to/content”/>
      <xsl:with-param name=”length” select=”’250’”/>
    </xsl:call-template>

This allows you to keep the template itself generic enough to be applied to any string, in any context, and invoke it only as needed.

> ###### Note
> 
> Calling named templates with parameters is a common way to do recursive operations in XSLT.

#### Priorities

Because XSLT allows you to build a complex system of template rules and instructions, the system needs a way to decide what to do when the node it’s processing is matched by more than one template rule. There’s a lot that goes into this decision, but the primary method of defining which template will win out is with a priority attribute:

    <xsl:template match=”entry” priority=”1”>
      … do stuff …
    </xsl:template>

The priority attribute can contain any real number, positive or negative. The template with the highest priority will be used.

#### Modes

We’ve already talked quite a bit about the ability to apply the same template to multiple nodes. Sometimes, though, you need to do the opposite—specify multiple ways or modes of processing the same content. 

XSLT makes this possible with a mode attribute. I’ll spare you the details for now, but you’ll see a useful example of modes in Chapter 12, when we abstract your HTML <head> into a common stylesheet while still allowing individual views to add CSS and JavaScript references to it.

### Anatomy of a Stylesheet

So how does this all come together? Let’s take another look at your home view template and break it down:

    <?xml version="1.0" encoding="UTF-8"?>
    <xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
      <xsl:output method="xml"
        doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
        doctype-system="http://www.w3.org/TR/xhtml1/DTD/ xhtml1-strict.dtd"
        omit-xml-declaration="yes"
        encoding="UTF-8"
        indent="yes" />
     
      <xsl:template match="/">
        <html>
          <head>
            <title><xsl:value-of select="data/context/website/name"/></title>
          </head>
          <body>
            <h1><xsl:value-of select="data/context/view/title"/></h1>
            <h2>Recent Posts</h2>
            <ul>
              <xsl:apply-templates select="data/recent-posts/entry"/>
            </ul>
          </body>
        </html>
      </xsl:template>
     
      <xsl:template match="recent-posts/entry">
        <li>
          <xsl:value-of select="title"/>
        </li>
      </xsl:template>
    
    </xsl:stylesheet>
    
Because every XSLT stylesheet is also an XML document, they all begin with what is called an XML declaration—basically just a line identifying the file as XML. It looks like this:

    <?xml version="1.0" encoding="UTF-8"?>

All XML documents also have what’s called a root element. This is a single element that contains all the other elements in the document. The root element of every XSLT stylesheet is <xsl:stylesheet>.

> ###### Note
> 
> The xsl: you see in front of the element’s name is a namespace prefix. XML namespaces are a pretty advanced topic, and you don’t need to worry too much about them now, but the basic idea is actually quite simple.
> 
> Because XML formats are allowed to define whatever elements they like, it’s possible for the same element names to be used in different contexts. Namespacing helps avoid confusion between formats, sort of like area codes in U.S. telephone numbers. Calling within an area code only requires a seven-digit number, but across the country lots and lots of people share the same seven-digit telephone number. Area codes are prefixes that allow people to communicate across different areas without confusion.

Everything in an XSLT stylesheet, then, is contained within an element that looks like this:

    <xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
      … everything goes here …
    </xsl:stylesheet>

> ###### Note
> 
> You’ll notice that the root element has two attributes. The first declares the xsl namespace so that the processor knows how to interpret the elements it finds. The second declares the version of XSLT that you’re using.

#### Top-Level Elements

The elements that can be direct children of `xsl:stylesheet` are called top-level elements. There aren’t many of these—just a dozen in total—which means that XSLT stylesheets are actually pretty simple, structurally speaking.

You’ve already seen the `xsl:import` and `xsl:include` elements. If you use those, they need to go before any other top-level elements. I also mentioned earlier that you could declare global parameters and variables (`xsl:param` and `xsl:variable`). And of course there’s xsl:template. Aside from those, there’s only one more important top-level element to talk about.
Because XSLT can transform XML into any kind of text-based format, you need to be able to configure a transformation’s output. You can do this with a series of attributes in a top-level element called xsl:output. Have a look at the output element in your Home view template:

    <xsl:output method="xml"
      doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
      doctype-system="http://www.w3.org/TR/xhtml1/DTD/ xhtml1-strict.dtd"
      omit-xml-declaration="yes"
      encoding="UTF-8"
      indent="yes" />

An XSLT stylesheet can have one of the following three output methods: xml, html, or text. xml is used for all proper XML formats, html for normal HTML documents, and text for everything else.

Your home view template’s output element begins by setting the output method as xml because it’s generating strict XHTML, and adds a couple of doctype attributes for the result document. It then specifies that it wants to omit the XML declaration that normally sits at the top of an XML document, and that its output should be encoded as UTF-8. Finally, it opts to have the output nicely indented. For your blog project, all of your view templates will use these same settings.

Most of the remaining top-level elements are seldom-used in a Symphony context, so most of the stylesheets you’ll create will have a structure that looks more or less like Figure 8-6:

    Figure 8-6	[f0806.png]

Exhale. That concludes your whirlwind tour of XSLT. Hopefully you’ve begun to see how it can empower you to craft elegant, modular, content-driven presentation systems. But if you’re not impressed yet, just know that it gets a lot cooler than this. We’re barely scratching the surface here. The point of this walkthrough has just been to expose you to XSLT’s syntax and basic structures so that you have a point of reference as we begin reviewing the language’s fundamentals.

[[deleted]]

Either way, you’ve learned enough that we can move on for now. I’m sure you’re itching to get your blog’s front end up and running, so let’s not dally any longer.

## Working with View Templates and XSLT Utilities 

View templates, as you saw earlier, are managed alongside the views they’re attached to, at Framework > Views.
XSLT Utilities can be managed at Framework > XSLT Utilities.

Of course, because they’re both XSLT stylesheets, they are available as physical files in your workspace. View templates are located in a view’s folder alongside its configuration file (so your Home view’s template is located at `workspace/views/home/home.xsl`). XSLT utilities are located in your `workspace/xslt-utilities/` folder.

### Writing a View Template

#### Home View

We’ll start by writing a proper view template for your Home view. You can follow along using the view template editor, or you can just open the file directly using your favorite text editor (`workspace/views/home/home.xsl`).

First things first, you’ll need to take a look at the source XML for the view (`http://example.com/?debug`). It should look something like this:

    <data>
      <context>
        <view>
          <title>Home</title>
          <handle>home</handle>
          <path>/</path>
          <current-url>http://example.com/</current-url>
        </view>
        <system>
          <site-name>Tales of a Highway Bandit Hacker</site-name>
          <site-url>http://example.com</site-url>
          <admin-url> http://example.com/symphony</admin-url>
          <symphony-version>3.0</symphony-version>
        </system>
        <date>
          <today>2011-01-25</today>
          <current-time>19:03</current-time>
          <this-year>2011</this-year>
          <this-month>01</this-month>
          <this-day>25</this-day>
          <timezone>America/New_York</timezone>
        </date>
        ...
      </context>
      <recent-posts section="blog-posts">
        <entry id="2">
          <title mode="formatted" handle="my-second-entry">My Second entry</title>
          <body mode="formatted"><p>Something something something complete.</p></body>
          <publish-date time="15:38" weekday="6">2011-02-12</publish-date>
          <category handle=”journal”>Journal</category>
        </entry>
        <entry id="1">
          <title mode="formatted" handle="my-first-entry">My First Entry</title>
          <body mode="formatted"><p>Something something something dark side.</p></body>
          <publish-date time="15:38" weekday="3">2011-01-12</publish-date>
          <category handle=”journal”>Journal</category>
        </entry>
      </recent-posts>
    </data>

That’s all the data that’s available to you as you’re crafting your output. Now let’s think about your desired XHTML:

    <html>
      <head>
        <title>The Name of Your Website</title>
      </head>
      <body>
        <h1>The Name of Your Website</h1>
        <ul id=”posts”>
          <li>
            <h2><a href=”/category/my-second-entry”>My Second Entry</a></h2>
            <p class=”date”>2011-02-12</p>
            <p>Something something something complete.</p>
          </li>
          …
        </ul>
      </body>
    </html>

Pretty simple, and thankfully, the default template has gotten you much of the way there. It sets up a basic XHTML document, taking care of the output declaration and so on. Here’s what it looks like after we tweaked at the beginning of the chapter:

    <?xml version="1.0" encoding="UTF-8"?>
    <xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
      <xsl:output method="xml"
        doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
        doctype-system="http://www.w3.org/TR/xhtml1/DTD/ xhtml1-strict.dtd"
        omit-xml-declaration="yes"
        encoding="UTF-8"
        indent="yes" />
     
      <xsl:template match="/">
        <html>
          <head>
            <title><xsl:value-of select=”context/website/name”/></title>
          </head>
          <body>
            <h1><xsl:value-of select=”context/view/title”/></h1>
            <h2>Recent Posts</h2>
            <ul>
              <xsl:apply-templates select=”recent-posts/entry”/>
            </ul>
          </body>
        </html>
      </xsl:template>
     
      <xsl:template match=”recent-posts/entry”>
        <li>
          <xsl:value-of select=”title”/>
        </li>
      </xsl:template>
    
    </xsl:stylesheet>

As you can see, there’s not much that you need to do. In the root template (I’m going to adopt the convention of referring to template rules according the nodes they match), you’ll just replace the content of the `<h1>` with your site name, get rid of the “Recent Posts” `<h2>`, and add an id to the posts list. Let’s take care of all that now.

1.	In the `<h1>`, change the select attribute of the value-of element to context/website/name. So the line should read `<h1><xsl:value-of select=”context/website/name”/></h1>`
2.	Delete the `<h2>Recent Posts</h2>` line
3.	Add an id attribute to the `<ul>` element, so it looks like this: `<ul id=”posts”>`

Now the last thing you’ll need to do is beef up the list items that are being output by the recent posts entry template. Right now, they just contain title text, but as you can see in the desired output, you’re aiming for a linked title heading, a paragraph containing the date, and then the entry’s textual content.

Building the title heading should be fairly easy. The `<h2>` and the `<a>` can be literal result elements. For the link’s content, we just need to use `value-of` to grab the title. The attribute is the only tricky bit. You’ll recall that individual posts will have URLs like `posts/category/title`. So you’ll have to piece together the anchor’s `href` attribute out of several different bits:

- The website’s base URL, which we can get from `/context/system/site-url`
- The entry’s category: `category/@handle` (you want the URL-friendly version provided by the element’s handle)
- The entry’s title: `title/@handle`

You’ll notice a difference between the first expression and the latter two. Because this template matches recent-posts/entry nodes, we’re able to grab the category and title data directly, but for the base URL we need to jump outside that context, so we build a path relative to the root node.
Piece it all together using attribute value templates (mixed with a bit of direct output), and you get:

    <h2><a href=”{/context/system/site-url}/posts/{category/@handle}/{title/@handle}”><xsl:value-of select=”title”/></a></h2>

Next you want to add a paragraph for the entry’s date. This bit’s pretty easy:

    <p class=”date”><xsl:value-of select=”publish-date”/></p>

The date won’t be nicely formatted (it’ll look like `2011-02-05`), but we’ll cover that later in the book.

Finally, you want to output the entry’s content. Because we’re capturing the content using Markdown formatting, it’ll be available as HTML, so you’ll want to use `xsl:copy-of` to make sure you get any HTML elements in the content.

    <xsl:copy-of select=”body/node()”/>

Why `body/node()` and not just `body`? If you used `<xsl:copy-of select=”body”/>`, you’d actually get the original <body> element in your output too. What you want is a copy of everything inside the body element, including any child elements. node() gets you exactly that.

One last optional bit: if you want some basic styling for your blog, you can add the following stylesheet to your head:

    <link rel=”stylesheet” type=”text/css” media=”screen” href=”http://book.symphony-cms.com/workspace/uploads/blog.css”/>

That’ll make your site a bit more presentable for the time being. Here’s the final stylesheet:

    <?xml version="1.0" encoding="UTF-8"?>
    <xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
      <xsl:output method="xml"
        doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
        doctype-system="http://www.w3.org/TR/xhtml1/DTD/ xhtml1-strict.dtd"
        omit-xml-declaration="yes"
        encoding="UTF-8"
        indent="yes" />
     
      <xsl:template match="/">
        <html>
          <head>
            <title><xsl:value-of select=”context/website/name”/></title>
            <!-- Optional stylesheet line goes here -->
          </head>
          <body>
            <h1><xsl:value-of select=”context/website/name”/></h1>
            <ul id=”posts”>
              <xsl:apply-templates select=”recent-posts/entry”/>
            </ul>
          </body>
        </html>
      </xsl:template>
     
      <xsl:template match=”recent-posts/entry”>
        <li>
          <h2>
            <a href=”{/context/system/site-url}/posts/{category/@handle}/{title/@handle}”>
              <xsl:value-of select=”title”/>
            </a>
          </h2>
          <p class=”date”><xsl:value-of select=”publish-date”/></p>
          <xsl:copy-of select=”body/node()”/>
        </li>
      </xsl:template>
    
    </xsl:stylesheet>

Got it? Ok, save the template. Now if you visit your home view you should see the full posts being displayed (Figure 8-7). Don’t worry that there’s no navigation yet. We’ll get to that shortly.

    Figure 8-7	[f0807.png]

#### Post View

Now let’s get the Post view set up so those title links will actually point to something. To edit the Post view template, either go to Framework > Views, click “Post,” and switch to the template tab, or open /workspace/views/post/post.xsl in your text editor. You’ll recognize the default template.
Let’s start again by figuring out what the desired XHTML will be for your individual posts. As with everything else, we’ll keep it very simple for now:

    <html>
      <head>
        <title>The Name of Your Website</title>
      </head>
      <body>
        <h1>Title of the post</h1>
        <p class=”date”>2012-12-21</p>
        <p>All the content...</p>
      </body>
    </html>

Have a peek at your source XML (http://example/com/post/journal/my-first-entry?debug). I won’t paste it here, but try to locate the bits of data you’re going to need—the post title, the date, the body content. Use the XPath tester to see if you can figure out the expressions that will point to them.
The templates you’ll end up writing are dead simple:

    <xsl:template match="/">
      <html>
        <head>
          <title><xsl:value-of select=”context/website/name”/></title>
          <!-- Optional stylesheet line goes here -->
        </head>
        <body>
          <xsl:apply-templates select=”individual-post/entry”/>
        </body>
      </html>
    </xsl:template>

    <xsl:template match=”individual-post/entry”>
      <h1><xsl:value-of select=”title”/></h1>
      <p class=”date”><xsl:value-of select=”publish-date”/></p>
      <xsl:copy-of select=”body/node()”/>
    </xsl:template>

Copy those two templates into your stylesheet (replacing the old one), and save it. If you go back to your Home view now, and click on a post title, you’ll be able to view your post.

#### Archive View

Last, but not least, let’s tackle the Archive view. Go to Framework > Views and click “Archive” and then the template tab, or open /workspace/views/archive/archive.xsl in your text editor.

Here’s the desired XHTML for your archive view:

    <html>
      <head>
        <title>The Name of Your Website</title>
      </head>
      <body id=”archive”>
        <h1>Archive</h1>
        <h2>2012</h2>
        <h3>December</h3>
        <ul>
          <li><a href=”http://example.com/posts/journal/my-second-entry”>My Second Entry</a></li>
        </ul>
      </body>
    </html>

Pretty simple, as usual, but the XML here is going to be a little different. In the last chapter, when you created the Posts by Date data source, you opted to have the results grouped by date. So instead of just having a set of entry elements, then, your data source will have a slightly more complex hierarchy that looks something like this:

    …
    <posts-by-date>
      <year value=”2012”>
        <month value=”11”>
          <entry/>
        </month>
        <month value=”12”>
          <entry/>
        </month>
      </year>
    </posts-by-date>
    ...

Your Archive view is set up to list entries either from a single month, or from an entire year, grouped by month. So this data structure plays right into your hands. You can have a template match the month elements, allowing you do render a separate heading and list for each month. Then another template can take care of the entry elements themselves. It’ll look like this:

    …
    <xsl:template match="/">
      <html>
        <head>
          <title><xsl:value-of select=”context/website/name”/></title>
          <!-- Optional stylesheet line goes here -->
        </head>
        <body>
          <h1>Archive</h1>
          <h2><xsl:value-of select=”posts-by-date/year/@value”/></h2>
          <xsl:apply-templates select=”posts-by-date/year/month”/>
        </body>
      </html>
    </xsl:template>
    
    <xsl:template match=”month”>
      <h3><xsl:value-of select=”@value”/></h3>
      <ul>
        <xsl:apply-templates select=”entry”/>
      </ul>
    </xsl:template>
    
    <xsl:template match=”posts-by-date//entry”>
      <li>
        <a href=”{/context/website/url}/posts/{category/@handle}/{title/@handle}”>
          <xsl:value-of select=”title”/>
        </a>
      </li>
    </xsl:template>
    …

In the root template, you include two headings, one for the view’s name and one for the year. Then you apply templates to the month elements. The template for months includes a third heading (for now containing just the month’s number), and an unordered list for its entries. Inside the unordered list, it applies templates to its child entry elements. That third template, for entries, just outputs a list item and a link for each entry.

Drop those three templates into your archive view stylesheet (replacing the default one), and save it. You should now have a pretty simple, but functional, archive view.

### Writing an XSLT Utility

Now, you’ve got three basic views, but they’re missing navigation. We’ll take this opportunity to introduce you to XSLT utilities and to named templates.

In many cases, you want your site’s navigation to be dynamic, so that your front-end can grow and adapt without you having to always manually change navigation items and so on. For now, though, we’re just going to stick with a static version.

	 1.	Go to Framework > XSLT Utilities
	 2.	Click the green “Create New” button
You’ll see that the XSLT utility editor looks just like the view template editor. The default stylesheet here is much more minimalist, though.

	 1.	In Name, enter navigation.xsl
The stylesheet you need is simple—one named template containing the output you’re looking for:

    <?xml version="1.0" encoding="UTF-8"?>
    <xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
    <xsl:template name=”navigation”>
      <ul id=”nav”>
        <li><a href=”{/context/website/url}/”>Home</a></li>
        <li><a href=”{/context/website/url}/archive”>Archive</a></li>
      </ul>
    </xsl:template>
    
    </xsl:stylesheet>

Enter this stylesheet into the editor and click “Create XSLT utility.”

### Using XSLT Utilities

As you learned earlier, you can make this navigation template available to your view templates by importing the stylesheet.

For each of the view templates you created above, go back and insert the following line before the xsl:output element:

    <xsl:import href=”navigation.xsl”/>

Then, just below each view template’s h1 element, add:

    <xsl:call-template name=”navigation”/>

Once you’ve updated and saved each of you view templates, you’ll see that each view on your front end includes a navigation menu now. That code is shared among all the views.

Of course, there’s a lot more they could share, and a lot more we could do to organize your templates more efficiently, but you’ll get there. What’s important right now is that you’ve got a basic sense of how view templates and XSLT utilities can fit together.

## Summary

Let’s take a walk down memory lane, all the way back to the beginning of this chapter when you were just sitting there, in your giant helmet, with no idea what XSLT was or how it worked. We’ve come a long way since then, huh?

We talked about Symphony’s “transformed output” approach to templating, and about how it differs from the kinds of templating systems you may have seen in the past. You learned that Symphony’s templating layer is comprised of view templates and XSLT utilities, and powered by XSLT.

After that, you learned what XSLT is and why it’s a great templating language for the web. You then got a crash course in XSLT and its accompanying technologies like XML and XPath. You learned about building XPath expressions, writing XSLT templates, and composing stylesheets. 

Finally, you wrote the templates that would make your new blog functional.

As exciting as this is, I’m sure you’re looking at your blog and thinking, “This is far from finished.” Part 3 of the book is going to help you master all of the most important Symphony workflows, and as you do so you’ll continue to flesh out and improve your modest little blog.

Before we move on to all that, though, we need to spend some time discussing issues of system management. How do you add users? How do you install extensions? Where can you set preferences? That’s what we’re going to discuss now.
