---
id: chapter-10
title: Planning Symphony Projects
layout: docs
---

# Chapter 10: Planning Symphony Projects

## What’s in This Chapter

- Planning to Plan
- Project Architecture
- Workflows

This chapter begins the third leg of your journey to complete and unquestioned Symphony mastery. (No pressure, though.)

In Part 2 of the book, your goal was to learn the ins and outs of how the system works and what it can do. Here, your goal will be slightly different. Instead of focusing on how the system itself works, we’re going to focus on how you can work with the system.

In other words, Part 3 is all about workflows—about learning to wield this powerful tool skillfully and efficiently. If the previous six chapters were your Symphony schooling, the next six will be your apprenticeship. We’ll explore concrete techniques and best practices that will get you working faster and smarter and building websites that are elegant and easy to maintain.

You’ve got the basics under your belt, so we’re going to pick up the pace a little bit now. In just a handful of chapters, you’re going to learn how to use Symphony for iterative development, how to abstract and reuse templates, how to profile and debug a site, and much more. And when it’s all said and done, you’ll be well prepared to tackle the fourth, and final, part of the book—the one with all the awesome projects.

…

But before we dive into all of that, we should begin, naturally, at the beginning. Every web project starts with some degree of planning—with making decisions about what you’re trying to achieve, how things are going to look, how they’ll behave, and so on.

So far in our journey together, I’ve been making all these kinds of decisions on your behalf. You’ve just followed along dutifully and trusted me to be right. Thankfully, for your sake, I’m every bit as thoughtful and intelligent as I am humble. 

Wait—

Anyway, sooner or later it’ll be time for you to build your first real Symphony site on your own, and you’re going to need to be able to scope the project yourself, map it out, manage its development, and so on. And even if you’ve done this sort of thing before, it can all look a bit different when you add Symphony to the mix. As with any tool, Symphony will impose a certain amount of structure, and certain kinds of conditions, on how you go about things. Getting your thinking attuned to the system early in the process will be an important first step whenever you’re about to embark on a new project.

My goal in this chapter, then, is to equip you with tools and techniques that will help you plan Symphony projects effectively. We’ll try to keep things brief. I want to talk about three separate planning topics—preparation, project architecture, and workflows—and for each I’ll just quickly review some helpful techniques and pointers.

Whatever your role—hobbyist, independent developer, project manager—my hope is that you’ll leave this chapter feeling confident in your ability to map out and orchestrate Symphony projects large and small.

## Planning to Plan

Blah

### Be Open

Every project will be different. Sometimes you know right away how you want a site to look and feel. Other times, it’s the content and interactions that are most clear from the outset.

Rather than always forcing yourself to proceed in this or that particular way, try to recognize what it is that you’ve got in the beginning and let that drive the planning process.

If what you’re starting with is a vision of an interface or a user experience, so be it. You can plan from the outside in, from sketches and wireframes to front-end architecture and down to the content, letting your vision of the user’s experience guide you.

If, on the other hand, the first thing you’ve got is a well-defined data model, then you can take an inside-out approach, starting with content outlines and data flow charts and letting those determine the site’s structure and design.

Now, of course, if you’ve already got a tried and true method of scoping and planning projects, by all means feel free to stick to it if it works for you. But in my experience no one approach is inherently better than any other. It all depends on the circumstance. And sometimes trying to prematurely force a great content-driven idea into wireframing, or a purely visual idea into abstract data modeling, can kill momentum and wind up being counter-productive.

### Lurk in the Community

Symphony’s is a vibrant open source community where conversation is constant and new projects and ideas are surfacing all the time. When you’re gearing up to start a new project, spend a little bit of extra time tuning into the community chatter. Chances are, other people are out there solving problems very similar to yours, and you’ll be able to learn from the questions they ask and the solutions they find. You can also gather inspiration from among the community’s ever-stunning showcase sites, or keep an eye on the development roadmap to see what changes are coming to the system.

The first and best place to go is the Symphony website: http://symphony-cms.com/community. But there are other places to find fellow Symphonists too:

- IRC: #symphony on Freenode
- Twitter: via the #symphonycms hashtag
- Convore: http://convore.com/symphonycms

