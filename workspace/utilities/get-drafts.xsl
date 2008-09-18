<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:import href="get-images.xsl"/>

<xsl:template match="drafts/entry" mode="list">
	<ul class="list">
		<li class="date">
			<xsl:call-template name="format-date">
				<xsl:with-param name="date" select="date"/>
				<xsl:with-param name="format" select="'d m y'"/>
			</xsl:call-template>
		</li>
		<li class="title">
			<a href="{$root}/drafts/{title/@handle}/">
				<xsl:value-of select="title"/>
			</a>
		</li>
		<li class="categories">
			<xsl:apply-templates select="categories/item"/>
		</li>
	</ul>
</xsl:template>

<xsl:template match="categories/item">
	<xsl:value-of select="."/>
	<xsl:if test="position() != last()">
		<xsl:text>, </xsl:text>
	</xsl:if>
</xsl:template>

<xsl:template match="drafts">
	<h2>
		<xsl:text>Latest</xsl:text>
		<xsl:if test="/data/events/user/@logged-in = 'true'">
			<xsl:text> &#8212; </xsl:text>
			<a class="edit" href="{$root}/symphony/publish/{sym:section/@handle}/edit/{entry/@id}/">Edit</a>
		</xsl:if>

	</h2>
	<div id="entry">
		<xsl:apply-templates select="entry"/>
	</div>
</xsl:template>

<xsl:template match="drafts/entry">
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
		<a href="{$root}/drafts/{title/@handle}/"><xsl:value-of select="title"/></a>
	</h3>
	<ul class="meta">
		<li class="icon-filed-under">
			<xsl:apply-templates select="categories/item"/>
		</li>
	</ul>
	<xsl:copy-of select="body/*[1]"/>
	<xsl:apply-templates select="/data/article-images[entry]"/>
	<xsl:copy-of select="body/*[position() &gt; 1]"/>

	<hr/>

	<form id="publish-article" action="" method="post">
		<fieldset>
			<input name="fields[publish]" value="yes" type="hidden" /> 
			<input name="redirect" value="{$root}/articles/{title/@handle}/" type="hidden" />
			<input name="id" value="{@id}" type="hidden" />
			<button id="submit" type="submit" name="action[publish-article]" value="Publish" />
		</fieldset>
	</form>
</xsl:template>

</xsl:stylesheet>