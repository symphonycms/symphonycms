/*-----------------------------------------------------------------------------
	Tags plugin
-----------------------------------------------------------------------------*/

	jQuery.fn.symphonyTags = function(custom_settings) {
		var objects = this;
		var settings = {
			items:				'li'
		};

		jQuery.extend(settings, custom_settings);

	/*-----------------------------------------------------------------------*/

		objects = objects.map(function() {
			var object = jQuery(this);

			object.find(settings.items).live('click', function() {

				var input = jQuery(this).parent().prevAll('label').find('input')[0];
				var tag = this.className || jQuery(this).text();

				if(input === undefined) {
					input = jQuery(this).parent().prevAll('#error').find('label input')[0]
				}

				input.focus();

				if (object.hasClass('singular')) {
					input.value = tag;
				}

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

/*---------------------------------------------------------------------------*/
