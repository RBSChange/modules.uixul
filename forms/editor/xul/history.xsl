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
		<binding xmlns="http://www.mozilla.org/xbl" extends="layout.cDocumentEditor#cDocumentEditorPanelHistory">
			<xsl:attribute name="id">
				<xsl:value-of select="php:function('uixul_DocumentEditorService::XSLGetBindingId', $moduleName, $documentName, $panelName)"/>
			</xsl:attribute>
			<xsl:copy-of select="@extends"/>
			<content>
				<xul:vbox flex="1">
					<!-- <xul:cmessageinfo anonid="message" /> -->
					<xul:scrollbox anonid="scrollctrl" flex="1" class="editordatacontainer" orient="vertical">
						<xul:groupbox>
							<xul:caption label="Informations" />
							<xul:hbox>
								<xul:label value="&amp;modules.uixul.bo.doceditor.CreationdateLabel;" />
								<xul:label anonid="creationdate" style="font-weight: bold;" />
								<xul:label value="&amp;modules.uixul.bo.doceditor.AuthorLabel;" />
								<xul:label anonid="author" style="font-weight: bold;" />
							</xul:hbox>	
							<xul:hbox>
								<xul:label value="&amp;modules.uixul.bo.doceditor.ModificationdateLabel;" />
								<xul:label anonid="modificationdate"  style="font-weight: bold;" />
								<xul:label value="&amp;modules.uixul.bo.doceditor.RevisionLabel;" />
								<xul:label anonid="documentversion"  style="font-weight: bold;" />
							</xul:hbox>
						</xul:groupbox>
						<xul:spacer height="10" />
						<xul:groupbox>
							<xul:caption label="Log" />
								<xul:grid flex="1">
									<xul:columns>
										<xul:column></xul:column>
										<xul:column></xul:column>										
										<xul:column flex="1"></xul:column>
									</xul:columns>
									<xul:rows anonid="logcontainer">
									</xul:rows>
								</xul:grid>
						</xul:groupbox>							
						<xul:spacer flex="1" />
					</xul:scrollbox>					
				</xul:vbox>
			</content>
			<implementation>
				<xsl:apply-templates select="/panel/xul/javascript" />
			</implementation>
		</binding>
	</xsl:template>
	
	<xsl:include href="xuljavascript.xsl"/>
</xsl:stylesheet>