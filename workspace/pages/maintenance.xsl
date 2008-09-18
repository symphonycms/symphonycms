<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:import href="../utilities/page-title.xsl"/>

<xsl:output method="xml"
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">

<html>
	<head>
		<title>
			<xsl:call-template name="page-title"/>
		</title>
		<link rel="icon" type="images/png" href="{$workspace}/images/icons/bookmark.png" />
		<link rel="stylesheet" type="text/css" media="screen" href="{$workspace}/css/maintenance.css" />
	</head>
	<body>
		<div id="package">
			<h1><xsl:value-of select="$website-name"/></h1>
			<p>The site is currently in maintenance. Please check back at a later date.</p>
		</div>
	</body>
</html>

</xsl:template>

</xsl:stylesheet>