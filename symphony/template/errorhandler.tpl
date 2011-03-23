<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Symphony Error</title>
	<style type="text/css" media="all">
		*{
			margin: 0; padding: 0;
		}

		body{
			margin: 20px auto;
			width: 95%%;
			min-width: 950px;
			font-family: Helvetica, "MS Trebuchet", Arial, sans-serif;
			background-color: #ccc;
			font-size: 12px;
		}

		.bubble{
			background-color: white;
			padding: 22px;

			-webkit-border-radius: 20px;
			-moz-border-radius: 20px;
			border-radius: 20px;

			border: 2px solid #bbb;
		}

		h1{
			font-size: 34px;
			text-shadow: 2px 2px 2px #999;
			margin-bottom: 10px;
		}

		h2, h3{
			text-shadow: 2px 2px 2px #ccc;
		}

		a.markdown {
			float: right;
			margin-right: 20px;
			font-weight: normal;
			color: blue;
		}

		code{
			font-size: 11px;
			font-family: Monaco, "Courier New", Courier;
		}

		pre#markdown {
			padding: 10px 0;
		}

		ul, pre#markdown{
			list-style: none;
			color: #111;
			margin: 20px;
			border-left: 5px solid #bbb;
			background-color: #efefef;
		}

		li{
			background-color: #dedede;
			padding: 1px 5px;

			border-left: 1px solid #ddd;
		}

		li.odd{
			background-color: #efefef;
		}

		li#error{
			background-color: #E8CACA;
			color: #B9191A;
		}

		li small{
			font-size: 10px;
			color: #666;
		}
	</style>
</head>
<body>
	<h1>Symphony %s</h1>
	<div class="bubble">
		<a class="markdown" href="#markdown" onclick="javascript:document.getElementById('markdown').style.display = ((document.getElementById('markdown').style.display == 'none') ? 'block' : 'none'); return false;">Show Markdown for copy/paste</a>

		<h2>%s</h2>
		<p>An error occurred in <code>%s</code> around line <code>%d</code></p>

		<pre id="markdown" style="display: none;">%s</pre>

		<ul>%s</ul>

		<h3>Backtrace:</h3>
		<ul>%s</ul>

		<h3>Database Query Log:</h3>
		<ul>%s</ul>
	</div>
</body>
</html>