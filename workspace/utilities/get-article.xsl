<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:import href="get-images.xsl"/>

<xsl:template match="homepage-article | articles">
	<h2>
		<xsl:text>Latest</xsl:text>
		<xsl:if test="$is-logged-in = 'true'">
			<xsl:text> &#8212; </xsl:text>
			<a class="edit" href="{$root}/symphony/publish/{section/@handle}/edit/{entry/@id}/">Edit</a>
		</xsl:if>

	</h2>
	<div id="article">
		<xsl:apply-templates select="entry"/>
	</div>
</xsl:template>

<xsl:template match="homepage-article/entry | articles/entry">
	<p class="date">
		<xsl:call-template name="format-date">
			<xsl:with-param name="date" select="date"/>
			<xsl:with-param name="format" select="'d'"/>
		</xsl:call-template>
		<span>
			<xsl:call-template name="format-date">
				<xsl:with-param name="date" select="date"/>
				<xsl:with-param name="format" select="'m'"/>
			</xsl:call-template>
		</span>
	</p>
	<h3>
		<a href="{$root}/articles/{title/@handle}/"><xsl:value-of select="title"/></a>
	</h3>
	<ul class="meta">
		<li class="icon-filed-under">
			<xsl:apply-templates select="categories/item"/>
		</li>
		<li class="icon-comments">
			<a href="{$root}/articles/{title/@handle}/#comments">
				<xsl:text>Comments (</xsl:text>
				<xsl:value-of select="@comments"/>
				<xsl:text>)</xsl:text>
			</a>
		</li>
	</ul>
	<xsl:copy-of select="body/*[1]"/>
	<xsl:apply-templates select="/data/article-images[entry]"/>
	<xsl:copy-of select="body/*[position() &gt; 1]"/>
</xsl:template>

<xsl:template match="categories/item">
	<xsl:value-of select="."/>
	<xsl:if test="position() != last()">
		<xsl:text>, </xsl:text>
	</xsl:if>
</xsl:template>

</xsl:stylesheet>