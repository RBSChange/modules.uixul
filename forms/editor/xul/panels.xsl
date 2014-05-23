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
			<implementation>
				<xsl:apply-templates select="/panels/xul/javascript" />
			</implementation>
			<content>
				<xul:vbox flex="1">
					<xul:toolbox class="change-toolbox" style="height: 20px; padding-top: 0px;">
						<xul:toolbar anonid="globaltoolbar" class="change-toolbar"></xul:toolbar>
					</xul:toolbox>
					<xul:tabbox flex="1" anonid="tabbox">
						<xul:tabs anonid="tabs">
							<xsl:apply-templates select="panel" mode="tab"/>
						</xul:tabs>
						<xul:tabpanels flex="1" anonid="panels">
							<xsl:apply-templates select="panel"  mode="panel"/>
						</xul:tabpanels>
					</xul:tabbox>
					<children />
				</xul:vbox>
			</content>
		</binding>
	</xsl:template>
	
	<xsl:template match="panel" mode="tab">
		<xsl:variable name="elem" select="php:function('uixul_DocumentEditorService::XSLSetDefaultPanelInfo', .)" />
		<xul:tab collapsed="true">
			<xsl:attribute name="anonid"><xsl:value-of select="@name" />_tab</xsl:attribute>
			<xsl:if test="$elem/@labeli18n">
				<xsl:attribute name="label">${transui:<xsl:value-of select="$elem/@labeli18n"/>,ucf,attr}</xsl:attribute>
			</xsl:if>
			<xsl:if test="$elem/@icon">
				<xsl:attribute name="image">{IconsBase}/small/<xsl:value-of select="$elem/@icon"/>.png</xsl:attribute>
			</xsl:if>
		</xul:tab>
	</xsl:template>
	
	<xsl:template match="panel" mode="panel">
		<xul:tabpanel>
			<xsl:attribute name="anonid"><xsl:value-of select="@name" /></xsl:attribute>
			<xsl:variable name="elemName">xul:c<xsl:value-of select="@name" />panel</xsl:variable>
			<xsl:element name="{$elemName}" namespace="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"></xsl:element>
		</xul:tabpanel>
	</xsl:template>
	<xsl:include href="xuljavascript.xsl"/>
</xsl:stylesheet>