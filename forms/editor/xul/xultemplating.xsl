<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	xmlns:xbl="http://www.mozilla.org/xbl"
	xmlns:php="http://php.net/xsl">

	<xsl:template match="sectionrow">
		<xul:row>
			<xul:label style="padding-left: 21px">
				<xsl:if test="@label">
					<xsl:attribute name="value"><xsl:value-of select="@label"/></xsl:attribute>
				</xsl:if>
				<xsl:if test="@labeli18n">
					<xsl:attribute name="value">&amp;<xsl:value-of select="@labeli18n"/>;</xsl:attribute>
				</xsl:if>
			</xul:label>
			<xul:cselectablelabel>
				<xsl:attribute name="anonid"><xsl:value-of select="@id"/></xsl:attribute>
			</xul:cselectablelabel>
		</xul:row>
	</xsl:template>
	
	<xsl:template match="row">
		<xul:row>
			<xsl:copy-of select="@*"/>
			<xsl:apply-templates/>
		</xul:row>
	</xsl:template>

	<xsl:template match="vbox">
		<xul:vbox>
			<xsl:copy-of select="@*"/>
			<xsl:apply-templates/>
		</xul:vbox>
	</xsl:template>	

	<xsl:template match="hbox">
		<xul:hbox>
			<xsl:copy-of select="@*"/>
			<xsl:apply-templates/>
		</xul:hbox>
	</xsl:template>
	
	<xsl:template match="label">
		<xul:label>
			<xsl:copy-of select="@*"/>
		</xul:label>
	</xsl:template>
	
	<xsl:template match="rowlabel">
		<xul:label style="padding-left: 21px">
			<xsl:copy-of select="@*"/>
		</xul:label>
	</xsl:template>
	
	<xsl:template match="toolbarbutton">
		<xul:toolbarbutton>
			<xsl:copy-of select="@*"/>
		</xul:toolbarbutton>
	</xsl:template>		
</xsl:stylesheet>