jQuery(document).ready(function(){

	$ = jQuery;
	
	$.fn.setSliderValue = function() {
		this.each(function(){
			$(this).siblings(".slider").slider("option", "value", $(this).val());
		});
	}

	var permissions = new Array();
	permissions[0] = "No Privileges";
	permissions[1] = "Own Entries";
	permissions[2] = "All Entries";
	
	$(".global-slider").slider({
		range: "min",
		value: 0,
		min: 0,
		max: 2,
		step: 1,
		slide: function(event, ui) {
			$("." + $(this).parents("td").attr("class") + " .slider").slider('option', 'value', ui.value);
			$("." + $(this).parents("td").attr("class") + " span").text(permissions[ui.value]).attr("class", "perm-" + ui.value);
			$("." + $(this).parents("td").attr("class") + " input").val(ui.value);
			$(this).siblings("span").text(permissions[ui.value]).attr("class", "perm-" + ui.value);
		}
	});

	$(".slider").slider({
		range: "min",
		min: 0,
		max: 2,
		step: 1,
		slide: function(event, ui) {
			$(this).siblings("span").text(permissions[ui.value]).attr("class", "perm-" + ui.value);
			$(this).siblings("input").val(ui.value);
			$(".global-slider").slider('option', 'value', 0);
			$(".global span").text('n/a').attr("class", "perm-0");
		}
	});
	
	$(".global .checkbox input[type='checkbox']").change(function() {
		$(".checkbox input").attr("checked", $(this).attr('checked'));
	});
	
	$(".edit input, .delete input").setSliderValue();
	
	$("td span").text(function() {
		return permissions[$(this).siblings("input").val()];
	}).attr("class", function() {
		return "perm-" + $(this).siblings("input").val();
	});
});
