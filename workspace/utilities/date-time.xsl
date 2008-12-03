<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!--

Description:

This is a date formatting utility. The named template "format-date" takes 2 parameters:

1. date - [required] takes an ISO date (2005-12-01)
2. format - [optional] takes a format string.

Format options:

Y - year in 4 digits e.g. 1981, 1992, 2008
y - year in 2 digits e.g. 81, 92, 08
M - month as a full word e.g. January, March, September
m - month in 3 letters e.g. Jan, Mar, Sep
N - month in digits without leading zero
n - month in digits with leading zero
D - day with suffix and no leading zero e.g. 1st, 23rd
d - day in digits with leading zero e.g. 01, 09, 12, 25
x - day in digits with no leading zero e.g. 1, 9, 12, 25
T - time in 24-hours e.g. 18:30
t - time in 12-hours e.g. 6:30pm
W - weekday as a full word e.g. Monday, Tuesday
w - weekday in 3 letters e.g. Mon, Tue, Wed

Examples:

M       => January
d M     => 21 September
m D, y  => Sep 21st, 81
n-d-y   => 09-21-81
d/n/y   => 21/09/81
d/n/y t => 21/09/81 6:30pm

-->

<xsl:template name="format-date">
  <xsl:param name="date"/>
  <xsl:param name="format" select="'D M, Y'"/>

  <xsl:choose>
    <xsl:when test="string-length($format) &lt;= 10">
      <xsl:call-template name="date-controller">
        <xsl:with-param name="date" select="$date"/>
        <xsl:with-param name="format" select="$format"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:otherwise>
      <xsl:text>Error: format parameter is not correctly set. You have: </xsl:text>
      <xsl:value-of select="string-length($format)"/>
      <xsl:text>.</xsl:text>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template name="date-controller">
  <xsl:param name="date"/>
  <xsl:param name="format"/>
  <xsl:param name="letter" select="substring($format,1,1)"/>
  <xsl:param name="tletter" select="translate($letter,'DMNYTW','dmnytw')"/>

  <xsl:choose>
    <xsl:when test="$tletter = 'y'">
      <xsl:call-template name="format-year">
        <xsl:with-param name="date" select="$date"/>
        <xsl:with-param name="format" select="$letter"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:when test="$tletter = 'm'">
      <xsl:call-template name="format-month">
        <xsl:with-param name="date" select="$date"/>
        <xsl:with-param name="format" select="$letter"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:when test="$tletter = 'n'">
      <xsl:call-template name="format-month">
        <xsl:with-param name="date" select="$date"/>
        <xsl:with-param name="format" select="$letter"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:when test="$tletter = 'd'">
      <xsl:call-template name="format-day">
        <xsl:with-param name="date" select="$date"/>
        <xsl:with-param name="format" select="$letter"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:when test="$tletter = 'x'">
      <xsl:call-template name="format-day">
        <xsl:with-param name="date" select="$date"/>
        <xsl:with-param name="format" select="$letter"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:when test="$tletter = 't'">
      <xsl:call-template name="format-time">
        <xsl:with-param name="time" select="$date/@time"/>
        <xsl:with-param name="format" select="$letter"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:when test="$tletter = 'w'">
      <xsl:call-template name="format-weekday">
        <xsl:with-param name="weekday" select="$date/@weekday"/>
        <xsl:with-param name="format" select="$letter"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:otherwise>
      <xsl:value-of select="$letter"/>
    </xsl:otherwise>
  </xsl:choose>

  <xsl:if test="$letter = not('')">
    <xsl:call-template name="date-controller">
      <xsl:with-param name="date" select="$date"/>
      <xsl:with-param name="format" select="substring($format,2)"/>
    </xsl:call-template>
  </xsl:if>

</xsl:template>

<xsl:template name="format-year">
  <xsl:param name="date"/>
  <xsl:param name="year" select="substring($date,1,4)"/>
  <xsl:param name="format" select="'y'"/>
  <xsl:choose>
    <xsl:when test="$format = 'y'">
      <xsl:value-of select="substring($year,3)"/>
    </xsl:when>
    <xsl:when test="$format = 'Y'">
      <xsl:value-of select="$year"/>
    </xsl:when>
  </xsl:choose>
</xsl:template>