### Research Extensions

The other reason to become a regular on the Symphony website is that you want to be intimately familiar with the extensions ecosystem.

As you’ve seen, Symphony is a lean, lightweight content management framework. All non-essential functionality is provided by extensions, which means that every project you’ll build will probably rely heavily on extensions.

These extensions are constantly being developed and refined, and new ones are born every week. You won’t be able to plan your projects effectively without knowing exactly what’s out there, what it does, how reliable it is, and so on.

This is especially true of extensions that provide core elements like field types, data source types, and the like. You’ve already seen how important these can be. Field types, for example, determine pretty much every aspect of how your data can be used. Coming up with a sound content model means knowing what field types are available to you and what the differences are between them.

### Ask for Help

Because there’s more than one way to do almost everything in the Symphony universe, you’ll probably spend lots of time ruminating over various kinds of approaches and implementations when you’re getting ready to start a new project.

One of Symphony’s best traits is that it makes it very easy for you to try things out quickly and see how they work (something we’ll discuss at length in the next chapter). But sometimes trial-and-error is too time-consuming, or the scope of the thing you’re trying to figure out is just too broad.

Generally speaking, it’s a good habit to spend time puzzling through things on your own, researching and weighing various options and trying to pinpoint the questions and uncertainties. But whenever you’re in doubt, do yourself a favor and ask for help. You’ll undoubtedly save yourself lots of time, and probably learn something new in the process.

Make sure your questions are well-considered and specific. Don’t be the person who comes to the forum making vague demands, like “Hey tell me how I can implement an ecommerce site.” Figure out what exactly your dilemma is. Are you unsure about your content model? Do you want suggestions for how to set up a certain set of views?

Asking well-thought-out, specific questions will get you clearer, faster answers.

## Project Architecture

No particular order...

### Outline your Content

As you saw in Chapter 5, the structure of content in Symphony has a cascading effect throughout an entire project. It determines what the publishing interface will look like for content producers. It determines how data is stored, and how entries can be sorted, filtered, and searched. This makes content modeling a crucial task for Symphony sites, and each decision you make here will reverberate into other phases and aspects of the project.

When the time comes to plan your data model, one technique that I’ve always found very helpful is outlining all of the content types a project will require before I start any actual development.

My method is fairly straightforward, and you got a taste of it back in Chapter 5. I simply list each content type, and then list its fields along with their types and any configuration options worth noting. Here’s an example:

#### Products

| Label       | Field Type              | Configuration Options          |
| ----------- | ----------------------- | ------------------------------ |
| Name        | *text input*            |                                |
| Description | *textarea*              |                                |
| Image       | *file upload* (image)   | `/workspace/uploads/products/` |
| Category    | *select box link*       | → `Categories::Name`           |

#### Categories

| Label       | Field Type              |
| ----------- | ----------------------- |
| Name        | *text input*            |
| Description | *textarea*              |

This is not unlike what you might see in a diagram of a database schema, only simplified, and with some Symphony-specific bits like upload directories or relationship targets.

As I mentioned above, you’ll want to pay special attention to the field types at this stage. They’re the primary determinant of how you’ll be able to use your data, and because they also determine how data is stored, changing field types is a destructive process. So you’ve got to get it right at the beginning.

I find mapping out a site’s content model like this incredibly helpful, but there’s a balance to be stricken here. The benefit of this exercise is to get you thinking in advance about the shape of your content, and to help you see where critical decision points will be (Should Categories be its own content type? Will I need to be able to attach more than one image to a Product?). But this outline shouldn’t be set in stone, and it’ll almost certainly change during implementation.

The important thing here is to prioritize completeness over perfection. Don’t worry if you’re uncertain about a handful of details, but make sure you’ve been through the whole content model at least once.

### Wireframe

