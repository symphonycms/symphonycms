/**
 * Symphony backend views
 *
 * @package assets
 */

(function($, Symphony) {

/*--------------------------------------------------------------------------
	General backend view
--------------------------------------------------------------------------*/

Symphony.View.add('/:context*:', function() {

	// Initialise core plugins
	Symphony.Elements.contents.find('select.picker[data-interactive]').symphonyPickable();
	Symphony.Elements.contents.find('ul.orderable[data-interactive]').symphonyOrderable();
	Symphony.Elements.contents.find('table.selectable[data-interactive]').symphonySelectable();
	Symphony.Elements.wrapper.find('.filters-duplicator[data-interactive]').symphonyDuplicator();
	Symphony.Elements.wrapper.find('.tags[data-interactive]').symphonyTags();
	Symphony.Elements.wrapper.find('div.drawer').symphonyDrawer();
	Symphony.Elements.header.symphonyNotify();

	// Fix for Webkit browsers to initially show the options. #2127
	$('select[multiple=multiple]').scrollTop(0);

	// Initialise tag lists inside duplicators
	Symphony.Elements.contents.find('.duplicator').on('constructshow.duplicator', '.instance', function() {
		$(this).find('.tags[data-interactive]').symphonyTags();
	});

	// Navigation sizing
	Symphony.Elements.window.on('resize.admin nav.admin', function() {
		var content = Symphony.Elements.nav.find('ul.content'),
			structure = Symphony.Elements.nav.find('ul.structure'),
			width = content.width() + structure.width() + 20;

		// Compact mode
		if(width > window.innerWidth) {
			Symphony.Elements.nav.removeClass('wide');
		}

		// Wide mode
		else {
			Symphony.Elements.nav.addClass('wide');
		}
	}).trigger('nav.admin');

	// Accessible navigation
	Symphony.Elements.nav.on('focus.admin blur.admin', 'a', function() {
		$(this).parents('li').eq(1).toggleClass('current');
	});

	// Notifier sizing
	Symphony.Elements.window.on('resize.admin', function() {
		Symphony.Elements.header.find('.notifier').trigger('resize.notify');
	});

	// Table sizing
	Symphony.Elements.window.on('resize.admin table.admin', function() {
		var table = Symphony.Elements.contents.find('table:first');

		// Fix table size, if width exceeds the visibile viewport area.
		if(table.width() > Symphony.Elements.html.width()){
			table.addClass('fixed');
		}
		else {
			table.removeClass('fixed');
		}
	}).trigger('table.admin');

	// Orderable tables
	var oldSorting = null,
		orderable = Symphony.Elements.contents.find('table.orderable[data-interactive]');

	// Ignore tables with less than two rows
	orderable = orderable.filter(function() {
		return ($(this).find('tbody tr').length > 1);
	});

	// Initalise ordering
	orderable.symphonyOrderable({
			items: 'tr',
			handles: 'td'
		})
		.on('orderstart.orderable', function() {

			// Store current sort order
			oldSorting = $(this).find('input').map(function(e) { return this.name + '=' + (e + 1); }).get().join('&');
		})
		.on('orderstop.orderable', function() {
			var newSorting = orderable.find('input').map(function(e) { return this.name + '=' + (e + 1); }).get().join('&');

			// Store sort order, if changed
			orderable.addClass('busy');
			if(oldSorting !== null && newSorting !== oldSorting) {

				// Update items
				orderable.trigger('orderupdate.admin');

				// Add XSRF token
				newSorting = newSorting + '&' + Symphony.Utilities.getXSRF(true);

				// Send request
				$.ajax({
					type: 'POST',
					url: Symphony.Context.get('symphony') + '/ajax/reorder' + Symphony.Context.get('route'),
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

	// Suggest
	if(orderable.length) {
		Symphony.Elements.breadcrumbs.append('<p class="inactive"><span> – ' + Symphony.Language.get('drag to reorder') + '</span></p>');
	}

	// With Selected
	Symphony.Elements.contents.find('fieldset.apply').each(function() {
		var applicable = $(this),
			selection = Symphony.Elements.contents.find('table.selectable'),
			select = applicable.find('select'),
			button = applicable.find('button');

		// Set menu status
		if(selection.length > 0) {
			selection.on('select.selectable deselect.selectable check.selectable', 'tbody tr', function() {

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
			button.on('click.admin', function() {
				if(applicable.is('.inactive')) {
					return false;
				}
			});
		}
	});

	// Confirm actions
	Symphony.Elements.contents.add(Symphony.Elements.context).on('click.admin', 'button.confirm', function() {
		var message = $(this).attr('data-message') || Symphony.Language.get('Are you sure you want to proceed?');

		return confirm(message);
	});

	// Confirm with selected actions
	Symphony.Elements.contents.find('> form').on('submit.admin', function() {
		var select = $('select[name="with-selected"]'),
			option = select.find('option:selected'),
			message = option.attr('data-message') ||  Symphony.Language.get('Are you sure you want to proceed?');

		// Needs confirmation
		if(option.is('.confirm')) {
			return confirm(message);
		}
	});

	// Catch all JavaScript errors and write them to the Symphony Log
	Symphony.Elements.window.on('error.admin', function(event) {
		$.ajax({
			type: 'POST',
			url: Symphony.Context.get('symphony') + '/ajax/log/',
			data: {
				'error': event.originalEvent.message,
				'url': event.originalEvent.filename,
				'line': event.originalEvent.lineno,
				'xsrf': Symphony.Utilities.getXSRF()
			}
		});
	});
});

Symphony.View.add('/publish/:context*:', function() {

	// Filtering
	Symphony.Interface.Filtering.init();

	// Pagination
	Symphony.Elements.contents.find('.page').each(function() {
		var pagination = $(this),
			form = pagination.find('form'),
			jump = form.find('input'),
			active = jump.attr('data-active'),
			inactive = jump.attr('data-inactive'),
			helper = $('<span />').appendTo(form),
			width;

		// Measure placeholder text
		width = Math.max(helper.text(active).width(), helper.text(inactive).width());
		jump.width(width + 20);
		helper.remove();

		// Set current page
		jump.val(inactive);

		// Display "Go to page …" placeholder
		form.on('mouseover.admin', function() {
			if(!form.is('.active') && jump.val() == inactive) {
				jump.val(active);
			}
		});

		// Display current page placeholder
		form.on('mouseout.admin', function() {
			if(!form.is('.active') && jump.val() == active) {
				jump.val(inactive);
			}
		});

		// Edit page number
		jump.on('focus.admin', function() {
			if(jump.val() == active) {
				jump.val('');
			}
			form.addClass('active');
		});

		// Stop editing page number
		jump.on('blur.admin', function() {

			// Clear errors
			if(form.is('.invalid') || jump.val() === '') {
				form.removeClass('invalid');
				jump.val(inactive);
			}

			// Deactivate
			if(jump.val() == inactive) {
				form.removeClass('active');
			}
		});

		// Validate page number
		form.on('submit.admin', function() {
			if(jump.val() > jump.attr('data-max')) {
				form.addClass('invalid');
				return false;
			}
		});
	});

	// Upload field destructors
	$('<em />', {
		text: Symphony.Language.get('Remove File'),
		on: {
			click: function(event) {
				event.preventDefault();

				var span = $(this).parent(),
					name = span.find('input').attr('name');

				span.empty().append('<input name="' + name + '" type="file">');
			}
		}
	}).appendTo('label.file:has(a) span.frame');

	// Calendars
	$('.field-date').each(function() {
		var field = $(this),
			datetime = Symphony.Context.get('datetime'),
			calendar;

		// Add calendar widget
		if(field.attr('data-interactive')) {
			calendar = new Symphony.Interface.Calendar();
			calendar.init(this);
		}

		// Add timezone offset information
		if(moment().utcOffset() !== datetime['timezone-offset']) {
			field.addClass('show-timezone');
		}
	});
});

Symphony.View.add('/:context*:/new', function() {
	Symphony.Elements.contents.find('input[type="text"], textarea').first().focus();
});

/*--------------------------------------------------------------------------
	Blueprints - Pages Editor
--------------------------------------------------------------------------*/

Symphony.View.add('/blueprints/pages/:action:/:id:/:status:', function() {
	// No core interactions yet
});

/*--------------------------------------------------------------------------
	Blueprints - Sections
--------------------------------------------------------------------------*/

Symphony.View.add('/blueprints/sections/:action:/:id:/:status:', function(action, id, status) {
	var duplicator = $('#fields-duplicator'),
		legend = $('#fields-legend'),
		expand, collapse, toggle;

	// Create toggle controls
	expand = $('<a />', {
		'class': 'expand',
		'text': Symphony.Language.get('Expand all')
	});
	collapse = $('<a />', {
		'class': 'collapse',
		'text': Symphony.Language.get('Collapse all')
	});
	toggle = $('<p />', {
		'class': 'help toggle'
	});

	// Add toggle controls
	toggle.append(expand).append('<br />').append(collapse).insertAfter(legend);

	// Toggle fields
	toggle.on('click.admin', 'a.expand, a.collapse', function toggleFields() {

		// Expand
		if($(this).is('.expand')) {
			duplicator.trigger('expandall.collapsible');
		}

		// Collapse
		else {
			duplicator.trigger('collapseall.collapsible');
		}
	});

	// Affix for toggle
	$('fieldset.settings > legend + .help').symphonyAffix();

	// Initialise field editor
	duplicator.symphonyDuplicator({
		orderable: true,
		collapsible: true,
		preselect: 'input'
	});

	// Load section list
	duplicator.on('constructshow.duplicator', '.instance', function() {
		var instance = $(this),
			sections = instance.find('.js-fetch-sections'),
			sectionsParent = sections.parent(),
			selected = [],
			options;

		if(sections.length) {
			options = sections.find('option').each(function() {
				selected.push(this.value);

				if(!isNaN(this.value)) {
					$(this).remove();
				}
			});

			$.ajax({
				type: 'GET',
				dataType: 'json',
				url: Symphony.Context.get('symphony') + '/ajax/sections/',
				success: function(result) {
					// offline DOM manipulation
					sections.detach();

					if(result.sections.length) {
						sections.prop('disabled', false);
					}
					var options = $();

					if (!sections.attr('data-required')) {
						// Allow de-selection, if permitted
						options = options.add($('<option />', {
							text: Symphony.Language.get('None'),
							value: ''
						}));
					}

					// Append sections
					$.each(result.sections, function(index, section) {
						var optgroup = $('<optgroup />', {
							label: section.name
						});
						options = options.add(optgroup);
						// Append fields
						$.each(section.fields, function(index, field) {
							var option = $('<option />', {
								value: field.id,
								text: field.name
							}).appendTo(optgroup);

							if($.inArray(field.id, selected) > -1) {
								option.prop('selected', true);
							}
						});
					});
					sections.append(options);
					sectionsParent.append(sections);
					sections.trigger('change.admin');
				}
			});
		}
	});
	duplicator.find('.instance').trigger('constructshow.duplicator');

	// Focus first input
	duplicator.on('constructshow.duplicator expandstop.collapsible', '.instance', function() {
		var item = $(this);
		if (!item.hasClass('js-animate-all')) {
			$(this).find('input:visible:first').trigger('focus.admin');
		}
	});

	// Update name
	duplicator.on('blur.admin input.admin', '.instance input[name*="[label]"]', function() {
		var label = $(this),
			value = label.val();

		// Empty label
		if(value === '') {
			value = Symphony.Language.get('Untitled Field');
		}

		// Update title
		label.parents('.instance').find('.frame-header strong').text(value);
	});

	// Update location
	duplicator.on('change.admin', '.instance select[name*="[location]"]', function() {
		var select = $(this);

		select.parents('.instance').find('.frame-header').removeClass('main').removeClass('sidebar').addClass(select.val());
	});

	// Update requirements
	duplicator.on('change.admin', '.instance input[name*="[required]"]', function() {
		var checkbox = $(this),
			headline = checkbox.parents('.instance').find('.frame-header h4');

		// Is required
		if(checkbox.is(':checked')) {
			$('<span />', {
				class: 'required',
				text: '— ' + Symphony.Language.get('required')
			}).appendTo(headline);
		}

		// Is not required
		else {
			headline.find('.required').remove();
		}
	});
	duplicator.find('.instance input[name*="[required]"]').trigger('change.admin');

	// Update select field
	duplicator.on('change.admin', '.instance select[name*="[dynamic_options]"]', function() {
		$(this).parents('.instance').find('[data-condition=associative]').toggle($.isNumeric(this.value));
	}).trigger('change.admin');

	// Update tag field
	duplicator.on('change.admin', '.instance select[name*="[pre_populate_source]"]', function() {
		var selected = $(this).val(),
			show = false;
		
		if(selected) {
			selected = jQuery.grep(selected, function(value) {
				return value != 'existing';
			});

			show = (selected.length > 0);
		}

		$(this).parents('.instance').find('[data-condition=associative]').toggle(show);
	}).trigger('change.admin');

	// Remove field
	duplicator.on('destructstart.duplicator', function(event) {
		var target = $(event.target),
			item = target.clone(),
			title = item.find('.frame-header strong').text(),
			type = item.find('.frame-header span').text(),
			index = target.index(),
			id = new Date().getTime();

		// Offer undo option after removing a field
		Symphony.Elements.header.find('div.notifier').trigger('attach.notify', [
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
				list = $('#fields-duplicator');

			// Add field
			list.parent().removeClass('empty');
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
	duplicator.on('orderstop.orderable', function() {
		Symphony.Elements.header.find('.undo').trigger('detach.notify');
	});

	// Highlight instances with the same location when ordering fields
	duplicator.on('orderstart.orderable', function(event, item) {
		var duplicator = $(this),
			header = item.find('.frame-header'),
			position = (header.is('.main') ? 'main' : 'sidebar');

		duplicator.find('li:has(.' + position + ')').not(item).addClass('highlight');
	});

	duplicator.on('orderstop.orderable', function() {
		$(this).find('li.highlight').removeClass('highlight');
	});

	// Restore collapsible states for new sections
	if(status === 'created') {
		var fields = duplicator.find('.instance'),
			storageId = Symphony.Context.get('context-id');

		storageId = storageId.split('.');
		storageId.pop();
		storageId = 'symphony.collapsible.' + storageId.join('.') + '.0.collapsed';

		if(Symphony.Support.localStorage === true && window.localStorage[storageId]) {
			$.each(window.localStorage[storageId].split(','), function(index, value) {
				var collapsed = duplicator.find('.instance').eq(value);
				if(collapsed.has('.invalid').length == 0) {
					collapsed.trigger('collapse.collapsible', [0]);
				}
			});

			window.localStorage.removeItem(storageId);
		}
	}
});

/*--------------------------------------------------------------------------
	Blueprints - Datasource Editor
--------------------------------------------------------------------------*/

Symphony.View.add('/blueprints/datasources/:action:/:id:/:status:/:*:', function(action) {
	if(!action) return;

	var context = $('#ds-context'),
		source = $('#ds-source'),
		name = Symphony.Elements.contents.find('input[name="fields[name]"]').attr('data-updated', 0),
		nameChangeCount = 0,
		params = Symphony.Elements.contents.find('select[name="fields[param][]"]'),
		pagination = Symphony.Elements.contents.find('.pagination'),
		paginationInput = pagination.find('input');

	// Update data source handle
	name.on('blur.admin input.admin', function updateDsHandle() {
		var current = nameChangeCount = nameChangeCount + 1,
		value = name.val();

		setTimeout(function fetchDsHandle(nameChangeCount, current, value) {
			if(nameChangeCount == current) {
				$.ajax({
					type: 'GET',
					data: { 'string': value },
					dataType: 'json',
					url: Symphony.Context.get('symphony') + '/ajax/handle/',
					success: function(result) {
						if(nameChangeCount == current) {
							name.data('handle', result.handle);
							params.trigger('update.admin');
						}
					}
				});
			}
		}, 500, nameChangeCount, current, value);
	});

	// Update output parameters
	params.on('update.admin', function updateDsParams() {
		var handle = name.data('handle') || Symphony.Language.get('untitled');

		// Process parameters
		if(parseInt(name.attr('data-updated')) !== 0) {
			params.find('option').each(function updateDsParam() {
				var param = $(this),
					field = param.attr('data-handle');

				// Set parameter
				param.text('$ds-' + handle + '.' + field);
			});
		}

		// Updated
		name.attr('data-updated', 1);
	}).trigger('update.admin');

	// Data source manager options
	Symphony.Elements.contents.find('.contextual select optgroup').each(function() {
		var optgroup = $(this),
			select = optgroup.parents('select'),
			label = optgroup.attr('data-label'),
			options = optgroup.remove().find('option').addClass('optgroup');

		// Show only relevant options based on context
		context.on('change.admin', function() {
			var option = $(this).find('option:selected'),
				context = option.attr('data-context') || 'section-' + option.val();

			if(context == label) {
				select.find('option.optgroup').remove();
				select.append(options.clone(true));
			}
		});
	});

	// Data source manager context
	context.on('change.admin', function() {
		var optgroup = context.find('option:selected').parent(),
			label = optgroup.attr('data-label') || optgroup.attr('label'),
			reference = context.find('option:selected').attr('data-context') || 'section-' + context.val(),
			components = Symphony.Elements.contents.find('.contextual');

		// Store context
		source.val(context.val());

		// Show only relevant interface components based on context
		components.addClass('irrelevant');
		components.filter('[data-context~=' + label + ']').removeClass('irrelevant');
		components.filter('[data-context~=' + reference + ']').removeClass('irrelevant');

		// Make sure parameter names are up-to-date
		Symphony.Elements.contents.find('input[name="fields[name]"]').trigger('blur.admin');
	}).trigger('change.admin');

	// Toggle pagination
	Symphony.Elements.contents.find('input[name*=paginate_results]').on('change.admin', function() {
		var disabled = !$(this).is(':checked');
		paginationInput.prop('disabled', disabled);
	}).trigger('change.admin');

	// Enabled fields on submit
	Symphony.Elements.contents.find('> form').on('submit.admin', function() {
		paginationInput.prop('disabled', false);
	});

	// Enable parameter suggestions
	Symphony.Elements.contents.find('.filters-duplicator, .ds-param').each(function() {
		Symphony.Interface.Suggestions.init(this, 'input[type="text"]');
	});

	// Toggle filter help
	Symphony.Elements.contents.find('.filters-duplicator').on('input.admin change.admin', 'input', function toggleFilterHelp(event) {
		var item = $(event.target).parents('.instance'),
			value = event.target.value,
			filters = item.data('filters'),
			help = item.find('.help');

		// Handle values that don't contain predicates
		var filter = value.search(/:/)
			? $.trim(value.split(':')[0])
			: $.trim(value);

		// Store filters
		if(!filters) {
			filters = {};
			item.find('.tags li').each(function() {
				var val = $.trim(this.getAttribute('data-value'));
				if (val.search(/:/)) {
					val = val.slice(0, -1);
				}
				filters[val] = this.getAttribute('data-help');
			});

			item.data('filters', filters);
		}

		// Filter help
		if(filters[filter]) {
			help.html(filters[filter]);
		}
	});
});

/*--------------------------------------------------------------------------
	Blueprints - Event Editor
--------------------------------------------------------------------------*/

Symphony.View.add('/blueprints/events/:action:/:name:/:status:/:*:', function() {
	var context = $('#event-context'),
		source = $('#event-source'),
		filters = $('#event-filters'),
		form = Symphony.Elements.contents.find('> form'),
		name = Symphony.Elements.contents.find('input[name="fields[name]"]').attr('data-updated', 0),
		nameChangeCount = 0;

	// Update event handle
	name.on('blur.admin input.admin', function updateEventHandle() {
		var current = nameChangeCount = nameChangeCount + 1;

		setTimeout(function checkEventHandle(nameChangeCount, current) {
			if(nameChangeCount == current) {
				Symphony.Elements.contents.trigger('update.admin');
			}
		}, 500, nameChangeCount, current);
	});

	// Change context
	context.on('change.admin', function changeEventContext() {
		source.val(context.val());
		Symphony.Elements.contents.trigger('update.admin');
	}).trigger('change.admin');

	// Change filters
	filters.on('change.admin', function changeEventFilters() {
		Symphony.Elements.contents.trigger('update.admin');
	});

	// Update documentation
	Symphony.Elements.contents.on('update.admin', function updateEventDocumentation() {
		if(name.val() == '') {
			$('#event-documentation').empty();
		}
		else {
			$.ajax({
				type: 'GET',
				data: {
					'section': context.val(),
					'filters': filters.serializeArray(),
					'name': name.val()
				},
				dataType: 'html',
				url: Symphony.Context.get('symphony') + '/ajax/eventdocumentation/',
				success: function(documentation) {
					$('#event-documentation').replaceWith(documentation);
				}
			});
		}
	});
});

/*--------------------------------------------------------------------------
	System - Authors
--------------------------------------------------------------------------*/

Symphony.View.add('/system/authors/:action:/:id:/:status:', function(action, id, status) {
	var password = $('#password');

	// Add change password overlay
	if(!password.has('.invalid').length && id && !status) {
		var overlay = $('<div class="password" />'),
			frame = $('<span class="frame centered" />'),
			button = $('<button />', {
				text: Symphony.Language.get('Change Password'),
				on: {
					click: function(event) {
						event.preventDefault();
						overlay.hide();
					}
				}
			}).attr('type', 'button');

		frame.append(button);
		overlay.append(frame);
		overlay.insertBefore(password);
	}

	// Focussed UI for password reset
	if(status == 'reset-password') {
		var fieldsets = Symphony.Elements.contents.find('fieldset'),
			essentials = fieldsets.eq(0),
			login = fieldsets.eq(1),
			legend = login.find('> legend');

		essentials.hide();
		login.children().not('legend, #password').hide();

		$('<p />', {
			class: 'help',
			text: Symphony.Language.get('Please reset your password')
		}).insertAfter(legend);
	}

	// Highlight confirmation promt
	Symphony.Elements.contents.find('input, select').on('change.admin input.admin', function() {
		$('#confirmation').addClass('highlight');
	});
});

/*--------------------------------------------------------------------------
	System - Extensions
--------------------------------------------------------------------------*/
Symphony.View.add('/system/extensions/:context*:', function() {
	Symphony.Language.add({
		'Enable': false,
		'Install': false,
		'Update': false
	});

	// Update controls contextually
	Symphony.Elements.contents.find('.actions select').on('click.admin focus.admin', function(event) {
		var selected = Symphony.Elements.contents.find('tr.selected'),
			canUpdate = selected.filter('.extension-can-update').length,
			canInstall = selected.filter('.extension-can-install').length,
			canEnable = selected.length - canUpdate - canInstall,
			control = Symphony.Elements.contents.find('.actions option[value="enable"]'),
			label = [];

		if(canEnable) {
			label.push(Symphony.Language.get('Enable'));
		}
		if(canUpdate) {
			label.push(Symphony.Language.get('Update'));
		}
		if(canInstall) {
			label.push(Symphony.Language.get('Install'));
		}

		control.text(label.join('/'));
	});
});

})(window.jQuery, window.Symphony);
