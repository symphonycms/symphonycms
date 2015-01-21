# Chapter 11: Adaptive Techniques

After that entire chapter on planning, let’s pause for a moment to face the facts. Sometimes requirements change. Sometimes plans blow up in your face.

In this chapter we’ll talk about what to do when plans go awry, or when you don’t have the ability or luxury to plan everything in advance, or when you decide that iterating makes more sense than planning...

Start with an introduction to adaptive/iterative development … Then take some baby steps with your existing blog project … Finally we’ll go all out and develop a new section/function for your site. The idea is to give you a sense of how flexible workflows can be when you’re using Symphony.

## Rapid Prototyping

## Interative Development

When everything goes according to plan, it usually involves unicorns and talking fish (because it’s all in your imagination).

A whole lot of development methods have gained popularity in the last few decades in response to what is commonly called “Waterfall development.” WD is when each phase of planning and development is nicely and neatly completed and then serves as the basis for the next bit.

As development teams began to realize that this method almost never mapped onto reality, they started coming up with alternatives: adaptive development, agile development, iterative development... Though each of these has its own particular definition and history, I think of them as a family of approaches that are trying to solve the same problem in different ways. 

the upshot is that they’re trying to combat the top-heavy process of WD with more flexible approaches where things are scoped as you go, there’s some room for trial ad error, for learning on the go, for prototyping something simple and building it up... and for all of these tasks, symphony’s a great tool
it’s modular, it allows to you build your solution bit by bit, and so you’re able to go in thin layers, or to do a circular thing. when you’re building symphony projects these kinds of approaches can be invaluable, esp when plans are difficult or they fall apart or requirements are shifting.
let’s give you a taste of how it works.

### First Steps: Adjusting What You’ve Got

We’ve already got a basically functional blog, so the first thing would be talking about how to add fields to an already-existing section and carry that through to the front end.

decide field to add

go to section editor, add it, configure it, add to layout

now update entries to reflect the change

get that data through to the front end by updating the DS

debug the page to check the data’s coming through

add/edit a template to handle that field

That’s the basic back-to-front linear process. Let’s look at some other workflows...

### Prototyping a Portfolio addition

Start at the design level this time... whip up a wireframe to guide us.

create the view, write some XSLT to give us basic output

take a rough swing at the data model

create some entries, adjusting the data model iteratively until we get what we want

create a ds to deliver them, attach to view

Test DS filters/sorts/etc/ like i just did with the Symphony home page

update templates to pull the data in where we want it

enhance the design, adding new elements

repeat the process

## Summary

This was fun...
