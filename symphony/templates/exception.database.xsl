<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="data">
	<html>
		<head>
			<title>Symphony Fatal Database Error</title>
			<link rel="stylesheet" type="text/css" media="screen" href="{$root}/symphony/assets/exception.css" /> 
		</head>
		<body>
			<h1>Symphony Fatal Database Error</h1>
			<div class="panel">
				<h2><xsl:value-of select="details/message"/></h2>
				<p>An error occurred while attempting to execute the following query</p>
				<ul>
					<li><xsl:value-of select="details/query"/></li>
				</ul>
			</div>
			<h3>Backtrace</h3>
			<div class="panel">
				<ul>
					<xsl:for-each select="backtrace/item">
						<li>
							<xsl:if test="position() mod 2 = 0"><xsl:attribute name="class">odd</xsl:attribute></xsl:if>
							<code>
								<xsl:value-of select="concat(@file, ':', @line)"/>
								<strong>
									<xsl:value-of select="@class"/>
									<xsl:value-of select="@type"/>
									<xsl:value-of select="@function"/>
									<xsl:text>();</xsl:text>
								</strong>
							</code>
						</li>
					</xsl:for-each>
				</ul>
			</div>
			<h3>Database Query Log</h3>
			<div class="panel">
				<ul>
					<xsl:for-each select="query-log/item">
						<li>
							<xsl:if test="position() mod 2 = 0">
								<xsl:attribute name="class">odd</xsl:attribute>
							</xsl:if>
							<code>
								<xsl:value-of select="."/></code>
								<small><xsl:value-of select="concat('[', @time, ']')"/></small>
						</li>
					</xsl:for-each>
				</ul>
			</div>
		</body>
	</html>
</xsl:template>

</xsl:stylesheet>