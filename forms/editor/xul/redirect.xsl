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
						<xul:button anonid="save_redirect" oncommand="saveRedirect()" label="&amp;modules.uixul.bo.doceditor.button.Save;" image="{{HttpHost}}/icons/small/save.png"/>
						<xul:button anonid="reset_redirect" oncommand="resetRedirect()" label="&amp;modules.uixul.bo.doceditor.button.Canceledit;" image="{{HttpHost}}/icons/small/undo.png"/>
					</xul:hbox>					
					<xul:scrollbox anonid="scrollctrl" flex="1" class="editordatacontainer" orient="vertical">
						<xul:groupbox>
							<xul:caption label="&amp;modules.uixul.bo.doceditor.Redirect-active-url;" />
							<xul:hbox>
								<xul:label value="&amp;modules.uixul.bo.doceditor.Redirect-languelabel;" />
								<xul:menulist anonid="for-lang" onselect="document.getBindingParent(this).onLangChange()">
						    		<xul:menupopup />
								</xul:menulist>
							</xul:hbox>	
							<xul:hbox>
								<xul:label value="&amp;modules.uixul.bo.doceditor.Redirect-URLlabel;" />
								<xul:textbox anonid="active-url" size="50" maxlength="255" />
								<xul:button anonid="modify-current-url" oncommand="modifyCurrentURL()" label="&amp;modules.uixul.bo.doceditor.Redirect-apply;" image="{{HttpHost}}/icons/small/edit.png"/>
								<xul:button anonid="gererated-url" oncommand="setGenerated()" label="&amp;modules.uixul.bo.doceditor.Redirect-default-url;" />
							</xul:hbox>	
						</xul:groupbox>
						<xul:spacer height="10" />
						<xul:groupbox>
							<xul:caption label="&amp;modules.uixul.bo.doceditor.Redirect-redirection-title;" />
								<xul:hbox>
									<xul:label value="&amp;modules.uixul.bo.doceditor.Redirect-new-redirectionlabel;" />
									<xul:textbox anonid="new-url" size="50" maxlength="255"/>
									<xul:button anonid="add-new-url" oncommand="addNewRedirect()" label="&amp;modules.uixul.bo.doceditor.Redirect-add;" image="{{HttpHost}}/icons/small/add.png"/>
								</xul:hbox>
								<xul:grid flex="1">
									<xul:columns>
										<xul:column></xul:column>										
										<xul:column></xul:column>
										<xul:column flex="1"></xul:column>
									</xul:columns>
									<xul:rows anonid="redirect-rows">
										<xul:row class="head">
											<xul:label value="&amp;modules.uixul.bo.doceditor.Redirect-actions;" />
											<xul:label value="&amp;modules.uixul.bo.doceditor.Redirect-temporary;" />
											<xul:label value="&amp;modules.uixul.bo.doceditor.Redirect-url;" />
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