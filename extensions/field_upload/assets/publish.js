jQuery(document).ready(function() {
	jQuery('.field-advancedupload').each(function() {
		var field = jQuery(this);
		var upload = field.find('.upload');
		var details = field.find('.details');
		var list = details.find('dl');
		var clear = details.find('.clear a');
		var popup = details.find('.popup a');
		var image = details.find('img');
		
		if (details.length) {
			upload.hide();
			
			clear.bind('click', function() {
				var hidden = upload.find('input[type = "hidden"]');
				var file = jQuery('<input type="file" />');
				
				file.attr('name', hidden.attr('name'));
				file.appendTo(upload);
				hidden.remove();
				
				details.hide();
				upload.show();
			});
			
			details.bind('mouseenter', function() {
				image.fadeTo('fast', 0.4);
			});
			
			details.bind('mouseleave', function() {
				image.fadeTo('fast', 0.8);
			});
			
			if (image.length) popup.bind('click', function() {
				jQuery('<div class="field-advancedupload-overlay" />')
					.append(image.clone().fadeTo(0, 1))
					.appendTo('body')
					.hide().fadeIn('fast')
					.bind('click', function() {
						jQuery(this)
							.unbind('click')
							.fadeOut('fast', function() {
								jQuery(this).remove();
							});
					});
					
				return false;
			});
		}
	});
});