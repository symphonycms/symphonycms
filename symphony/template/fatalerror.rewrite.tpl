<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Symphony Error</title>
	<link media="screen" href="{SYMPHONY_URL}/assets/css/symphony.css" type="text/css" rel="stylesheet">
	<link media="screen" href="{SYMPHONY_URL}/assets/css/symphony.frames.css" type="text/css" rel="stylesheet">
	<script type="text/javascript" src="{SYMPHONY_URL}/assets/js/jquery.js"></script>
	<script type="text/javascript" src="{SYMPHONY_URL}/assets/js/symphony.js"></script>
	<script type='text/javascript'>Symphony.Context.add('root', '{URL}');Symphony.Context.add('env', {});</script>
	<script type="text/javascript" src="{SYMPHONY_URL}/assets/js/symphony.collapsible.js"></script>
	<script type="text/javascript" src="{SYMPHONY_URL}/assets/js/symphony.error.js"></script>
</head>
<body id="fatalerror">
	<div class="frame">
		<ul>
			<li>
				<h1><em>Symphony Error:</em> <code>mod_rewrite</code> is not enabled</h1>
				<p>It appears the <code>mod_rewrite</code> is not enabled or available on this server.</p>
			</li>
		</ul>
	</div>
</body>
</html>
