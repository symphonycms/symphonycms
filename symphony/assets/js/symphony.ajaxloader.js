;(function($, undefined){

	if( Symphony.AjaxLoader === undefined ){

		var defaults = {
			target: 'body',    // jQuery selector / object which will contain the loader
			position: 'center' // the CSS background-position property value
		};

		Symphony.AjaxLoader = {

			/**
			 * Displays the loader
			 *
			 * @return the target jQuery object
			 */
			show: function(options){
				var o = $.extend(defaults, options);
				var $target = $(o.target);

				var $loader = $('<div></div>').attr({
					'id': 'ajaxloader',
					'class': 'ajaxloader'
				}).css('background-position', o.position);

				$target.append($loader);

				$target.data('symphony.ajaxloader', {'original-position': $target.css('position')});
				$target.css('position', 'relative');

				return $target;
			},

			/**
			 * Hides the loader
			 *
			 * @param $target - the object returned by Symphony.Ajaxloader.show()
			 */
			hide: function($target){
				$('#ajaxloader').fadeOut(function(){
					$(this).remove();
				});

				var position = $target.data('symphony.ajaxloader')['original-position'];
				$target.css('position', position)
			}
		}

	}

})(jQuery);
