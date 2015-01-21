# Chapter 5: Content

#### What's In This Chapter

- Introducing our first Symphony project
- About content modeling
- Understanding content in Symphony
- Working with content in Symphony

Let's build a website, shall we?

Over the course of the next few chapters, we're going to whip up a simple but functional blog. We’ll proceed slowly, building the site one layer at a time so that along the way we can stop to elaborate on important concepts and tasks as they arise. That means you’ll still have to stomach my long-winded explanations, but at least they’ll be broken up into smaller doses (and you’ll get to do some fun web building in between).

The first thing you’ll need to do is tell Symphony what kinds of content you want to manage. And though you probably already have a pretty good idea of what you expect to find in a blog (things like posts and comments, for instance), there's more to it than just naming them. You have to figure out what they're going to look like, how they'll behave, and how you want the system to handle them. The process of answering these sorts of questions is called “content modeling,” and it shapes the very foundation of a content-driven website.

So we’ll start by talking a little bit about the general practice of content modeling—what it entails, how it works, and what you'll need to look out for. Then we'll formally introduce you to all of the elements that make up Symphony's content layer, and as we begin building your blog, you'll see firsthand what it takes to model and manage content in Symphony. By the time we finish the chapter, you'll be comfortable defining content and working with the content layer on your own, and your new blog will have a rock solid foundation.

Let's start with a teaser exercise.

