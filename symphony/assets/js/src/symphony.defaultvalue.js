/**
 * @package assets
 */

(function($, Symphony) {

	/**
	 * Fills the target input/textarea with a value from the source element.
	 * The plugin cease to change the value when the target is edited by the user and has a value.
	 *
	 * @name $.symphonyDefaultValue
	 * @class
	 *
	 * @param {Object} options An object specifying containing the attributes specified below
	 * @param {String} [options.sourceElement='.js-defaultvalue-source'] Selector to find the default value
	 * @param {String} [options.sourceEvent='select'] The event that triggers setting the value in the target element
	 * @param {String} [options.targetEvent='keyup blur'] The event(s) to watch for user interaction
	 *
	 * @example

			$('.js-defaultvalue-target').symphonyDefaultValue();
	 */
	$.fn.symphonyDefaultValue = function(options) {
		var objects = this,
			isOn = false,
			settings = {
				sourceElement: '.js-defaultvalue-source',
				sourceEvent: 'change',
				targetEvent: 'keyup blur'
			};

		$.extend(settings, options);

		// append our namespace on the sourceEvent
		settings.sourceEvent += '.symphony-defaultvalue';

		var source = $(settings.sourceElement);

		var getTargetValue = function () {
			return objects.val();
		};

		var setTargetValue = function (val) {
			objects.val(val);
		};

		var getSourceValue = function () {
			return source.find('option:selected').text();
		};

		var sourceChanged = function (e) {
			if (isOn) {
				setTargetValue(getSourceValue());
			}
		};

		var on = function () {
			if (isOn) {
				return;
			}
			source.on(settings.sourceEvent, sourceChanged);
			isOn = true;
		};

		var off = function () {
			if (!isOn) {
				return;
			}
			$(settings.sourceElement).off(settings.sourceEvent);
			isOn = false;
		};

	/*-------------------------------------------------------------------------
		Initialisation
	-------------------------------------------------------------------------*/

		objects.on(settings.targetEvent, function (e) {
			if (!getTargetValue()) {
				on();
			}
			else {
				off();
			}
		});

		if (!getTargetValue()) {
			on();
		}

	/*-----------------------------------------------------------------------*/

		return objects;
	};

})(window.jQuery, window.Symphony);
