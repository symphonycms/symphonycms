/**
 * @package assets
 */

(function($) {

	/**
	 * This plugin inserts tags from a list into an input field. It offers three modes:
	 * singular - allowing only one tag at a time
	 * multiple - allowing multiple tags, comma separated
	 * inline - which adds tags at the current cursor position
	 *
	 * @param {Object} custom_settings
	 *  An object specifying the tag list items
	 */
	$.fn.symphonyTags = function(custom_settings) {
		var objects = this,
			settings = {
				items: 'li'
			};

		$.extend(settings, custom_settings);

	/*-----------------------------------------------------------------------*/

		return objects.delegate(settings.items, 'click.tags', function(event) {
			var item = $(this),
				object = item.parent(),
				input = object.prev().find('input'),
				value = input.val(),
				tag = item.attr('class') || item.text();

			// Singular
			if(object.is('.singular')) {
				input.val(tag);
			}

			// Inline
			else if(object.is('.inline')) {
				var start = input[0].selectionStart,
					end = input[0].selectionEnd,
					position = 0;

				// Insert tag
				if(start > 0) {
					input.val(value.substring(0, start) + tag + value.substring(end, value.length));
					position = start + tag.length;
				}

				// Append tag
				else {
					input.val(value + tag);
					position = value.length + tag.length;
				}

				// Reset cursor position
				input[0].selectionStart = position;
				input[0].selectionEnd = position;
			}

			// Multiple
			else {
				var exp = new RegExp('^' + tag + '$', 'i'),
					tags = value.split(/,\s*/),
					removed = false;

				// Check existing tags
				for(var index in tags) {

					// Remove existing tag
					if(tags[index].match(exp)) {
						tags.splice(index, 1);
						removed = true;
					}

					// Remove empty tags
					else if(tags[index] == '') {
						tags.splice(index, 1);
					}
				}

				// Add new tag
				if(removed === false) {
					tags.push(tag);
				}

				// Save tags
				input.val(tags.join(', '));
			}
		});
	};

})(jQuery.noConflict());
