<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="navigation">
  <ul id="menu">
    <xsl:apply-templates select="page[not(types/type = 'hidden') and not(types/type = 'admin')]"/>

    <xsl:if test="/data/events/login-info/@logged-in = 'true'">
      <li><a href="{$root}/drafts/">Drafts</a></li>
      <li><a href="{$root}/symphony/">Admin</a></li>
      <li><a href="?debug">Debug</a></li>
    </xsl:if>

  </ul>
</xsl:template>

<xsl:template match="page">
  <li>
    <a href="{$root}/{@handle}/">
      <xsl:if test="@handle = $current-page">
        <xsl:attribute name="class">active</xsl:attribute>
      </xsl:if>
      <xsl:value-of select="name"/>
    </a>
  </li>
</xsl:template>

</xsl:stylesheet>