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
		<binding xmlns="http://www.mozilla.org/xbl" extends="layout.cDocumentEditor#cDocumentEditorPanelRedirect">
			<xsl:attribute name="id">
				<xsl:value-of select="php:function('uixul_DocumentEditorService::XSLGetBindingId', $moduleName, $documentName, $panelName)"/>
			</xsl:attribute>
			<xsl:copy-of select="@extends"/>
			<content>
				<xul:vbox flex="1">
					<xul:cmessageinfo anonid="message"/>
					<xul:hbox anonid="action-bar">
						<xul:button anonid="save_redirect" oncommand="saveRedirect()" label="${{transui:m.uixul.bo.doceditor.button.save,ucf,attr}}" image="{{IconsBase}}/small/save.png"/>
						<xul:button anonid="reset_redirect" oncommand="resetRedirect()" label="${{transui:m.uixul.bo.doceditor.button.canceledit,ucf,attr}}" image="{{IconsBase}}/small/undo.png"/>
					</xul:hbox>					
					<xul:scrollbox anonid="scrollctrl" flex="1" class="editordatacontainer" orient="vertical">
						<xul:groupbox>
							<xul:caption label="${{transui:m.uixul.bo.doceditor.redirect-active-url,ucf,attr}}" />
							<xul:hbox>
								<xul:label value="${{transui:m.uixul.bo.doceditor.redirect-site,lab,ucf,attr}}" />
								<xul:menulist anonid="for-lang" onselect="document.getBindingParent(this).onLangChange()">
						    		<xul:menupopup />
								</xul:menulist>
								<xul:sitemapoverride class="document" anonid="sitemap-info" />
							</xul:hbox>	
							<xul:hbox>
								<xul:label value="${{transui:m.uixul.bo.doceditor.redirect-url,lab,ucf,attr}}" />
								<xul:textbox anonid="active-url" size="50" maxlength="255" />
								<xul:button anonid="modify-current-url" oncommand="modifyCurrentURL()" label="${{transui:m.uixul.bo.doceditor.redirect-apply,ucf,attr}}" image="{{IconsBase}}/small/edit.png"/>
								<xul:button anonid="gererated-url" oncommand="setGenerated()" label="${{transui:m.uixul.bo.doceditor.redirect-default-url,ucf,attr}}" />
							</xul:hbox>	
						</xul:groupbox>
						<xul:groupbox>
							<xul:caption label="${{transui:m.uixul.bo.doceditor.redirect-redirection-title,ucf,attr}}" />
								<xul:hbox>
									<xul:label value="${{transui:m.uixul.bo.doceditor.redirect-new-redirection,lab,ucf,attr}}" />
									<xul:textbox anonid="new-url" size="50" maxlength="255"/>
									<xul:button anonid="add-new-url" oncommand="addNewRedirect()" label="${{transui:m.uixul.bo.doceditor.redirect-add,ucf,attr}}" image="{{IconsBase}}/small/add.png"/>
								</xul:hbox>
								<xul:grid flex="1">
									<xul:columns>
										<xul:column></xul:column>										
										<xul:column></xul:column>
										<xul:column flex="1"></xul:column>
									</xul:columns>
									<xul:rows anonid="redirect-rows">
										<xul:row class="head">
											<xul:label value="${{transui:m.uixul.bo.doceditor.redirect-actions,ucf,attr}}" />
											<xul:label value="${{transui:m.uixul.bo.doceditor.redirect-temporary,ucf,attr}}" />
											<xul:label value="${{transui:m.uixul.bo.doceditor.redirect-url,ucf,attr}}" />
										</xul:row>
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