<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="archive">
  <xsl:apply-templates select="year/month"/>
</xsl:template>

<xsl:template match="month">
  <h4>
    <xsl:call-template name="format-date">
      <xsl:with-param name="date" select="concat(../@value, '-', @value, '-01')"/>
      <xsl:with-param name="format" select="'M y'"/>
    </xsl:call-template>
  </h4>
  <xsl:apply-templates select="entry"/>
</xsl:template>

<xsl:template match="month/entry">
  <ul class="list">
    <li class="date">
      <xsl:call-template name="format-date">
        <xsl:with-param name="date" select="date"/>
        <xsl:with-param name="format" select="'D'"/>
      </xsl:call-template>
    </li>
    <li class="title">
      <a href="{$root}/articles/{title/@handle}/">
        <xsl:value-of select="title"/>
      </a>
    </li>
    <li class="comments">
        <a href="{$root}/articles/{title/@handle}/#comments">
          <xsl:text>Comments (</xsl:text>
          <xsl:value-of select="@comments"/>
          <xsl:text>)</xsl:text>
        </a>
    </li>
  </ul>
</xsl:template>

</xsl:stylesheet>