var Symphony;

(function($) {
	Symphony = {
		WEBSITE: $('script[src]')[0].src.match('(.*)/symphony')[1],
		Cookie: {
			set: function(name, value, seconds) {
				var expires = "";
				
				if (seconds) {
					var date = new Date();
					date.setTime(date.getTime() + seconds);
					expires = "; expires=" + date.toGMTString();
				}
				
				document.cookie = name + "=" + value + expires + "; path=/";
			},
			get: function(name) {
				var nameEQ = name + "=";
				var ca = document.cookie.split(';');
				
				for (var i=0;i < ca.length;i++) {
					var c = ca[i];
					while (c.charAt(0)==' ') c = c.substring(1,c.length);
					if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
				}
				
				return null;
			}
		},
		Message: {
			post: function(message, type) {
				this.queue = this.queue.concat($('#notice').remove().get()); // Store previous message

				$('h1').before('<div id="notice" class="' + type + '">' + message + '</div>');
			},
			clear: function(type) {
				$('#notice.' + type).remove();

				this.queue = $(this.queue).filter(':not(.' + type + ')').get();

				if (document.getElementById('notice') === null && this.queue.length > 0) {
					$(this.queue.pop()).insertBefore('h1'); // Show previous message
				}
			},
			fade: function() {
				$('#notice.success').animate({
					backgroundColor: '#e4e4e0',
					borderTopColor: '#979792',
					borderBottomColor: '#777',
					color: '#fff'
				}, 'slow', 'linear', function() {
					$(this).removeClass('success');
				});
			},
			timer: function() {
				var time = Date.parse($('abbr.timeago').attr('title'));
				var from = new Date;
				from.setTime(time);
				$('abbr.timeago').text(this.distance(from, new Date));
				window.setTimeout("Symphony.Message.timer()", 60000);
			},
  			distance: function(from, to) {
  				var distance = to - from;
				var time = Math.floor(distance / 60000);
				if (time < 1) { 
					return Symphony.Language.get('just now'); 
				}
				if (time < 2) { 
					return Symphony.Language.get('a minute ago'); 
				}
				if (time < 45) { 
					return Symphony.Language.get('{$minutes} minutes ago', {
						'minutes': time
					}); 
				}
				if (time < 90) { 
					return Symphony.Language.get('about 1 hour ago'); 
				}
				else { 
					return Symphony.Language.get('about {$hours} hours ago', {
						'hours': Math.floor(time / 60)
					}); 
				}
			},
			queue: []
		}
	};
	
/*-----------------------------------------------------------------------------
	Symphony languages
-----------------------------------------------------------------------------*/
	
	Symphony.Language = {
		NAME: $('html').attr('lang'),
		DICTIONARY: {},
		
		// TODO: Load regular expressions from lang.php.
		createHandle: function(value) {
			var exp = /[^\w]+/;
			
			value = value.split(exp).join('-');
			value = value.replace(/^-/, '');
			value = value.replace(/-$/, '');
			
			return value.toLowerCase();
		},
		get: function(string, tokens) {
			// Get translated string
			translatedString = Symphony.Language.DICTIONARY[string];

			// Return string if it cannot be found in the dictionary
			if(translatedString !== undefined) string = translatedString;
				
			// Insert tokens
			if(tokens !== undefined) string = Symphony.Language.insert(string, tokens);
			
			// Return translated string
			return string;
		},
		insert: function(string, tokens) {
			// Replace tokens
			$.each(tokens, function(index, value) { 
				string = string.replace('{$' + index + '}', value);
			});
			return string;
		},
		add: function(strings) {
			// Set key as value
			$.each(strings, function(key, value) {
				strings[key] = key;
			});
			// Save English strings
			if(Symphony.Language.NAME == 'en') {
				Symphony.Language.DICTIONARY = $.extend(Symphony.Language.DICTIONARY, strings);
			}
			// Translate strings
			else {
				Symphony.Language.translate(strings);
			}
		},
		translate: function(strings) {
			// Load translations synchronous
			$.ajax({
				async: false,
				type: 'GET',
				url: Symphony.WEBSITE + '/symphony/ajax/translate',
				data: strings,
				dataType: 'json',
				success: function(result) {
					Symphony.Language.DICTIONARY = $.extend(Symphony.Language.DICTIONARY, result);
				}
			});
		}
	};
	
	// Add language strings
	Symphony.Language.add({
		'Add item': false,
		'Remove selected items': false,
		'Are you sure you want to {$action} {$name}?': false,
		'Are you sure you want to {$action} {$count} items?': false,
		'Are you sure you want to {$action}?': false,
		'Reordering was unsuccessful.': false,
		'Password': false,
		'Change Password': false,
		'Remove File': false,
		'at': false,
		'just now': false,
		'a minute ago': false,
		'{$minutes} minutes ago': false,
		'about 1 hour ago': false,
		'about {$hours} hours ago': false
	});
	
/*-----------------------------------------------------------------------------
	Events Page
-----------------------------------------------------------------------------*/
	
	$(document).ready(function() {
		var selector = jQuery('#event-context-selector');
		
		selector.bind('change', function() {
			var options = selector.find('option');
			
			options.each(function() {
				var option = $(this);
				var context = jQuery('#event-context-' + option.val());
				
				if (context.length == 0) return;
				
				if (option.val() == selector.val()) {
					context.show();
				}
				
				else {
					context.hide();
				}
			});
		});
		
		selector.trigger('change');
	});
	
/*-----------------------------------------------------------------------------
	Views List
-----------------------------------------------------------------------------*/
	
	$(document).ready(function() {
		var table = $('#views-list');
		var rows = table.find('tbody tr');
		var parents = [];
		
		// Insert toggle controls:
		rows.each(function() {
			var row = $(this);
			var cell = row.find('td:first').addClass('toggle');
			
			if (row.is('[id]')) {
				var depth = 0;
				
				$(parents).each(function(index, value) {
					if (row.is('.' + value)) depth++;
				});
				
				if (depth) {
					$('<span />')
						.html('&#x21b5;')
						.css('margin-left', ((depth - 1) * 20) + 'px')
						.prependTo(cell);
				}
				
				if (table.find('tr.' + row.attr('id')).length) {
					parents.push(row.attr('id'));
					
					if (!depth) {
						$('<a />')
							.text('▼')
							.addClass('hide')
							.prependTo(cell);
					}
				}
			}
/*			
			else {
				$('<span />')
					.html('&#x21b5;')
					.prependTo(cell);
			}
*/
			
			cell.wrapInner('<div />');
		});
		
		$('#views-list td.toggle a, #views-list td.toggle + td span').live('mousedown', function() {
			return false;
		});
		
		$('#views-list td.toggle a').live('click', function() {
			var link = $(this);
			var row = link.parents('tr');
			var children = table.find('tr.' + row.attr('id'));
			
			if (link.is('.hide')) {
				link.text('▼').removeClass('hide').addClass('show');
				children.hide().removeClass('selected');
			}
			
			else if (link.is('.show')) {
				link.text('▼').removeClass('show').addClass('hide');
				children.show();
			}
		});
		
		$('#views-list td.toggle + td span').live('click', function() {
			$(this).parent().click();
			
			return false;
		});
		
		// Collapse by default on long pages:
		if (table.find('tbody tr').length > 17) {
			$('#views-list tr[id] td.toggle a').click();
		}
	});
})(jQuery.noConflict());