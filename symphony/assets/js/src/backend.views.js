/**
 * Symphony backend views
 *
 * @package assets
 */

(function($) {

/*--------------------------------------------------------------------------
	General backend view
--------------------------------------------------------------------------*/

Symphony.View.add('/symphony/:context*:', function() {

	// Initialise core plugins
	Symphony.Elements.contents.find('.filters-duplicator').symphonyDuplicator();
	Symphony.Elements.contents.find('.tags').symphonyTags();
	Symphony.Elements.contents.find('select.picker').symphonyPickable();
	Symphony.Elements.contents.find('ul.orderable').symphonyOrderable();
	Symphony.Elements.contents.find('table.selectable').symphonySelectable();
	Symphony.Elements.wrapper.find('div.drawer').symphonyDrawer();
	Symphony.Elements.header.symphonyNotify();

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
	var oldSorting = null;
	Symphony.Elements.contents.find('table.orderable')
		.symphonyOrderable({
			items: 'tr',
			handles: 'td'
		})
		.on('orderstart.orderable', function() {

			// Store current sort order
			oldSorting = $(this).find('input').map(function(e) { return this.name + '=' + (e + 1); }).get().join('&');
		})
		.on('orderstop.orderable', function() {
			var orderable = $(this).addClass('busy'),
				newSorting = orderable.find('input').map(function(e) { return this.name + '=' + (e + 1); }).get().join('&');

			// Store sort order, if changed
			if(oldSorting !== null && newSorting !== oldSorting) {

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

	// Pagination
	Symphony.Elements.contents.find('.pagination').each(function() {
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

	// Catch all JavaScript errors and write them to the Symphony Log
	Symphony.Elements.window.on('error.admin', function(event) {
		$.ajax({
			type: 'POST',
			url: Symphony.Context.get('root') + '/symphony/ajax/log/',
			data: {
				'error': event.originalEvent.message,
				'url': event.originalEvent.filename,
				'line': event.originalEvent.lineno
			}
		});

		return false;
	});
});

Symphony.View.add('/symphony/:context*:/new', function() {
	Symphony.Elements.contents.find('input[type="text"], textarea').first().focus();
});

/*--------------------------------------------------------------------------
	Blueprints - Pages Editor
--------------------------------------------------------------------------*/

Symphony.View.add('/symphony/blueprints/pages/:action:/:id:/:status:', function() {
	// No core interactions yet
});

/*--------------------------------------------------------------------------
	Blueprints - Sections
--------------------------------------------------------------------------*/

Symphony.View.add('/symphony/blueprints/sections/:action:/:id:/:status:', function() {
	var duplicator = $('#fields-duplicator'),
		legend = $('#fields-legend'),
		expand, collapse, toggle;

	// Create toggle controls
	expand = $('<a />', {
		'class': 'expand',
		'text': Symphony.Language.get('Expand all fields')
	});
	collapse = $('<a />', {
		'class': 'collapse',
		'text': Symphony.Language.get('Collapse all fields')
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

	// Initialise field editor	
	duplicator.symphonyDuplicator({
		orderable: true,
		collapsible: (Symphony.Context.get('env')[0] !== 'new'),
		preselect: 'input'
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
});

/*--------------------------------------------------------------------------
	Blueprints - Datasource Editor
--------------------------------------------------------------------------*/

Symphony.View.add('/symphony/blueprints/datasources/:action:/:id:/:status:', function() {
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
					url: Symphony.Context.get('root') + '/symphony/ajax/handle/',
					success: function(result) {
						if(nameChangeCount == current) {
							name.data('handle', result);
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
				param.text('$-' + handle + '.' + field);
			});
		}

		// Updated
		name.attr('data-updated', 1);
	}).trigger('update.admin');

	// Data source manager options
	Symphony.Elements.contents.find('select optgroup').each(function() {
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
		var enabled = $(this).is(':checked');
		paginationInput.prop('disabled', enabled);
	}).trigger('change.admin');

	// Enabled fields on submit
	Symphony.Elements.contents.find('> form').on('submit.admin', function() {
		paginationInput.prop('disabled', false);
	});

	// Enable parameter suggestions
	Symphony.Elements.contents.find('.filters-duplicator').symphonySuggestions();
	pagination.symphonySuggestions();
	Symphony.Elements.contents.find('label:has(input[name*="url_param"])').symphonySuggestions({
		trigger: '$',
		source: '/symphony/ajax/parameters/?filter=page&template=$%s'
	});
});

/*--------------------------------------------------------------------------
	Blueprints - Event Editor
--------------------------------------------------------------------------*/

Symphony.View.add('/symphony/blueprints/events/:action:/:name:/:status:', function() {
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
	});

	// Change filters
	filters.on('change.admin', function changeEventFilters() {
		Symphony.Elements.contents.trigger('update.admin');
	});

	// Update documentation
	Symphony.Elements.contents.on('update.admin', function updateEventDocumentation() {
		$.ajax({
			type: 'POST',
			data: { 
				'section': context.val(),
				'filters': filters.serializeArray(),
				'name': name.val()
			},
			dataType: 'html',
			url: Symphony.Context.get('root') + '/symphony/ajax/eventdocumentation/',
			success: function(documentation) {
				$('#event-documentation').replaceWith(documentation);
			}
		});
	});
});

/*--------------------------------------------------------------------------
	System - Authors
--------------------------------------------------------------------------*/

Symphony.View.add('/symphony/system/authors/:action:/:id:/:status:', function(action, id) {
	var password = $('#password');

	// Add change password overlay
	if(!password.has('.invalid').length && id) {
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
			});

		frame.append(button);
		overlay.append(frame);
		overlay.insertBefore(password);
	}
});

})(window.jQuery);
