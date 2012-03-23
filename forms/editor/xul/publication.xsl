<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	xmlns:xbl="http://www.mozilla.org/xbl"
	xmlns:php="http://php.net/xsl">
	<xsl:param name="moduleName" />
	<xsl:param name="documentName" />
	<xsl:param name="panelName" />
	<xsl:output indent="no" method="xml" omit-xml-declaration="yes" encoding="UTF-8" />
	
	<xsl:template match="/">
		<bindings xmlns="http://www.mozilla.org/xbl" xmlns:xbl="http://www.mozilla.org/xbl"
			xmlns:html="http://www.w3.org/1999/xhtml"
			xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul">
			<xsl:apply-templates select="panel" />
		</bindings>
	</xsl:template>	
	
	<xsl:template match="panel">	
		<binding xmlns="http://www.mozilla.org/xbl" extends="layout.cDocumentEditor#cDocumentEditorPanelPublish">
			<xsl:attribute name="id">
				<xsl:value-of select="php:function('uixul_DocumentEditorService::XSLGetBindingId', $moduleName, $documentName, $panelName)"/>
			</xsl:attribute>
			<xsl:copy-of select="@extends"/>
			<content>
				<xul:vbox flex="1">
					<xul:cmessageinfo anonid="message" />
					<xul:scrollbox anonid="scrollctrl" flex="1" class="editordatacontainer" orient="vertical">	
						<xul:ci18nsynchrosection collapsed="true" anonid="i18nsynchrosection" />						
						<xsl:apply-templates />
						<xul:spacer flex="1" />
					</xul:scrollbox>					
				</xul:vbox>
			</content>
			<implementation>
				<xsl:apply-templates select="/panel/xul/javascript" />
			</implementation>
		</binding>
	</xsl:template>
	
	<xsl:template match="section">
		<xul:cpublicationsection>
			<xsl:copy-of select="@class"/>
			<xsl:attribute name="forlang">
				<xsl:value-of select="@name" />
			</xsl:attribute>
		</xul:cpublicationsection>
	</xsl:template>
	
	<xsl:template match="actionsdef">
		<xul:actionsdef anonid="actionsdef">
			<xsl:copy-of select="@value"/>
		</xul:actionsdef>
	</xsl:template>
	
	<xsl:include href="xultemplating.xsl"/>
	
	<xsl:include href="xuljavascript.xsl"/>
</xsl:stylesheet>