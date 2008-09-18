<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:import href="../utilities/master.xsl"/>
<xsl:import href="../utilities/get-article.xsl"/>
<xsl:import href="../utilities/get-notes.xsl"/>
<xsl:import href="../utilities/get-comments.xsl"/>

<xsl:template match="data">
  <xsl:apply-templates select="homepage-article"/>
  <hr/>
  <xsl:apply-templates select="notes"/>
</xsl:template>

</xsl:stylesheet>