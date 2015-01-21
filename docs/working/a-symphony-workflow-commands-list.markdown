##META
* Doc Version: 1
* Author: John Porter
* Applies to: 2.x
* Based on: 2.x
* [Production URL]()

#A Symphony Workflow - Terminal Command List


	mkdir mywebsite
	cd mywebsite
	git init

	git remote add --no-tags -t master symphonycms git:github.com/symphonycms/symphony-2.git
	git remote add mywebsite git@github.com:myusername/mywebsite.git

	git fetch symphonycms master

	git merge FETCH_HEAD
	git branch symphony FETCH_HEAD
	git branch develop FETCH_HEAD

	git push --all mywebsite

	git branch --set-upstream master mywebsite/master
	git branch --set-upstream develop mywebsite/develop
	git branch --set-upstream symphony symphonycms/master

	git checkout develop

	git-auto-submod.sh path/to/extensions.csv

	mkdir manifest manifest/cache manifest/logs manifest/tmp
	touch manifest/cache/.gitignore manifest/logs/.gitignore manifest/tmp/.gitignore

	echo "*" >> manifest/cache/.gitignore
	echo "*" >> manifest/logs/.gitignore
	echo "*" >> manifest/tmp/.gitignore
	echo "!.gitignore" >> manifest/cache/.gitignore
	echo "!.gitignore" >> manifest/logs/.gitignore
	echo "!.gitignore" >> manifest/tmp/.gitignore

	cp -R manifest manifest.dev
	cp -R manifest manifest.stage
	mv manifest manifest.prod

	ln -s manifest.dev manifest

	echo "manifest" >> .gitignore

	git add manifest.*
	git commit -m 'Added manifest folders'
	git push mywebsite
