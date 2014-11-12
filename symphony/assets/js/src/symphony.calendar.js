/**
 * @package assets
 */

(function($, Symphony, moment) {
	'use strict';

	/**
	 * Symphony calendar interface.
	 */
	Symphony.Interface.Calendar = function() {
		var template = '<div class="calendar"><nav><a class="clndr-previous-button">previous</a><div class="switch"><ul class="months"><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul><ul class="years"><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul></div><a class="clndr-next-button">next</a></nav><table><thead><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr></thead><tbody><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr><tr><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td><td><span></span></td></tr></tbody></table></div>',
			context, calendar, storage, format, datetime, clndr;

		var init = function(element) {
			context = $(element);
			calendar = context.find('.calendar');
			storage = context.find('input');
			format = calendar.attr('data-format');

			// Set locale
			moment.locale(Symphony.Elements.html.attr('lang'));

			// Create calendar
			storeDate();
			prepareTemplate();
			create();

			/**
			 * Events
			 *
			 * Use `mousedown` instead of `click` event in order to prevent 
			 * conflicts with suggestions and other core plugins.
			 */

			// Switch sheets
			calendar.on('mousedown.calendar', 'a, .clndr', function(event) {
				event.stopPropagation();
				event.preventDefault();
			});
			calendar.on('mousedown.calendar', '.switch', switchSheet);
			calendar.on('mousedown.calendar', '.months li', switchMonth);
			calendar.on('mousedown.calendar', '.years li', switchYear);

			// Handle current date
			storage.on('focus.calendar', focusDate);
			storage.on('input.calendar', updateDate);
		};

		/**
		 * Load calendar template and add week day titles.
		 */
		var prepareTemplate = function() {
			template = $(template);
			
			renderTitles();
		};

		/**
		 * Create CLNDR instance.
		 */
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

		/**
		 * Render calendar sheet.
		 *
		 * @param object data
		 *  The CLDNR data object
		 */
		var render = function(data) {
			var sheet = template.clone(),
				month = moment().month(data.month).get('month');

			sheet = renderMonths(sheet, data, month);
			sheet = renderYears(sheet, data);
			sheet = renderDays(sheet, data);

			return sheet.html();
		};

		/**
		 * Render week day titles.
		 */
		var renderTitles = function() {
			template.find('thead td').each(function(index) {
				this.textContent = moment().day(index).format('dd').substr(0, 1);
			});
		};

		/**
		 * Render month selector.
		 *
		 * @param jQuery sheet
		 *  The calendar sheet jQuery object
		 * @param object data
		 *  The CLNDR data object
		 * @param integer month
		 *  The current month
		 */
		var renderMonths = function(sheet, data, month) {
			sheet.find('.months li').each(function(index) {
				this.textContent = moment().month(month - 6 + index).format('MMMM');
			});

			return sheet;
		};

		/**
		 * Render year selector.
		 *
		 * @param jQuery sheet
		 *  The calendar sheet jQuery object
		 * @param object data
		 *  The CLNDR data object
		 */
		var renderYears = function(sheet, data) {
			sheet.find('.years li').each(function(index) {
				this.textContent = data.year - 6 + index;
			});

			return sheet;
		};

		/**
		 * Render day in calendar sheet.
		 *
		 * @param jQuery sheet
		 *  The calendar sheet jQuery object
		 * @param object data
		 *  The CLNDR data object
		 */
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

		/**
		 * Select day in calendar.
		 *
		 * @param object day
		 *  The CLNDR day object
		 */
		var select = function(day) {
			var date = day.date;

			date.subtract(date.zone(), 'minutes');
			datetime.set('year', date.year());
			datetime.set('month', date.month());
			datetime.set('date', date.date());

			console.log(datetime.format(format), storage);

			storage.val(datetime.format(format));

			calendar.find('.active').removeClass('active');
			day.element.classList.add('active');
		};

		/**
		 * Switch sheets.
		 *
		 * @param Event event
		 *  The click event
		 */
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

		/**
		 * Switch months.
		 *
		 * @param Event event
		 *  The click event
		 */
		var switchMonth = function(event) {
			clndr.setMonth(event.target.textContent);
		};

		/**
		 * Switch year.
		 *
		 * @param Event event
		 *  The click event
		 */
		var switchYear = function(event) {
			clndr.setYear(event.target.textContent);
		};

		/**
		 * Store current date.
		 */
		var storeDate = function() {
			var date = storage.val();

			if(date) {
				datetime = moment.utc(date, format);		
			}
			else {
				datetime = moment.utc();
			}
		};

		/**
		 * Focus current date.
		 */
		var focusDate = function() {
			clndr.setMonth(datetime.get('month'));
			clndr.setYear(datetime.get('year'));
		};

		/**
		 * Set current date to active in calendar view.
		 */
		var updateDate = function() {
			storeDate();
			focusDate();
		};

		// API
		return {
			init: init
		};
	};

})(window.jQuery, window.Symphony, window.moment);
