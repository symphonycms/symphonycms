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
				<h1><em>Symphony Fatal Database Error:</em> %s</h1>
				<p>An error occurred while attempting to execute the following query</p>
				<ul>
					<li class="error full">
						<code>%s</code>
					</li>
				</ul>
			</li>
			<li>
				<header class="frame-header">Backtrace</header>
				<div class="content">
					<ul>%s</ul>
				</div>
			</li>
			<li>
				<header class="frame-header">Database Query Log</header>
				<div class="content">
					<ul>%s</ul>
				</div>
			</li>
		</ul>
	</div>
</body>
</html>
