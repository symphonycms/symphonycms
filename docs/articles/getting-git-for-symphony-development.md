---
id: getting-git-for-symphony-development
title: Getting Git for Symphony Development
layout: docs
---

##META
* Doc Version: 102910
* Author: Jonas Downey, based heavily on the work of Git wizard Stephen Bau
* Applies to: 2.x
* Based on: 2.1.2
* [Production URL](http://symphonycms.com/learn/articles/view/getting-git-for-symphony-development/)

#Getting Git for Symphony Development

You're already a Symphony superhero — you've downloaded and installed Symphony, and used it to build an amazing website. Now you want the capability to easily update Symphony and its extensions as they are released, and perhaps also version control your own installation to better manage your changes and customizations. But getting Git? Good grief! 

Never fear, a guide is here!

##Cloning the core repository

Most commonly you will begin by cloning the Symphony core, and updating its submodules. This provides a simple way to update Symphony when new versions are released. You should perform these commands wherever your development Symphony installation lives.

	git clone git://github.com/symphonycms/symphony-2.git directory
	cd directory
	git submodule update --init

If you already have an existing (non-git) Symphony installation, you will need to perform these commands in a new location, then move your existing workspace and any other customizations there.

If you don't need to version control anything else, you can quit while you're ahead and enjoy the easy updates your cloned repository provides. Just issue the command:

	git pull

and your core is up to date.

##Switching to a specific release

Occasionally Symphony betas or release candidates are available for testing, and you want to try one out. Here's how. (Note that any required database changes between versions will not be reflected in this process, unless you opt to run `update.php`. In other words, don't do this until you know what you're doing.)

Fetch all tags and view them:

	git fetch --tags
	git tag
	
choose the one you want (e.g., 2.1.1) and perform a `checkout`. Subsequently, name the branch.

	git checkout 2.1.1
	git checkout -b 211
	
You can see all of your local branches with:

	git branch -v
	
To switch back to the master (stable) release:

	git checkout master
	
##Switching to the `integration` branch and using `cherry-pick`

If you're living on the bleeding edge, you will most likely need to switch to the `integration` branch, which is where active development on Symphony happens. This also comes in handy if you need specific bugfixes that haven't been bundled into an official (tagged) release yet.

	git checkout -b integration
	git pull origin integration

On the somewhat rare occasion that you want to grab code from a particular `commit`, you can do so with `git cherry-pick`. Just switch to the branch with the relevant commit, and specify the commit you want. 

	git checkout master
	git cherry-pick 3346e9459d1964438934194f6f28b290b8e71bcd
	
You can also undo this if something blows up:

	git revert 3346e9459d1964438934194f6f28b290b8e71bcd

##Adding extensions to your installation

It's good practice to add any custom Symphony extensions as git submodules. To add an extension, find its URL on Github, then issue a command like this in your installation root directory:

	git submodule add git://github.com/user/extension-name.git extensions/extension-name

After adding an extension as a submodule, you will probably need to update it to a newer version from time to time. Perform these commands in your Symphony root folder:

	cd extensions/extension-name
	git pull origin master
	cd ../../
	git commit -m "I updated extension-name."

##Updating Symphony

You've cloned the Symphony repository, set up your workspace, and added some extensions as submodules. Now you want to upgrade to a new Symphony release. `git pull`, right? Wrong:

	Entry '.gitmodules' would be overwritten by merge. Cannot merge.

Doh! We have a conflict: the master Symphony repository and your local version have different sets of submodules. Git needs your help! First, `stash` your changes. Then do your `pull` and apply the changes to the updated repository.

	git stash
	git pull origin master
	git stash apply

Be aware that you may need to resolve some conflicts during the `stash apply` step by using `git add` or `git rm`.

##A repository of your own

Now you're feeling happy and you want to version control your own site customizations. How you do this depends on your deployment environment — do you have git locally? Do you have staging and production servers? Do you want to post your stuff to Github? We'll cover the bases — choose your flavor of control and scale up as you need.

###Lightweight: using a local `workspace` repository only

If you have a simple site with very few extensions, it's easy to keep only your workspace under local version control. In this example, git must be available on your system, and you will simply maintain a private, local git repository for the workspace.

	cd workspace
	git init
	git add .
	git commit -m "initial commit"

###Welterweight: using a complete site repository

Usually most customizations occur in the workspace area, but in practice, there may be other items you want to track, such as settings in the `manifest/config.php` file or your entire site installation. In these cases, you can turn your whole site into a git repository. 

One great way to do this is to create a new branch for your site. After installing Symphony, create a second branch. We'll call it "mysite."

	git branch mysite
	git checkout mysite

henceforth you'll use the "mysite" branch to do all your work. When you need to update Symphony, you'll switch back to the master branch, update, and merge.

	git checkout master
	git pull origin master
	git checkout mysite
	git merge master

Again, you may have to clean up conflicts during the merge step, by using `git add` or `git rm`.

###Middleweight: deploying your site to public or private repositories 

Suppose you want to host a copy of your site's repository on Github, or use a local development repository and occasionally push it to another server. You’ll maintain a link to the official repository for core updates, but change the origin to your own repository.

	git remote add symphony git://github.com/symphonycms/symphony-2.git
	git remote rm origin
	git remote add origin git@github.com:username/site.git
	git push origin master

You could also skip Github and do this on your own server. First set up your repository:
	
	ssh user@host.com
	mkdir /path/to/mysite.git
	cd /path/to/mysite.git
	git --bare init
	
Then update your local cloned Symphony:

	git remote add symphony git://github.com/symphonycms/symphony-2.git
	git remote rm origin
	git remote add origin ssh://user@host.com:/path/to/mysite.git
	git push origin master

When you want to update the core, pull from the Symphony remote:

	git pull symphony master

###Heavyweight: the workspace as a submodule

The benefit of maintaining the workspace as a submodule is the ability to maintain a clean separation between site customizations and the Symphony core. The downside is that you need to maintain two repositories. Let's say that your core repository is called `site` and your workspace repository is called `site-workspace`. Here’s the process of setting up the core repository with the workspace repository as a submodule.

Assuming that you want to start with a clean Symphony installation, you would install Symphony at this point to create and populate the workspace directory. Once that's done, you can set up the remote repository.

	cd workspace
	git init
	git add .
	git commit -m "Initialize workspace repository"
	git remote add origin git@github.com:username/site-workspace.git
	git push origin master

Then, remove the existing workspace and add the workspace as a submodule.

	cd ..
	rm -rf workspace
	git submodule add git@github.com:username/site-workspace.git workspace

###Heavyweight champion: contributing to Symphony

Now that you're a bonafide Git genius, the last step in your path to world domination is to improve Symphony itself. We'll assume you've already forked Symphony on Github, because you're awesome.

Clone your fork and checkout the integration branch:

	git clone git@github.com:username/symphony-2.git 
	git checkout -b integration
	git pull origin integration
	
Now make a new branch for your changes:
	
	git checkout -b mybranch
	git merge integration
	git push origin mybranch
	
When you're ready to submit your changes to the Symphony team, issue a pull request on Github. See [Github's instructions](http://help.github.com/pull-requests/) for help.
