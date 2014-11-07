(function($, Symphony, moment) {
	'use strict';

	Symphony.Interface.Calendar = function() {
		var template = '<div class="calendar"><nav><a class="clndr-previous-button">previous</a><div class="switch"><ul class="months"><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul><ul class="years"><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul></div><a class="clndr-next-button">next</a></nav><table><thead><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr></thead><tbody><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr></tbody></table></div>',
			context, calendar, storage, format, datetime, clndr;

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

			// Switch sheets
			calendar.on('click.calendar', '.switch', switchSheet);
			calendar.on('click.calendar', '.months li', switchMonth);
			calendar.on('click.calendar', '.years li', switchYear);
		};

		var prepareTemplate = function() {
			template = $(template);
			
			renderTitles();
		};

		var create = function() {
			clndr = calendar.clndr({
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

		var renderTitles = function() {
			template.find('thead td').each(function(index) {
				this.textContent = moment().day(index).format('dd').substr(0, 1);
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
			sheet.find('tbody td span').each(function(index) {
				var date = data.days[index];

				this.textContent = date.day;
				this.setAttribute('class', date.classes);

				if(date.date.isSame(datetime, 'day')) {
					this.classList.add('active');
				}
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

			calendar.find('.active').removeClass('active');
			day.element.classList.add('active');
		};

		var switchSheet = function(event) {
			var selector;
			
			event.stopPropagation();
			event.preventDefault();

			selector = calendar.find('.switch');
			selector.addClass('select');

			// Close selector
			Symphony.Elements.body.on('click.calendar', function(event) {
				var target = $(event.target);

				if(!target.parents('.switch').length) {
					selector.removeClass('select');
					Symphony.Elements.body.off('click.calendar');
				}
			});
		};

		var switchMonth = function(event) {
			clndr.setMonth(event.target.textContent);
		};

		var switchYear = function(event) {
			clndr.setYear(event.target.textContent);
		};

		// API
		return {
			init: init
		};
	};

})(window.jQuery, window.Symphony, window.moment);
