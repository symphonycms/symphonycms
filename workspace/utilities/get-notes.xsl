<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="notes">
	<h2>
		<xsl:text>Notes</xsl:text>
		<xsl:if test="$is-logged-in = 'true'">
			<xsl:text> &#8212; </xsl:text>
			<a href="{$root}/symphony/publish/{section/@handle}/new/">Add</a>
		</xsl:if>
	</h2>
	<dl class="note">
		<xsl:apply-templates select="entry"/>
	</dl>
</xsl:template>

<xsl:template match="notes/entry">
	<dt>
		<xsl:call-template name="format-date">
			<xsl:with-param name="date" select="date"/>
			<xsl:with-param name="format" select="'d m'"/>
		</xsl:call-template>
	</dt>
	<dd><xsl:copy-of select="note/*"/></dd>
</xsl:template>

</xsl:stylesheet>