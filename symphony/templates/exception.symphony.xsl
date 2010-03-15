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
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
			<title>Symphony - <xsl:value-of select="/data/heading"/></title>
			<link rel="stylesheet" type="text/css" media="screen" href="{$root}/symphony/assets/exception.css" /> 
		</head>
		<body>
			<h1><xsl:value-of select="/data/heading"/></h1>
			<div class="panel">
				<h2><xsl:value-of select="/data/message"/></h2>
				<xsl:if test="not(/data/description = '')">
					<p><xsl:value-of select="/data/description"/></p>
				</xsl:if>
			</div>
		</body>
	</html>
</xsl:template>
</xsl:stylesheet>