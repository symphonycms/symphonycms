/**
 * @package assets
 */

(function($) {
	/**
	 * Symphony core interactions
	 */
	$(document).ready(function() {

		/*--------------------------------------------------------------------------
			Core Functions
		--------------------------------------------------------------------------*/

		// Initialize Symphony
		Symphony.init();

		// Catch all javascript errors and write them to the Symphony Log
		window.onerror = function(errorMsg, url, line) {
			$.ajax({
				type: 'POST',
				url: Symphony.Context.get('root') + '/symphony/ajax/log/',
				data: {
					'error': errorMsg,
					'url': url,
					'line': line
				}
			});

			return false;
		};

		/*--------------------------------------------------------------------------
			Plugins - Tags, Pickable, Selectable, Notify and Drawers
		--------------------------------------------------------------------------*/

		// Tags
		$('.tags').symphonyTags();

		// Pickers
		$('select[name="settings[Email][default_gateway]"]').symphonyPickable();

		$('select[name="fields[dynamic_xml][format]"]').symphonyPickable({
			pickables: '#xml'
		});

		// Selectable
		$('table.selectable').symphonySelectable();

		// Notify
		$('#header').symphonyNotify();

		// Drawers
		$('div.drawer').symphonyDrawer();

		/*--------------------------------------------------------------------------
			Plugins - Orderable
		--------------------------------------------------------------------------*/

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
			if(new_sorting !== old_sorting) {

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


		/*--------------------------------------------------------------------------
			Plugins - Duplicator and Collapsible
		--------------------------------------------------------------------------*/

		// Duplicators
		$('.filters-duplicator').symphonyDuplicator();

		// Field editor
		$('#fields-duplicator')
			.symphonyDuplicator({
				orderable: true,
				collapsible: true,
				preselect: 'input'
			})
			.on('keyup', '.instance input[name*="[label]"]', function(event) {
				var label = $(this),
					value = label.val();

				// Empty label
				if(value == '') {
					value = Symphony.Language.get('Untitled Field');
				}

				// Update title
				label.parents('.instance').find('header strong').text(value);
			})
			.on('change', '.instance select[name*="[location]"]', function(event) {
				var select = $(this);

				// Set location
				select.parents('.instance').find('header').removeClass('main').removeClass('sidebar').addClass(select.val());
			});

		// Highlight instances with the same location when ordering fields
		$('div.duplicator')
			.on('orderstart.orderable', function(event, item) {
				var duplicator = $(this);

				setTimeout(function() {
					if(duplicator.is('.ordering')) {
						duplicator.find('li:has(.' + item.find('header').attr('class') + ')').not(item).addClass('highlight');
					}
				}, 250);
			})
			.on('orderstop.orderable', function(event, item) {
				$(this).find('li.highlight').removeClass('highlight');
			});

		/*--------------------------------------------------------------------------
			Components - With Selected
		--------------------------------------------------------------------------*/

		$('fieldset.apply').each(function() {
			var applicable = $(this),
				selection = $('table.selectable'),
				select = applicable.find('select'),
				button = applicable.find('button');

			// Set menu status
			if(selection.length > 0) {
				selection.on('select deselect check', 'tbody tr:has(input)', function(event) {

					// Activate menu
					if(selection.has('.selected').length > 0) {
						applicable.removeClass('inactive');
						select.removeAttr('disabled');
					}

					// Deactivate menu
					else {
						applicable.addClass('inactive');
						select.attr('disabled', 'disabled');
					}
				});

				selection.find('tbody tr:has(input):first').trigger('check');

				// Respect menu state
				button.on('click', function(event) {
					if(applicable.is('.inactive')) {
						return false;
					}
				});
			}
		});

		/*--------------------------------------------------------------------------
			Components - Pagination
		--------------------------------------------------------------------------*/

		var pageform = $('ul.page form');
		if(pageform.length > 0) {
			var	pagegoto = pageform.find('input'),
				pageactive = pagegoto.attr('data-active'),
				pageinactive = pagegoto.attr('data-inactive'),
				pagehelper = $('<span />').appendTo(pageform),
				width;

			// Measure placeholder text
			width = Math.max(pagehelper.text(pageactive).width(), pagehelper.text(pageinactive).width());
			pagehelper.remove();
			pagegoto.width(width + 20);

			// Set current page
			pagegoto.val(pageinactive);

			// Display "Go to page …" placeholder
			pageform.on('mouseover', function(event) {
				if(!pageform.is('.active') && pagegoto.val() == pageinactive) {
					pagegoto.val(pageactive);
				}
			});

			// Display current page placeholder
			pageform.on('mouseout', function(event) {
				if(!pageform.is('.active') && pagegoto.val() == pageactive) {
					pagegoto.val(pageinactive);
				}
			});

			// Edit page number
			pagegoto.on('focus', function(event) {
				if(pagegoto.val() == pageactive) {
					pagegoto.val('');
				}
				pageform.addClass('active');
			});

			// Stop editing page number
			pagegoto.on('blur', function(event) {

				// Clear errors
				if(pageform.is('.invalid') || pagegoto.val() == '') {
					pageform.removeClass('invalid');
					pagegoto.val(pageinactive);
				}

				// Deactivate
				if(pagegoto.val() == pageinactive) {
					pageform.removeClass('active');
				}
			});

			// Validate page number
			pageform.on('submit', function(event) {
				if(pagegoto.val() > pagegoto.attr('data-max')) {
					pageform.addClass('invalid');
					return false;
				}
			});
		}

		/*--------------------------------------------------------------------------
			Components - XSLT Editor
		--------------------------------------------------------------------------*/

		// XSLT utilities
		$('#blueprints-utilities fieldset.primary textarea, #blueprints-pages fieldset.primary textarea')
			.on('keydown', function(event) {

				// Allow tab insertion
				if(event.which == 9) {
					var start = this.selectionStart,
						end = this.selectionEnd,
						position = this.scrollTop;

					event.preventDefault();

					// Add tab
					this.value = this.value.substring(0, start) + "\t" + this.value.substring(end, this.value.length);
					this.selectionStart = start + 1;
					this.selectionEnd = start + 1;

					// Restore scroll position
					this.scrollTop = position;
   				}
			})
			.on('blur', function() {
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
				numberOfNewLines = 1,
				number_lines = lines.length,
				i;

			if ($(this).hasClass('selected')) {
				for (i = 0; i < number_lines; i++) {
					if ($.trim(lines[i]).match(regexp) != null) {
						(lines[i + 1] === '' && $.trim(lines[i - 1]).substring(0, 11) !== '<xsl:import') ? lines.splice(i, 2) : lines.splice(i, 1);
						break;
					}
				}

				editor.val(lines.join(newLine));
				$(this).removeClass('selected');
			}
			else {
				for (i = 0; i < number_lines; i++) {
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

		/*--------------------------------------------------------------------------
			Components - User Password
		--------------------------------------------------------------------------*/

		// Change user password
		$('#password').each(function() {
			var password = $(this),
				overlay = $('<div class="password"><span class="frame"><button type="button">' + Symphony.Language.get('Change Password') + '</button></span></div>');

			// Add overlay
			if(password.has('.invalid').length == 0 && Symphony.Context.get('env')[0] != 'new') {
				overlay.insertBefore(password).find('button').on('click', function(event) {
					event.preventDefault();
					overlay.hide();
				});
			}
		});

		/*--------------------------------------------------------------------------
			Components - Confirm Actions
		--------------------------------------------------------------------------*/

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
				count = input.length,
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

		/*--------------------------------------------------------------------------
			Page - Datasource Editor
		--------------------------------------------------------------------------*/

		// Update DS Parameters selectbox as the user types a new name for the resource
		$('#blueprints-datasources input[name="fields[name]"]').on('change', function(){
			var value = $(this).val();

			if(value == '' || $('select[name="fields[param][]"]:visible').length == 0) {
				$('select[name="fields[param][]"] option').each(function(){
					var item = $(this),
						field = item.text().split('.')[1];

					item.text('$ds-' + '?' + '.' + field);
				});
				return;
			}

			$.ajax({
				type: 'GET',
				data: { 'string': value },
				dataType: 'json',
				url: Symphony.Context.get('root') + '/symphony/ajax/handle/',
				success: function(result) {
					$('select[name="fields[param][]"] option').each(function(){
						var item = $(this),
							field = item.text().split('.')[1];

						item.text('$ds-' + result + '.' + field);
					});
				}
			});
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

		$('#ds-context')
			// Trigger the parameter name being remembered when the Datasource context changes
			.on('change', function() {
				$('#blueprints-datasources input[name="fields[name]"]').trigger('change');
			})
			// Set data source manager context
			.change();

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

		// Validate pagination input on submit
		$('.page form').submit(function() {
			var $input = $(this).find('input');
			if(!$input.val().match('^[0-9]+$') || $input.val() > parseInt($(this).find('span').html(), 10)) {
				$input.addClass('error');
				window.setTimeout(function() { $('.page form input').removeClass("error"); }, 500);
				return false;
			}
		});

		/*--------------------------------------------------------------------------
			Field - Upload
		--------------------------------------------------------------------------*/

		// Upload fields
		$('<em>' + Symphony.Language.get('Remove File') + '</em>').appendTo('label.file:has(a) span.frame').click(function(event) {
			var span = $(this).parent(),
				name = span.find('input').attr('name');

			// Prevent clicktrough
			event.preventDefault();

			// Add new empty file input
			span.empty().append('<input name="' + name + '" type="file">');
		});

		/*--------------------------------------------------------------------------
			Miscellanea
		--------------------------------------------------------------------------*/

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

		// Set table to "fixed mode" if its width exceeds the visibile viewport area.
		// See https://github.com/symphonycms/symphony-2/issues/932.
		$(window).resize(function() {
			var table = $('table:first');

			if(table.width() > $('html').width() && !table.hasClass('fixed')){
				return table.addClass('fixed');
			}

			if(table.width() < $('html').width() && table.hasClass('fixed')){
				return table.removeClass('fixed');
			}
		});

		$(window).trigger('resize');
	});

})(jQuery.noConflict());
