<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

	<xsl:variable name='create-car' select='/data/events/create-car' />
	<xsl:variable name='create-dealer' select='/data/events/create-dealer' />

	<xsl:template match="/">
		<h1><xsl:value-of select="$page-title"/></h1>

		<form method="post">
			<xsl:choose>
				<xsl:when test='$create-car[@result = "success"]'>
					<xsl:value-of select='$create-car/message' />
					<dl>
						<dt>Manufacturer</dt>
						<dd>
							<xsl:value-of select='$create-car/manufacturer' />
						</dd>
						<dt>Car - Model</dt>
						<dd>
							<xsl:value-of select='concat($create-car/post-values/car, " ", $create-car/post-values/year)' />
						</dd>
					</dl>
				</xsl:when>
				<xsl:otherwise>
					<fieldset>
						<legend>Car</legend>
						<label>Manufacturer
						  <input name="create-car[fields][manufacturer]" type="text" />
						</label>
						<label>Make
						  <input name="create-car[fields][name]" type="text" />
						</label>
						<label>Year
						  <select name="create-car[fields][year]">
						    <option value="2008">2008</option>
						    <option value="2009">2009</option>
						    <option value="2010">2010</option>
						    <option value="2011">2011</option>
						  </select>
						</label>
					</fieldset>
				</xsl:otherwise>
			</xsl:choose>

			<fieldset>
				<legend>Dealer</legend>
				<label>Name
				  <input name="create-dealer[fields][name]" type="text" value='{$create-dealer/post-values/name}' />
				</label>
				<label>Suburb
				  <input name="create-dealer[fields][suburb]" type="text" value='{$create-dealer/post-values/suburb}' />
				</label>
				<xsl:if test='$create-car[@result = "success"]'>
					<input type='hidden' name="create-dealer[fields][related-car]" value='{$create-car/@id}' />
				</xsl:if>
			</fieldset>

			<xsl:choose>
				<xsl:when test='$create-car[@result = "success"] and $create-dealer[@result != "success"]'>
					<input name="action[create-dealer]" type="submit" value="Submit" />
				</xsl:when>
				<xsl:otherwise>
					<input name="action[create-car]" type="submit" value="Submit" />
				</xsl:otherwise>
			</xsl:choose>
		</form>

		<xsl:apply-templates select='$create-car' mode='event' />
		<xsl:apply-templates select='$create-dealer' mode='event' />
	</xsl:template>

	<xsl:template match='*' mode='event'>
		<div>
			<h3>
				<xsl:value-of select='local-name(.)' />
				<xsl:choose>
					<xsl:when test='@result = "error"'>
						<span style='color:red'> error</span>
					</xsl:when>
					<xsl:otherwise>
						<span style='color:green'> success</span>
					</xsl:otherwise>
				</xsl:choose>
			</h3>

			<xsl:copy-of select='message' />
			<ul>
				<xsl:apply-templates select='*[@message]' />
			</ul>
		</div>
	</xsl:template>

	<xsl:template match='*[@message]'>
		<li><xsl:value-of select='@message' /></li>
	</xsl:template>

</xsl:stylesheet>