<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xul="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	xmlns:xbl="http://www.mozilla.org/xbl"
	xmlns:php="http://php.net/xsl">

	<xsl:template match="xul" />
	
	<xsl:template match="javascript">
		<xsl:apply-templates mode="code"/>
	</xsl:template>
	
	<xsl:template match="constructor" mode="code">
		<xbl:constructor>
			<xsl:copy-of select="text()"/>
		</xbl:constructor>
	</xsl:template>

	<xsl:template match="destructor" mode="code">
		<xbl:destructor>
			<xsl:copy-of select="text()"/>
		</xbl:destructor>
	</xsl:template>
	
	<xsl:template match="field" mode="code">
		<xbl:field>
			<xsl:copy-of select="@*"/>
			<xsl:copy-of select="text()"/>
		</xbl:field>
	</xsl:template>
	
	<xsl:template match="property" mode="code">
		<xbl:property>
			<xsl:copy-of select="@*"/>
			<xsl:apply-templates mode="code"/>	
		</xbl:property>
	</xsl:template>
	
	<xsl:template match="getter" mode="code">
		<xbl:getter>
			<xsl:copy-of select="text()"/>
		</xbl:getter>
	</xsl:template>

	<xsl:template match="setter" mode="code">
		<xbl:setter>
			<xsl:copy-of select="text()"/>
		</xbl:setter>
	</xsl:template>
	
	<xsl:template match="method" mode="code">
		<xbl:method>
			<xsl:copy-of select="@name"/>
			<xsl:apply-templates mode="code"/>	
		</xbl:method>
	</xsl:template>
	
	<xsl:template match="parameter" mode="code">
		<xbl:parameter>
			<xsl:copy-of select="@name"/>
		</xbl:parameter>
	</xsl:template>
	
	<xsl:template match="body" mode="code">
		<xbl:body>
			<xsl:copy-of select="text()"/>
		</xbl:body>
	</xsl:template>
	
	<xsl:template match="text()|@*" mode="code"/>
</xsl:stylesheet>