Websites and web applications often begin their lives as wireframes—simplified visual outlines of pages or interfaces that roughly approximate things like layout and content placement (Figure 10-#). 

    Figure 10-#

Wireframing is a tremendously helpful planning tool and often a great place to start a Symphony project, especially for non-developers, who might find it difficult to begin with something as abstract as a data model. Wireframes allow you to start with the part of your project that’s probably most tangible—the interfaces your visitors will see and explore—and help you get a feel for how your content is going to be used.

It’s common to annotate wireframes, normally by placing numbered circles over various parts of the visual and then adding a corresponding note in the margin. When you’re working on a Symphony project, this can be a helpful way to use what you’ve got (visual ideas, in this case) to help you begin planning other parts of the system. For example, a wireframe for a blog’s home view might contain annotations like “Blog Post entries, 10 most recent” and “Title, teaser, date, author” (Figure 10-#). Now you’ve got a head start on a data source and a content type.

    Figure 10-#

If you’re reading this book, there’s a good chance you know all about wireframes and have probably created lots of them yourself, so I’ll leave it at that. If you want to explore the practice a little more in depth, there’s a fairly thorough guide at <http://sixrevisions.com/user-interface/website-wireframing/>.

### Do a Task Analysis

Another technique that’s common among information architects and user experience designers is the task analysis or task flow.

Details can vary, but the basic idea is to chart out what it is that users need to be able to do on your site and where. Sometimes this takes the form of flow charts that track particular user journeys. Sometimes it’s a sequential table of user actions and accompanying system actions. Figure 10-# provides some examples:

    Figure 10-#

The important thing is that this sort of exercise gets you thinking about how people will use your site, and that can help you raise architecture and design questions early on that you might otherwise miss.

If task analysis is part of your planning process, it can be a great place to mine for structural Symphony requirements. Charting user journeys, for example, can help clarify the interfaces you’ll need to provide and where they’ll live. And detailing how the system should behave in response to specific user actions is a good way to start planning data sources and events.

Figure 10-# shows how you might layer Symphony specific details onto the examples provided above.

    Figure 10-#

### Plan Your Markup as a System

When you’re ready to produce live presentation code for the first time (which will hopefully be fairly early on, as page prototypes), try to remember that with Symphony you’re not “skinning” system output but rather developing a robust and flexible design system. In other words, you’ll want to be attentive to patterns as they emerge.

This is the sort of thing you might even begin to intuit during the wireframing stage—where you’ll often be identifying global interface elements and reusable visual idioms.

By the time you get around to building live prototypes, writing the actual code will likely reinforce these early intuitions and reveal even more patterns and opportunities for abstraction. Imagine, for instance, that you decide to markup some event data in one of your prototypes using the hCalendar microformat. If you knew you’d be using events all throughout your site, this would be an ideal place to abstract that markup into a reusable template that can be applied whenever event data is being output.

Being able to streamline and organize your presentation layer like this is one of the nicest benefits of using a rule-based templating system like XSLT, and you should be prepared to take full advantage of it.

### Map Everything

Another common technique in web design and development is site mapping—part of the information architecture process that, in its simplest form, maps a site’s pages or interfaces into a hierarchy to help visualize its organization.

With a Symphony project, especially a large or complex one, it’s often helpful to take this mapping exercise one step further. You can begin by listing and arranging all of the views you imagine your site will require. You’ll want to include details like their handle and parameters, so that you can get a sense of how your URL schema will look. Views can also be organized hierarchically, so you’ll want to capture or represent those relationships too.

Then, for each view, think about what content it needs to display or what interactions it needs to support. This is where wireframes can come in handy if you’ve made them. Going through the views this way will give you an overview of the various kinds of data sources and events you’ll need, and you can begin to list and describe those too.

What you end up with in the end is a pretty thorough mapping of your front-end. Mine often look like Figure 10-#:

    Figure 10-#

This exercise helps you pull disparate pieces together, fill in gaps, and spot redundancies and trouble areas before they become problems. It can be overkill for small projects, but for large sites, I find it invaluable.

…

Because I happen to find these techniques so helpful, I created a Project Planning Kit that provides PDF and SVG templates for creating Symphony-specific wireframes, content outlines, system maps, and the like. The templates have dozens and dozens of variations and can be printed out or used within your favorite vector drawing program (Figure 10-#).

    Figure 10-#

You can find the project planning kit in a community-maintained Github repository at http://github.com/####/#####.

## Workflows

Of course, getting the system scoped and mapped out is only part of the battle. In addition to knowing what you’re going to build, you’ll probably want to spend time figuring out how you’re going to build it.

This is especially true if you’re working with others. When you’re working alone, you get to do things however you like. No one will be stepping on your toes, getting in your way, or making changes while you’re not looking. But when you’re working in a team, whether it’s two people or twenty, things can get complicated very quickly. 

In either scenario, it can pay to spend a little bit of time before any project hashing out workflows and procedures to keep things from getting messy.

### Plan to Test

First and foremost, plan to test. The kinds of system specifications you saw above are going to help you spot the easy errors, like when an interface is misbehaving or is missing content. But you’ll want to be prepared to catch subtler problems before your project goes live and they start tripping up your users.

One obvious way to surface these kinds of issues quickly is to develop iteratively—building specific sections and functionalities one-by-one, starting with the simplest implementation and then adding to it little by little and testing (preferably with real users) all along the way. We’ll cover this strategy in much more depth in the next chapter.

There are other ways to incorporate testing into your workflows, though, whether it’s stress-testing your data model with sample content, seeing how views and data sources respond to unexpected parameters, submitting malformed or malicious data to your front-end events, and so on. We’ll talk more about testing and debugging in Chapter 15, but do yourself a favor and work it into your plans and timelines from the beginning.

### Think about management

Will ppl be working concurrently? Or in rapid succession? 

One benefit of Symphony’s modular architecture is that you can you can 

### Use Version Control

This is not really a suggestion. Just do it. It might take you some time to get the hang of using a version control system (I recommend Git), but trust me: it’s well worth it.

Version control will give you peace of mind as you commence development on your Symphony project. You’ll be able to undo changes, resolve conflicts if you’re working with others, and if you use a hosted service like Github or Codebase, you’ll know that your build is always backed up somewhere.

Symphony itself is optimized for version control. All of the structural information about your project is stored in physical files right in your workspace (the database only contains entry data). So by using version control, you’ll give yourself access to incremental snapshots of your entire build at every stage of development (Figure 10-#).

    Figure 10-#

When you’re going to version-control a project, you need to have a plan. Know where you’re going to host your repository. Know how it’s going to be organized (for example, separate branches for development and deployment code). And know exactly how you intend to deploy from the repository to the production server.

For individuals and small teams, you’ll want to keep all of this as simple as possible. But really big projects may require elaborate schemes for things like branching and versioning, and if you’re working with a large team you’re going to need guidelines on how changes should be reviewed and merged. If you’re working in an agency setting or planning a big, ambitious project, make sure you think this stuff through first.

Getting into the details of a version control system like Git are well beyond the scope of this book, but Symphony’s online documentation has loads of details on how to use Git with Symphony, and it will also point you to other helpful Git resources: <http://symphony-cms.com/documentation/git>.

### Have a Solid Plan for Deployment

This is related to the discussion about version control, but there are specific questions about deployment and ongoing development that I want to make sure you consider.

First things first, have a deployment plan and test it before you start actively developing. For example, if you plan to use Git, test your deployment strategy as soon as you install Symphony and initialize the repo. After you’ve done some initial development work, see what happens when you try to pull those updates to the production server. You don’t want to wait until the day before launching a site to see if all this stuff works as intended.

For big projects, a staging server can be a good idea. Instead of developing entirely in isolation on your local machine or network, you periodically deploy the project to a private staging server. This gives you a chance to test with your clients or users and gather live input. And if your staging server is located on the same host as your production server, it’ll also give you a chance to test the environment and iron out any problems there.

Finally, if you’re developing locally or on a staging server and then deploying to a production server, think carefully about how you’ll keep your databases in sync. Entry data is the only thing that falls outside the purview of a version control system, so just have a plan in place. If your client is entering live data on the staging server, for instance, you don’t want to lose that when you push to production.

    [[Symphony 3 may have some built-in db tools, so will have to revisit this]]

## Summary

We’ve quickly run through a whole bunch of shit that I think you will find helpful next time you have to sit down and plan a new project.

One technique we haven’t discussed, though, can often be the most useful of all. It’s the anti-plan.
