/**
 * @package assets
 */

(function($) {
	/**
	 * Symphony core interactions
	 */
	$(document).ready(function() {

		// Initialize Symphony
		Symphony.init();

		// Tags
		$('.tags').symphonyTags();

		// Pickers
		$('.picker').symphonyPickable();

		// Selectable
		$('table.selectable').symphonySelectable();

		// Orderable list
		$('ul.orderable').symphonyOrderable();

		// Orderable tables
		var old_sorting, orderable = $('table.orderable');
		orderable.symphonyOrderable({
			items: 'tr',
			handles: 'td'
		});

		// Store current sort order
		orderable.on('orderstart.orderable', function() {
			old_sorting = orderable.find('input').map(function(e, i) { return this.name + '=' + (e + 1); }).get().join('&');
		});

		// Process sort order
		orderable.on('orderstop.orderable', function() {
			orderable.addClass('busy');

			// Get new sort order
			var new_sorting = orderable.find('input').map(function(e, i) { return this.name + '=' + (e + 1); }).get().join('&');

			// Store new sort order
			if(new_sorting != old_sorting) {

				// Update items
				orderable.trigger('orderupdate');

				// Send request
				$.ajax({
					type: 'POST',
					url: Symphony.Context.get('root') + '/symphony/ajax/reorder' + location.href.slice(Symphony.Context.get('root').length + 9),
					data: new_sorting,
					success: function() {
						Symphony.Message.clear('reorder');
					},
					error: function() {
						Symphony.Message.post(Symphony.Language.get('Reordering was unsuccessful.'), 'reorder error');
					},
					complete: function() {
						orderable.removeClass('busy').find('tr').removeClass('selected');
						old_sorting = '';
					}
				});
			}
			else {
				orderable.removeClass('busy');
			}
		});

		// Duplicators
		$('.filters-duplicator').symphonyDuplicator();

		// Collapsible duplicators
		var duplicator = $('#fields-duplicator');
		duplicator.symphonyDuplicator({
			orderable: true,
			collapsible: true
		});

		// Dim system messages
		Symphony.Message.fade('silence', 10000);

		// Relative times in system messages
		$('abbr.timeago').each(function() {
			var time = $(this).parent();
			time.html(time.html().replace(Symphony.Language.get('at') + ' ', ''));
		});
		Symphony.Message.timer();

		// XSLT utilities
		$('textarea').blur(function() {
			var source = $(this).val(),
				utilities = $('#utilities li');

			// Remove current selection
			utilities.removeClass('selected');

			// Get utitities names
			utilities.find('a').each(function() {
				var utility = $(this),
					expression = new RegExp('href=["\']?(?:\\.{2}/utilities/)?' + utility.text());

				// Check for utility occurrences
				if(expression.test(source)) {
					utility.parent().addClass('selected');
				}
			});
		}).blur();

		// Clickable utilities in the XSLT editor
		$('#utilities li').click(function(event) {
			if ($(event.target).is('a')) return;

			var editor = $('textarea.code'),
				lines = editor.val().split('\n'),
				link = $(this).find('a').text(),
				statement = '<xsl:import href="../utilities/' + link + '"/>',
				regexp = '^<xsl:import href="(?:\.\./utilities/)?' + link + '"',
				newLine = '\n',
				numberOfNewLines = 1;

			if ($(this).hasClass('selected')) {
				for (var i = 0; i < lines.length; i++) {
					if ($.trim(lines[i]).match(regexp) != null) {
						(lines[i + 1] === '' && $.trim(lines[i - 1]).substring(0, 11) !== '<xsl:import') ? lines.splice(i, 2) : lines.splice(i, 1);
						break;
					}
				}

				editor.val(lines.join(newLine));
				$(this).removeClass('selected');
			}
			else {
				for (var i = 0; i < lines.length; i++) {
					if ($.trim(lines[i]).substring(0, 4) === '<!--' || $.trim(lines[i]).match('^<xsl:(?:import|variable|output|comment|template)')) {

						numberOfNewLines = $.trim(lines[i]).substring(0, 11) === '<xsl:import' ? 1 : 2;

						if (Symphony.Context.get('env')[0] != 'template') {
							lines[i] = statement.replace('../utilities/', '') + Array(numberOfNewLines + 1).join(newLine) + lines[i];
						}
						else {
							// we are inside the page template editor
							lines[i] = statement + Array(numberOfNewLines + 1).join(newLine) + lines[i];
						}
						break;
					}
				}

				editor.val(lines.join(newLine));
				$(this).addClass('selected');
			}
		});

		// Change user password
		$('#change-password').each(function() {
			var password = $(this),
				labels = password.find('label'),
				help = password.next('p.help'),
				placeholder = $('<label>' + Symphony.Language.get('Password') + ' <span class="frame"><button>' + Symphony.Language.get('Change Password') + '</button></span></label>'),
				invalid = password.has('.invalid');

			if(invalid.size() == 0) {

				// Hide password fields
				password.removeClass();
				labels.hide();
				help.hide();

				// Add placeholder
				password.append(placeholder).find('button').click(function(event) {
					event.preventDefault();

					// Hide placeholder
					placeholder.hide();

					// Shwo password fields
					password.addClass('group');
					if(password.find('input[name="fields[old-password]"]').length) password.addClass('triple');
					labels.show();
					help.show();
				});

			}

		});

		// Confirm actions
		$('button.confirm').live('click', function() {
			var button = $(this),
				name = document.title.split(/[\u2013]\s*/g)[2],
				message = button.attr('data-message');

			// Set default message
			if(!message) {
				message = Symphony.Language.get('Are you sure you want to proceed?');
			}

			return confirm(message);
		});

		// Confirm with selected actions
		$('form').submit(function(event) {
			var select = $('select[name="with-selected"]'),
				option = select.find('option:selected'),
				input = $('table input:checked'),
				count = input.size(),
				message = option.attr('data-message');

			// Needs confirmation
			if(option.is('.confirm')) {

				// Set default message
				if(!message) {
					message = Symphony.Language.get('Are you sure you want to proceed?');
				}

				return confirm(message);
			}
		});

		// Data source manager options
		$('select.filtered > optgroup').each(function() {
			var optgroup = $(this),
				select = optgroup.parents('select'),
				label = optgroup.attr('label'),
				options = optgroup.remove().find('option').addClass('optgroup');

			// Fix for Webkit browsers to initially show the options
			if (select.attr('multiple')) {
				select.scrollTop(0);
			}

			// Show only relevant options based on context
			$('#ds-context').change(function() {
				if($(this).find('option:selected').text() == label) {
					select.find('option.optgroup').remove();
					select.append(options.clone(true));
				}
			});
		});

		// Data source manager context
		$('*.contextual').each(function() {
			var area = $(this);

			$('#ds-context').change(function() {
				var select = $(this),
					optgroup = select.find('option:selected').parent(),
					value = select.val().replace(/\W+/g, '_'),
					group = optgroup.attr('label').replace(/\W+/g, '_');

				// Show only relevant interface components based on context
				area[(area.hasClass(value) || area.hasClass(group)) ^ area.hasClass('inverse') ? 'removeClass' : 'addClass']('irrelevant');
			});
		});

		// Set data source manager context
		$('#ds-context').change();

		// Once pagination is disabled, max_records and page_number are disabled too
		var max_record = $('input[name*=max_records]'),
			page_number = $('input[name*=page_number]');

		$('input[name*=paginate_results]').change(function(event) {

			// Turn on pagination
			if($(this).is(':checked')) {
				max_record.attr('disabled', false);
				page_number.attr('disabled', false);
			}

			// Turn off pagination
			else {
				max_record.attr('disabled', true);
				page_number.attr('disabled', true);
			}
		}).change();

		// Disable paginate_results checking/unchecking when clicking on either max_records or page_number
		max_record.add(page_number).click(function(event) {
			event.preventDefault();
		});

		// Enabled fields on submit
		$('form').bind('submit', function() {
			max_record.attr('disabled', false);
			page_number.attr('disabled', false);
		});

		// Upload fields
		$('<em>' + Symphony.Language.get('Remove File') + '</em>').appendTo('label.file:has(a) span').click(function(event) {
			var span = $(this).parent(),
				name = span.find('input').attr('name');

			// Prevent clicktrough
			event.preventDefault();

			// Add new empty file input
			span.empty().append('<input name="' + name + '" type="file">');
		});

		// Focus first text-input or textarea when creating entries
		if(Symphony.Context.get('env') != null && (Symphony.Context.get('env')[0] == 'new' || Symphony.Context.get('env').page == 'new')) {
			$('input[type="text"], textarea').first().focus();
		}

		// Accessible navigation
		$('#nav').delegate('a', 'focus blur', function() {
			$(this).parents('li').eq(1).toggleClass('current');
		});

		// Auto-highlight content in pagination input
		$('.page input').focus(function() {
			$(this).select();
		});

		// Validate pagination input on submit
		$('.page form').submit(function() {
			if(!$(this).find('input').val().match('^[0-9]+$') || $(this).find('input').val() > parseInt($(this).find('span').html())) {
				$(this).find('input').addClass("error");
				window.setTimeout(function() { $('.page form input').removeClass("error"); }, 500);
				return false;
			}
		});
	});

})(jQuery.noConflict());
