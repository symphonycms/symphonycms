<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<html>
		<head>
			<title>Symphony: <xsl:value-of select="/data/details/@type"/></title>
			<link rel="stylesheet" type="text/css" media="screen" href="{$root}/symphony/assets/exception.css" /> 
		</head>
		<body>
			<h1>Symphony <xsl:value-of select="/data/details/@type"/></h1>
			<div class="panel">
				<a class="markdown" href="#markdown" onclick="javascript:document.getElementById('markdown').style.display = ((document.getElementById('markdown').style.display == 'none') ? 'block' : 'none'); return false;">Show Markdown for copy/paste</a>
				<h2><xsl:value-of select="/data/details"/></h2>
				<p class="error-line">An error occurred in <code><xsl:value-of select="/data/details/@file"/></code> around line <code><xsl:value-of select="/data/details/@line"/></code></p>
				<pre id="markdown" style="display: none;"><xsl:value-of select="/data/markdown"/></pre>
				<ul class="focus">
					<xsl:for-each select="/data/nearby-lines/item">
						<li>
							<xsl:if test="position() mod 2 = 0">
								<xsl:attribute name="class">odd</xsl:attribute>
							</xsl:if>
							<xsl:if test="@number = /data/details/@line"><xsl:attribute name="id">error</xsl:attribute></xsl:if>
							<strong><xsl:value-of select="@number"/></strong> <code><xsl:copy-of select="."/></code>
						</li>
					</xsl:for-each>
				</ul>
			</div>
			<h3>Backtrace</h3>
			<div class="panel">
				<ul class="focus">
					<xsl:for-each select="/data/backtrace/item">
						<li>
							<xsl:if test="position() mod 2 = 0"><xsl:attribute name="class">odd</xsl:attribute></xsl:if>
							<code>[<xsl:value-of select="@file"/>:<xsl:value-of select="@line"/>] <strong><xsl:value-of select="@class"/><xsl:value-of select="@type"/><xsl:value-of select="@function"/>();</strong></code>
						</li>
					</xsl:for-each>
				</ul>
			</div>
			<h3>Database Query Log (<a href="#query-log" onclick="javascript:document.getElementById('query-log').style.display = ((document.getElementById('query-log').style.display == 'none') ? 'block' : 'none'); return false;">Show</a>)</h3>
			<div class="panel">
				<ul class="focus" id="query-log">
					<xsl:for-each select="/data/query-log/item">
						<li>
							<xsl:if test="position() mod 2 = 0"><xsl:attribute name="class">odd</xsl:attribute></xsl:if>
							<code><xsl:value-of select="."/></code> <small>[<xsl:value-of select="@time"/>]</small>
						</li>
					</xsl:for-each>
				</ul>
			</div>
		</body>
	</html>
</xsl:template>

</xsl:stylesheet>