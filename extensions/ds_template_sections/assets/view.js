jQuery(document).ready(function() {
	jQuery('#context').bind('change', function() {
		jQuery('.context').hide().filter('.context-' + this.value).show();
	}).change();
});