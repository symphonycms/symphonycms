---
id: chapter-07
title: Data Flow
layout: docs
---

# Chapter 7: Data Flow

## What’s in This Chapter

- What is data flow?
- Understanding how data flow works in Symphony
- Working with data sources
- Working with events

This is an exciting moment. Over the past two chapters, you’ve been dutifully creating the structures that are going to give your blog its shape—from its content models to its front-end architecture. With these pieces in place, you’re finally ready to begin breathing life into your new site.

On the web, “life” means interaction. It means give and take. It means communicating with your visitors, responding to their requests, allowing them to do things. And the substance of all these interactions is data. 

Every time one of your visitors clicks a link or submits a form, data is on the move—being exchanged, requested, collected, altered, stored, and so on. This data can be anything from website content to server information to user details. All the stuff that makes the web tick.

In short, every interaction on the web is a product of data flow. And so your next task is to pave the way for data to move within and around your blog, powering the interfaces you’ve so carefully planned.

As in the previous two chapters, we’ll begin with a broad discussion of the concept itself—what we mean when we talk about data flow, and what designing data flow for an application or a website generally entails. Then we’ll talk more specifically about how this works in Symphony, and introduce you to a few important concepts that you’ll need to understand before you get started. Finally, we’ll go about setting up the structures that will be responsible for managing data flow in your site.

But first, let’s get a brief taste of what this sort of work entails:

1. Navigate to Blueprints > Data Sources
2. Click the green “Create New” button. You’ll see Symphony’s data source editor (Figure 7-1).

        Figure 7-1	[f0701.png]

3. Name your new data source Recent Posts
4. In the Section dropdown, select “Blog Posts”
5. Under “Filtering,” you’ll see the filter tool. Click “Add Filter”.
6. In the drawer that opens up, select the “Published” field. A panel will appear for configuring your filter.
7. Leave the Mode dropdown set to “Is” and in the accompanying input, enter yes (or click the “yes” link beneath the field).
8. Under “Sorting,” in the Sort By dropdown, you’ll see a list of fields from your Blog Posts section. Select “Publish Date”.
9. Beneath that, in the Sort Order dropdown, select “descending”.
10. Under “Limiting,” enter a limit of 10 results per page.
11. In the “Included Fields” multiselect, choose “Title (formatted)”, “Body (formatted)”, “Publish Date”, and “Category”.
12. [Step to attach DS to Home view -- this functionality is not fully implemented yet]
13. Click “Create Data Source”

Now, if you recall, in Chapter 6 you decided that you wanted your blog’s Home view to serve up your most recent posts (well, actually, I decided that... but let’s pretend it was you). What you’ve done here, then, is created a data source that’s responsible for fetching that content—ten published posts, sorted by their publish date in descending order—and then attached that data source to your Home view.

