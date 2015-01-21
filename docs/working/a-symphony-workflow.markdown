##META
* Doc Version: 1
* Author: John Porter
* Applies to: 2.x
* Based on: 2.x
* [Production URL]()

#A Symphony Workflow

This article is based on solid git techniques, taking heed of advice learned from Symphony community members, many many tips on the web, and flicking through the pages of [O'Reilly's Version Control with git](http://oreilly.com/catalog/9780596520137/).

The core aim of this article, is to get a good Symphony development and deployment environment using git as the basis for the organisation, and have it written down for reference.

##Before you start, be aware:

*This article assumes you will be developing Symphony projects, and storing them under **private** remote repos. Due to some of the techniques described in this article, **sensitive information** relating to your your **install and database** will be stored in the git repo. If you decide to store your project under public remote repos **this information will be publicly available**. I hold no responsibility for you doing this publicly. You have been warned!*

##Planning a Symphony Workflow

So, where to begin and what to do. We need to outline a few things first, so we don't miss anything in the setup. I know from experience that having to backtrack and retrospectively do project-wide setup can go horribly wrong, and there have been many times I've scrapped a repo and started again (usually down to submodule headaches, and my own oversights). Luckily, I'm a sole developer. Just try doing that when there are two or more of you, and you could end up on the losing side of a git battle, and we don't want to fall out with fellow developers.

###Keeping branches separate

We need to Keep the Symphony, Development and Master branches separate. This is key to a hassle free working life, and can make it very easy to do separate work on mainline development, bug-fixing, and urgent quick-fix development all on the same source code. It will allow you to make use of the really useful `git stash` command and temporary `git branch`ing.

The best git branching model explanation I've seen, and advocate at my employers agency is '[A successful git branching model, by Vincent Driessen](http://nvie.com/posts/a-successful-git-branching-model/)'. I highly recommend reading this article, whether you adopt it or not as I won't go in to detail here, and it will give you a good understanding of Git branches.

We will begin with three branches `master`, `develop` and `symphony` to separate out our main actions. More will be added as and when you need them, following the branching model.

> Hang on, symphony isn't on the branching model!

No, it's not. As we're using Symphony as a codebase of sorts, which gets updated often, we need a separate branch that's not in the model to pull our updates into; This way, we can test for any merge conflicts separately from our development, therefore keeping Symphony core development separate to that of our site.

###Apply your remotes early

This makes sure that before development starts, you have all your sources/targets in place. It should really only be your repo and the Symphony repo to start with.

If you're planning to deploy the site to git enabled web-servers, there will be another remote or two to add, but this is beyond the scope of this article. Maybe in the future, filed under 'Seriously advanced Git'.

###Extensions and Libraries Submodules

With a Symphony project, you're inevitably going to use submodules for extensions, and libraries of code too. This can be a pain to do if you don't plan it properly first. With submodules being quite a new feature in the life-cycle of Git, they are still buggy in their implementation, and the issues haven't even been fully discussed yet, let alone ironed out, especially when it comes to removal/deletion. So proper planning can be a lifesaver.

List the extensions, code libraries and submodules that you know about and intend to use for the project. Adding these later isn't a problem, but start a list, including the remote paths for them. It's better to copy and paste during setup than to have to keep going and finding them. Later additions can be added, to keep a record of outsourced code.

####Extensions Planning

When it comes to extensions, it's very useful to make your list like the following, so you can use my wonderful method of automatically adding them all at once. The list needs to be a plain text csv document stored like the following:

	git://github.com/symphonycms/export_ensemble.git,extensions/export_ensemble
	git://github.com/symphonycms/markdown.git,extensions/markdown
	git://github.com/symphonycms/maintenance_mode.git,extensions/maintenance_mode
	git://github.com/symphonycms/selectbox_link_field.git,extensions/selectbox_link_field
	git://github.com/symphonycms/jit_image_manipulation.git,extensions/jit_image_manipulation
	git://github.com/symphonycms/profiledevkit.git,extensions/profiledevkit
	git://github.com/symphonycms/debugdevkit.git,extensions/debugdevkit
	git://github.com/symphonycms/xssfilter.git,extensions/xssfilter

Notice how there is no space after the comma? It needs to be that way if you intend to automatically add them all.

*The above list is the extensions that ship with Symphony for display purposes, so you won't need to list these, but your list should follow suit.*

The first item is the git link to the extension's repo. You will need to use read-only links here, don't use write access links as these can cause issues for developers who don't have write access to the extension (If you want to add write access you can do this by manually cd-ing into the directory and altering the submodule's git repo, although this can cause headaches down the line). The second item is the path to the etension, from the root of your repo. If you save it in this format, you will be pleasantly surprised later on

####Libraries Planning

You're inevitably going to use code libraries at some point in your project too. Libraries are such things as jQuery, jQuery plug-ins etc. Making a list of ones you think you'll need will help in the future, but don't worry about it as these things will more than likely not be known until later in your project.

##Flex your terminal fingers

And we shall begin...

*This example project is called `mywebsite`. You should replace these references with something more meaningful to your project.*

You may notice through this process that we aren't doing any git `clone`s. Cloning a repo is ideal for development on that code-base, but that isn't what we want to do here. We are starting a new project that uses an existing code-base as a kick-off point, not a duplicate of it, that's why we need to do a little more work in setting it up. It will save you overhead and confusion later on, trust me...

###Setting Up

Firstly, we need to set up the project folder.

*In Terminal, make the folders then initialise the git repo:*

	mkdir mywebsite
	cd mywebsite
	git init

Once this is done, we can start to add our remotes.

###Git Remotes

We will need to apply at least our two main remotes for the project. One for the Symphony official Github repo, and the other for your project's remote repo.

For Symphony, we need to use git options to limit what we use from the repo. This will enable us to not overload our project with excess information that we will never use, i.e. all of the Symphony version tags and other branches.

The options we use are:

 -	`--no-tags` which will make sure we never pull the tag list down. You just have to ask yourself:

	> What happens when I want to tag my site as 2.0 or 2.1, when that version tag already exists in Symphony?

	Exactly. Symphony's `master` branch is the most recent version of the code, and that is what you will want to develop on top of.

 -	`-t master` which will only deal with the master branch for our repo. (Some of you may notice that this is tracking the two branches together, but we will change this later.)

*In Terminal, add the git remotes*

	git remote add --no-tags -t master symphonycms git:github.com/symphonycms/symphony-2.git
	git remote add mywebsite git@github.com:myusername/mywebsite.git

Our second remote makes use of the `ssh` style git protocol to give us write access to the repository, where `myusername` is the username you have on Github. Feel free to host your project elsewhere, changing the details as required. If you choose to host it on Github, remember to use a private repo, as explained earlier.

###Branching Our Project

We need to set up our branches next, but just before that, we have to give our project some default content to work with. We have to `fetch` the Symphony repo into the project. With this step, we can make sure we don't copy the entire history of the project and bloat our own project.

*In Terminal, fetch the Symphony repo*

	git fetch symphonycms master

Once done, we can start branching. We should specify the start point for best practice. *It isn't required, but getting into the habit will benefit us in the long run.* Our start point will be the code we just pulled, which resides in the `FETCH_HEAD`. Because we havent done anything yt, we have no default `master` branch, so we need to create that too. We do that by merging the `FETCH_HEAD` to our current location

*In Terminal, create the required branches*

	git merge FETCH_HEAD
	git branch symphony FETCH_HEAD
	git branch develop FETCH_HEAD

All is good so far. Now we have our three branches.

###Remote Tracking

Remote tracking makes it easier for us to `push` and `pull` from our remotes, without having to remember which branches on our remotes we are working with. When you end up having lots of branches for different features/bug-fixes/developers, things can get very confusing when you have to switch between them for simultaneous development and management of your project.

First off, we need to `push` to our site's remote to give us something to track to, for all of our project branches (including the Symphony branch). If we skip this step, git will give us an error when setting up tracking, saying that the branches don't exist on the remote.

*In Terminal, push to our remote*

	git push --all mywebsite

Now we can commence tracking our remotes

*In Terminal, apply tracking for our branches*

	git branch --set-upstream master mywebsite/master
	git branch --set-upstream develop mywebsite/develop
	git branch --set-upstream symphony symphonycms/master

An upstream basically tells git that the branches are linked to each other, and lets git give us useful information when committing changes, like how many commits we are ahead of the remote, or whether the remote has received more commits than we have locally. This information can become vital when dealing with multiple developers, but is very useful when you're developing on your own too.

It also allows us to omit the repo and branch name to `push` to, so dependent on which branch we are developing on we can use

	git push

instead of

	git push mywebsite master

or

	git push mywebsite develop

So, that's the setup complete, now all we need to do is checkout our development branch, and get on with it.

*In Terminal, checkout the `develop` branch*

	git checkout develop

###Adding Extension Submodules

Adding submodules is rather easy. Just make sure you get it right, as removing mistakes *isn't* easy. It can be really annoyingly confusing (until you read Removing Submodules later on).

To add a Symphony extension, you need to know the repo's path and the extension's directory folder name. Irrespective of the fact that some extension developers don't use the generally accepted method of naming a repo, we will have to manually write out the extensions path to add it anyway, so we can rectify these inconsistencies.

If you have made the list as mentioned in 'Planning A Symphony Workflow', then you can just copy and paste the required text.

*In Terminal, from the root of your repo, add an extension like so:*

	git submodule add git://github.com/developer/extension.git extensions/extension

The `git://` path is the path to the developer's repo, and the second path is where we would like the extension to be added within the `/extensions` folder. You really need to get these paths right because once the submodule is added, it can't be changed other than updating the contents.

####Auto Adding Extension Submodules

Because we will invariably want to use lots of the really cool extensions that are available to Symphony, adding them one by one at the beginning of the project is going to make you pull your hair out in desperation of finding a better way of doing it, well, look no further! I asked for help on StackOverflow and I got it, so now I will share this with you all.

The first thing you will need is the list of extensions you should have made during the 'Planning A Symphony Workflow]' section. We will need this to automate the extension adding. Thoroughly check this list to make sure that there are no mistakes, as the automation process isn't foolproof by any means, and will just keep on trucking past mistakes.

*In a text editor of your choice, write this code out*

	#!/bin/bash
	filename="$1"
	while IFS="," read one two
	do
	git submodule add "$one" "$two"
	done < "$filename"
	git submodule update --init

What's that? Doesn't make any sense to you? No, me neither, but it works. Thanks go to `ghostdog74` on stackoverflow.com for writing this for me. I'm no bash-er and would never have come up with this.

Once you have this script, save it as `git-auto-submod.sh`, somewhere that you can access it via your shell.

On my Mac, I have it saved under `/usr/local/bin` which is in my path. You will need to give it Execute permissions to use it too.

OK. Ready? Grab the path to the list you made earlier.

*In Terminal, from the root of your repo, run the script*

	git-auto-submod.sh path/to/extensions.csv

This will automatically add every extension you have listed in the csv file. Magical eh?

####Removing Submodules

Submodules can be a nightmare and the bain of a git user, although as a feature, they're quite cool! Until you need to remove them, that is.

There isn't as of yet a magical command to do this, why it's been left out I don't really know, but it is doable, it just takes a little work.

-	Find the relevant line in your `.gitmodules` file and remove it, then save the file.
-	Find the relevant section in your `.git/config` file and remove it, then save the file.

*In Terminal, from the root of your repo, remove a cached submodule*

	git rm --cached path_to_submodule
	git commit -m 'your message here'

Not so hard now was it?

###Rowan's Marvelous Multiple Manifest's

One problem that has been encountered by many with Symphony is how to keep separate configurations for separate servers. There is discussion among the working groups to add this functionality in to the config file, but until that happens, we should use Rowan Lewis' method of adding multiple manifest's.

***Warning:** this is the bit the disclaimer was talking about. `manifest/config.php` is where your database details are stored and will be tracked by git using the method outlined here. If you add this to git control and save it publicly, everyone and their grandmother (techno-gran) will have your database!*

*The following has been taken from [Rowan's article](http://rowanlewis.com/using-git-and-symphony-cms), and updated to fit this article's writing style.*

Rowan talks about just two manifest folders, one for each server in the workflow, however my own workflow uses three servers (dev, staging, production) so I will be writing for three.

####Create the Manifest folder

Firstly, as we haven't installed Symphony yet, we need to create the manifest folder ourselves.

*In Terminal, at the root of your repo, create some folders*

	mkdir manifest manifest/cache manifest/logs manifest/tmp

We will need to add some git ignore code to these now to ensure that the contents are hidden from Git

*In Terminal, create some ignore files*

	touch manifest/cache/.gitignore manifest/logs/.gitignore manifest/tmp/.gitignore

These files need to be edited now to contain the following lines. You can use your personal editor of choice.

	*
	!.gitignore

*In Terminal, do the following*

	echo "*" >> manifest/cache/.gitignore
	echo "*" >> manifest/logs/.gitignore
	echo "*" >> manifest/tmp/.gitignore
	echo "!.gitignore" >> manifest/cache/.gitignore
	echo "!.gitignore" >> manifest/logs/.gitignore
	echo "!.gitignore" >> manifest/tmp/.gitignore

This tells git to ignore every file except for the ignore file itself, which is handy as git won’t try and merge temp files, cache files or logs.

Now, we will use this manifest folder to create our live and development manifests.

####A Manifest For Each Server

As we're writing about a three server workflow, we will need to duplicate this manifest folder for each server in our workflow.

*In Terminal, copy the manifest folder, appending the name of the server to the end*

	cp -R manifest manifest.dev
	cp -R manifest manifest.stage
	mv manifest manifest.prod

That last command just renames the `manifest` folder to our production copy. It's easier than copying three times and deleting one.

If we were to install Symphony at this stage, it would create itself a new `manifest` folder, and ignore the hard (easy really) work we've just done, so we need to let Symphony know that there is a manifest folder already there. We do this by creating a link to the relevant `manifest.*` folder we've created.

*In Terminal, create a symbolic link*

	ln -s manifest.dev manifest

Easy as that. Now we need to let Git see that we have three extra folders to track.

> Hang on, only three? What about the link we just created?

Good question. Because of the nature of the `manifest` folder for Symphony, we never track it's content as it can get very heavy with temp files, cache files etc, and constantly merging them with each commit will be tedious, especially when they are only relevant to the current install of Symphony.

Symphony uses the `.gitignore` file at it's root to ignore the `manifest` *folder*, but our *symbolic link* will not be ignored as standard. We want our other `manifest` folders to be tracked by Git, so that when we deploy to a server, we can create a symbolic link there too, and have our config file ready and waiting to work. So, to ignore the `manifest` sym-link, edit your root .gitignore file, and add `manifest`. Like so

*In Terminal, add to `.gitignore`*

	echo "manifest" >> .gitignore

*In Terminal, track the `manifest` folders and commit them*

	git add manifest.*
	git commit -m 'Added manifest folders'
	git push mywebsite

When we install Symphony on our dev server now, it will create all the relevant files under the `manifest.dev` folder as we sym-linked to it. A config file will be created too, and this is the only file you will need to copy into each other `manifest` folder. When you are ready to push to another server, all you will need to do is copy the config.php file and change the database section to match you servers database details.

And that's it for this article. Please have a look at the articles available for installing Symphony and good luck with your projet!