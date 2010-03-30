jQuery(document).ready(function() {
	var options = '';
	var matches = location.href.match(/\?filter=(([^:]+):(.*))?/);
	var field = ''; var value = '';
	
	var regex = false;
	var regex_prefix = 'regexp';
	var comparison_options = '';
	var comparisons = ['contains', 'is'];
	
	var selected_field = null;
	
	if (matches && matches[3] != undefined) {
		field = decodeURI(matches[2]);
		value = decodeURI(matches[3]);
	}
	
	if (value.indexOf(regex_prefix) != -1) {
		regex = true;
		value = value.replace(/regexp:/,'');
	}
	
	for (var item in filters) {
		var selected = '';
		var handle = filters[item].handle;
		if (field == handle) {
			selected = ' selected="selected"';
			selected_field = filters[item];
		}
		
		options += '<option' + selected + ' value="' + handle + '">' + item + '</option>';
	}	
	
	for (var i = 0; i < comparisons.length; i++) {
		var selected = '';
		
		if (comparisons[i] == 'contains' && regex) selected = ' selected="selected"';
		if (comparisons[i] == 'is' && !regex && value) selected = ' selected="selected"';
		
		comparison_options += '<option' + selected + '>' + comparisons[i] + '</option>';
	}
	
	function buildValueControl() {
		
		if (selected_field && selected_field.options) {
			
			jQuery('.filters select.match').val('is');
			
			var select = '<select name="value" class="value">';
			
			for (var i=0; i < selected_field.options.length; i++) {
				var selected = '';
				var option_label = selected_field.options[i].label ? selected_field.options[i].label : selected_field.options[i];
				var option_value = selected_field.options[i].value ? selected_field.options[i].value : selected_field.options[i];
				
				if (option_value == value) selected = ' selected="selected"';
				
				select += '<option value="' + option_value + '"' + selected + '>' + option_label + '</option>';
			}
			
			select += '</select>';
			return select;
			
		} else {
			jQuery('.filters select.match').val('contains');
			return '<input class="value" name="value" value="' + value + '" />';
		}
		
	}
	
	jQuery('h2').after('\
		<div id="view-options">\
			<form class="filters" method="POST" action="">\
				<label>Filter by</label>\
				<select class="field" name="field">' + options + '</select>\
				<select class="match" name="match">' + comparison_options + '</select>' + buildValueControl() + '<input class="apply" type="submit" value="' + filters_apply + '" />\
				<input class="clear" type="button" value="' + filters_clear + '" />\
			</form>\
		</div>\
	');
	
	jQuery('.filters select').change(function() {
		jQuery('.filters .value').focus();
	});
	
	jQuery('.filters select.field').change(function() {
		var value = jQuery(this).attr('value');
		for(var item in filters) {
			if (value == filters[item].handle) selected_field = filters[item];
		}
		
		jQuery('.filters .value').remove();
		jQuery('.filters .match').after(buildValueControl());
		jQuery('.filters .value').focus();
	});
	
	jQuery('.filters .clear').click(function() {
		location.href = location.href.replace(/\?.*/, '');
		
		return false;
	});
	
	jQuery('.filters').submit(function() {
		var self = jQuery(this);
		var field = self.find('.field').val();
		var value = self.find('.value').val();

		if (field && value) {
			var href = '?filter=' + encodeURI(field) + ':';
			if (self.find('.match').val() == 'contains') href += regex_prefix + ':';
			href += encodeURI(value);
			location.href = href;
		}
		
		return false;
	});
	
	var pagination = jQuery('ul.page');
	var count = 0;
	if (pagination.length) {
		var title = pagination.find("li[title~='Viewing']").attr('title');
		count = parseInt(title.split('of')[1].replace(/^\s+|\s+jQuery/g,''));
	} else {
		// if there are no entries, there will be one row but its first td will be inactive
		// so we count all *but* this instance
		count = jQuery('tbody tr:not(:has(td:first.inactive))').length;
	}
	
	var h2 = document.getElementsByTagName('h2')[0];
	var index = 1;
	if (h2.childNodes[0].nodeType == 3) index = 0;
	h2.childNodes[index].nodeValue += ' (' + count + ' entr' +  ((count == 1) ? 'y' : 'ies') + ')';
	
});