<xsl:template name="format-month">
  <xsl:param name="date"/>
  <xsl:param name="month" select="format-number(substring($date,6,2), '##')"/>
  <xsl:param name="format" select="'m'"/>
  <xsl:param name="month-word">
    <xsl:choose>
      <xsl:when test="$month = 01">January</xsl:when>
      <xsl:when test="$month = 02">February</xsl:when>
      <xsl:when test="$month = 03">March</xsl:when>
      <xsl:when test="$month = 04">April</xsl:when>
      <xsl:when test="$month = 05">May</xsl:when>
      <xsl:when test="$month = 06">June</xsl:when>
      <xsl:when test="$month = 07">July</xsl:when>
      <xsl:when test="$month = 08">August</xsl:when>
      <xsl:when test="$month = 09">September</xsl:when>
      <xsl:when test="$month = 10">October</xsl:when>
      <xsl:when test="$month = 11">November</xsl:when>
      <xsl:when test="$month = 12">December</xsl:when>
    </xsl:choose>
  </xsl:param>

  <xsl:choose>
    <xsl:when test="$format = 'm'">
      <xsl:value-of select="substring($month-word, 1,3)"/>
    </xsl:when>
    <xsl:when test="$format = 'M'">
      <xsl:value-of select="$month-word"/>
    </xsl:when>
    <xsl:when test="$format = 'n'">
      <xsl:value-of select="format-number($month, '00')"/>
    </xsl:when>
    <xsl:when test="$format = 'N'">
      <xsl:value-of select="format-number($month, '0')"/>
    </xsl:when>
  </xsl:choose>
</xsl:template>

<xsl:template name="format-day">
  <xsl:param name="date"/>
  <xsl:param name="day" select="format-number(substring($date,9,2),'00')"/>
  <xsl:param name="format" select="'d'"/>
  <xsl:param name="suffix">
    <xsl:choose>
      <xsl:when test="(substring($day,2) = 1) and not(substring($day,1,1) = 1)">st</xsl:when>
      <xsl:when test="(substring($day,2) = 2) and not(substring($day,1,1) = 1)">nd</xsl:when>
      <xsl:when test="(substring($day,2) = 3) and not(substring($day,1,1) = 1)">rd</xsl:when>
      <xsl:otherwise>th</xsl:otherwise>
    </xsl:choose>
  </xsl:param>

  <xsl:choose>
    <xsl:when test="$format = 'd'">
      <xsl:value-of select="$day"/>
    </xsl:when>
    <xsl:when test="$format = 'x'">
      <xsl:value-of select="format-number($day,'0')"/>
    </xsl:when>
    <xsl:when test="$format = 'D'">
      <xsl:value-of select="format-number($day,'0')"/>
      <sup><xsl:value-of select="$suffix"/></sup>
    </xsl:when>
  </xsl:choose>
</xsl:template>

<xsl:template name="format-time">
  <xsl:param name="time"/>
  <xsl:param name="hour" select="substring-before($time, ':')"/>
  <xsl:param name="minute" select="substring-after($time, ':')"/>
  <xsl:param name="format" select="'T'"/>
  <xsl:choose>
    <xsl:when test="$format = 'T'">
      <xsl:value-of select="$time"/>
    </xsl:when>
    <xsl:when test="$format = 't'">
      <xsl:choose>
        <xsl:when test="$hour mod 12 = 0">12</xsl:when>
        <xsl:otherwise><xsl:value-of select="($hour mod 12)"/></xsl:otherwise>
      </xsl:choose>

      <xsl:value-of select="concat(':',$minute)"/>

      <xsl:choose>
        <xsl:when test="$hour &lt; 12">am</xsl:when>
        <xsl:otherwise>pm</xsl:otherwise>
      </xsl:choose>
    </xsl:when>
  </xsl:choose>
</xsl:template>

<xsl:template name="format-weekday">
  <xsl:param name="weekday"/>
  <xsl:param name="format" select="'w'"/>
  <xsl:param name="result">
    <xsl:choose>
      <xsl:when test="$weekday = 1">Monday</xsl:when>
      <xsl:when test="$weekday = 2">Tuesday</xsl:when>
      <xsl:when test="$weekday = 3">Wednesday</xsl:when>
      <xsl:when test="$weekday = 4">Thursday</xsl:when>
      <xsl:when test="$weekday = 5">Friday</xsl:when>
      <xsl:when test="$weekday = 6">Saturday</xsl:when>
      <xsl:when test="$weekday = 7">Sunday</xsl:when>
    </xsl:choose>
  </xsl:param>
  <xsl:choose>
    <xsl:when test="$format = 'W'">
      <xsl:value-of select="$result"/>
    </xsl:when>
    <xsl:when test="$format = 'w'">
      <xsl:value-of select="substring($result,1,3)"/>
    </xsl:when>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>