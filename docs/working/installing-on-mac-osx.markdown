##META
* Doc Version: 1
* Author: John Porter
* Applies to: 2.x
* Based on: 2.x
* [Production URL]()

# Installing Symphony on your Mac (OS X >= 10.5)

This article will take you through the simple steps to setting up Symphony on your shiny Mac. We'll be looking at three setups; MAMP, MAMP Pro, and Apache/MySQL that comes on your Mac. Also, if you're using MAMP, you're in for an easy ride setting up Symphony, and a slightly more advanced easy ride on MAMP Pro.

There are differing opinions on whether MAMP/MAMP Pro is a better route to take over the shipped Apache/MySQL setup on Mac, but it is my setup of chioce as it saves headaches. That is my opinion though, it's up to you which route you choose to go down, so we've got instructions for both

## MAMP & MAMP Pro

So, to begin with it's advisable to switch off the apache setup that shipped with your Mac. You can do this under the System Preferences > Sharing panel, just de-select Web Sharing if it is selected.

With MAMP and MAMP Pro, you have two options available in the way of ports. You can use the MAMP ports (8888 and 8889), or you can set your server to use default ports (80 and 3306). These tutorials will assume the default port option.

### MAMP

Setting up on MAMP couldn't be simpler. All you have to do is follow these few steps.

0.	Create a utf-8 MySQL database using phpMyAdmin that has been installed with MAMP, taking note of the database name, username and password too.

0.	Unzip the Symphony download and move the contents of the unzipped folder to `/Applications/MAMP/httpdocs`

0.	In your browser, go to `http://localhost/`

This will begin the installer, so just follow all the steps, and Symphony will do all the hard work for you!

Once you've done this, you will be able to access your site at http://localhost/ and the admin at http://localhost/symphony/

### MAMP Pro

Setting up on MAMP Pro is also pretty straight forward. With MAMP Pro, you have many more options available to you, which we're not going to go into here.

1. Just like with MAMP, Create a utf-8 MySQL database using phpMyAdmin that has been installed with MAMP, taking note of the database name, username and password too.

2. With MAMP Pro, you are at your leisure to set your document root (where your website files sit) to anywhere you want, so unzip the Symphony download and move the contents of the unzipped folder to the location you choose.

3.	In MAMP Pro's dashboard, go to the 'hosts' tab on the top left of the panel.

4. Click the little plus button at the bottom toadd a new host, and you will be greeted with a blank form on the right.

5. Fill in the server name, and disk location, use the folder to look this up using a dialog window.

6. Don't worry for now about any of the other advanced options available to you, they're out of scope for this article.

7. Click the APply button at the bottom to restart the Apache and MySQL instances, and add your new host to the server.

8. In your browser, go to the url you entered in the Server Name box in step 5.

For instance, if you added the server name `symphony-install` then your site will be available at `http://symphony-install/` and after installation, the admin will be available at `http://symphony-install/symphony/`.

Pretty simple eh?

## Apache & MySQL default