Now I’m going to show you something really cool. Navigate to your blog’s Home view, but append ?debug to the end of the URL (so, http://example.com/?debug). You’ll see something that looks like Figure 7-2:

    Figure 7-2	[f0702.png]

What you’re looking at is Symphony’s debug devkit—an interface, provided by one of Symphony’s core extensions, that allows you to go behind the scenes of any Symphony view (only if you’re logged into the system, of course). The debug devkit will show you a view’s XML source content, the stylesheets that have been used to transform it, and the result of that transformation (e.g. HTML). You can also examine the parameters available to the view at runtime. In short, it’s X-Ray vision for every view on your site—allowing you to see exactly how it was built and from what pieces. Neat, right?

Don’t worry if you’re a little overwhelmed by what you see. If you’ve never looked at code before you might feel like you’re staring at the title sequence from The Matrix. Take courage—I’m going to teach you all about XML in the next chapter. 

Now, do you remember the sample blog posts we created back in Chapter 5? You should see those entries in your view’s source XML. Look closely. See them? Congratulations. Your content has made its way to the front end.

> ###### Note
>
> The debug devkit has even more tricks up its sleeve, but we’ll save most of those for Chapter 15. Still, it won’t hurt you to poke around a little bit if you’re curious. You’ll use this tool a whole lot when developing in Symphony—might as well get familiar with it.

Now that you’ve laid the foundation for this very basic interaction—a visitor arrives at your blog’s home view and is provided with a listing of recent posts—it’s time to think a little more thoroughly about how you imagine people using your blog. Every interaction you plan is going to have its own specific data needs, and in this chapter your job will be to figure those out, and to meet them.

## What is Data Flow?

Think of the last time you received a package in the mail, or a bill, or a postcard from a “friend” who was visiting some beautiful and exotic land and felt compelled to shove it in your face. Just a handful of coded lines jotted onto the front of an envelope or label and that little something could travel halfway around the world and land right on your doorstep. Amazing, no?

Millions of letters and parcels travel through the world’s postal systems every day—originating at mailboxes and post offices and in handshakes with letter carriers, and then somehow finding their way to even the most forgotten alleys, back roads, and outposts. 

Part of this everyday miracle is in the infrastructures—the buildings, machines, vehicles, and workers that collect, organize, sift, transport, and disseminate all that mail.

The other part of the miracle is basic logic. Every day, millions and millions of decisions are made to help move all this stuff along. What mail goes into which pile. Onto which truck. Which carrier gets which bag. These decisions are made over and over, every day, based on three or four tiny lines of information.

These systems aren’t perfect, as we’ve all learned at one time or another, but for the most part things tend to get to where they’re going, and every time they do it’s a little bit incredible.

Websites have to perform a similar task, albeit on a much smaller scale. While your visitors are submitting forms, clicking links, and browsing through interfaces, your site is churning away behind the scenes—handling and routing requests, parsing information, and retrieving, sorting, and delivering content.

Maybe not as miraculous, I’ll admit, but your website actually manages to keep all this data flowing in much the same way that a postal system does: with a carefully-designed and flexible infrastructure, and with a collection of rules and conditions that enable it to make decisions about what data goes where.

When we talk about designing data flow for a Symphony website, then, we’re talking about a handful of interrelated tasks:

- identifying the data needs of each of your interfaces
- building the infrastructures to manage that data
- designing logic to ensure that the data goes where it’s supposed to

Let’s take a quick look at how all this is implemented in Symphony.

## Understanding Data Flow in Symphony

You saw in Chapter 6 that when a view is called upon to respond to a request, it kicks its resources into action—first triggering its events and then executing its data sources. These two components are responsible for regulating the flow of data to and from the view (Figure 7-3).

    Figure 7-3	[f0703.png]

Events process various kinds of interactions, and these can be fairly wide-ranging—they’re able to save data to the system, set or alter contextual information, or perform other kinds of tasks depending on the event type. 

Data sources are more specific in scope. They fetch data and deliver it to the view as XML. Pretty straightforward, but there are many data source types, each responsible for retrieving content from a different kind of source.

What makes data sources and events so important is not just that they handle the flow of data to and from a view but that they also define the logic that governs it all. They comprise what would commonly be called the site’s “application logic”—the rules that determine what the system does and when.

For events, this logic comes in the form of event options, which make it possible to adjust an event’s behavior—for instance by specifying conditions for its execution or adding additional actions to be triggered.

Data sources do most of the heavy lifting, though, when it comes to application logic. Data source filtering is the primary hinge for determining what content is going to be delivered and under which circumstances. Data sources can also specify conditions for their execution, rules for how their results should be sorted and limited, and various options for how those results should be output.

The key to designing data flow, then, is understanding how you can use data sources and events to craft meaningful, intelligent interactions. Let’s review the most important concepts:

### Data Sources

Data sources fetch, filter, sort, and output content. Each of these tasks is a sort of logic axis—a place where you can have the system make decisions about what it should do.

Data sources can use dynamic environmental variables. You saw this in our URL parameters example in the previous chapter. The decisions your data sources make while fetching and processing content don’t have to be static or rigid. Their logic can adapt to their environment.

Data sources can have execution conditions. This is yet another logic axis, another decision point, where you can tell a data source whether or not to execute at all.

Data sources deliver their content to views. After all that, after all these decisions wrapped in decisions that depend on other decisions, it’s still entirely up to the view’s template to decide what it’s going to do with the content it gets.

### Data Source Types

Data source types determine where a data source gets its content. Most often, you’ll be fetching content from your entries—content that you modeled and created yourself. But there are many other data source types, which allow you to grab, for example:

- XML or JSON from external sites (for instance from APIs and feeds)
- static XML
- system information like users or views
- anything else an extension developer can dream up

You could say, then, that data source types define the range of data that can be used to power your site’s interactions.

Data source types determine how content can be filtered. Entry data sources, for instance, give you granular filtering and sorting options on a per-field basis. Dynamic XML data sources, on the other hand, allow you to filter their content using XPath (more about that in Chapter 8). This means that the logic implicit in the filtering process is both extensible and context-specific—it’s tailored to the data itself.

### Data Source Filters

Data source filters are rules used to identify the content you want. They are the difference between walking into a library and saying “I’m looking for books,” and saying “I’m looking for books written by political theorists on the concept of biopolitics.”

In other words, data source filters allow you to be precise about what you deliver to your visitors and in what contexts.

> ###### Note
>
> If you’re familiar with SQL syntax you might find it helpful to think of filters as WHERE clauses in a query—you define conditions that need to be met in order for content to be selected and returned.

When a data source contains multiple filters, they are joined using AND, meaning all of the filters must evaluate to TRUE in order for content to match and be returned.

### Events

Events enable views to take action with their data. For this reason, they’re often seen as complementary to data sources. Events can do anything from authenticate users to process credit card transactions or run commands on a server. But by far the most commonly used type of Symphony event is the entry-saving event, which allows system content to be submitted and saved from the front end.

> ###### Note
>
> Entry-saving events are often used to power simple user interactions like commenting, voting, and rating, but they can just as easily allow you to build fairly complex web applications. The Symphony website (http://symphony-cms.com), for example, has user-driven forums and a downloads repository, all powered by simple entry-saving events.

### Event Options

Event options allow you to adjust an event’s behavior or handling. There are generally three types of event options:

- conditions for the event’s execution (restricting events to authenticated users, for example, or performing anti-spam tests)
- additional actions to be performed (for instance, sending an email when an event is successful)
- event-specific settings (such as whether to allow an entry-saving event to process multiple entries in a single request)

Event options give you an additional vector of control when you’re crafting interactions.

### Planning Your Data Flow

Now that you’ve got a handle on the basic ingredients, let’s get down to business.

You’ll recall from the previous chapter that you’ve got three interfaces you need to plan around: a Home view, a Post view, and an Archive view. And you’ve already put some thought into the interactions that these three interfaces will need to support. Let’s take a moment now to evaluate the specific data needs of each.

Evaluating your interfaces’ data needs involves asking a series of targeted questions: 

- Where is the content coming from?
- Under what circumstances do you want it to be provided?
- What rules should be used to evaluate and select results?

This is also where you need to begin thinking about presentation, because how you intend to display your content determines what exactly you ask your data sources to output. Let’s add those questions too:

- What data do you need to display?
- How do you want it arranged and organized?

Answering these questions for each of your interfaces will give you a helpful blueprint when planning data flow.

Your Home view, we know, is going to display your ten most recent posts; we already took a swing at that at the beginning of this chapter. But you’ll recall that we also decided to allow that interface to be filtered by category. So the data source will need not only to deliver the ten most recent posts, but also to optionally filter its results by the category parameter if that’s been set in the view’s URL. Here’s a summary of what you need from this data source:

- **Source:** Blog Posts
- **Filtering:** Must be published; if category parameter is set, fetch only entries in that category
- **Sorting:** By date, descending
- **Limiting:** 10 entries per page
- **Output:** Title, Body, Publish Date, Category

The Post view is going to display a single post in its entirety. We’ll create another data source for this purpose, one that filters its results by the title and category parameters in the view’s URL. It’ll need some special handling instructions too. If the title parameter isn’t set, for instance, it shouldn’t even execute. And if it doesn’t execute, or if there’s no entry that matches the title, it should throw a 404 (Page not found) error. Here’s an overview:

- **Source:** Blog Posts
- **Conditions:** The title parameter must be set
- **Filtering:** Must be published; fetch the entry whose title matches the title parameter, and whose category matches the category parameter.
- **Limiting:** 1 entry
- **Output:** Title, Body, Publish Date, Category
- **Handling:** Redirect to 404 if no results

We also want to enable commenting on individual posts, so this view will need an entry-saving event allowing Comments to be submitted and saved. We’ll want to add some basic security measures to the event, which I’ll discuss later. Finally, we’ll have the event send you an email whenever someone comments. This is what you need from the event:

- **Section:** Comments
- **Overrides:** Post field set to the ID of the post being viewed
- **Options:** Send email filter, XSS Filter

You’ll also need a data source to fetch a post’s comments so that you can display them alongside the post. Let’s keep this one basic—return all comments attached to this post, and sort them from oldest to newest:

- **Source:** Comments
- **Filtering:** Post field matches the ID of the post being viewed
- **Sorting:** By date, ascending
- **Output:** Author, Email, Date, Comment

Finally, your archive view is going to provide an interface for browsing older posts. Let’s think about how you’ll want it to behave. By default, when no parameters are specified in the URL (http://example.com/archive/), the view should simply display all entries in the current year, grouped by month. If only a year is specified (http://example.com/archive/2011/), we’ll have it display that year’s entries, again grouped by month. If both year and month are specified (http://example.com/archive/2011/01/), it’ll just list all the entries from that month. And since we only want to list post titles here, we won’t need the body returned. Sound good?

Here’s the breakdown:

- **Source:** Blog Posts
- **Filtering:** Must be published; publish date matches year or year/month
- **Sorting:** By publish date, descending
- **Output:** Title, Date, Category; grouped by publish date

Now that you’ve got a clear sense of each interface’s data needs and the logic you’re going to use to regulate their interactions, let’s get the data flowing!

## Working with Data Sources

Data sources are managed in the admin interface at Blueprints > Data Sources.

> ###### Note
>
> Like sections and views, data sources are stored as physical files in your workspace, making them easy to version control. Unlike those other elements, though, data sources are stored as PHP files, and really shouldn’t be edited directly unless you know what you’re doing.

### Creating and Editing Data Sources

The first step in creating a data source is to choose its type. Each data source type has distinct filtering, sorting, and output options, so you’ll need to define the type before you can configure anything else.

1. Go to Blueprints > Data Sources
2. Click “Create New”

The configuration options available in the data source editor will change depending on what is selected in the “Type” dropdown (Figure 7-4).

    Figure 7-4	[f0704.png]

There are five data source types included by default with Symphony (many more are available as extensions). Each is identified by where it gets its content:

- An Entries data source fetches entry content from the sections you’ve defined in Symphony.
- A Users data source fetches user account data from within the system.
- A Views data source fetches data about the system’s views (useful for building a navigation, for example).
- A Dynamic XML data source fetches raw XML from an external source such as an RSS or Atom feed or a web service or API.
- A Static XML data source actually stores raw XML itself and returns that.

“Entries” is far and away the most common and so is selected by default. All of the data sources we’ll create in this chapter are going to be Entries data sources. Feel free to cycle through the other types to get a sense of what they look like, but in this chapter we’ll only cover the Entries type. You’ll be introduced to each of the others later in the book, and Appendix C provides a complete breakdown of all the types and the configuration options available for each.

Let’s walk through the configuration options available when creating an Entries data source. There are six sections—Essentials, Conditions, Filtering, Sorting, Limiting, and Output Options.

### Essentials

- Name is how your data source will be identified within the system.
- Section is the section from which your entries will be fetched.

> ###### Note / Best Practice
>
>Because data sources are attached to views, and because in very simple websites there can often be a one-to-one correspondence between a view and the data source that delivers its content, it can be easy to fall into the habit of thinking about a data source in terms of the view for which you’ve intended it. For example, you might be tempted to create a “Home” view and an accompanying “Home” data source.
> 
> This isn’t a great practice, though. Not only will most of your views rely on more than one data source, but you’ll also find ways to reuse data sources across multiple views.
> 
> Instead, try thinking about—and naming—your data sources in terms of their logic and the results they yield, rather than the views you’re attaching them to. You’ll notice, for instance, in the example at the beginning of this chapter we named the data source for your Home view “Recent Posts” rather than something like “Home” or “Homepage Posts.” If we ever decide to create a web feed, and we want to provide it with our most recent posts, we’ve got a data source clearly earmarked for just that purpose.
> 
> This practice will help you think more carefully about planning your data sources to be flexible and reusable, and in large projects you’ll always be able to tell exactly what each data source does just by glancing at its name.

### Conditions

The conditions tool (Figure 7-5) allows you to add and configure execution conditions for your data source.

    Figure 7-5	[f0705.png]

Each condition you add allows you to specify a parameter to test and a state (“is set” or “is empty”), and the data source will not execute if any of its conditions are met. Data source conditions can help you optimize your views by preventing unnecessary processing.

### Filtering

The filtering tool (Figure 7-6) allows you to add and configure filters to hone your results.

    Figure 7-6	[f0706.png]

For each filter you add, you need to specify a field to use. Once you’ve selected a field, you’re presented with a dropdown of the filtering modes that field offers (a text field, for example, has modes like “matches,” “contains,” “does not contain,” and so on), and an input for the expression you want to use to filter the results.

We’ll discuss filtering—modes, patterns, syntaxes, and the like—in greater detail below.

### Sorting

- Sort By allows you to select the field to use for sorting your results. Different field types may have different sorting rules, so be sure to review the list of field types and their behaviors in Appendix A.
- Sort Order allows you to specify how entries will be ordered: ascending, descending, or random.
Limiting
- Results per page is the number of entries you want the data source to return for each page of results
- Page is the page to return, and the field can accept a URL parameter. This allows you to automatically paginate results simply by using URLs like http://example.com/1/ and http://example.com/2/.
- The Redirect to 404 checkbox allows you to have your system respond with a “Page not found” error when no results have been returned.
Output Options
- Included fields allows you to specify which fields’ content you want to output and, for some fields, the output mode (some field types allow you to output either formatted or unformatted text, for instance). You can also choose to include system fields.
- Grouping allows you to optionally choose a field to use for grouping entries. Most field types will just group entries that have the same value, but some have special grouping rules. Date fields, for example, create a grouping hierarchy by year, month, and then date. This will be helpful for your archive view, where we want to list entries by month.
- Two additional checkboxes allow you to specify whether you want to output Pagination data and Sorting data for your results alongside the field content.

Having reviewed all the options available, let’s go ahead and create another data source—the one that’ll return your individual posts. You should already be looking at a blank data source editor:

1. Make sure “Entries” is selected as the Type
2. For Name, enter Individual Post
3. In the Section dropdown, choose “Blog Posts”

    We said that you don’t want the data source to execute if the view’s title parameter is empty, so let’s add that condition:

4. In the conditions tool, click “Add Condition”
5. In the condition panel (Figure 7-5, above), you’ll see two fields: Parameter and Logic.
6. Enter title in the Parameter field and select “Is Empty” from the Logic dropdown.

    Now, this data source is supposed to return the entry whose title is specified in the URL’s title parameter, and then only if that entry is marked as published. We also want to make sure the entry is in the right category. We’ll need three filters, then:

7. In the filtering tool, click “Add Filter”
8. In the drawer that appears, click the button for the Published field.
9. In the filter panel (Figure 7-6, above), select “Is” in the Mode dropdown and enter yes in the Value field (or click the “yes” link beneath the input—some field types give you hints like this if their values are predefined).
10. Click “Add Filter” again.
11. In the drawer that appears, click the button for the Title field.
12. In the filter panel, select “Is” in the Mode dropdown and enter {$title} into the Value field.

    > ###### Note
    >
    > You’ll use this sort of syntax a lot when creating data sources and events. It mirrors XPath’s attribute value template syntax (see Appendix C), which uses curly braces to wrap expressions that need to be evaluated rather than taken literally, and dollar signs as prefixes for parameters. (Don’t stress out; we’ll talk more about XPath in Chapter 8).
    > 
    > Let’s imagine your view’s title URL parameter is set to choosing-a-stripper. Here’s how the system would interpret the following filter values:
    >  
    > | Entering... | ...would filter using the string |
    > | ----------- | -------------------------------- |
    > | title       | title                            |
    > | $title      | $title                           |
    > | {title}     | title                            |
    > | {$title}    | choosing-a-stripper              |
    > 
    > We’ll discuss filtering syntax in more detail below.
    
13. Click “Add Filter” one more time
14. Click the button for the Category field
15. In the filter panel, select “Is” in the Mode dropdown, and enter {$category} into the Value field.
16. You can leave the Sorting options at their defaults. Since we’re only returning one entry, sorting instructions won’t apply.
17. Enter 1 in the Results Per Page field.
18. Check the box next to “Redirect to 404 when no results are found”. This ensures that if a visitor enters some nonsensical post title, like http://example.com/painting/i-hate-this-website/, they’ll get bounced to an error page (where you can scold them for making up nasty URLs).
19. Under “Output Options,” in the Included Elements field, select “Title (Formatted)”, “Body (Formatted)”, “Publish Date”, and “Category”.
20. [Step to attach DS to Posts view -- this functionality is not fully implemented yet]
21. Click “Create Data Source”

You’ve created a data source called Individual Post that uses the view’s title and category URL parameters to help it find the right entry, while taking care to filter out posts that are unpublished. It also makes sure that title parameter is actually set, otherwise there’s no use in it executing. If the requested post isn’t found, the view will redirect to a 404 error page.

Your Post view will now get live entry data when a valid, published post is requested. Go see for yourself. In Chapter 5, we created a test post titled “My First Post,” and we placed it in the “Journal” category. If you visit http://example.com/journal/my-first-post/?debug, in the XML source you should see the output of your new data source. It’ll look something like this:

    …
    <individual-post>
      <entry id=”1”>
        <title handle=”my-first-post”>My First Post</title>
        …
      </entry>
    </individual-post>
    …

Delivered to the front end and ready for templating.

This data source required you to specify several filters, and I’m sure it’s left you with some unanswered questions about how exactly this filtering stuff works. Before we go any further, let’s get those questions answered.

### Filtering Data Sources

As noted above, every data source type has its own particular methods for filtering the content it fetches. Rather than bog you down in the details of each one, I’ve collected thorough descriptions of all the core data source types, and their filtering options, in Appendix B. For our purposes, then, we’ll only look closely at the entries data source type.

When you filter an entries data source you’re essentially telling the system what attributes you want your results to have. If you think back to Chapter 5, you’ll remember that your contents’ attributes are captured by fields. So filtering a data source’s results is a matter of identifying the fields you want to filter on, and evaluating their contents to determine whether a particular entry should be included in the results.

The filtering tool you saw in Figure 7-5 provides a straightforward workflow for doing this: you select the field, select a filter mode, and enter a value expression.

> ###### Note
> 
> This probably sounds a lot more complicated than it actually is. In truth, you likely formulate rules like this every day—when you’re choosing a place to eat lunch, for instance:
> 
> | Attribute (Field) | Filter Mode      | Value Expression                      |
> | ----------------- | ---------------- | ------------------------------------- |
> | Distance          | is within        | 1 mile                                |
> | Cost of lunch     | is less than     | $10                                   |
> | Quality of food   | is               | yummy                                 |
> | Kitchen           | does not contain | roaches, rats, open bottles of poison |

#### Filter Modes

In Chapter 5, I made a big fuss about how important field types are because they enable you to treat distinct kinds of data differently. This is one of the places where you see this principle in action. 

Each field type defines its own filter modes. Text fields, for instance, have modes like “contains” and “regexp” (a regular expression search). Date fields have modes like “earlier than” or “later than.” A map location field would have some sort of radius filtering mode.

What each mode does is pretty straightforward, and I don’t imagine you’ll have much trouble figuring out how they work. If you do have any questions, though, Appendix A lists each of the core field types and the filtering modes it supports.

#### Value Expressions

While choosing the field and mode for your filter usually takes all of about four seconds, value expressions can be a bit more challenging. They don’t have to be, though. They’re evaluated using some relatively simple rules, and once you understand those, you should have no trouble at all creating data source filters for every purpose. Here’s what you need to know:

By default, expressions are interpreted literally. That means if you enter the word melonhead as a filter’s value expression, it’s going to test the filtering field in each entry for that exact word.

Anything wrapped in curly braces will be evaluated by the system first. As I’ve mentioned several times, filters have access to all of the view’s environmental variables (URL parameters, contextual information, and so on). In order to include dynamic values like these in your expression, they must appear inside curly braces so the system knows to evaluate them and not use them literally. 

Within curly braces, the following rules are used when evaluating expressions:

- A dollar sign delimits a parameter. So: {$title} or {$category}.
- A colon delimits a fallback option. Entering {$category:programming} is akin to saying, “If the category parameter is set, use that, otherwise, use the word programming.”

Commas are used as a union operator. They delimit possible matching values that are joined using OR. Entering programming,football,botany tells the system, “I’m looking for matches to any of these terms, doesn’t matter which one.”

Plus signs are used as an intersection operator. They delimit items in a set of required matching values joined by AND. So entering food+travel says “I’m looking for matches where the target field contains both of these values.”

As I’ve noted elsewhere, it’s the field types that are ultimately responsible for taking care of the filtering, and they’ll often provide additional keywords and operators.  One example is the range operater to in date and number fields, allowing you to enter, say, 1 to 10 or 2009-01-01 to 2009-12-31. Consult Appendix A for a breakdown of all core field types and the operators and keywords they support.

> ###### Note
>
> The combination of the curly braces syntax with all the various delimiters and operators can make for all sorts of possibilities. 

The operators, for example, can come from within the dynamic values themselves. Example: you’ll recall that your blog’s Home view accepts a category parameter. Let’s say you visited http://example.com/writing,reading/. This would set the value of the category parameter to writing,reading. Which means using {$category} in your filter expression would be functionally equivalent to using writing,reading.

On the other hand, you could just as easily do something like painting, {$category}. It all depends on your exact use case. And this applies to field-specific operators too. It’s not uncommon to see expressions like 2011-01-01 to {$today}.

As you see, curly braces and delimiters can also be interspersed with literal text: my-favorite-{$color:black}-websites.

#### Conditional Filtering

If a filter value expression contains only a parameter, and that parameter isn’t set when the data source is executed, the filter rule will be completely ignored and the data source will execute as if the filter didn’t exist. 

Confused? Hopefully an example will help. 

Remember the Recent Posts data source that you created at the beginning of this chapter? The plan for your Home view was that it’d be filterable using a URL parameter called category. You wanted visitors to be able to view all posts at http://example.com, but then drill down to categories like http://example.com/journal/ or http://example.com/painting/. Because the sorting and pagination logic is that same, it’d be nice to be able to use one data source for all of these interfaces.

Because of conditional filtering, we can add the category filter to your Recent Posts data source, and if category is not set, it’ll just be ignored:

1. Go to Blueprints > Data Sources and click on the “Recent Posts” data source you created earlier.
2. In the filtering tool, click “Add Filter”
3. Click the button for the Category field
4. Leave “Is” selected in the Mode dropdown and enter {$category} in the Value field.
5. Click “Save Changes”

This allows you to create an additional layer of conditionality without having to duplicate any structure or logic. If the parameter is set, the filter will be applied. If not, it’ll be ignored. One data source, as many interfaces as you have categories.

Let’s finish our discussion of filtering by creating another of your blog’s data sources, this one for its archive functionality:

1. Go to Blueprints > Data Sources and click “Create New”
2. Name your data source Posts by Date
3. In the section dropdown, choose “Blog Posts”
4. In the filtering tool, click “Add Filter”
5. In the drawer that appears, click the button for the Published field.
6. In the filter panel, select “Is” in the Mode dropdown and enter yes in the Value field (or click the “yes” link beneath the input).
7. Click “Add Filter” again.
8. In the drawer that appears, click the button for the Publish Date field.
9. In the filter panel, select “Is” in the Mode dropdown and enter {$year:$this-year}-{$month}
10. In Sort By, select “Publish Date” and in Sort Order select “descending”
11. In the “Output Options” section, in the Group By dropdown, select “Publish Date.” This will group the result entries first by year, then month, and then date.
12. [[Attach to view]]
13. Click “Create Data Source”

This data source uses a date field to filter entries based on the value of two URL parameters: year and month. Year defaults to the current, meaning if the value is not specified in the URL (i.e. a visitor simply goes to http://example.com/archive/), it’ll show entries for this year. Month, on the other hand, has no fallback. If it’s not provided, the data source will simply filter by year alone.

Let’s make sure it’s working. Visit http://example.com/archive/?debug. You should see your sample entries again in the source XML, only this time you’ll only see the titles, and they’ll be nested further down in the hierarchy, because you’ve grouped the results by date.

That leaves us with just one more data source to build.

### Chaining Data Sources

Very often, you’ll want to filter one of your data sources using data that’s been output from another. Such is the case with your blog’s comments. In order to fetch the comments for an individual post, you need to filter the comments entries using the ID of the post itself. This is not information that’s defined by the view or in a URL parameter, though. It is returned by the Individual Post data source. We need to use that data source’s output to filter our new Post Comments data source:

1. Go to Blueprints > Data Sources and click the green “Create New” button
2. Name your data source Post Comments
3. In the Section dropdown, select “Comments”
4. In the filtering tool, click “Add Filter”
5. Click the button for the Post field
6. Leave the Mode dropdown set to “Is” and enter {/individual-post/entry/@id}
7. In the Sort By dropdown, select “Date”
8. For Sort Order, choose “ascending.” We want older comments at the bottom.
9. We want to include all comments with the post, so uncheck the Paginate Results box.
10. [[Step to define dependencies??]]
11. [[Attach to view]]
12. Click “Create Data Source”

Using a piece of output from one data source for filtering in another is called “data source chaining.” With this technique, multiple data sources can be linked together, each depending on the results of the one before. This can add a great deal of dynamism to your interfaces.

Data source chains can be one-to-many in either direction. Several dependent data sources can be chained to the same primary data source, and a single dependent data source can be filtered using results from multiple primary data sources.

Again, let’s go back and check our results. The first thing we’ll need to do is add a test comment. Because we’ve not set up commenting on the front end yet, we’ll just do this in the admin interface.

1. Go to Content > Comments
2. Click “Create New”
3. Enter your name and email address in the appropriate fields, and then enter a comment
4. In the Post field, select “My First Post”
5. Click “Save Entry”

Now, check out http://example.com/journal/my-first-post/?debug. You should see your comment listed there in the source XML.

Now that you’ve been through the ins and outs of how data sources work, let’s move on to discuss events.

## Working with Events

Events are managed in the admin interface at Blueprints > Events.

> ###### Note
> 
> Events, like data sources, are stored as PHP files in your workspace. The same ideas apply: easy to version control, easy to reuse between projects, not trivial to edit directly or customize unless you’re comfortable with PHP.

### Creating and Editing Events

As with data sources, the first step in creating an event is to choose its type. By default, there are X core event types that are included with the base package of Symphony. The one you’ll use most often is the entry-saving event type, and we’ll cover that in depth here. The rest of the event types are described in Appendix D.

Entry-saving Events and Form Submission

Entry-saving events allow content to be submitted and saved to your sections from the front end. Symphony-powered web applications rely on this functionality for nearly everything, but even simple sites use entry-saving events for things like contact forms, comment submission, and so on.

Entry-saving events specify a section and a set of options and then, once attached to a view, will process POST data submitted to that view.

> ###### Note
>
> If you don’t know what POST data is, you should read up on it. Here’s a quick summary though. Browsers and other clients can make different types of requests to a server. The most common are GET and POST. When you enter a URL into your browser, the browser makes a GET request at that URL and waits for data to be sent back.  When you submit a form, on the other hand, the browser takes the data you entered and sends a POST request to submit that data to the server.

You need to create an event that saves comments, so that people reading your blog posts will be able to submit comments from the front end.

1. Go to Blueprints > Events
2. Click the green “Create New” button. You’ll see the event editor (Figure 7-#).
    Figure 7-#	[f070#.png]

3. Name your event Save Comment
4. In the Section dropdown, choose “Comments”

#### Event Options

The next section of the event editor allows you to specify options for the event, which you’ll recall from earlier allow you to adjust a particular event’s behavior with additional actions, conditions, and the like.

The base package of Symphony ships with the following event options:

[[This functionality is not fully implemented or defined]]

Let’s assume that you want to receive an email notification whenever someone leaves a comment on your blog. Let’s also assume you want to protect your blog against common cross-site scripting attacks. All you have to do is add these two options to your event.

1. In the Options multiselect, choose the “Send Email” option and the “XSS Filter” option.

#### Defaults and Overrides

Because entry-saving events rely on user submissions from the front end, you’ll want to take other kinds of precautions with your data too. There’s no stopping a malicious visitor from manually changing names or values in your form markup to try to save unwanted or potentially harmful data to your system.

Thankfully, Symphony gives you a way to prevent this. You can specify default values and override values for any field. Default values will be used if no value is submitted for the field. This allows you to specify a fallback if your users skip a field. Override values are used no matter what users submit. This allows you to prevent users from forcing in data you don’t want.

For our purposes, we’re capturing five fields. Three will be provided by the user: Name, Email, and Comment. The other two, Date and Post, we’ll specify as overrides. This will keep malicious users from trying to manually specify values that they shouldn’t:

1. In the Overrides and defaults tool, click “Add Item.”
2. In the Type dropdown, select “Override”
3. In the Field dropdown, select “Date”
4. In the Value field, enter {/data/context/date}
5. Click “Add Item” again
6. In the Field dropdown, select “Post”
7. In the Value field, enter {/data/individual-post/entry/@id}
8. [[Step to attach event to view]]
9. Click “Create Event”

Now, no matter what data is submitted, every comment’s Date field will be populated with the current date, and the Post field will be populated with the ID of the post being viewed.

#### Form Markup

Once you save your event, you’ll be provided with sample form HTML. You could simply copy and paste this into your template, but you should know the basic syntax that’s at work here. Entry-saving events rely on fields in the POST data following a naming convention: fields[field-name]. Here’s an example:

    <input type=”text” name=”fields[email]” />

    <textarea name=”fields[comment]”></textarea>

You can use whatever kinds of form elements you like. The only other requirement is that the name of the input used to submit the event contains the event’s handle: action[event-handle]. In your case, then:

    <input type=”submit” name=”action[save-comment]” value=”Submit”/>

We’ll leave it at that for now. You’ll finish setting this up in the next chapter.

## Summary

In this chapter you were introduced to the concept of data flow—the transactions and exchanges between websites and their users that make interactions possible on the web. We talked about data flow being defined by infrastructures and by logic, and you learned that, in Symphony, designing data flows means:

- identifying the data needs of each of your interfaces
- building the infrastructure to manage that data
- designing the logic that will make it all work

You were then introduced to the two elements that regulate data flow in a Symphony website—data sources and events. You learned ins and outs of each, and you went on to build out the infrastructure that will be responsible for managing the flow of data for your blog.

You were also introduced, briefly, to Symphony’s debug devkit, which allowed you to check your work and verify that your content is indeed making its way to the front end.

It is, and that means that your crash course is almost complete. You started by learning how to model content in Symphony, then how to define front end architecture. Now you’ve learned how to set these structures in motion and channel data to and from your interfaces.

There’s just one big piece left to the puzzle: what your visitors will actually see.
