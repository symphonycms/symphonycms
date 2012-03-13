<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Symphony Error</title>
	<link media="screen" href="{SYMPHONY_URL}/assets/css/symphony.css" type="text/css" rel="stylesheet">
	<link media="screen" href="{SYMPHONY_URL}/assets/css/symphony.frames.css" type="text/css" rel="stylesheet">
	<script type="text/javascript" src="{SYMPHONY_URL}/assets/js/jquery.js"></script>
	<script type="text/javascript" src="{SYMPHONY_URL}/assets/js/symphony.js"></script>
	<script type="text/javascript" src="{SYMPHONY_URL}/assets/js/symphony.collapsible.js"></script>
	<script type="text/javascript" src="{SYMPHONY_URL}/assets/js/symphony.error.js"></script>
</head>
<body id="fatalerror">
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
				<header>Backtrace</header>
				<div class="content">
					<ul>%s</ul>
				</div>
			</li>
			<li>
				<header>Database Query Log</header>
				<div class="content">
					<ul>%s</ul>
				</div>
			</li>
		</ul>
	</div>
</body>
</html>