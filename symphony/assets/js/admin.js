var Symphony;

(function($) {
	Symphony = {
		WEBSITE: $('script[src]')[0].src.match('(.*)/symphony')[1],
		WEBSITE: $('script')[0].src.match('(.*)/symphony')[1],
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
		Language: {
			NAME: $('html').attr('lang'),
			DICTIONARY: {},
			get: function(string, tokens) {
				// Get translated string
				translatedString = Symphony.Language.DICTIONARY[string];

				// Return string if it cannot be found in the dictionary
				if(translatedString !== false) string = translatedString;

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
					color: '#555'
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

	// Set JavaScript status
	$(document.documentElement).addClass('active');

	// Sortable lists
	var movable = {
		move: function(e) {
			var t,
			    n,
			    y = e.pageY;

			if (y < movable.min) {
				t = movable.target.prev();
				for (;;) {
					movable.delta--;
					n = t.prev();
					if (n.length === 0 || y >= (movable.min -= n.height())) {
						movable.target.insertBefore(t);
						break;
					}
					t = n;
				}
			} else if (y > movable.max) {
				t = movable.target.next();
				for (;;) {
					movable.delta++;
					n = t.next();
					if (n.length === 0 || y <= (movable.max += n.height())) {
						movable.target.insertAfter(t);
						break;
					}
					t = n;
				}
			} else {
				return;
			}

			movable.update(movable.target);
			movable.target.parent().children().each(function(i) { $(this).toggleClass('odd', i % 2 === 0); });
		},
		drop: function() {
			$(document).unbind('mousemove', movable.move);
			$(document).unbind('mouseup', movable.drop);

			movable.target.removeClass('movable');

			if (movable.delta) {
				movable.target.trigger($.Event('reorder'));
			}
		},
		update: function(target) {
			var a = target.height(),
			    b = target.offset().top,
				prev_offset = (target.prev().length) ? target.prev().offset().top : 0;

			movable.target = target;
			movable.min    = Math.min(b, a + (prev_offset || -Infinity));
			movable.max    = Math.max(a + b, b + (target.next().height() ||  Infinity));
		}
	};

	$('.orderable tr, .subsection > ol > li').live('mousedown', function(e) {
		if (!/^(?:h4|td)$/i.test(e.target.nodeName)) {
			return true;
		}

		movable.update($(this).addClass('movable'));
		movable.delta = 0;

		$(document).mousemove(movable.move);
		$(document).mouseup(movable.drop);

		return false;
	});

	$('table.orderable').live('reorder', function() {
		var t = $(this).addClass('busy');

		$.ajax({
			type: 'POST',
			url: Symphony.WEBSITE + '/symphony/ajax/reorder' + location.href.slice(Symphony.WEBSITE.length + 9),
			data: $('input', this).map(function(i) { return this.name + '=' + i; }).get().join('&'),
			success: function() {
				Symphony.Message.clear('reorder');
			},
			error: function() {
				Symphony.Message.post(Symphony.Language.get('Reordering was unsuccessful.'), 'reorder error');
			},
			complete: function() {
				t.removeClass('busy');
			}
		});
	});

	$('.selectable td, .subsection h4').live('click', function(e) {
		if (movable.delta || !/^(?:td|h4)$/i.test(e.target.nodeName)) {
			return true;
		}

		var r = $(this.parentNode).toggleClass('selected');

		r.trigger($.Event(r.hasClass('selected') ? 'select' : 'deselect'));
		r.find('td input').each(function() { this.checked = !this.checked; });

		// when shift held when selecting a row
		if (e.shiftKey && r.hasClass('selected')) {

			// find first selected row above newly-selected row
			var selected_above = r.prevAll('.selected');
			if (selected_above.length) {
				var from = $('.selectable tr').index(selected_above);
				var to = $('.selectable tr').index(r);
				$('.selectable tr').each(function(i) {
					if (i > from && i < to) {
						var r = $(this).toggleClass('selected');
						r.trigger($.Event(r.hasClass('selected') ? 'select' : 'deselect'));
						r.find('td input').each(function() { this.checked = !this.checked; });
					}
				});
			}
			// de-select text caused by holding shift
			if (window.getSelection) window.getSelection().removeAllRanges();
		}

		return false;
	});

	// Suggestion lists
	$('.tags > li').live('click', function() {
		var list = $(this.parentNode);
		var input = list.prevAll('label').find('input')[0];
		var tag = this.className || $(this).text();

		input.focus();

		if (list.hasClass('singular')) {
			input.value = tag;
		} else if(list.hasClass('inline')) {
			var start = input.selectionStart;
			var end = input.selectionEnd;

			if(start >= 0) {
			  input.value = input.value.substring(0, start) + tag + input.value.substring(end, input.value.length);
			} else {
			  input.value += tag;
			}

			input.selectionStart = start + tag.length;
			input.selectionEnd = start + tag.length;
		} else {
			var exp = new RegExp('^' + tag + '$', 'i');
			var tags = input.value.split(/,\s*/);
			var removed = false;

			for (var index in tags) {
				if (tags[index].match(exp)) {
					tags.splice(index, 1);
					removed = true;

				} else if (tags[index] == '') {
					tags.splice(index, 1);
				}
			}

			if (!removed) tags.push(tag);

			input.value = tags.join(', ');
		}
	});

	$(function() {
		// Ugly DOM maintenance
		$('table:has(input)').addClass('selectable');

		if (/[?&]debug[&=][^#]*#line-\d+$/.test(location.href)) {
			$('ol a').eq(parseInt(/\d+$/.exec(location.href)[0], 10) - 1).addClass('active');
		}

		$('ul.tags > li').mousedown(silence);
		$('#nav').mouseover(silence);
		$('.orderable td, .subsection h4').bind('selectstart', silence); // Fix for IE bug

		function silence() { return false; }

		// Change user password
		$('#change-password').each(function() {

			// Do not hide fields if there is some error there.
			if ($('div.invalid', $(this)).length > 0) return;

			var a = $(this),
			    b = a.next('p.help').remove();

			if (a.find('label').length !== 3 && a.find('label').length !== 2) {
				return;
			}

			a.before('<div class="label">' + Symphony.Language.get('Password') + ' <span><input id="change-password" type="submit" value="'+ Symphony.Language.get('Change Password') + '"></button></span></div>').remove();

			$('#change-password').click(function() {
				$(this.parentNode.parentNode).replaceWith(b);
				a.insertBefore(b).find('input')[0].focus();
			});
		});

		// Upload fields
		$('<em>' + Symphony.Language.get('Remove File') + '</em>').appendTo('label.file:has(a) span').click(function() {
			var s = $(this.parentNode),
			    d = '<input name="' + $(this).siblings('input').attr('name') + '" type="file">';

			setTimeout(function() { s.html(d); }, 50); // Delayed to avoid WebKit clickthrough bug
		});

		// confirm() dialogs
		$('button.confirm').live('click', function() {
			var n = document.title.split(/[\u2013]\s*/g)[2],
			    t = (n ? 'Are you sure you want to {$action} {$name}?' : 'Are you sure you want to {$action}?');

			return confirm(Symphony.Language.get(t, {
				'action': this.firstChild.data.toLowerCase().trim(),
				'name': n
			}));
		});

		if ($('[name=with-selected] option.confirm').length > 0) {
			$('form').submit(function() {
				var i = $('table input:checked').length,
					t = (i > 1 ? 'Are you sure you want to {$action} {$count} items?' : 'Are you sure you want to {$action} from {$name}?'),
					s = document.getElementsByName('with-selected')[0],
				    o = $(s.options[s.selectedIndex]);

				t = Symphony.Language.get(t, {
					'action': o.text().toLowerCase(),
					'name': $.trim($('table input:checked').parents('tr').find('td').eq(0).text()),
					'count': i
				});

				return i > 0 && !o.hasClass('confirm') || confirm(t);
			});
		}

		// XSLT utilities
		$('#utilities a').each(function() {
			var a = $(this.parentNode),
			    r = new RegExp('href=["\']?\\.{2}/utilities/' + $(this).text());

			$('textarea').blur(function() {
				a[r.test(this.value) ? 'addClass' : 'removeClass']('selected');
			});
		});

		$('textarea').blur();

		// Fields duplicator:
		$('.section-duplicator[id]').symphonyCollapsedDuplicator({
			orderable:		true
		});
		$('.section-duplicator:not([id])').symphonyDuplicator({
			orderable:		true
		});

		//console.log(fields[0].collapsible.collapseAll());

		// Filters duplicator:
		$('.filters-duplicator, .events-duplicator').symphonyDuplicator();

		$('.events-duplicator').each(function() {

		});

		// Repeating sections
		$('div.subsection').each(function() {
			var m = $(this),
			    t = m.find('.template'),
			    h = t.map(function() { return $(this).height(); }).get();

			t.remove().css('height', 0);
			m.append('<div class="actions"><a>' + Symphony.Language.get('Add item') + '</a><a class="inactive">' + Symphony.Language.get('Remove selected items') + '</a></div>')
			m.bind('select', select).bind('deselect', select);

			var r = m.find('.actions > a.inactive'),
			    i = 0;

			function select(e) {
				r.toggleClass('inactive', !(i += e.type === 'select' ? 1 : -1));
			}

			if (t.length > 1) {
				var s = document.createElement('select'),
				    l = t.find('h4');

				for (var i = 0; i < l.length; i++) {
					s.options[i] = new Option(l[i].firstChild.data, i);
				}

				$('.actions', this).prepend(s);
			}

			m.find('.actions > a').click(function() {
				var a = $(this);

				if (a.hasClass('inactive')) {
					return;
				}

				if (a.is(':last-child')) {
					m.find('li.selected').animate({height: 0}, function() {
						$(this).remove();
					});

					i = 0;
					a.addClass('inactive');
				} else {
					var j = s ? s.selectedIndex : 0,
					    w = m.find('ol');

					t.eq(j).clone(true).appendTo(w).animate({height: h[j]}, function() {
						$('input:not([type=hidden]), select, textarea', this).eq(0).focus();
					});
				}
			});

			$('form').submit(function() {
				m.find('ol > li').each(function(i) {
					$('input,select,textarea', this).each(function() {
						this.name = this.name.replace(/\[-?\d+(?=])/, '[' + i);
					});
				});
			});
		});

		// system messages
		window.setTimeout("Symphony.Message.fade()", 10000);
		$('abbr.timeago').each(function() {
			var html = $(this).parent().html();
			$(this).parent().html(html.replace(Symphony.Language.get('at') + ' ', ''));
		});

		Symphony.Message.timer();
	});
})(jQuery.noConflict());


/*
 * jQuery Color Animations
 * Copyright 2007 John Resig
 * Released under the MIT and GPL licenses.
 */

(function(jQuery){

	// We override the animation for all of these color styles
	jQuery.each(['backgroundColor', 'borderBottomColor', 'borderLeftColor', 'borderRightColor', 'borderTopColor', 'color', 'outlineColor'], function(i,attr){
		jQuery.fx.step[attr] = function(fx){
			if ( fx.state == 0 ) {
				fx.start = getColor( fx.elem, attr );
				fx.end = getRGB( fx.end );
			}

			fx.elem.style[attr] = "rgb(" + [
				Math.max(Math.min( parseInt((fx.pos * (fx.end[0] - fx.start[0])) + fx.start[0]), 255), 0),
				Math.max(Math.min( parseInt((fx.pos * (fx.end[1] - fx.start[1])) + fx.start[1]), 255), 0),
				Math.max(Math.min( parseInt((fx.pos * (fx.end[2] - fx.start[2])) + fx.start[2]), 255), 0)
			].join(",") + ")";
		}
	});

	// Color Conversion functions from highlightFade
	// By Blair Mitchelmore
	// http://jquery.offput.ca/highlightFade/

	// Parse strings looking for color tuples [255,255,255]
	function getRGB(color) {
		var result;

		// Check if we're already dealing with an array of colors
		if ( color && color.constructor == Array && color.length == 3 )
			return color;

		// Look for rgb(num,num,num)
		if (result = /rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(color))
			return [parseInt(result[1]), parseInt(result[2]), parseInt(result[3])];

		// Look for rgb(num%,num%,num%)
		if (result = /rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(color))
			return [parseFloat(result[1])*2.55, parseFloat(result[2])*2.55, parseFloat(result[3])*2.55];

		// Look for #a0b1c2
		if (result = /#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(color))
			return [parseInt(result[1],16), parseInt(result[2],16), parseInt(result[3],16)];

		// Look for #fff
		if (result = /#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(color))
			return [parseInt(result[1]+result[1],16), parseInt(result[2]+result[2],16), parseInt(result[3]+result[3],16)];

		// Otherwise, we're most likely dealing with a named color
		return colors[jQuery.trim(color).toLowerCase()];
	}

	function getColor(elem, attr) {
		var color;

		do {
			color = jQuery.curCSS(elem, attr);

			// Keep going until we find an element that has color, or we hit the body
			if ( color != '' && color != 'transparent' || jQuery.nodeName(elem, "body") )
				break;

			attr = "backgroundColor";
		} while ( elem = elem.parentNode );

		return getRGB(color);
	};

})(jQuery);

jQuery(document).ready(function() {
	var $ = jQuery;

	$('#master-switch').bind('change', function() {
		var select = $(this);

		window.location.search = '?type=' + select.val();
	});
});
