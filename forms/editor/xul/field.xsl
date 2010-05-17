<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	xmlns:xbl="http://www.mozilla.org/xbl"
	xmlns:php="http://php.net/xsl">
	
	<xsl:template match="fieldlabel">
		<xsl:call-template select="." name="fieldLabel"/>
	</xsl:template>

	<xsl:template match="fieldinput">
		<xsl:value-of select="php:function('uixul_DocumentEditorService::XSLSetDefaultFieldInfo', .)"/>
		<xsl:call-template select="." name="fieldInput"/>	
	</xsl:template>
	
	<xsl:template match="field" mode="fieldLabel" name="fieldLabel" >
		<xul:clabel>
			<xsl:attribute name="id"><xsl:value-of select="@id" />_label</xsl:attribute>
			<xsl:attribute name="control"><xsl:value-of select="@id" /></xsl:attribute>
			<xsl:if test="@label">
				<xsl:attribute name="value"><xsl:value-of select="@label"/></xsl:attribute>
			</xsl:if>
			<xsl:if test="@labeli18n">
				<xsl:attribute name="value">&amp;<xsl:value-of select="@labeli18n"/>;</xsl:attribute>
			</xsl:if>
		</xul:clabel>
	</xsl:template>
	
	<xsl:template match="field" mode="fieldInput" name="fieldInput">
		<xul:cfield>
			<!-- functional attribute -->
			<xsl:copy-of select="@name"/>
			<xsl:copy-of select="@id"/>
			<xsl:copy-of select="@anonid"/>
			<xsl:attribute name="fieldtype">
				<xsl:value-of select="@type"/>
			</xsl:attribute>
			<xsl:copy-of select="@class"/>	
			<xsl:copy-of select="@required"/>
			<xsl:copy-of select="@listid"/>
			<xsl:copy-of select="@nocache"/>
			<xsl:copy-of select="@emptylabel"/>
			<xsl:if test="@allow">
				<xsl:attribute name="allow">
					<xsl:value-of select="php:function('uixul_DocumentEditorService::XSLExpandAllowAttribute', @allow)"/>
				</xsl:attribute>
			</xsl:if>
			<xsl:copy-of select="@allowfile"/>
			<xsl:copy-of select="@mediafoldername"/>
			<xsl:copy-of select="@allowunits"/>			
			<xsl:copy-of select="@moduleselector"/>
							
			<!-- common presentation attribute -->
			<xsl:copy-of select="@initialvalue"/>
			<xsl:copy-of select="@disabled"/>
			<xsl:copy-of select="@hidehelp"/>
			<xsl:copy-of select="@shorthelp"/>
			
			<!-- extra presentation attributes -->
			<xsl:copy-of select="@size"/>
			<xsl:copy-of select="@maxlength"/>
			
			<xsl:copy-of select="@cols"/>
			<xsl:copy-of select="@rows"/>
			
			<xsl:copy-of select="@editwidth"/>
			<xsl:copy-of select="@editheight"/>
			<xsl:copy-of select="@blankUrlParams"/>
			
			<xsl:copy-of select="@hidespinbuttons"/>
			<xsl:copy-of select="@increment"/>
			
			<xsl:copy-of select="@hideorder"/>
			<xsl:copy-of select="@hidedelete"/>
			<xsl:copy-of select="@hideselector"/>
			
			<xsl:copy-of select="@hidetime"/>
			<xsl:copy-of select="@timeoffset"/>
			
			<xsl:copy-of select="@orient"/>	
			<xsl:copy-of select="@flex"/>	
			<xsl:copy-of select="@editable"/>
			
			
			<xsl:apply-templates mode="fieldInput" />
		</xul:cfield>
	</xsl:template>
	
	<xsl:template match="constraint" mode="fieldInput">
		<xul:cconstraint>
			<xsl:copy-of select="@name"/>
			<xsl:copy-of select="@parameter"/>
		</xul:cconstraint>
	</xsl:template>
	
	<xsl:template match="fieldlistitem" mode="fieldInput">
		<xul:clistitem>
			<xsl:copy-of select="@value"/>
			<xsl:if test="@label">
				<xsl:attribute name="label"><xsl:value-of select="@label"/></xsl:attribute>
			</xsl:if>
			<xsl:if test="@labeli18n">
				<xsl:attribute name="label">&amp;<xsl:value-of select="@labeli18n"/>;</xsl:attribute>
			</xsl:if>
		</xul:clistitem>
	</xsl:template>
</xsl:stylesheet>