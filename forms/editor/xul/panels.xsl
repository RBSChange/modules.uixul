<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	xmlns:xbl="http://www.mozilla.org/xbl"
	xmlns:php="http://php.net/xsl">
	<xsl:output indent="yes" method="xml" omit-xml-declaration="yes" encoding="UTF-8" />
	
	<xsl:template match="/">
		<bindings xmlns="http://www.mozilla.org/xbl" xmlns:xbl="http://www.mozilla.org/xbl"
			xmlns:html="http://www.w3.org/1999/xhtml"
			xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul">
			<xsl:apply-templates select="panels" />
		</bindings>
	</xsl:template>
	
	<xsl:template match="panels">			
		<binding xmlns="http://www.mozilla.org/xbl" id="DOCUMENTNAME" extends="layout.cDocumentEditor#cDocumentEditor">
			<xsl:attribute name="id"><xsl:value-of select="$documentName" /></xsl:attribute>
			<xsl:copy-of select="@extends"/>
			<xbl:implementation>
				<xsl:apply-templates select="/panels/xul/javascript" />
			</xbl:implementation>
		</binding>
	</xsl:template>
	<xsl:include href="xuljavascript.xsl"/>
</xsl:stylesheet>