1. Point your browser to Symphony's admin interface.
2. Wink coyly at the screen and whisper, “Hey there, sexy.” (What? I did say "teaser").
3. Navigate to Blueprints > Sections, and click the green “Create New” button. You'll see a page that looks like Figure 5-1.

        Figure 5-1	[f0501.png]`

4. Under "Essentials," enter Blog Posts as the name and Content as the navigation group (don't worry, I'll explain this later).
5. To the right, under "Fields," you’ll see the section fields tool. Click the “Add Field” button. A drawer will open displaying a set of field types. Click “Text”
6. A panel will appear for configuring your new field (Figure 5-2). Just enter the Title as the name, and select "Single Line" in the size dropdown. Leave the remaining items untouched.
    
        Figure 5-2	[f0502.png]

7.	Click “Text” again to add another text field to your section. This time, enter Body as the name, and in the size dropdown, choose “Large Box.” Leave the remaining items untouched.
8.	Click “Create Section.”

Well done. You’ve just created your first section, essentially telling Symphony that you want to manage a type of content called “Blog Posts,” and that each of its entries should have a title and a body.

At this point, you could actually already begin creating blog posts in the system. See for yourself:

1. In the navigation menu, under "Content," click "Blog Posts."
2. You'll see an empty table, because we've not yet created any entries. Click the green "Create New" button.
3. You'll see the entry editor for your new section (Figure 5-3). Go ahead and fill in the form with some sample info.

        Figure 5-3	[0503.png]

4.	Save the entry, and navigate back to Content > Blog Posts. You should see the entry you just created listed there.

Easy, huh? Now, I’m sure you’ve got all kinds of great ideas on how we can make these blog posts more interesting, but for now let's hold off until you've got a firmer grasp on how all of this actually works.

## What is Content Modeling?

When a system gives you the freedom to dream up your own kinds of content, it needs to know what you want from that content—how you want it to behave, how you want it treated. And it needs to be told in terms that it can use and understand. You have to create a representation of that content for the system, a model, using the system's own tools and idioms.

Take a spreadsheet, for example. You can put whatever you like into a spreadsheet—shop inventory, household chores, contact lists—but it's all got to be organized into rows, columns, and cells. These are the basic elements of a spreadsheet, and they determine how its data can be entered, sorted, graphed, and so on.

So let’s say, for instance, that you’re a nutritionist. You want to create a spreadsheet for your clients to help them get a clearer idea of what they’re consuming. It will list all of the foods they eat on a regular basis, and you want them to be able to sort that list to see, for example, which foods have the most calories, or sugar, or provide them most calcium. So you decide that you’ll be entering food items, one per row, and that you’ll add columns for each nutritional fact you want to capture. And just like that, you’ve modeled some content.

For our purposes, then, content modeling comprises three basic tasks:

1. identifying the types of content you want to manage
2. defining the data you want to capture for each
3. representing this information in the system

The idea is fairly simple, but it actually does take a bit of knowledge and forethought. In your nutritionist's spreadsheet, for example, you had to already understand how spreadsheet applications work, and you needed to put some time into figuring out what information you wanted to store about each food.

And that was a very simplistic approach. It could easily get more challenging. What if you wanted to organize foods by type? Would you add that as a column? Make separate spreadsheets? And then what if a food could belong to more than one type? Or have different characteristics depending on where it's from or how it’s cooked?

Nuances like these make it critical that you understand what you want to be able to achieve with your content and how exactly the system is going to treat it once you've created it.

## Understanding Content in Symphony

Thankfully, as you saw in the previous chapter, Symphony's content layer is pretty straightforward.

The various kinds of things you want to manage, be they blog posts, products, photos, food items, or whatever, are sections. The bits of information you want to capture in each section—a blog post’s title, a product’s serial number—are fields. Your data will come in many different shapes and sizes, so field types give you lots of choices for how to handle it. And each individual content item—each blog post, for example—is an entry.

This granular approach means you can combine these various pieces in any way you like. You can decide not only what kinds of content to manage and what data to capture but also how the data gets captured and stored, how it can be interacted with, and so on. This is important because each bit of your data is going to be meaningful in its own, specific way, and you don’t want to treat it all the same.

So what do you need to know in order to plan your blog’s content effectively? Let’s break it down piece by piece:

### Sections

First up are sections, the fundamental building blocks of a Symphony website.

Sections define the content you can manage. Creating a section and adding fields to it enables you to start producing content entries. The section, and the fields it contains, define what the entries will look like and how and where they’ll be managed.

Sections define your publishing interfaces. For each section, Symphony creates an area in the admin interface for managing its entries. There’s an entries index, or a table view for browsing and managing them in bulk, and an entry editor for creating and updating individual entries. Sections are added to the navigation menu so you can access these interfaces (unless you choose to hide the section).

Though Symphony creates each section’s entry editor view automatically, the section itself defines how it will be structured (we’ll see how below). Obviously, the fields contained in a section determine the form elements that you see (Figure 5-4), but you can configure the layout and organization of these elements.

    Figure 5-4	[f0504.png]

Sections organize your content. All entries in the system are naturally grouped by section. Later on, when you need to fetch content entries or process data submissions from the front end, you'll do so by section.

You can view and manage the sections you’ve created at Blueprints > Sections.

### Fields

Fields do a lot of the heavy lifting for sections. It’s fields that give a section its shape, store its data, and determine its output.

Fields capture discrete bits of data. When you’re creating an entry, you’re not just dumping its content into some abstract container. Every entry is made up of one or more fields, and it’s the fields that capture the actual data, piece by piece.

Fields validate and store data. Each field is responsible for storing the content it’s captured. This means that individual fields can use their own rules to validate, transform, and save their data.

Fields output your data for templating. When your entries are passed to the front end, it’s the fields that are responsible for handling and outputting their own data.

Because fields are the primary data handlers, they're used throughout the system for fetching, filtering, sorting, and outputting content.

Once you add a field to a section, you can configure how that individual field will handle all of these tasks—how and where it should be displayed in the entry editor, what rules it should use to validate its input, if it should be displayed in the entry index, and so on.

### Field Types

The actual options you get when you’re configuring a field, and the way it captures, stores, and handles its data, are determined by its type.

Field types define how fields capture data. A text field, for example, gives you either a text input or a textarea, depending on its size. A select box field gives you a dropdown menu. A checkbox field gives you… wait for it… a checkbox. There are field types that give you more advanced form elements too: calendar date pickers, maps, sliders, autocomplete inputs, and more.

Field types define how fields validate and store their data. The number field type will only accept digits, for example. The map location field stores the chosen locations as coordinates. The date field type accepts textual date strings and stores them as timestamps.

Field types determine how a field's data is output. The title field in our Blog Posts section, for example, being a text field, would output its content like this: <title handle="my-first-entry">My First Entry</title>. A date field, on the other hand, would give us something more appropriate for the kind of content it’s meant to capture: <date time="10:52" weekday="5">2010-12-21</date>.

Field types are provided by extensions. This is one of the primary reasons that Symphony is so flexible. Instead of limiting you to a handful of common field types like text, date, select box, checkbox, and so on, any number of specialized field types can provide whatever functionality you might need. We’ll talk more about working with extensions in Chapter 9.

> ###### Note
> 
> Field types also determine how a field can be filtered. Each field type has its own filter rules, so while text fields can be tested against phrases, for instance, date fields can be tested relative to other dates (e.g. ‘earlier than’ or ‘later than’). We’ll discuss filtering in much more detail in Chapter 7.

### Relationships

Very often, the various kinds of content we’re modeling for a website are somehow related to one another. The blog we’re building, for example, should support commenting, and each entry in the Comments section will need to be linked to a specific Blog Post entry.

Content relationships can make websites and web applications tremendously robust, allowing you to organize and browse entries via their relationship to other entries.

Relationships in Symphony are managed via fields. There are a handful of specialized field types that can be used to create and manage relationships. Adding one of these fields to a section will enable you to create relationships between entries in that section and entries in whatever target section you choose.

[This part of the system is still being designed, so the remainder of this section will have to be filled in once it’s complete]

### Planning Our Blog

Armed with this knowledge, let’s map out a content structure for our new blog website.

What do we want to be able to manage? We’ve already set up a basic Blog Posts section. That one’s sort of a no-brainer. We know we’ll want visitors to be able to leave comments, so we’ll need a Comments section too. And let’s imagine that we want to organize our posts into a handful of predefined categories like “Work” and “Travel,” so we’ll add a Categories section. That should do it for now.

We’ve already started modeling our Blog Posts, but obviously they’ll need much more than a title and body. If we’re aiming for a traditional blog view (posts listed in reverse chronological order), then we’ll need our posts to have a publish date. And if we want to be able to assign them to a category, we’ll need a category field that creates that relationship. Finally, let’s add a checkbox to denote whether the entry is published or not, this way we can have unpublished drafts. That wasn’t too difficult, was it?

Comments are easy too. When visitors submit comments, we’ll want to capture their name, email address, and the comment itself. We’ll also want to record the date and time. A field pointing back to the associated blog post should round off this section.

As for categories, let’s just say each category will have a name and a description. We won’t need much more than that.

So here’s what our content structure looks like at the moment:

- Blog Posts
- Comments
- Categories
- Title
- Author
- Name
- Body
- Email
- Description
- Publish Date
- Comment
- Category
- Date
- Published
- Post

Simple, but it will do the job. Now let’s go build it.

## Working with Content in Symphony

The last section was all ideas and descriptions; this one is about doing. Here we’ll explore Symphony’s content layer in more detail while you go about creating your blog’s content structure.

### Creating and Editing Sections

You already got a taste of sections during the teaser exercise at the beginning of the chapter. You’ll remember that to create a section, you’ll need to:

1. Navigate to Blueprints > Sections
2. Click the green “Create New” button at the top right of the view.

Aside from the field configuration and the layout (both of which we’ll cover below) there are only three things you need to define for each section, as you can see in Figure 5-5:
Figure 5-5	[f0505.png]

- Name is how your section will be identified within the system. The convention is to use plural nouns, like “Products” or “Articles.”
- Navigation Group is used to organize your sections in the back-end navigation menu.
- Hide this Section... keeps the section out of the navigation menu altogether (although it can still be accessed directly)

Let’s create our Comments and Categories sections now. Assuming you’re already looking at the section creation view:

1. In “Name,” enter Comments
2. In “Navigation Group,” enter Content (or click “Content” just beneath the input, which will populate it for you. Symphony will list all existing navigation groups here, for your convenience.)
3. Click “Create Section”
4. At the top of your screen, you’ll see a green notification bar with a message that begins “Section created at...” Click the “Create Another” link.
5. In “Name,” enter Categories
6. In “Navigation Group,” enter Content again (In the other projects at the end of this book, we’ll make much more use of navigation groups, but our blog is simple enough that we don’t really need to over-classify our sections.)
7. Click “Create Section”

Don’t worry that we’ve essentially just created empty shells without any fields in them. I wanted you to get a rough scaffold set up first, and I also wanted you to see firsthand that sections can be developed iteratively—that you can update sections at any time, even after they’ve been created and saved. You can try things, see if they work, and if not, go back and tweak them. This means that your Symphony sites are able to grow and evolve over time. We’ll see this principle in action many times, and in many places, over the course of the book.

### Working with Fields

Of course, sections are kind of useless without fields in them, so let’s go ahead and get those sorted out.

#### Choosing Field Types

The first thing we’ll need to do for each section is decide which field type to use for each of the fields we’re planning on adding.

As you saw above, field types determine a great deal about how your data is going to be handled by the system. You’ll have to take into account all of the things for which a field type is responsible:

What kind of form element does it provide in the entry editor? Do you want your users to enter text directly, for example, or choose items from a dropdown?

What validation options are available? Can you enforce that the value be a URL, for example, or an email address?

Can you apply text formatters to the content? A WYSIWYG editor, for instance, or a syntax like Markdown?

How will filtering and sorting be handled? Text fields, for example, won’t sort numbers correctly. Relationship fields actually store entry IDs and so filtering and sorting by those might not work as you’d expect.

What will the field’s XML output look like?

Quite a bit to keep in mind. And what’s more, once a field’s been added to a section, you can’t switch between types. So making informed choices at this stage is important. You won’t want to add too many content entries until you’re confident in your section structure, because swapping field types means removing the original field and losing all its data.

All that may seem intimidating, but like many things in Symphony, a lot of this can be figured out with good old common sense. The system comes bundled with eight basic field types, most of which are self-explanatory. Dozens more are available as extensions, but for now we’ll just review the basic ones.

For each field type listed below, I’ll outline:

- what form element it provides for capturing data in the entry editor
- how it stores its values
- whether/how it validates its input
- whether/how it can be used to filter entries
- whether/how it can be used to sort entries
- what some example output looks like
- whether/what special configuration options are available

#### Checkbox

- **Form Element:** checkbox
- **Stores:** One of two values: “Yes” or “No”
- **Filter:** by value
- **Example Output:** <field-name>Yes</field-name>
- **Configuration Options:** whether field should be checked by default

#### Date

- **Form Element:** text input
- **Stores:** timestamp
- **Validates:** as date or timestamp
- **Filter:** by year, date, or timestamp
- **Sort:** by date
- **Example Output:** <field-name time=”12:21” weekday=”5”>2012-12-21</field-name>
- **Configuration Options:** whether to prepopulate with current date

#### Number

- **Form Element:** text input
- **Stores:** number
- **Validates:** as integer
- **Filter:** by value
- **Sort:** by value, numerically
- **Output:** <field-name>0</field-name>

#### Relationship

- Form Element: select box
- Stores: an entry ID
- Validates: as entry ID from linked section
- Filter: by ID or value of linked field
- Example Output: <field-name>Value of linked field</field-name>
- Configuration Options: what section and field to link to, what type of relationship to create

#### Select Box

- **Form Element:** select box
- **Stores:** a textual value
- **Validates:** as one or more values among preconfigured options
- **Filter:** by textual value
- **Sort:** by textual value
- **Example Output:** <field-name><item>option 1</item></field-name>
- **Configuration Options:** what static/dynamic options to provide, whether to allow multiple options to be selected

#### Tag List

- **Form Element:** text input with suggestions list
- **Stores:** a textual value
- **Validates:** as comma-delimited values
- **Filter:** by items’ textual value
- **Example Output:** <field-name><item>Tag 1</item><item>Tag 2</item></field-name>
- **Configuration Options:** where to draw suggestions list from

#### Text

- **Form Element:** text input or textarea
- **Stores:** a textual value
- **Validates:** against any regular expression pattern (URL and email address are preset options)
- **Filter:** by textual value or regular expression
- **Sort:** by textual value
- **Example Output:** <field-name handle=”sample-text”>Sample Text</field-name>
- **Configuration Options:** text formatters to apply, size of field

#### Upload

- **Form Element:** file upload
- **Stores:** the file, at designated location, and the file name, path, and size
- **Validates:** as file type (image and document are preset options)
- **Filter:** by file name
- **Example Output:** <field-name><file path=”/workspace/uploads” name=”hamburgler.png”><meta><type>image/png</type><size>44kb</size></meta></file></field-name>
- **Configuration Options:** upload location

#### User

- **Form Element:** select box
- **Stores:** a user ID
- **Validates:** as user ID
- **Filter:** by user ID or user name
- **Sort:** by user ID
- **Output:** <field-name username=”user-name” id=”1”>Full Name</field-name>

That’s a lot of info, but all pretty straightforward, actually. We’ll be able to choose our field types without much deliberation at all. Our Blog Posts’ “title” and “body”, we know, are text fields. The “publish date,” clearly, will be a date field. “Published” is going to be a simple checkbox, because we only need to be able to mark entries as published or not published. And “category” has to be a relationship field pointing to Categories.

For Comments, “author,” “email,” and “comment” will all be text fields. “Date” will be a date field, of course. And “Post” will be a relationship field pointing to Blog Posts.

Categories will just have its two text fields, “Name” and “Description.”

### Adding and Configuring Fields

Now that we’ve got our content model mapped out, the rest should be easy. You’re still looking at your empty Categories section, right?

1.	Under “Fields,” you’ll see the fields tool. Click the “Add Field” button.
2.	A drawer will appear containing a list of field types. Click “Text”

As you’ve seen, whenever you add a field to a section, you’re presented with a configuration panel that allows you to specify options for that field’s appearance and behavior. Some of these options are common to all field types, and as we saw above, field types can also provide their own configuration options.

Figure 5-6 highlights the options common to all field types.

    Figure 5-6	[f0506.png]

Name is the name used to reference the field internally. It’s what you’ll use when choosing the field for filtering, sorting, and output, and is used an as element name in the field’s XML output.

Publish Label is optional, and is used when the text you need to display with the field’s form element is more complex or explicit. You might have a date field, for instance, whose label needs to read “Enter the date to publish this entry,” but you don’t want the system to use that unwieldy phrase everywhere.

Show column tells the system whether to display the field’s value in the section’s entry index.

Make this a required field tells the system whether to require that the field have a valid value before saving.

The rest of the options you see above are specific to the text field type. We won’t get bogged down with all of that here, but you might want to take a quick glance at Appendix B for a description of the configuration options provided by each field type. For now, let’s finish configuring your Categories.

First, configure the name field:

1.	Enter Name into the Name field
2.	In the Size dropdown, select “Single Line”
3.	Check the box next to Make this a required field
4.	Leave the rest of the options in their default state

Now we’ll add the description field:

1.	Click “Text” again in the field types list to add another text field
2.	Enter Description into the Name field
3.	In the Size dropdown, select “Medium Box”
4.	Uncheck the box next to Show column
5.	Uncheck the box next to Output with handles
6.	Click “Save Changes”

Let’s do the Comments section next:

1.	Go to Blueprints > Sections (or in the notification bar that appeared at the top of your screen when you saved this section, click the “View all” link)
2.	Click “Comments” in the sections index
3.	In the fields tool, click “Add Field”

We’ll start with the author field:

1.	Click “Text”
2.	Enter Author as the field’s name
3.	In the Size dropdown, select “Single Line”
4.	Check the box next to Make this a required field

Now the email field:

1.	Click “Text” in the list of field types
2.	Enter Email as the field’s name
3.	In the Size dropdown, select “Single Line”
4.	Click the gray “email” link beneath Validation Rule. This will populate the Validation Rule with a regular expression pattern that matches email addresses.
5.	Check the box next to Make this a required field

Now a field for the actual comment:

1.	Click “Text” to add another text field
2.	Enter Comment as the field’s name
3.	In the Size dropdown, select “Medium Box”
4.	In the Text Formatter dropdown, select “Markdown (with HTML Purifier)”. Because you’re allowing visitors to submit comments from the front end, we’ll want to make sure the input is cleaned up before we save it. Adding this text formatter to the field will take care of that, and will also allow commenters to add formatting to their comments using Markdown syntax.
5.	Uncheck the box next to Show column. Comments will be far too long to display in a table cell.
6.	Check the box next to Make this a required field. No use saving a comment if there’s no comment.
7.	Uncheck the box next to Output with handles.

Lastly, add the date field:

1.	Click “Date” in the list of field types
2.	Enter Date as the field’s name
3.	Check the box next to Make this a Required field

I know there’s one more field to add, but we’re going to take care of creating relationships a little later. For now, we’re all set.

1.	Click “Save Changes”

Now let’s go back to our good old Blog Posts section and round it out:

1.	Go to Blueprints > Sections, or use the “View all” link in the notification bar
2.	Click “Blog Posts” in the sections index
3.	Click “Add Field” in the fields tool
4.	Click “Date” in the list of field types
5.	Enter Publish Date as the field’s name
6.	Check the box next to Make this a Required field

We’ll skip the category field for now, because we’re going to address relationships a bit later. So let’s finish off by adding the published field:

1.	Click “Checkbox” in the list of field types
2.	Enter Published as the field’s name
3.	Let’s add a more descriptive label for this field. In the Publish label input, enter Publish this post
4.	Click “Save Changes”

### Reordering and Removing Fields

Let’s take a closer look at what the fields tool looks like once you’ve got a bunch of fields in it (Figure 5-7):

    Figure 5-7	[f0507.png]

Each of the fields you’ve added is listed on the left side in a sort of “tab.” The tab contains the field’s name and field type so you can see at a glance what your section looks like.

If you hover over these tabs with your mouse pointer, you’ll see a small delete button appear. This is how you remove a field from your section.

To reorder fields, simply click the field tab and drag it up or down. Try reordering the fields in your Blog Posts section and saving your changes.

Whichever field is listed first is treated by Symphony as the section’s primary field. This means it’s used when referencing an entry in the admin interface (for instance, in the entries index for that section, it’ll be the field that’s listed in the first column and linked to the entry). Restore the original order of the fields and save your changes again.

One last trick to know about the fields tool: sometimes you need to see the configuration options for more than one field at a time. Try holding the Shift button while clicking on tabs. This allows you to select multiple fields, and when more than one field is selected, the configuration panels will stack on the side. This can come in handy when you want to quickly compare fields.

### Designing Section Layouts

As you’ve seen, Symphony automatically creates interfaces that allow you to manage the content in your sections. One is the entries index, and we’ve already seen how you can use field configurations to define what gets displayed in that table.

The other interface that Symphony creates for you is the entry editor. You know now that each field is responsible for rendering its own form element in the entry editor, and you’ve seen how field configurations allow you to define how these are labeled (and often set other options as well). Let’s take a quick glance at what your Blog Posts entry editor looks like with the new fields added:

1.	Go to Content > Blog Posts
2.	Click “Create New”

You should see something that looks like Figure 5-8:

    Figure 5-8	[f0508.png]

Simple, but not at all elegant or user-friendly. Thankfully, Symphony allows you to define how these form elements are organized and arranged, meaning you actually have quite a bit of control over the content publishing experience.

Let's go back to our Blog Posts section so you can see what I mean:

1.	Go to Blueprints > Sections
2.	Click on “Blog Posts” in the sections index

You may not have noticed this, but whenever you create a section, a second tab appears in the section editor below the section name. Click on the Layout tab.

    Figure 5-9	[f0509.png]

This is the section layout tool (Figure 5-9). It enables you to define what a section's entry editor will look like—namely, the order and organization of its fields.

You start by choosing a base column layout. If you click the “Choose Layout” button, you’ll see a drawer appear with the various column options. For our Blog Posts, we’ll stick with the default column setup, so go ahead and click the button again to close the drawer.

### Working with Fieldsets

Within your chosen base layout, fields can be grouped into fieldsets. Each column contains a single fieldset by default, but you can easily add additional fieldsets using the “Add Fieldset” button.

At the top of each fieldset, above the dotted line, you can enter a title for that fieldset. This is entirely optional, and by default fieldsets will have no titles, but they can be helpful for sections that have multiple groups of fields, and overall usually make your entry editor much more usable.

Once you’ve chosen a base layout, you can begin organizing your fields. By default, Symphony just stacks your fields into a single fieldset in the first column. You should see all four of your Blog Posts’ fields there. let’s do a little rearranging:

1.	Click and hold the Publish Date field, and drag it into the right column.
2.	Click and hold the Published field, and drag it into the right column.
3.	In the left column, enter Content as the fieldset title (above the dotted line).
4.	In the right column, enter Info as the fieldset title.
5.	Save your changes.

Now, if you go back to Content > Blog Posts and click "Create New," you'll see a more organized entry form (Figure 5-10).

    Figure 5-10	[f0510.png]

Now, your Categories section is so simple that we don’t really need to look at its layout, but let’s do some fine-tuning in the Comments section. Even though comments are going to be submitted from the front end rather than here in the admin interface, there’s no harm in making it tidier.

I’m actually going to leave this one to you. Using whatever column layout, fieldsets, and field arrangement you like, go ahead and reorganize your Comments layout. Take the time to experiment with various groupings and columns and see how they affect the entry editor for that section.

Once you’re happy with what you’ve done, we’ll move on.

### Tabs and Steps

For especially complex sections, with lots and lots of fields or unique workflow needs, it’s possible to split fields and fieldsets over multiple tabs or steps. Tabs simply allow you to spread your fields and fieldsets over multiple views, whereas steps enable the creation of so-called “wizard” interfaces where content must be entered in order, one step at a time.

To split your fields over multiple tabs or steps...

[This functionality hasn’t yet been implemented. Will need to revisit once development is further along.]

### Creating Relationships

[This functionality hasn’t yet been implemented. Will need to revisit once development is further along.]

### Managing Sections

Guess what? Your blog’s content structure is complete! And if I hadn’t spent so much time blathering about all the various concepts and options you encountered, it probably would’ve only taken you five or six minutes from beginning to end! The nice thing is, once you’re more familiar with all these nuances, you’ll be able to build a website’s content structure in the blink of an eye.

I just want to review a few more helpful tidbits about managing sections before we move on.

There are two ways to delete a section. The first is to go to the section editor and click the red “Delete” button. The second is to go to the sections index, click the section’s row (it’ll be highlighted blue when it’s selected), choose “Delete” from the “With selected” dropdown below the table, and click “Apply.” (This paradigm actually applies to pretty much everything in Symphony).

The other thing to know is that everything you can accomplish in the admin interface—creating, editing, and deleting sections, adding and configuring fields, defining a section’s layout—all of this can also be achieved by directly editing the section file (though doing so isn’t recommended).

> ###### Note
> 
> All of the configuration data that makes up a section, from its name and its field makeup to the layout of its entries, is stored in a physical XML file in your workspace (in the /workspace/sections/ folder). Here's a simplified example—the file for our Blog Posts section:

    <section>
      <name handle="blog-posts">Blog Posts</name>
      ...
      <fields>
        <field>
          ...
          <type>textbox</type>
          <element-name>title</element-name>
          <name>Title</name>
          ...
        </field>
        ...
      </fields>
      <layout>
        <column>
          <size>large</size>
          <fieldset>
            <name>Content</name>
            <field>title</field>
            <field>body</field>
          </fieldset>
        </column>
        <column>
          <size>small</size>
          <fieldset>
            <name>Info</name>
            <field>publish date</field>
            <field>published</field>
          </fieldset>
        </column>
      </layout>
    </section>

Having all of this information in a physical file will help you develop more efficiently. For starters, unlike tables in a database, files can be easily version-controlled. That means teams can develop content structures for their projects collaboratively, and iteratively, without worrying about stepping on each other’s toes. It also means that if you spend time developing a very finely-tuned section for one site, and then you find that you need to manage similar content in another site, all you need to do is copy the file over.

### Synchronizing Sections

Whenever a section file is updated, the system will detect the changes and ask you to sync the section so your database can be brought up to date. This is an automated and painless process.

[This functionality hasn’t yet been fully implemented. Will need to revisit once development is further along.]

### Managing Entries

The last thing we need to discuss in this chapter is how to manage entries. You’ve already seen how entries are created and edited. And by now, I hope you’re becoming familiar with Symphony’s common user interface paradigms (the indexes, editors, action buttons, and so on). So most of this should be easy to figure out on your own, but I’ll just highlight a few especially helpful things.

You’ll probably find yourself needing to sort, browse, and search through entries on a regular basis. The entries index table for each section is sortable by whatever fields you choose to include in the table, so if you want to sort your Blog Posts by date, for instance, make sure to check “Show column” in that field’s configuration. By default, entries are sorted in order of creation, newest to oldest. The tables are paginated, of course, and the number of items per page can be adjusted in the system configuration (which we’ll discuss in Chapter 9).

There’s also a filtering mechanism that allows you to run more precise searches on a section’s entries. [This functionality hasn’t yet been fully implemented. Will need to revisit once development is further along.]

Deleting entries works exactly like deleting sections. You can do so either from the entry editor itself, or from the entries index (using the “With selected” dropdown).

This “With selected” dropdown—or bulk actions menu—can also offer other options. Certain field types allow their values to be toggled via this menu, which means that, depending on how you’ve modeled a section, you’ll usually have some options for updating field values in multiple entries at once. To see it in action, first add a few additional test entries to your Blog Posts section. Once you’ve got two or three entries:

1. Navigate back to the Blog Posts index (Content > Blog Posts)
2. Click on one or more rows to select those entries
3. Click on the “With Selected” dropdown

You’ll see that the checkbox field allows its value to be toggled here, so you can publish or unpublish lots of Blog Posts at once. Select box is another field type that allows its value to be set via the bulk actions menu. This functionality is often very useful in situations where you need to enforce moderation or publishing workflows—for example, marking entries as approved or published, or changing their status.

## Summary

We’ve covered quite a lot in this chapter. We started by introducing you to the practice of content modeling, so that you’d understand what goes into defining a website’s content structure. We then reviewed in more detail the elements that make up Symphony’s content layer—sections, fields, field types, and entries.

Once you were familiar with the basic architecture and concepts, we went about setting up a content structure for your blog. Along the way, you learned how to work with each of these elements in turn, from basic section creation to advanced field configuration and entry management.

There always more to learn, of course. Appendix B outlines in painstaking detail everything you need to know about common field types, and you should study that closely. On top of that, ever more can be found online in the official documentation (http://symphony-cms.com/learn/). This is a great start, though, and by now you should feel pretty comfortable working in Symphony’s content layer on your own.

Let’s move on to the front end.