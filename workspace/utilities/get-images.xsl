<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="article-images">
  <ul id="article-images">
    <xsl:apply-templates select="entry"/>
  </ul>
</xsl:template>

<xsl:template match="article-images/entry">
  <li>
    <a href="{$workspace}/uploads/{image}">
      <xsl:if test="position() mod 4 = 0">
        <xsl:attribute name="class">last-column</xsl:attribute>
      </xsl:if>
      <img src="{$root}/image/2/133/88/2/uploads/{image}" title="{description}"/>
    </a>
  </li>
</xsl:template>

</xsl:stylesheet>