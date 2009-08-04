var Symphony;

(function($) {
	Symphony = {
		WEBSITE: $('script')[0].src.split('/symphony/')[0],
		Language: {
			UNTITLED:         "Untitled",
			CREATE_ITEM:      "Add item",
			REMOVE_ITEMS:     "Remove selected items",
			CONFIRM_SINGLE:   "Are you sure you want to {$action} {$name}?",
			CONFIRM_MANY:     "Are you sure you want to {$action} {$count} items?",
			CONFIRM_ABSTRACT: "Are you sure you want to {$action}?",
			REORDER_ERROR:    "Reordering was unsuccessful.",
			PASSWORD:         "Password",
			CHANGE_PASSWORD:  "Change Password",
			REMOVE_FILE:      "Remove File",
			TIME_SEPARATOR:   "at",
			TIME_NOW:         "just now",
			TIME_MINUTE:      "a minute ago",
			TIME_MINUTES:     "{$minutes} minutes ago",
			TIME_HOUR:        "about 1 hour ago",
			TIME_HOURS:       "about {$hours} hours ago"
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
				if (time < 1) { return Symphony.Language.TIME_NOW; }
				if (time < 2) { return Symphony.Language.TIME_MINUTE; }
				if (time < 45) { return Symphony.Language.TIME_MINUTES.replace('{$minutes}', time); }
				if (time < 90) { return Symphony.Language.TIME_HOUR; }
				else { return Symphony.Language.TIME_MINUTES.replace('{$hours}', time); }
			},
			queue: []
		}
	};

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
			    b = target.offset().top;

			movable.target = target;
			movable.min    = Math.min(b, a + (target.prev().offset().top || -Infinity));
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
			complete: function(x) {
				if (x.status === 200) {
					Symphony.Message.clear('reorder');
				} else {
					Symphony.Message.post(Symphony.Language.REORDER_ERROR, 'reorder error');
				}
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

			a.before('<div class="label">' + Symphony.Language.PASSWORD + ' <span><button id="change-password" type="button">' + Symphony.Language.CHANGE_PASSWORD + '</button></span></div>').remove();

			$('#change-password').click(function() {
				$(this.parentNode.parentNode).replaceWith(b);
				a.insertBefore(b).find('input')[0].focus();
			});
		});

		// Upload fields
		$('<em>' + Symphony.Language.REMOVE_FILE + '</em>').appendTo('label.file:has(a) span').click(function() {
			var s = $(this.parentNode),
			    d = '<input name="' + $(this).siblings('input').attr('name') + '" type="file">';

			setTimeout(function() { s.html(d); }, 50); // Delayed to avoid WebKit clickthrough bug
		});

		// confirm() dialogs
		$('button.confirm').live('click', function() {
			var n = document.title.split(/[\u2013]\s*/g)[2],
			    t = Symphony.Language[n ? 'CONFIRM_SINGLE' : 'CONFIRM_ABSTRACT'];

			return confirm(t.replace('{$action}', this.firstChild.data.toLowerCase()).replace('{$name}', n));
		});

		if ($('[name=with-selected] option.confirm').length > 0) {
			$('form').submit(function() {
				var i = $('table input:checked').length,
				    t = Symphony.Language[i > 1 ? 'CONFIRM_MANY' : 'CONFIRM_SINGLE'],
				    s = document.getElementsByName('with-selected')[0],
				    o = $(s.options[s.selectedIndex]);

				t = t.replace('{$action}', o.text().toLowerCase())
				     .replace('{$name}'  , $('table input:checked').parents('tr').find('td').eq(0).text())
				     .replace('{$count}' , i);

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

		// Repeating sections
		$('div.subsection').each(function() {
			var m = $(this),
			    t = m.find('.template'),
			    h = t.map(function() { return $(this).height(); }).get();

			t.remove().css('height', 0);
			m.append('<div class="actions"><a>' + Symphony.Language.CREATE_ITEM + '</a><a class="inactive">' + Symphony.Language.REMOVE_ITEMS + '</a></div>')
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

		// Data source switcheroo
		$('select.filtered > optgroup').each(function() {
			var s = this.parentNode,
			    l = this.label,
			    z = $(this).siblings('option').length,
			    o = $(this).remove().find('option');

			$('#context').change(function() {
				if ($(this.options[this.selectedIndex]).text() === l) {
					s.options.length = z;
					o.clone(true).appendTo(s);
				}
			});
		});

		$('*.contextual').each(function() {
			var a = $(this);

			$('#context').change(function() {
				var o = $(this.options[this.selectedIndex]).parent('optgroup'),
				    c = this.value.replace(/\W+/g, '_'),
				    g = o.attr('label').replace(/\W+/g, '_');

				a[(a.hasClass(c) || a.hasClass(g)) ^ a.hasClass('inverse') ? 'removeClass' : 'addClass']('irrelevant');
			});
		});

		$('#context').change();
		
		// system messages
		window.setTimeout("Symphony.Message.fade()", 10000);
		$('abbr.timeago').each(function() {
			var html = $(this).parent().html();
			$(this).parent().html(html.replace(Symphony.Language.TIME_SEPARATOR + ' ', ''));
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