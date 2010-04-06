/*---------------------------------------------------------------------------*/
	
/**
 * Get the current coordinates of the first element in the set of matched
 * elements, relative to the closest positioned ancestor element that
 * matches the selector.
 * @param {Object} selector
 */
jQuery.fn.positionAncestor = function(selector) {
    var left = 0;
    var top = 0;
    this.each(function(index, element) {
        // check if current element has an ancestor matching a selector
        // and that ancestor is positioned
        var $ancestor = $(this).closest(selector);
        if ($ancestor.length && $ancestor.css("position") !== "static") {
            var $child = $(this);
            var childMarginEdgeLeft = $child.offset().left - parseInt($child.css("marginLeft"), 10);
            var childMarginEdgeTop = $child.offset().top - parseInt($child.css("marginTop"), 10);
            var ancestorPaddingEdgeLeft = $ancestor.offset().left + parseInt($ancestor.css("borderLeftWidth"), 10);
            var ancestorPaddingEdgeTop = $ancestor.offset().top + parseInt($ancestor.css("borderTopWidth"), 10);
            left = childMarginEdgeLeft - ancestorPaddingEdgeLeft;
            top = childMarginEdgeTop - ancestorPaddingEdgeTop;
            // we have found the ancestor and computed the position
            // stop iterating
            return false;
        }
    });
    return {
        left:    left,
        top:    top
    }
};

	
	jQuery(document).ready(function() {
		var $ = jQuery;
		var layout = $('.layout');
		var templates = layout
			.children('.templates')
			.remove().children();
		
	/*----------------------------------------------------------------------------
		Fieldset events
	----------------------------------------------------------------------------*/
		
		// Initialize fieldset:
		layout.find('*').live('fieldset-initialize', function() {
			var fieldset = $(this).addClass('fieldset');
			
			fieldset.sortable({
				connectWith:	'.fieldset',
				cursorAt:		{ top: 15, left: 10 },
				distance:		10,
				items:			'.line',
				placeholder:	'ui-sortable-highlight',
				tolerance:		'pointer',
				zindex:			1000,
				
				start:			function() {
					fieldset
						.find('.ui-sortable-highlight')
						.addClass('line');
				},
				
				stop:			function() {
					if (fieldset.find('> .line').length == 0) {
						fieldset.trigger('fieldset-remove');
					}
				}
			});
			
			fieldset.children('ol')
				.trigger('line-initialize')
				.children('li:not(.control)')
				.trigger('field-initialize');
			
			layout.find('> .content > .fieldset > .line').trigger('line-refresh');
			
			return false;
		});
		
		// Remove fieldset:
		layout.find('.fieldset').live('fieldset-remove', function() {
			var fieldset = $(this);
			
			if (fieldset.parent().find('.fieldset').length == 1) return;
			
			fieldset.remove();
			
			// Reselect field:
			if (fieldset.find('.field.selected').length) {
				layout.find('> .settings').remove();
				layout.find('> content > .fieldset .field:first')
					.trigger('field-edit-start');
			}
			
			layout.find('> .content > .fieldset > .line')
				.trigger('line-refresh');
			
			return false;
		});
		
	/*----------------------------------------------------------------------------
		Line events
	----------------------------------------------------------------------------*/
		
		// Initialize lines:
		layout.find('*').live('line-initialize', function() {
			var line = $(this).addClass('line');
			
			line.prepend('<li class="control remove"><span class="title">×</span></li>');
			line.prepend('<li class="control dropdown"><span class="title">+</span></li>');
			
			line.sortable({
				connectWith:	'.fieldset .line',
				cursorAt:		{ top: 15, left: 10 },
				distance:		10,
				items:			'li:not(.control)',
				placeholder:	'ui-sortable-highlight',
				tolerance:		'pointer',
				zindex:			1000,
				
				sort:			function() {
					var helper = $('.ui-sortable-helper');
					var highlight = $('.layout .ui-sortable-highlight');
					
					highlight.css({
						'-moz-box-flex':	helper.css('-moz-box-flex'),
						'-webkit-box-flex':	helper.css('-webkit-box-flex')
					});
					helper.height(highlight.height());
					helper.width(highlight.width());
					
					layout.find('> .content > .fieldset > .line')
						.trigger('line-refresh');
				},
				
				stop:			function() {
					layout.find('> .content > .fieldset > .line')
						.trigger('line-refresh');
				}
			});
			
			layout.find('> .content > .fieldset > .line')
				.trigger('line-refresh');
			
			return false;
		});
		
		// Refresh lines:
		layout.find('.line').live('line-refresh', function() {
			var line = $(this), hide = false;
			
			if (layout.find('> .content > .fieldset > .line').length == 1) {
				hide = layout.find('> .content > .fieldset > .line > :not(.control)').length > 0;
				
				if (layout.find('> .content > .fieldset').length) hide = true;
			}
			
			else if (line.children(':not(.control)').length) {
				hide = true;
			}
			
			if (hide) line.children('.control.remove').hide();
			else line.children('.control.remove').show();
			
		/*---------------------------------------------------------------------------
			
			TODO: Change input names just like the duplicator does,
			so the inputs in the first field would have names like:
			
				field[1][label]
			
			And fields in the second:
			
				field[2][label]
			
			Remember that the .selected field is a special case.
			
		---------------------------------------------------------------------------*/
			
			return false;
		});
		
		// Remove line:
		layout.find('.line').live('line-remove', function() {
			var line = $(this);
			
			if (line.parent().children('.line').length > 1) {
				line.remove();
				
				// Reselect field:
				if (line.find('.field.selected').length) {
					$('.layout > .settings').remove();
					$('.fieldset .field:first')
						.trigger('field-edit-start');
				}
				
				layout.find('> .content > .fieldset > .line')
					.trigger('line-refresh');
			}
			
			else {
				line.parent().trigger('fieldset-remove');
			}
			
			return false;
		});
		
		// Line menu:
		layout.find('.line').live('line-menu-start', function() {
			var line = $(this);
			var position = line.position();
			var fieldset = line.parents('.fieldset');
			
			var menu = $('<div />').addClass('menu');
			var fields = $('<ol />').appendTo(menu);
			var actions = $('<ol />').appendTo(menu);
			
			// Build fields menu:
			$(templates).each(function() {
				var template = $(this);
				var name = template.find('h3:first').text();
				var wrap = $('<div />').addClass('settings');
				var after = line.children('.control:last');
				
				// Insert after the selected element:
				if (line.children('.selected').length) {
					after = line.children('.selected');
				}
				
				$('<li />')
					.text(name)
					.appendTo(fields)
					.bind('click', function() {
						$('.fieldset .field.selected')
							.trigger('field-edit-stop');
						
						template.clone()
							.wrapInner(wrap)
							.insertAfter(after)
							.trigger('field-initialize')
							.trigger('field-edit-start');
						
						menu.remove();
					});
			});
			
			// Insert line after:
			$('<li />')
				.text('Row')
				.appendTo(actions)
				.bind('click', function() {
					$('<ol />').insertAfter(line)
						.trigger('line-initialize');
					
					menu.remove();
				});
			
			// Insert fieldset after:
			$('<li />')
				.text('Fieldset')
				.appendTo(actions)
				.bind('click', function() {
					$('<div />')
						.append($('<h3 />').append($('<input />').val('Unknown')))
						.append($('<ol />'))
						.insertAfter(fieldset)
						.trigger('fieldset-initialize');
					
					menu.remove();
				});
			
			
			// Mozilla stuffs up positions:
			if ($.browser.mozilla = true) {
				// TODO: Find out where this gap is coming from and what versions of Firefox it applies to.
				position.top -= 15;
			}
			
			// Show menu:
			menu.appendTo('.layout')
				.show()
				.css({
					'top':	position.top + 'px',
					'left':	position.left + 'px'
				});
			
			return false;
		});
		
	/*----------------------------------------------------------------------------
		Field events
	----------------------------------------------------------------------------*/
		
		// Initialize field:
		layout.find('*').live('field-initialize', function() {
			var field = $(this).addClass('field');
			var settings = field.find('.settings');
			var title = $('<span />')
				.addClass('title')
				.prependTo(field);
			var change = function() {
				var label = settings.find('.field-label input');
				var flex = 2;
				
				switch (settings.find('.field-flex select').val()) {
					case '1': flex = 1; break;
					case '2': flex = 2; break;
					case '3': flex = 4; break;
				}
				
				title.text(label.val() || 'Unknown');
				field.css({
					'-moz-box-flex':		'' + flex,
					'-webkit-box-flex':		'' + flex
				});
			};
			
			settings
				.bind('change', change)
				.bind('keyup', change);
			
			$('<span />')
				.addClass('remove-field')
				.text('×')
				.appendTo(field);
			
			change();
			
			layout.find('> .content > .fieldset > .line')
				.trigger('line-refresh');
			
			return false;
		});
		
		// Remove field:
		layout.find('.field').live('field-remove', function() {
			var self = $(this);
			var line = self.parent();
			var fields = layout.find('.field');
			var next, prev;
			
			fields.each(function(index) {
				if (this == self.get(0)) {
					next = $(fields.get(index + 1));
					prev = $(fields.get(index - 1));
				}
			});
			
			self.remove();
			
			if (self.is('.selected')) {
				var select = $('.fieldset .field:first');
				
				// Select the next field:
				if (next.length > 0) select = next;
				
				// Select the previous field:
				else if (prev.length > 0) select = prev;
				
				$('.layout > .settings').remove();
				select.trigger('field-edit-start');
			}
			
			layout.find('> .content > .fieldset > .line')
				.trigger('line-refresh');
			
			return false;
		});
		
		// Show the settings editor:
		layout.find('.field').live('field-edit-start', function() {
			var self = $(this).addClass('selected');
			var settings = self.find('.settings');
			
			layout.append(settings);
			settings
				.find('input:first')
				.focus();
			
			return false;
		});
		
		// Hide the settings editor:
		layout.find('.field').live('field-edit-stop', function() {
			var self = $(this).removeClass('selected');
			var settings = $('.layout > .settings');
			
			self.append(settings);
			
			return false;
		});
		
	/*----------------------------------------------------------------------------
		Triggers
	----------------------------------------------------------------------------*/
		
		// Ignore mouse clicks:
		layout.find('> .content > .fieldset .line').live('mousedown', function() {
			return false;
		});
		
		// Show menu:
		layout.find('> .content > .fieldset > .line > .control.dropdown').live('click', function() {
			layout.find('.menu:not(.control):visible').remove();
			
			$(this).parents('.line').trigger('line-menu-start');
			
			return false;
		});
		
		// Hide menu:
		$('html').live('click', function() {
			if (layout.find('.menu:not(.control):visible').remove().length) return false;
		});
		
		// Remove current line or fieldset:
		layout.find('> .content > .fieldset > .line > .control.remove').live('click', function() {
			$(this).parents('.line').trigger('line-remove');
			
			return false;
		});
		
		// Remove current field:
		layout.find('> .content > .fieldset > .line > .field .remove-field').live('click', function() {
			$(this).parent().trigger('field-remove');
			
			return false;
		});
		
		// Edit field:
		layout.find('> .content > .fieldset > .line > .field:not(.selected)').live('click', function() {
			$('.fieldset .field.selected').trigger('field-edit-stop');
			$(this).trigger('field-edit-start');
			
			return false;
		});
		
	/*----------------------------------------------------------------------------
		Initialize
	----------------------------------------------------------------------------*/
		
		layout.find('> .content > *')
			.trigger('fieldset-initialize');
		layout.find('> .content > .fieldset > .line > .field:first')
			.trigger('field-edit-start');
		
	/*----------------------------------------------------------------------------
		Listen to form event
	----------------------------------------------------------------------------*/
		
		layout.live('prepare-submit', function() {
			var expression = /^fieldset\[[0-9]+\]\[fields\]\[[0-9]+\]\[(.*)]$/;
			
			layout.find('> .content > .fieldset').each(function(fieldset_index) {
				var fieldset = $(this);
				var input = fieldset.find('input:visible:first');
				
				input.attr('name', 'fieldset[' + fieldset_index + '][label]');
				
				fieldset.find('.field').each(function(field_index) {
					var field = $(this)
					var settings = $(this).children('.settings');
					
					if (!settings.length) {
						settings = layout.find('> .settings');
					}
					
					if (!settings.length) return;
					
					settings.find('[name]').each(function() {
						var input = $(this);
						var name = input.attr('name');
						var match = null;
						
						// Extract name:
						if (match = name.match(expression)) name = match[1];
						
						input.attr(
							'name',
							'fieldset['
							+ fieldset_index
							+ '][fields]['
							+ field_index
							+ ']['
							+ name
							+ ']'
						);
					});
				});
			});
		});
	});
	
	jQuery(document).ready(function() {
		var $ = jQuery;
		
		$('form').submit(function() {
			$('.layout').trigger('prepare-submit');
			console.log($(this).attr('action'));
			
			return true;
		});
	});
	
/*---------------------------------------------------------------------------*/