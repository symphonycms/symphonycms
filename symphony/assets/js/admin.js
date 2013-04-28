/**
 * @package assets
 */

(function($) {
	/**
	 * Symphony core interactions
	 */
	$(document).ready(function() {
		var html = $('html').addClass('active'),
			body = html.find('body'),
			wrapper = html.find('#wrapper'),
			header = wrapper.find('#header'),
			nav = wrapper.find('#nav'),
			navContent = nav.find('ul.content'),
			navStructure = nav.find('ul.structure'),
			session = header.find('#session'),
			context = wrapper.find('#context'),
			contents = wrapper.find('#contents'),
			form = contents.find('> form'),
			user = session.find('li:first a'),
			pagination = contents.find('ul.page');

	/*--------------------------------------------------------------------------
		Core Functions
	--------------------------------------------------------------------------*/

		// Set basic context information
		Symphony.Context.add('user', {
			fullname: user.text(),
			name: user.data('name'),
			type: user.data('type'),
			id: user.data('id')
		});
		Symphony.Context.add('lang', html.attr('lang'));

		// Initialise core language strings
		Symphony.Language.add({
			'Are you sure you want to proceed?': false,
			'Reordering was unsuccessful.': false,
			'Change Password': false,
			'Remove File': false,
			'Untitled Field': false,
			'The field “{$title}” ({$type}) has been removed.': false,
			'Undo?': false,
			'unnamed': false,
			'Expand all fields': false,
			'Collapse all fields': false
		});

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

		// Navigation sizing
		$(window).on('resize.admin nav.admin', function(event) {
			var width = navContent.width() + navStructure.width() + 20;

			// Compact mode
			if(width > $(window).width()) {
				nav.removeClass('wide');
			}

			// Wide mode
			else {
				nav.addClass('wide');
			}
		}).trigger('nav.admin');

		// Accessible navigation
		nav.on('focus.admin blur.admin', 'a', function() {
			$(this).parents('li').eq(1).toggleClass('current');
		});

		// Notifier sizing
		$(window).on('resize.admin', function(event) {
			header.find('.notifier').trigger('resize.notify');
		});

		// Table sizing
		$(window).on('resize.admin table.admin', function(event) {
			var table = $('table:first');

			// Fix table size, if width exceeds the visibile viewport area.
			if(table.width() > $('html').width()){
				table.addClass('fixed');
			}
			else {
				table.removeClass('fixed');
			}
		}).trigger('table.admin');

		// Focus first text-input or textarea when creating entries
		if(Symphony.Context.get('env') != null && (Symphony.Context.get('env')[0] == 'new' || Symphony.Context.get('env').page == 'new')) {
			contents.find('input[type="text"], textarea').first().focus();
		}

	/*--------------------------------------------------------------------------
		Plugins - Tags, Pickable, Selectable, Notify and Drawers
	--------------------------------------------------------------------------*/

		// Tags
		contents.find('.tags').symphonyTags();

		// Pickers
		contents.find('select.picker').symphonyPickable();

		// Selectable
		contents.find('table.selectable').symphonySelectable();

		// Notify
		header.symphonyNotify();

		// Drawers
		wrapper.find('div.drawer').symphonyDrawer();

	/*--------------------------------------------------------------------------
		Plugins - Orderable
	--------------------------------------------------------------------------*/

		// Orderable list
		contents.find('ul.orderable').symphonyOrderable();

		// Orderable tables
		contents.find('table.orderable')
			.symphonyOrderable({
				items: 'tr',
				handles: 'td'
			})
			.on('orderstart.orderable', function() {

				// Store current sort order
				oldSorting = $(this).find('input').map(function(e, i) { return this.name + '=' + (e + 1); }).get().join('&');
			})
			.on('orderstop.orderable', function() {
				var orderable = $(this).addClass('busy'),
					newSorting = orderable.find('input').map(function(e, i) { return this.name + '=' + (e + 1); }).get().join('&');

				// Store sort order, if changed
				if(newSorting !== oldSorting) {

					// Update items
					orderable.trigger('orderupdate.admin');

					// Send request
					$.ajax({
						type: 'POST',
						url: Symphony.Context.get('root') + '/symphony/ajax/reorder' + location.href.slice(Symphony.Context.get('root').length + 9),
						data: newSorting,
						error: function() {
							Symphony.Message.post(Symphony.Language.get('Reordering was unsuccessful.'), 'error');
						},
						complete: function() {
							orderable.removeClass('busy').find('tr').removeClass('selected');
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
		contents.find('.filters-duplicator').symphonyDuplicator();

		// Highlight instances with the same location when ordering fields
		contents.find('div.duplicator')
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

		contents.find('fieldset.apply').each(function() {
			var applicable = $(this),
				selection = $('table.selectable'),
				select = applicable.find('select'),
				button = applicable.find('button');

			// Set menu status
			if(selection.length > 0) {
				selection.on('select.selectable deselect.selectable check.selectable', 'tbody tr', function(event) {

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

				selection.find('tbody tr:first').trigger('check');

				// Respect menu state
				button.on('click.admin', function(event) {
					if(applicable.is('.inactive')) {
						return false;
					}
				});
			}
		});

	/*--------------------------------------------------------------------------
		Components - Pagination
	--------------------------------------------------------------------------*/

		if(pagination.length > 0) {
			var pageform = pagination.find('form'),
				pagegoto = pageform.find('input'),
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
			pageform.on('mouseover.admin', function(event) {
				if(!pageform.is('.active') && pagegoto.val() == pageinactive) {
					pagegoto.val(pageactive);
				}
			});

			// Display current page placeholder
			pageform.on('mouseout.admin', function(event) {
				if(!pageform.is('.active') && pagegoto.val() == pageactive) {
					pagegoto.val(pageinactive);
				}
			});

			// Edit page number
			pagegoto.on('focus.admin', function(event) {
				if(pagegoto.val() == pageactive) {
					pagegoto.val('');
				}
				pageform.addClass('active');
			});

			// Stop editing page number
			pagegoto.on('blur.admin', function(event) {

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
			pageform.on('submit.admin', function(event) {
				if(parseInt(pagegoto.val()) > parseInt(pagegoto.attr('data-max'))) {
					pageform.addClass('invalid');
					return false;
				}
			});
		}

	/*--------------------------------------------------------------------------
		Components - Confirm Actions
	--------------------------------------------------------------------------*/

		// Confirm actions
		contents.add(context).on('click.admin', 'button.confirm', function() {
			var button = $(this),
				name = document.title.split(/[\u2013]\s*/g)[2],
				message = button.attr('data-message') || Symphony.Language.get('Are you sure you want to proceed?');

			return confirm(message);
		});

		// Confirm with selected actions
		form.on('submit.admin', function(event) {
			var select = $('select[name="with-selected"]'),
				option = select.find('option:selected'),
				message = option.attr('data-message') ||  Symphony.Language.get('Are you sure you want to proceed?');

			// Needs confirmation
			if(option.is('.confirm')) {
				return confirm(message);
			}
		});

	/*--------------------------------------------------------------------------
		Blueprints - Sections
	--------------------------------------------------------------------------*/

		if(body.is('#blueprints-sections')) {

			// Field editor
			contents.find('#fields-duplicator')
				.symphonyDuplicator({
					orderable: true,
					collapsible: (Symphony.Context.get('env')[0] !== 'new'),
					preselect: 'input'
				})
				.on('blur.admin input.admin', '.instance input[name*="[label]"]', function(event) {
					var label = $(this),
						value = label.val();

					// Empty label
					if(value == '') {
						value = Symphony.Language.get('Untitled Field');
					}

					// Update title
					label.parents('.instance').find('header strong').text(value);

					return false;
				})
				.on('change.admin', '.instance select[name*="[location]"]', function(event) {
					var select = $(this);

					// Set location
					select.parents('.instance').find('header').removeClass('main').removeClass('sidebar').addClass(select.val());
				})
				.on('destructstart.duplicator', function(event) {
					var target = $(event.target);
						item = target.clone(),
						title = item.find('header strong').text(),
						type = item.find('header span').text(),
						index = target.index();
						id = new Date().getTime();

					// Offer undo option after removing a field
					header.find('div.notifier').trigger('attach.notify', [
						Symphony.Language.get('The field “{$title}” ({$type}) has been removed.', {
							title: title,
							type: type
						}) + '<a id="' + id + '">' + Symphony.Language.get('Undo?') + '</a>', 'protected undo']
					);

					// Prepare field recovery
					$('#' + id).data('field', item).data('preceding', index - 1).on('click.admin', function() {
						var undo = $(this),
							message = undo.parent(),
							field = undo.data('field').hide(),
							list = $('#fields-duplicator'),
							duplicator = list.parent().removeClass('empty');

						// Add field
						field.trigger('constructstart.duplicator');
						list.find('.instance:eq(' + undo.data('preceding') + ')').after(field);
						field.trigger('constructshow.duplicator');
						field.slideDown('fast', function() {
							field.trigger('constructstop.duplicator');
						});

						// Clear system message
						message.trigger('detach.notify');
					});
				});

			// Discard undo options because the field context changed
			contents.find('.duplicator').on('orderstop.orderable', function(event) {
				header.find('.undo').trigger('detach.notify');
			});

			// Field legend
			var fieldLegend = contents.find('#fields legend'),
				fieldExpand = $('<a />', {
					'class': 'expand',
					'text': Symphony.Language.get('Expand all fields')
				}),
				fieldCollapse = $('<a />', {
					'class': 'collapse',
					'text': Symphony.Language.get('Collapse all fields')
				}),
				fieldToggle = $('<p />', {
					'class': 'help toggle'
				}).append(fieldExpand).append('<br />').append(fieldCollapse),
				fieldLegendTop, fieldToggleTop;

			// Add toggle controls
			fieldLegend.after(fieldToggle);

			/* Check if there is DOM Element:
			 * This prevents a bug in the section page since offset will return null on empty selections */
			fieldLegendTop = !!fieldLegend.length ? fieldLegend.offset().top : 0;
			fieldToggleTop = !!fieldToggle.length ? fieldToggle.offset().top : 0;

			// Fix toggle controls
			$(window).on('scroll.admin', function fixFieldControls(event) {
				var top = $(this).scrollTop() + 20;

				if(top >= fieldLegendTop) {
					fieldLegend.add(fieldToggle).addClass('fixed');
				}
				else {
					fieldLegend.add(fieldToggle).removeClass('fixed');
				}
			});

			// Toggle fields
			fieldToggle.on('click.admin', 'a.expand, a.collapse', function toggleFields(event) {
				var control = $(this),
					fields = contents.find('#fields-duplicator > .instance');

				// Expand
				if(control.is('.expand')) {
					fields.trigger('expand.collapsible');
				}

				// Collapse
				else {
					fields.trigger('collapse.collapsible');
				}
			});
		}

	/*--------------------------------------------------------------------------
		Blueprints - Pages and Utilities
	--------------------------------------------------------------------------*/

		if(body.is('#blueprints-utilities') || body.is('#blueprints-pages')) {

			// XSLT utilities
			contents.find('fieldset.primary textarea')
				.on('keydown.admin', function(event) {

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
				.on('blur.admin', function() {
					var source = $(this).val(),
						utilities = $('#utilities').find('li');

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
				}).trigger('blur.admin');

			// Clickable utilities in the XSLT editor
			contents.find('#utilities li').on('click.admin', function(event) {
				if($(event.target).is('a')) return;

				var utility = $(this),
					editor = $('textarea.code'),
					lines = editor.val().split('\n'),
					link = $(this).find('a').text(),
					statement = '<xsl:import href="../utilities/' + link + '"/>',
					regexp = '^<xsl:import href="(?:\.\./utilities/)?' + link + '"',
					newLine = '\n',
					numberOfNewLines = 1,
					number_lines = lines.length,
					i;

				if ($(this).hasClass('selected')) {
					for(i = 0; i < number_lines; i++) {
						if($.trim(lines[i]).match(regexp) != null) {
							(lines[i + 1] === '' && $.trim(lines[i - 1]).substring(0, 11) !== '<xsl:import') ? lines.splice(i, 2) : lines.splice(i, 1);
							break;
						}
					}

					editor.val(lines.join(newLine));
					utility.removeClass('selected');
				}
				else {
					for(i = 0; i < number_lines; i++) {
						if($.trim(lines[i]).substring(0, 4) === '<!--' || $.trim(lines[i]).match('^<xsl:(?:import|variable|output|comment|template)')) {

							numberOfNewLines = $.trim(lines[i]).substring(0, 11) === '<xsl:import' ? 1 : 2;

							if(Symphony.Context.get('env')[0] != 'template') {
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
					utility.addClass('selected');
				}
			});
		}

	/*--------------------------------------------------------------------------
		Blueprints - Datasource Editor
	--------------------------------------------------------------------------*/

		if(body.is('#blueprints-datasources')) {
			var dsName = contents.find('input[name="fields[name]"]').attr('data-updated', 0),
				dsNameChangeCount = 0,
				dsParams = contents.find('select[name="fields[param][]"]'),
				dsMaxRecord = contents.find('input[name*=max_records]'),
				dsPageNumber = contents.find('input[name*=page_number]');

			// Update data source handle
			dsName.on('blur.admin input.admin', function updateDsHandle() {
				var current = dsNameChangeCount = dsNameChangeCount + 1,
					value = dsName.val();

				setTimeout(function fetchDsHandle() {
					if(dsNameChangeCount == current) {
						$.ajax({
							type: 'GET',
							data: { 'string': value },
							dataType: 'json',
							url: Symphony.Context.get('root') + '/symphony/ajax/handle/',
							success: function(result) {
								if(dsNameChangeCount == current) {
									dsName.data('handle', result);
									dsParams.trigger('update.admin');
								}
							}
						});
					}
				}, 500);
			});

			// Update output parameters
			dsParams.on('update.admin', function updateDsParams() {
				var params = $(this),
					handle = dsName.data('handle') || Symphony.Language.get('unnamed');

				// Process parameters
				if(parseInt(dsName.attr('data-updated')) !== 0) {
					params.find('option').each(function updateDsParam() {
						var param = $(this),
							field = param.attr('data-handle');

						// Set parameter
						param.text('$ds-' + handle + '.' + field);
					});
				}

				// Updated
				dsName.attr('data-updated', 1);
			}).trigger('update.admin');

			// Data source manager options
			contents.find('select.filtered > optgroup').each(function() {
				var optgroup = $(this),
					select = optgroup.parents('select'),
					label = optgroup.attr('label'),
					options = optgroup.remove().find('option').addClass('optgroup');

				// Fix for Webkit browsers to initially show the options
				if (select.attr('multiple')) {
					select.scrollTop(0);
				}

				// Show only relevant options based on context
				$('#ds-context').on('change.admin', function() {
					if($(this).find('option:selected').text() == label) {
						select.find('option.optgroup').remove();
						select.append(options.clone(true));
					}
				});
			});

			// Data source manager context
			contents.find('.contextual').each(function() {
				var area = $(this);

				$('#ds-context').on('change.admin', function() {
					var select = $(this),
						optgroup = select.find('option:selected').parent(),
						value = select.val().replace(/\W+/g, '_'),
						group = optgroup.data('label') || optgroup.attr('label').replace(/\W+/g, '_');

					// Show only relevant interface components based on context
					area[(area.hasClass(value) || area.hasClass(group)) ^ area.hasClass('inverse') ? 'removeClass' : 'addClass']('irrelevant');
				});
			});

			// Trigger the parameter name being remembered when the Datasource context changes
			contents.find('#ds-context')
				.on('change.admin', function() {
					contents.find('input[name="fields[name]"]').trigger('blur.admin');
				})
				.trigger('change.admin');

			// Once pagination is disabled, dsMaxRecords and dsPageNumber are disabled too
			contents.find('input[name*=paginate_results]').on('change.admin', function(event) {
				// Look within the existing context to ensure that these actions only fire
				// on the active Datasource type
				var $paginate_results = $(this),
					$paging_container = $paginate_results.closest('label'),
					$dsMaxRecords = $paging_container.find('input[name*=max_records]'),
					$dsPageNumber = $paging_container.find('input[name*=page_number]');

				// Turn on pagination
				if($(this).is(':checked')) {
					$dsMaxRecords.add($dsPageNumber).prop('disabled', false);
				}

				// Turn off pagination
				else {
					$dsMaxRecords.add($dsPageNumber).prop('disabled', true);
				}
			}).trigger('change.admin');

			// Disable paginate_results checking/unchecking when clicking on either dsMaxRecords or dsPageNumber
			dsMaxRecord.add(dsPageNumber).on('click.admin', function(event) {
				event.preventDefault();
			});

			// Enabled fields on submit
			form.on('submit.admin', function() {
				dsMaxRecord.add(dsPageNumber).prop('disabled', false);
			});

			// Enable parameter suggestions
			contents.find('.duplicator:has(.filters-duplicator)').symphonySuggestions();
		}

	/*--------------------------------------------------------------------------
		Blueprints - Event Editor
	--------------------------------------------------------------------------*/

		// This is transitional code needed until the event editor makes proper use of Pickable.
		// This is scheduled to be removed in Symphony 2.4.
		if(body.is('#blueprints-events')) {
			var eventSections = $('#sections');
			$('#event-context').find('option').each(function() {
				eventSections.clone().attr('id', 'choice' + $(this).val()).insertAfter(eventSections);
			}).trigger('change.pickable');

			form.on('submit', function() {
				$('.pickable:not(:visible)').remove();
			});
		}

	/*--------------------------------------------------------------------------
		System - Authors
	--------------------------------------------------------------------------*/

		if(body.is('#system-authors')) {

			// Change user password
			contents.find('#password').each(function() {
				var password = $(this),
					overlay = $('<div class="password"><span class="frame"><button type="button">' + Symphony.Language.get('Change Password') + '</button></span></div>');

				// Add overlay
				if(password.has('.invalid').length == 0 && Symphony.Context.get('env')[0] != 'new') {
					overlay.insertBefore(password).find('button').on('click.admin', function(event) {
						event.preventDefault();
						overlay.hide();
					});
				}
			});
		}

	/*--------------------------------------------------------------------------
		Field - Upload
	--------------------------------------------------------------------------*/

		// Upload fields
		$('<em>' + Symphony.Language.get('Remove File') + '</em>').appendTo('label.file:has(a) span.frame').on('click.admin', function(event) {
			var span = $(this).parent(),
				name = span.find('input').attr('name');

			// Prevent clicktrough
			event.preventDefault();

			// Add new empty file input
			span.empty().append('<input name="' + name + '" type="file">');
		});

	});

})(window.jQuery);
