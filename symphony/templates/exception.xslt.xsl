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
			<title>Symphony XSLT Transformation Error</title>
			<link rel="stylesheet" type="text/css" media="screen" href="{$root}/symphony/assets/exception.css" /> 
		</head>
		<body>
			<h1>Symphony XSLT Transformation Error</h1>
			<div class="panel">
				<a class="markdown" href="#markdown" onclick="javascript:document.getElementById('markdown').style.display = ((document.getElementById('markdown').style.display == 'none') ? 'block' : 'none'); return false;">Show Markdown for copy/paste</a>
				<h2><xsl:value-of select="/data/details"/></h2>
				<p class="error-line">An error occurred during the XSL transformation process around line <code><xsl:value-of select="/data/details/@line"/></code> of <code><xsl:value-of select="/data/details/@file"/></code>.</p>
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
			<h3>Processor Errors</h3>
			<div class="panel">
				<ul class="focus">
				<xsl:for-each select="/data/processing-errors/item">
					<li>
						<xsl:if test="position() mod 2 = 0">
							<xsl:attribute name="class">odd</xsl:attribute>
						</xsl:if>
						<xsl:if test="not(@file = '')">[<xsl:value-of select="@file"/>:<xsl:value-of select="@line"/>] </xsl:if>
						<xsl:value-of select="."/>
					</li>
				</xsl:for-each>
				</ul>
			</div>
		</body>
	</html>
</xsl:template>
</xsl:stylesheet>