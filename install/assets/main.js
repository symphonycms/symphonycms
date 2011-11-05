window.onload = function() {
	var submit = document.getElementsByName("submit")[0];
	document.forms[0].onsubmit = function() {
		submit.disabled = true;
	};
	setTimeout(function() {
		var warnings = document.getElementsByTagName("p");
		for (var element, i = 0; element = warnings[i]; i++) {
			if (element.className != "warning") continue;
			element.parentNode.scrollIntoView(true);
			break;
		}
	}, 100);
};