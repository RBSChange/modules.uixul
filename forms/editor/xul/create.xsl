<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	xmlns:xbl="http://www.mozilla.org/xbl"
	xmlns:php="http://php.net/xsl" >
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
		<binding xmlns="http://www.mozilla.org/xbl" extends="layout.cDocumentEditor#cDocumentEditorPanelCreate">
			<xsl:attribute name="id">
				<xsl:value-of select="php:function('uixul_DocumentEditorService::XSLGetBindingId', $moduleName, $documentName, $panelName)"/>
			</xsl:attribute>
			<xsl:copy-of select="@extends"/>
			<content>
				<xul:vbox flex="1">
					<xul:cmessageinfo anonid="message"/>
					<xul:hbox anonid="action-bar">					
						<xul:button anonid="save_properties" oncommand="saveProperties()" label="&amp;modules.uixul.bo.doceditor.button.Create;" image="{{HttpHost}}/icons/small/save.png"/>
						<xul:button anonid="reset_properties" oncommand="resetProperties()" label="&amp;modules.uixul.bo.doceditor.button.Cancelcreate;" image="{{HttpHost}}/icons/small/undo.png"/>
						<xul:button anonid="next_error_property" oncommand="nextErrorProperty()" label="&amp;modules.uixul.bo.doceditor.button.Nexterror;" image="{{HttpHost}}/icons/small/next-invalid-field.png"/>
					</xul:hbox>
					<xul:scrollbox anonid="scrollctrl" flex="1" class="editordatacontainer" orient="vertical">
						<xsl:apply-templates />			
						<xul:spacer flex="1" />
					</xul:scrollbox>					
				</xul:vbox>
			</content>
			<implementation>
				<field name="mFieldNames"><xsl:value-of select="php:function('uixul_DocumentEditorService::XSLFieldsName')"/></field>
				<xsl:apply-templates select="/panel/xul/javascript" />
			</implementation>
		</binding>		
	</xsl:template>
		
	<xsl:template match="section">
		<xul:cfieldsgroup >
			<xsl:copy-of select="@class"/>
			<xsl:copy-of select="@image"/>
			<xsl:copy-of select="@anonid"/>
			<xsl:if test="@hidden">
				<xsl:attribute name="hide-content">true</xsl:attribute>
			</xsl:if>
			<xsl:if test="@label">
				<xsl:copy-of select="@label"/>
			</xsl:if>
			<xsl:if test="@labeli18n">
				<xsl:attribute name="label">&amp;<xsl:value-of select="@labeli18n"/>;</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates />
		</xul:cfieldsgroup>
	</xsl:template>
	
	<xsl:template match="field">
		<xul:row>
			<xsl:attribute name="anonid">row_<xsl:value-of select="@name" /></xsl:attribute>
			<xsl:value-of select="php:function('uixul_DocumentEditorService::XSLSetDefaultFieldInfo', .)"/>
			<xsl:apply-templates select="." mode="fieldLabel"/>
			<xsl:apply-templates select="." mode="fieldInput"/>
		</xul:row>
	</xsl:template>
	
	<xsl:include href="field.xsl"/>
	
	<xsl:include href="xultemplating.xsl"/>
	
	<xsl:include href="xuljavascript.xsl"/>
</xsl:stylesheet>