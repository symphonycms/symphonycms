/**
 * @package assets
 */

(function($) {

	/**
	 * Convert absolute to relative dates.
	 *
	 * @name $.symphonyTimeAgo
	 * @class
	 *
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {String} [options.items='time'] Selector to find the absolute date
	 * @param {String} [options.timestamp='datetime'] Attribute of `object.items` representing the timestamp of the given date
	 *
	 * @example

			$('.notifier').symphonyTimeAgo();
	 */
	$.fn.symphonyTimeAgo = function(options) {
		var objects = this,
			settings = {
				items: 'time',
				timestamp: 'datetime'
			};

		$.extend(settings, options);

	/*-----------------------------------------------------------------------*/

		function parse(item) {
			var timestamp = item.data('timestamp'),
				datetime, date, now;

			// Fetch stored timestamp
			if($.isNumeric(timestamp)) {
				return timestamp;
			}

			// Parse date
			else {
				datetime = item.attr(settings.timestamp);

				// Defined date and time
				if(datetime) {

					// Parse ISO 8601
					date = datetime.split(/[-T:+]/);
					timestamp = new Date(date[0], date[1] - 1, date[2], date[3], date[4], date[5]);
				}

				// Undefined date and time
				else {
					now = new Date();
					timestamp = now.getTime();
				}

				// Store and return timestamp
				item.data('timestamp', timestamp);
				return timestamp;
			}
		}

		function say(from, to) {

			// Calculate time difference
			var distance = to - from,

			// Convert time to minutes
			time = Math.floor(distance / 60000);

			// Return relative date based on passed time
			if(time < 1) {
				return Symphony.Language.get('just now');
			}
			if(time < 2) {
				return Symphony.Language.get('a minute ago');
			}
			if(time < 45) {
				return Symphony.Language.get('{$minutes} minutes ago', {
					'minutes': time
				});
			}
			if(time < 90) {
				return Symphony.Language.get('about 1 hour ago');
			}
			else {
				return Symphony.Language.get('about {$hours} hours ago', {
					'hours': Math.floor(time / 60)
				});
			}
		};

	/*-----------------------------------------------------------------------*/

		objects.find(settings.items).each(function timeago() {
			var item = $(this),
				from = parse(item),
				to = new Date();

			// Set relative time
			item.text(say(from, to));
		});

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(jQuery.noConflict());
