<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Symphony Error</title>
	<link media="screen" href="{ASSETS_URL}/css/symphony.min.css" type="text/css" rel="stylesheet">
	<script type="text/javascript" src="{ASSETS_URL}/js/symphony.min.js"></script>
	<script type='text/javascript'>Symphony.Context.add('root', '{URL}');Symphony.Context.add('env', {});</script>
</head>
<body id="error">
	<div class="frame">
		<ul>
			<li>
				<h1><em>Symphony %s:</em> %s</h1>
				<p>An error occurred in <code>%s</code> around line <code>%d</code></p>
			</li>
		</ul>
	</div>
</body>
</html>
