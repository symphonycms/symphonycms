(function($, Symphony, moment) {
	'use strict';

	Symphony.Interface.Calendar = function() {
		var template = '<div class="calendar"><nav><a class="previous"></a><div class="switch"><ul class="months"><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul><ul class="years"><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul></div><a class="next"></a></nav><table><thead><tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr></thead><tbody><tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr></tbody></table></div>',
			context, calendar, storage, format, datetime;

		var init = function(element) {
			context = $(element);
			calendar = context.find('.calendar');
			storage = context.find('input');
			format = calendar.attr('data-format');
			datetime = moment.utc(storage.val(), format);

			// Set locale
			moment.locale(Symphony.Elements.html.attr('lang'));

			// Create calendar
			prepareTemplate();
			create();
		};

		var prepareTemplate = function() {
			template = $(template);
			
			renderTitles();
		};

		var create = function() {
			calendar.clndr({
				startWithMonth: datetime,
				showAdjacentMonths: true,
				adjacentDaysChangeMonth: true,
				forceSixRows: true,
				render: render,
				clickEvents: {
					click: select
				}			
			});	
		};

		var render = function(data) {
			var sheet = template.clone(),
				month = moment().month(data.month).get('month');

			sheet = renderMonths(sheet, data, month);
			sheet = renderYears(sheet, data);
			sheet = renderDays(sheet, data);

			return sheet.html();
		};

		var renderTitles = function(sheet, data) {
			template.find('thead td').each(function(index) {
				this.textContent = moment().day(index).format('dd');
			});
		};

		var renderMonths = function(sheet, data, month) {
			sheet.find('.months li').each(function(index) {
				this.textContent = moment().month(month - 6 + index).format('MMMM');
			});

			return sheet;
		};

		var renderYears = function(sheet, data) {
			sheet.find('.years li').each(function(index) {
				this.textContent = data.year - 6 + index;
			});

			return sheet;
		};

		var renderDays = function(sheet, data) {
			sheet.find('tbody td').each(function(index) {
				var date = data.days[index];

				this.textContent = date.day;
				this.setAttribute('class', date.classes);
			});

			return sheet;
		};

		var select = function(day) {
			var date = day.date;

			date.subtract(date.zone(), 'minutes');

			datetime.set('year', date.year());
			datetime.set('month', date.month());
			datetime.set('date', date.date());

			storage.val(datetime.format(format));
		};

		// API
		return {
			init: init
		};
	};

})(window.jQuery, window.Symphony, window.moment);
