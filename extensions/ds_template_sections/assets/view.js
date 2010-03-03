jQuery(document).ready(function() {
	jQuery('#context').bind('change', function() {
		jQuery('.context').hide().filter('.context-' + this.value).show();
	}).change();
	
	// Cleanup old contexts:
	jQuery('form').bind('submit', function() {
		jQuery('.context:not(:visible)').remove();
	});
});