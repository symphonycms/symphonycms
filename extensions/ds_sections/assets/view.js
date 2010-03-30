jQuery(document).ready(function() {
	var update = function() {
		jQuery('.context').hide().filter('.context-' + this.value).show();
	};
	
	jQuery('#context').bind('change', update).bind('keyup', update).change();
	
	// Cleanup old contexts:
	jQuery('form').bind('submit', function() {
		jQuery('.context:not(:visible)').remove();
	});
});