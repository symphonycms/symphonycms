jQuery(document).ready(function($) {
	if (Symphony.Cookie.get('symphony-nav') == 'expanded') {
		$('#nav').addClass('expanded');
		$('#nav-expand').text('-');
	}
	
	$(':not(.expanded) #nav-expand').live('click',
		function(){
			$('#nav').addClass('expanded');
			$(this).text('-');
			
			Symphony.Cookie.set('symphony-nav', 'expanded');
		}
	);
	
	$('.expanded #nav-expand').live('click',
		function(){
			$('#nav').removeClass('expanded');
			$(this).text('+');
			
			Symphony.Cookie.set('symphony-nav', 'collapsed');
		}
	);
})
