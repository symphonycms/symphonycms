/**
 * SYMPHONY FACTORY
 * A design framework for the Symphony Network
 * 
 * @link http://symphonycms.github.com/factory/
 */
 
// Publicly declare Factory object
var Factory;

(function($) {

/*-----------------------------------------------------------------------------
	Symphony Factory:

	This object bundles all functions for Symphony Factory.
-----------------------------------------------------------------------------*/

	Factory = {
	
		elements: {},
		
		init: function() {
		
			// Cache elements
			Factory.elements.body = $('body');
			Factory.elements.network = $('#network');
			Factory.elements.networkToolbar = Factory.elements.network.find('.network-toolbar');
			Factory.elements.networkLogo = Factory.elements.networkToolbar.find('.network-logo');
			Factory.elements.networkNav = Factory.elements.network.find('.network-nav');			
			Factory.elements.networkUser = Factory.elements.network.find('.network-user');			
			Factory.elements.networkUsername = Factory.elements.network.find('.network-username');			
			Factory.elements.networkUserImg = Factory.elements.networkUser.find('img');			
			Factory.elements.networkProfile = Factory.elements.network.find('.network-profile');
			Factory.elements.networkDrawer = Factory.elements.network.find('.network-drawer');
			Factory.elements.site = $('#site');
			Factory.elements.siteHeader = Factory.elements.site.find('> header');
			Factory.elements.siteTitle = Factory.elements.siteHeader.find('h1');
			Factory.elements.siteNav = Factory.elements.siteHeader.find('nav');
			Factory.elements.siteNavItem = Factory.elements.siteNav.find('a');
			Factory.elements.footer = $('#footer');

			Factory.elements.contentExtension = Factory.elements.site.find('.content-extension');
						
			// Init profile
			Factory.initNetworkProfile();
		},

	/*-------------------------------------------------------------------------
		Network toggling
	-------------------------------------------------------------------------*/
	
		toggleNetwork: function(event) {
			Factory.elements.network.toggleClass('action-open');
		},

	/*-------------------------------------------------------------------------
		Network user
	-------------------------------------------------------------------------*/
	
		userWidth: 0,
	
		adjustNetworkUser: function(event) {
					
			// Store user profile width
			if(Factory.userWidth == 0) {
				Factory.userWidth = Factory.elements.networkUser.find('p').outerWidth();
			}
		
			var space = Factory.elements.network.innerWidth() - Factory.elements.networkLogo.outerWidth() - Factory.userWidth;
						
			// Substract navigation width, if it's not wrapping
			if(Factory.elements.networkToolbar.height() <= Factory.elements.networkUserImg.height()) {
				space = space - Factory.elements.networkNav.outerWidth();
			}
			
			// Collapse user details
			if(space < Factory.userWidth + 10) {
				Factory.elements.networkUser.addClass('action-collapse');
			}
			
			// Expand user details
			else {
				Factory.elements.networkUser.removeClass('action-collapse');
			}
		},

	/*-------------------------------------------------------------------------
		Network profile
	-------------------------------------------------------------------------*/
	
		initNetworkProfile: function() {
			Factory.elements.networkProfile.addClass('action-profile-hide');
			Factory.elements.networkUsername.on('click.factory', Factory.toggleNetworkProfile);
			Factory.elements.networkUserImg.on('click.factory', Factory.toggleNetworkProfile);
		},
		
		toggleNetworkProfile: function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			// Remove CSS transition delay when closing the profile 
			if(Factory.elements.network.is('.action-profile-open')) {
				Factory.elements.network.addClass('action-profile-hide');
				setTimeout(function() {
					Factory.elements.network.removeClass('action-profile-hide');
				}, 500);
			}
			
			// Toggle profile
			Factory.elements.network.toggleClass('action-profile-open');		
			Factory.elements.networkProfile.addClass('action-profile-animate').toggleClass('action-profile-hide');
		},

	/*-------------------------------------------------------------------------
		Site
	-------------------------------------------------------------------------*/
	
		adjustSiteHeight: function(event) {
			var windowHeight = $(window).height(),
				siteHeight = Factory.elements.site.outerHeight() + parseInt(Factory.elements.site.find(':last-child()').css('margin-bottom')),
				windowHeight = windowHeight - Factory.elements.networkToolbar.outerHeight() - siteHeight,
				contentHeight = Factory.elements.network.innerHeight() + siteHeight;
			
			// Make footer sticky
			if(windowHeight > Factory.elements.footer.outerHeight() && windowHeight > contentHeight) {
				Factory.elements.footer.addClass('sticky');
			}
			
			// Let footer flow
			else {
				Factory.elements.footer.removeClass('sticky');
			}
		},

	/*-------------------------------------------------------------------------
		Site navigation
	-------------------------------------------------------------------------*/
	
		navWidth: 0,
		
		adjustNavigation: function(event) {
			
			// Store navigation width
			if(Factory.navWidth == 0) {
				Factory.elements.siteNavItem.each(function() {
					Factory.navWidth += $(this).outerWidth();
				});
			}

			// Get available space		
			var space = Factory.elements.siteHeader.width() - Factory.elements.siteTitle.width() - parseInt(Factory.elements.siteTitle.css('margin-right')) - Factory.navWidth;
			
			// Collapse navigation
			if(space < 10) {
				Factory.elements.siteHeader.addClass('action-collapse');
			}
			
			// Expand navigation
			else {
				Factory.elements.siteHeader.removeClass('action-collapse');
				Factory.elements.siteNav.removeClass('action-open');
			}
		},
		
		toggleNavigation: function(event) {
			Factory.elements.siteNav.toggleClass('action-open');
		},
		
	/*-------------------------------------------------------------------------
		Users
	-------------------------------------------------------------------------*/

		initExtension: function() {
			Factory.elements.contentExtensionGit = Factory.elements.contentExtension.find('.content-extension-git');
		
			// Set scroll position
			$(window).on('resize.factory', Factory.scrollExtensionCompatibilities);
			Factory.scrollExtensionCompatibilities();
			
			// Store git commands
			var git = Factory.elements.contentExtensionGit.text(),
				submodule = 'git submodule add ' + git + ' extensions/' + git.match(/\/([a-zA-Z0-9_]+).git/)[1] + ' --recursive';
				
			Factory.elements.contentExtensionGit.data('git', git).data('submodule', submodule);
			
			// Add command toggle
			$('<a class="content-extension-submodule">&#128193;</a>')
				.insertBefore(Factory.elements.contentExtensionGit)
				.on('click.factory', Factory.toggleExtensionSubmodule);
				
			// Select Git URL
			Factory.elements.contentExtensionGit.on('focus.factory', Factory.selectExtensionGitURL);
		},
		
		scrollExtensionCompatibilities: function() {
			Factory.elements.contentExtension.find('.content-extension-compatibility')[0].scrollLeft = 1000;
		},
		
		toggleExtensionSubmodule: function() {
			$(this).toggleClass('content-extension-submodule-active');
		
			// Restore plain clone URL
			if(Factory.elements.contentExtensionGit.text().substring(0, 13) == 'git submodule') {
				Factory.elements.contentExtensionGit.text(Factory.elements.contentExtensionGit.data('git'));
			}
			
			// Set submodule command
			else {
				Factory.elements.contentExtensionGit.text(Factory.elements.contentExtensionGit.data('submodule'));
			}
			
			Factory.elements.contentExtensionGit.trigger('focus.factory');
		},
		
		selectExtensionGitURL: function() {
			var selection = window.getSelection();
			selection.selectAllChildren(Factory.elements.contentExtensionGit[0]);
		},

	/*-------------------------------------------------------------------------
		Users
	-------------------------------------------------------------------------*/
		
		linkUsers: function() {
			var context = $(this);
			
			context.html(context.html().replace(/@([A-Za-z0-9-_]+)/g, function(username, name) {
				return '<a href="http://getsymphony.com/get-involved/member/' + name + '" class="user">' + username + '</a>';
			}));
		},

	/*-------------------------------------------------------------------------
		Users
	-------------------------------------------------------------------------*/
		
		initCollapsible: function() {
			var item = $(this),
				expand = item.attr('data-expand') || 'Expand',
				collapse = item.attr('data-collapse') || 'Collapse';
			
			// Create toggle links
			var toggle = $('<a />', {
				class: 'collapsible-toggle',
				text: collapse
			}).insertAfter(item).on('click.factory', function() {
				item.slideToggle();
				if(toggle.text() == collapse) {
					toggle.text(expand);
				}
				else {
					toggle.text(collapse);
				}
			});
			
			// Collapsed
			if(item.is('.collapsed')) {
				toggle.text(expand);
				item.hide();
			}
		},
		
	/*-------------------------------------------------------------------------
		Media
	-------------------------------------------------------------------------*/
	
		loadRetinaImages: function() {
			if(window.devicePixelRatio >= 1.5) {
				$('img[src*="gravatar.com"]').each(Factory.loadRetinaGravatar);
			}
		},
		
		loadRetinaGravatar: function() {
			var gravatar = $(this),
				src = gravatar.attr('src');
			
			// Double image size
			gravatar.attr('src', src.replace(/s=([0-9]*)/, function(match, size) {
				return 's=' + (size * 2);
			}));			
		},
		
	/*-------------------------------------------------------------------------
		Date and Time
	-------------------------------------------------------------------------*/
		
		getRelativeTime: function() {
			var time = $(this),
				datetime = time.attr('datetime') || time.text();
			
			// Get relative time distance
			time.text(Factory.parseTime(datetime));
		},

		parseTime: function(datetime) {
			var from = Date.parse(datetime);
			
			// Check date validity
			if(!isNaN(from)) {
				var	to = new Date().getTime(),
					distance = to - from;
	
				// Convert time to minutes
				time = Math.floor(distance / 60000);
	
				// Return relative time
				if(time < 1) {
					return 'just now';
				}
				if(time < 45) {
					return time + 'minutes ago';
				}
				if(time < 90) {
					return '1 hour ago';
				}
				if(time < 1440) {
					return Math.floor(time / 60) + ' hours ago';
				}
				if(time < 2880) {
					return '1 day ago';
				}
				if(time < 43200) {
					return Math.floor(time / 1440) + ' days ago';
				}
				if(time < 86400) {
					return 'a month ago';
				}
				if(time < 525960) {
					return Math.floor(time / 43200) + ' months ago';
				}
				if(time < 1051199) {
					return 'a year ago';
				}
				else {
					return 'over ' + Math.floor(time / 525960) + ' years ago';
				}
			}
		},
		
	/*-------------------------------------------------------------------------
		Storage
	-------------------------------------------------------------------------*/
	
		storeSetting: function(name, value) {
			if(Modernizr.localstorage) {
				localStorage.setItem('symphony.' + name, value);
			}
		},
		
		loadSetting: function(name) {
			var value;
			
			if(Modernizr.localstorage) {
				value = localStorage.getItem('symphony.' + name);
				
				// Browsers store boolean values as string, convert to boolean
				if(value == 'true') {
					value = true;
				}
				if(value == 'false') {
					value = false;
				}
			}
			
			return value;
		}
		
	};

/*-----------------------------------------------------------------------------
	Initialisation
-----------------------------------------------------------------------------*/

	$(document).on('ready.factory', function ready() {
		Factory.init();

		// Resizing
		$(window)
			.on('resize.factory', Factory.adjustNetworkUser)
			.on('resize.factory', Factory.adjustSiteHeight)
			.trigger('resize.factory');
		
		// Network
		Factory.elements.network.on('touchstart.factory', Factory.toggleNetwork);
	
		// Site navigation
		$(window).on('resize.factory', Factory.adjustNavigation).trigger('resize.factory');
		Factory.elements.siteNav.on('click.factory', Factory.toggleNavigation);
			
		// Set relative time
		$('time.relative').each(Factory.getRelativeTime);
		
		// Users
		$('.content p').each(Factory.linkUsers);
		
		// Collapsible items
		$('.collapsible').each(Factory.initCollapsible);
		
		// Compatibility table
		if(Factory.elements.contentExtension.length > 0) {
			Factory.initExtension();
		}
		
		// Syntax highlighting
		$('pre code').each(function() {
			var code = $(this).addClass('cm-s-symphony'),
				language = code.attr('data-language') || 'htmlmixed';
				
			CodeMirror.runMode(this.textContent, language, this);
		});
		
		// Retina images
		Factory.loadRetinaImages();
	});
	
})(jQuery.noConflict());
