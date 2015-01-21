---
id: chapter-02
title: Symphony in Action
layout: docs
---

# Chapter 2: Symphony in Action

## What’s in this Chapter

## A tour of Symphony-powered websites

Whether or not you found any of these arguments persuasive (and I’ll assume you did because I wore a sweet salesman-style fake mustache while I was writing them), the truth is that you’ve got work to do, and you’re going to need a lot more than arguments to convince you that Symphony should be part of that work. Tools have to do more than sound good. They have to do the job. And at this point you’re likely wondering if Symphony can help you solve your problems in practice, and not just in theory.

The purpose of this chapter is to try and answer that question—not with lots of theory or marketing-speak, but by showing you concrete examples of Symphony in action in various contexts. They won’t be overly detailed, because you still haven’t been thoroughly introduced to the system. And they’re not anywhere near exhaustive as far as Symphony’s capabilities are concerned. The goal here is just to give you a more concrete idea of the kinds of things you’ll be able to achieve if you decide to turn to Symphony for your next project.

We’ll be looking at a small sampling of websites that I’ve chosen simply for their variety, and because I know them well or have easy access to their developers. There are literally hundreds of other wonderful, unique examples in the Symphony showcase (http://symphony-cms.com/explore/showcase/), and I encourage you to check them out, too.

## Personal Website: The Interactive Manufactory of Jonas Downey

    Figure 2-1	[0201.png]

### Overview

- URL: <http://jonasdowney.com>
- Developer: Jonas Downey

Jonas Downey is a clever dude, and one visit to his website should be enough to convince you of that if you’re not inclined to take my word for it. In addition to being a talented writer and web developer, Jonas does some really amazing work with data visualization and computation, and he whips up all sorts of fascinating conceptual and artistic experiments. He also blogs, tweets, takes photos, listens to music, and bookmarks interesting sites.
His website, or “Interactive Manufactory,” is designed to be a virtual home for all these endeavors, a hub both for his day-to-day life and for his creative work. Like most websites of its kind, it is meant to be a reflection of its owner—his work, his interests, his personality, his tastes. The site sports Jonas’ own homespun design, built using HTML5 and CSS3. It features a compelling visual portfolio, a bio page, a contact form, and many of the other things you’d expect from a personal website. It also aggregates the scattered digital impressions Jonas makes on the web each day, from Twitter updates and Last.fm albums to Flickr photos and Delicious bookmarks.

### Points of Interest

With his data spread out across so many third-party services (including his own external blog), Jonas wanted a way to pull it all together. But he wanted to do more with the data than just display it. He wanted to be able to work with it, manipulate it. So instead of just having Symphony grab and template the data dynamically (which it could easily do), Jonas used a Symphony extension to import the XML data he collects from each source right into his own site’s content structure.

This means he can go on using these other services and all the tools they offer, but he also gets to keep his own archives of tweets and bookmarks, for instance. It also means that, because he’s got the content stored locally, he’s able to mix and mash it and put it to interesting uses. The most obvious of these is that he takes the bits and pieces that he gathers and uses them, along with his own daily notes and reflections, to render some pretty neat visualizations of his activity over time (Figure 2-2).

    Figure 2-2	[0202.png]

### Developer’s Thoughts

“Truly, this site would never have happened without Symphony. For several years I had envisioned aggregating my various personal data into a cohesive site, but the available tools were complex and functionally lackluster.

“With Symphony, the XML data loading capability was the feature that sold me on day one. I no longer had to worry about how to get my content into my content management system, which is really what they're supposed to do, isn't it? Once sold, I quickly discovered all the other great features of the system; in particular I loved the elegant interface, the complete control over markup and output, XSLT for templating, and abstracted database queries via Data Sources. As an extra bonus, any time I got stuck on something, the community was super helpful.

“It was definitely hard work learning everything, but it was ultimately a complete joy to build with Symphony. And now I'm already in the process of using Symphony for an even bigger data visualization site.”

Jonas Downey

## Web/Mobile App: Clorox MyStain

    Figure 2-3	[0203.png]

### Overview

- URL: <http://clorox2.com/mystain/>
- Developer: Vine Street Interactive

I’m notorious for staining just about every shirt I’ve ever worn. Ask my wife. And usually I can’t be bothered to think about how to get those stains out—to me they’re just soup-dumpling-flavored badges of honor. But Clorox’s nifty (Symphony-powered) myStain app might very well change that.

Available as both a web app and a set of mobile apps, Clorox’s myStain provides entertaining and timely tips for would-be stain fighters, whether they’re at home with their full arsenal of laundering products or out and about with little more than a bottle of water and a packet of salt. Users can browse through a stain library, vote on stain-fighting solutions and share them with their hapless friends via email or social media, or take a spin with the app’s slot-machine-style stain scenario generator (which is a nice way to feel like you’re winning something even when the crotch of your best-fitting pants has been smeared with sloppy joe).

### Points of Interest

The myStain project leverages Symphony to orchestrate the flow of content to and from four different environments—an XHTML website, a Flash-based web app, an iPhone app, and an Android app. Symphony’s flexible XSLT templating layer allows the app to output its content in whatever format each environment requires. It provides XHTML for the website, generic XML for the Flash app, an Apple Plist for the iPhone app, and JSON for the Android app. This means of course although users can access the app’s content in several contexts on several devices, producers only have to manage their stain tips in one place. And the data flow goes two ways: Symphony also receives user-submitted votes from all of the different app environments.

The broader website in which the myStain app content appears is actually not Symphony-powered. It’s managed by Clorox’s own proprietary CMS. But again, because of the openness and flexibility of XSLT, the Symphony-powered portions are able to elegantly integrate with the company’s existing architecture.

### Developer’s Thoughts

“The primary criterion for myStain's content management was a system that played well with others. When the project started, we knew the site would be talking to an iPhone app and a Flash component in addition to serving vanilla XHTML. Since both of these client apps could easily consume XML, I knew Symphony would be a good fit.

“Most of our workload on this project was the development of the mobile apps rather than the data service that would feed them, so we needed to be able to get content web services running quickly, even before the information architecture (IA) was completely locked in. Before I coded anything, Symphony's extremely straightforward content- and event-modeling interface had most of the content service up way ahead of schedule, which is always a nice way to start a big project.

“As development progressed, requirement changes came in for both the IA as well as what the mobile app developers wanted. Symphony's use of XSLT was really important in this situation. Since XSLT is built to handle XML, the iPhone developer had free reign to code for any Plist structure he wanted. Late in the project, when the client added an Android version to the plan, we chose JSON as the transport format for the Android app. Since XSLT is an open standard, any generic XSLT solution can be used in Symphony. I easily found a template online that helped me output the necessary JSON.

“To integrate Symphony’s output with the Clorox content system, we needed to use simple php include function calls to render the header, footer, and other components of the page not under Symphony control. PHP in the view layer is a relatively un-Symphony thing to do, but fortunately I had previously written an extension (EXSL Function Manager) to help with just such a requirement. This extension converts PHP functions into EXSLT functions, keeping our XSLT templates nice and clean while incorporating the needed external markup.”

Andrew Shooner, Developer, Vine Street Interactive

## Publication Website: Public Culture

    Figure 2-4	[0204.png]

### Overview

- URL: <http://publicculture.org>
- Developer: Craig Zheng

I think I remember hearing that most people who build and manage websites for a living spend their copious free time catching up with the latest scholarly discourse on topics like globalization, capitalism, cosmopolitanism, and modernity. As such, you’re probably already intimately familiar with the journal Public Culture.

The journal’s website is a showcase of sorts for its content and for related news, events, and resources. Users can explore Public Culture’s recent archives, browse its contributors, and see artwork and photography that has appeared in the journal. The site also features lots of detailed information about the journal (mastheads, submission guidelines, and the like) along with news and events listings, a visual “Books Received” stream (powered by Readernaut), a host of web feeds, and more.

### Points of Interest

As a journal, Public Culture is dealing with loads of content that is inherently meaningful. Issues have numbers, publish dates, and covers. Articles belong to issues, and have page numbers, authors, sometimes artwork. The journal’s contributors can have bios, academic disciplines, affiliations, and can be linked to articles, artwork, news items, events, and so on. Symphony’s open content structure allows all of this data to be stored in bits that are most meaningful in their particular context. For example, capturing page numbers means it can use those to build links to citation tools on the front end.

The website features some interesting third-party integrations as well. Its search function is powered by Google’s Custom Search Engine (CSE)—meaning it takes advantage of the search giant’s powerful indexing and algorithms, but receives search results as XML that can be templated natively within the site. It’s a seamless experience for users, and Google takes care of all the heavy lifting.

There is also some integration with CampaignMonitor (CM), a service the journal uses to send email newsletters. If you’ve ever had to design an HTML newsletter, you know what a hellish process that can be. But because of CampaignMonitor’s HTML import tool and Symphony’s flexible front end, the site simply uses an alternate template to render newsletters for import into CM. What that means is that the journal’s staff never has to think about formatting HTML emails. They just write a newsletter once in Symphony, and it is made available both within the website itself and as a standalone HTML email (Figure 2-5).

    Figure 2-5	[0205.png]

### Developer’s Thoughts

Public Culture is actually the reason I found Symphony. I was a graduate student in anthropology at the time, and working as a manuscript editor for the journal. I had a bit of experience with web development, though, and so when the journal needed a new website, I volunteered my amateurish services.

I built the first version of the site using another (very popular) CMS. All throughout the development process, I grew increasingly frustrated as I ran into inefficiencies and constraints over and over again. One of the reasons I’d chosen the system is that it allowed me to define the content types I needed (journal issues, articles, authors, and so on), but the further I got the more I realized that the system itself was still remarkably inflexible. I couldn’t even sort authors by last name! And the more I learned about web development and web standards, the more I realized that this system really was doing a lot of things poorly.

I started searching for alternatives and found Symphony just as we were launching the first version of the site. The difference between the systems was so stark that I began rebuilding it almost immediately. Symphony was a breath of fresh air, giving me the freedom to craft the site exactly as I wished I could’ve the first time around. I had total control. I defined the content that would drive the site, bit by bit, and Symphony allowed me to bring it all to life however I wanted. I could create interfaces to browse issues by volume or authors by the first letter of their last name, and enable browsing via the many rich relationships embedded in the content. I was able to implement a very systematic and structured design system. And no matter what new problems or requirements arose along the way, Symphony always gave me a way to solve them, easily and elegantly.

## Business Website: Original Travel

    Figure 2-6	[0206.png]

### Overview

- URL: <http://originaltravel.co.uk>
- Developer: Airlock

Original Travel is an award-winning travel company in the UK that provides custom, luxury vacations and tours all around the world. If, like me, you’ve been holed up inside working feverishly for extended periods of time, be warned that visiting this rich, visually stunning website is going to make you very, very sad.

The site allows potential clients to explore the hundreds of different trip possibilities on offer—browsing by destination, type of trip, time of year, etc. Every step of the way, visitors are provided with local travel tips and detailed information about hotels and activities. Dedicated trip specialists for each region contribute to a company travel journal. The site also features a shop (with checkout), and once a customer has booked a trip they can login and view their itinerary and other documents online.

### Points of Interest

Original Travel’s website is all about places (places I can’t be, incidentally), and so it’s fitting that one of its most distinctive features is a robust integration with Google Maps. When destinations and journal entries are entered into the system, a map location field (provided by a Symphony extension) allows content editors to set their location simply by placing a pin in a map. The data saved by that field is actually a pair of longitude and latitude coordinates—the useful bits, in other words. On the front end, visitors are then able to browse destinations using an interactive map, onto which all of the different sets of coordinates in the system have been plotted.

    Figure 2-7	[0207.png]

Original Travel also makes heavy use of Symphony’s dynamic image manipulation capabilities. The site’s many gorgeous photos of tropical beaches and European villas that torture me so relentlessly can each appear in multiple contexts, and for each context the design has different size requirements. These range from full-width (974x368) to promo size (306x172) to thumbnail (160x90). Does that mean that for every image (and there are lots), editors have to create several versions? No. They upload an image once and only once, and everything just works. How? The designer has crafted the site’s templates to specify what size images belong in which contexts, using a specially-crafted URL in the image element’s src attribute. Symphony takes care of the rest, dynamically resizing, cropping, or otherwise editing the original and then caching and serving the resulting image.

    Figure 2-8	[0208.png]

### Developer’s Thoughts

“Symphony allowed us to quickly build our data model directly from the wireframe blueprints, in a matter of days rather than weeks. We modeled continents, counties, trips, hotels, activities, seasons, journal articles, testimonials and many more (52 in total). We were able to let the client add start adding their content to these sections before the designers had even finished designing the site! And because sections are easy to modify, we could react to evolving content requirements as data entry progressed. This meant that by the time we came to build the frontend, we already had more than half of the final content to style with. You don't get much more agile than that.”

Nick Dunn, Head of User Experience, Airlock

## Social Network: Whisky Connosr

    Figure 2-9	[0209.png]

### Overview

- URL: <http://connosr.com>
- Developer: Jean-Luc Thiebaut

Everyone’s got a passion, and for some people that passion just happens to be hard liquor. Whisky Connosr is a social networking community built especially for enthusiasts of that coppery, distilled beverage.

The site has many of the features you’d expect to find in a social network—member registration, profiles, “buddies,” activity streams. And there are tie-ins to existing networks as well. Member profiles, for instance, can incorporate Twitter streams, and bottles of whisky can be “liked” via Facebook. But most of what makes this community work is totally unique and hinges on its subject matter. Members can “follow” particular whiskies, review and rate specific bottles, and add bottles to their personal whisky cabinet or wishlist on the site. On top of that, there is an active discussion board (“The Whisky Wall”), a blog, an events section, and an impressive online whisky magazine called Connosr Distilled.

### Points of Interest

Connoisseurs of any kind tend to be obsessed with details, and this enthusiasts’ social network revolves around several quite specialized kinds of web content. Every bottle of whisky, for example, comes from a specific distillery, has a particular water source and bottler, an age, an alcohol-by-volume ratio, and so on. Whisky reviews point to specific bottles, and capture unique characteristics like the whisky’s color and the reviewer’s overall rating of the bottle (Figure 2-9).

    Figure 2-10	[0210.png]

This is a social network, though, and participation above all is the driving force. The site makes heavy use of Symphony’s ability to power unique interactions and user journeys by allowing all kinds of content to be submitted to the system from the front end. From the discussions on The Whisky Wall to the site’s thousand-plus reviews and its member profiles and whisky cabinets, Symphony is helping the site’s developers fuel avid participation among its thousands of members.

### Developer’s Thoughts

[Waiting for developer quote]

## Summary

In this chapter, we’ve toured a handful of Symphony-powered websites and web applications in the hopes of demonstrating the breadth of the system’s capabilities. We looked in particular at five different types of Symphony projects: a personal site, a web/mobile app, a publication site, a business site, and a social network. There are many other worthy candidates we might have explored—like the high-demand media and contest sites that have been developed for clients like BBC and Channel 4, or a software community like the Symphony website itself—but we’ll save some of these examples for other parts of the book.

In the meantime, you’re probably itching to just roll up your sleeves already and start exploring the system. Let’s go get you started.