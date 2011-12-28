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
			Plugins - Tags, Pickable and Selectable
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

		// Collapsible duplicators
		var duplicator = $('#fields-duplicator');
		duplicator.symphonyDuplicator({
			orderable: true,
			collapsible: true,
			preselect: 'input'
		});

		/*--------------------------------------------------------------------------
			Plugins - System Messages
		--------------------------------------------------------------------------*/

		$('header').symphonyNotify();

		/*--------------------------------------------------------------------------
			Components - XSLT Editor
		--------------------------------------------------------------------------*/

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
				numberOfNewLines = 1,
				number_lines = lines.length;

			if ($(this).hasClass('selected')) {
				for (var i = 0; i < number_lines; i++) {
					if ($.trim(lines[i]).match(regexp) != null) {
						(lines[i + 1] === '' && $.trim(lines[i - 1]).substring(0, 11) !== '<xsl:import') ? lines.splice(i, 2) : lines.splice(i, 1);
						break;
					}
				}

				editor.val(lines.join(newLine));
				$(this).removeClass('selected');
			}
			else {
				for (var i = 0; i < number_lines; i++) {
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
		$('#change-password').each(function() {
			var password = $(this),
				labels = password.find('label'),
				help = password.next('p.help'),
				placeholder = $('<label>' + Symphony.Language.get('Password') + ' <span class="frame"><button>' + Symphony.Language.get('Change Password') + '</button></span></label>'),
				invalid = password.has('.invalid');

			if(invalid.length == 0) {

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

						item.text('$ds-' + result + '.' + field)
					});
				}
			});
		});

		// Datasource collapsable links
		$('#blueprints-datasources.index table tr, #blueprints-events.index table tr').each(function() {
			var links = [];
			$('td:eq(2) a', this).each(function(){
				links.push($(this));
			});

			// If there is less than 3, show them all by default
			if(links.length <= 3) return;

			// Clear the field and append the links
			$('td:eq(2)', this).html('');
			for(var i=0, l=links.length; i<l; i++) {
				$('td:eq(2)', this).append(links[i]).append('<span>, </span>');
			}
			$("td:eq(2) a:gt(2)", this).each(function(){
				$(this).hide().next().hide();
			});

			$('td:eq(2)', this).append(
				'<a href="#" class="expand">' +
				' <span class="more">' + (links.length - 3) + ' more</span>' +
				' <span class="less">less</span>&hellip;</a>'
			);

			// Listen for click on the 'expand' links, to hide/show
			$(this).on('click', '.expand', function() {
				var $parent = $(this).parent();

				if($(this).hasClass('expanded')) {
					$(">a:gt(2), >span:gt(2)", $parent).not('.expand').hide();
					$(this).removeClass('expanded');
				}
				else {
					$(">a, >span", $parent).show();
					$(this).addClass('expanded');
				}

				return false;
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
			if(!$input.val().match('^[0-9]+$') || $input.val() > parseInt($(this).find('span').html())) {
				$input.addClass('error');
				window.setTimeout(function() { $('.page form input').removeClass("error"); }, 500);
				return false;
			}
		});

		/*--------------------------------------------------------------------------
			Field - Upload
		--------------------------------------------------------------------------*/

		// Upload fields
		$('<em>' + Symphony.Language.get('Remove File') + '</em>').appendTo('label.file:has(a) span').click(function(event) {
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
