<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="comments">
  <h2>
    <xsl:text>Comments</xsl:text>
    <xsl:if test="$is-logged-in = 'true'">
      <xsl:text> &#8212; </xsl:text>
      <a href="{$root}/symphony/publish/comments/?filter=article:{/data/articles/entry/@id}">Manage</a>
    </xsl:if>
  </h2>
  <div id="comments">
    <xsl:apply-templates select="entry"/>
    <xsl:apply-templates select="error"/>
  </div>
</xsl:template>

<xsl:template match="comments/entry">
  <dl class="comment">
    <xsl:if test="authorised = 'Yes'">
      <xsl:attribute name="class">
        <xsl:text>comment authorised</xsl:text>
      </xsl:attribute>
    </xsl:if>
    <dt>
      <xsl:choose>
        <xsl:when test="website">
          <a href="{website}">
            <xsl:value-of select="author"/>
          </a>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="author"/>
        </xsl:otherwise>
      </xsl:choose>
      <em>
        <xsl:call-template name="format-date">
          <xsl:with-param name="date" select="date"/>
          <xsl:with-param name="format" select=" 'd m y, t' "/>
        </xsl:call-template>
      </em>
    </dt>
    <dd>
      <xsl:copy-of select="comment/*"/>
    </dd>
  </dl>
</xsl:template>

<xsl:template match="comments/error">
  <p>There are no comments made so far.</p>
</xsl:template>

</xsl:stylesheet>