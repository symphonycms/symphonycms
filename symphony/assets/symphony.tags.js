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
		var objects = this;
		var settings = {
			items:				'li'
		};

		$.extend(settings, custom_settings);

	/*-----------------------------------------------------------------------*/

		objects = objects.map(function() {
			var object = $(this);
			
			object.find(settings.items).bind('click', function() {

				var input = $(this).parent().prevAll('label').find('input')[0];
				var tag = this.className || $(this).text();

				if(input === undefined) {
					input = $(this).parent().prevAll('#error').find('label input')[0]
				}

				input.focus();

				// Singular
				if (object.hasClass('singular')) {
					input.value = tag;
				}

				// Inline
				else if (object.hasClass('inline')) {
					var start = input.selectionStart;
					var end = input.selectionEnd;

					if (start >= 0) {
						input.value = input.value.substring(0, start) + tag + input.value.substring(end, input.value.length);
					}

					else {
						input.value += tag;
					}

					input.selectionStart = start + tag.length;
					input.selectionEnd = start + tag.length;
				}

				// Multiple
				else {
					var exp = new RegExp('^' + tag + '$', 'i');
					var tags = input.value.split(/,\s*/);
					var removed = false;

					for (var index in tags) {
						if (tags[index].match(exp)) {
							tags.splice(index, 1);
							removed = true;
						}

						else if (tags[index] == '') {
							tags.splice(index, 1);
						}
					}

					if (!removed) tags.push(tag);

					input.value = tags.join(', ');
				}
			});

			return object;
		});

		return objects;
	};

})(jQuery.noConflict());
