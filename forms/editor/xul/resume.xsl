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
		<binding xmlns="http://www.mozilla.org/xbl" extends="layout.cDocumentEditor#cDocumentEditorPanelResume">
			<xsl:attribute name="id">
				<xsl:value-of select="php:function('uixul_DocumentEditorService::XSLGetBindingId', $moduleName, $documentName, $panelName)"/>
			</xsl:attribute>
			<xsl:copy-of select="@extends"/>
			<content>
				<xul:vbox flex="1">
					<xul:cmessageinfo anonid="message" />
					<xul:scrollbox anonid="scrollctrl" flex="1" class="editordatacontainer" orient="vertical">
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
		<xul:cresumesection>
			<xsl:copy-of select="@class"/>
			<xsl:attribute name="sectionname">
				<xsl:value-of select="@name" />
			</xsl:attribute>
			<xsl:copy-of select="@linkedtab"/>
			<xsl:if test="@linkedtab">
				<xsl:if test="@actiontext">
					<xsl:attribute name="actiontext"><xsl:value-of select="@actiontext" /></xsl:attribute>
				</xsl:if>
				<xsl:if test="@actiontexti18n">
					<xsl:attribute name="actiontext">${transui:<xsl:value-of select="@actiontexti18n" />,ucf,attr}</xsl:attribute>
				</xsl:if>				
			</xsl:if>
			<xsl:copy-of select="@image"/>
			<xsl:copy-of select="@viewempty"/>
			<xsl:copy-of select="@label"/>
			<xsl:if test="@labeli18n">
				<xsl:attribute name="label">${transui:<xsl:value-of select="@labeli18n"/>,ucf,attr}</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates />
		</xul:cresumesection>
	</xsl:template>
	
	<xsl:template match="property">
		<xul:cproperty>	
			<xsl:attribute name="propertyname">
				<xsl:value-of select="@name" />
			</xsl:attribute>
			<xsl:copy-of select="@type"/>
			<xsl:copy-of select="@class"/>
			<xsl:copy-of select="@label"/>
			<xsl:copy-of select="@width"/>
			<xsl:copy-of select="@height"/>
			<xsl:if test="@labeli18n">
				<xsl:attribute name="label">${transui:<xsl:value-of select="@labeli18n"/>,ucf,attr}</xsl:attribute>
			</xsl:if>
		</xul:cproperty>
	</xsl:template>
	
	<xsl:include href="xultemplating.xsl"/>
	
	<xsl:include href="xuljavascript.xsl"/>
</xsl:stylesheet>