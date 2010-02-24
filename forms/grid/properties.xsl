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
			<binding id="wPropertyGrid" extends="widgets.wPropertyGrid#wPropertyGrid">
				<content>
					<xul:vbox flex="1">
						<xul:vbox flex="2" class="change-toolbox-rsctree">
							<xul:toolbox class="change-toolbox-dark" style="height: 20px; padding-top: 0px;">
								<xul:toolbar class="change-toolbar" align="baseline">
									<xul:image anonid="propertyGridIcon" style="max-height: 16px; max-width: 16px;">
										<xsl:attribute name="src">{HttpHost}/icons/small/<xsl:value-of select="/block/@icon"/>.png</xsl:attribute>
									</xul:image>
									<xul:label class="toolbarLabel" style="font-size: 1.3em" crop="center" anonid="propertyGridLabel" flex="1" value="Image">
										<xsl:attribute name="value"><xsl:value-of select="/block/@label"/></xsl:attribute>
									</xul:label>					
									<xul:toolbarbutton oncommand="closePropertyGrid()" tooltiptext="Fermer" image="{{HttpHost}}/icons/small/delete.png" />
								</xul:toolbar>
								<xul:toolbar class="change-toolbar">
									<xul:vbox flex="1">
										<xul:checkbox anonid="cbToggleRealtimeUpdate" oncommand="toggleRealtimeUpdate(this)" label="Rafraîchir le bloc automatiquement"/>
										<xul:hbox>
											<xul:toolbarbutton anonid="btnValidateBlockParameters" oncommand="validateBlockParameters()" label="Appliquer les valeurs au bloc" image="{{HttpHost}}/icons/small/check.png"/>			
											<xul:spacer flex="1"/>
											<xul:toolbarbutton anonid="btnUndo" oncommand="undoBlockParameters()" tooltiptext="Revenir aux paramètres précédents du bloc (undo)" image="{{HttpHost}}/icons/small/undo.png"/>
											<xul:toolbarbutton anonid="btnRestore" oncommand="restoreDefaultBlockParameters()" tooltiptext="Réinitialiser les paramètres du bloc avec les paramètres par défaut" image="{{HttpHost}}/icons/small/nav_plain_blue.png"/>
										</xul:hbox>
									</xul:vbox>
								</xul:toolbar>
							</xul:toolbox>			          
							<xul:vbox style="overflow:auto" flex="1" anonid="mainContainer">
								<xsl:apply-templates select="/block/parameters/parameter" />
							</xul:vbox>
						</xul:vbox>
						
						<xul:groupbox height="100px" style="overflow: auto;max-width: 280px ! important;width:280px;" anonid="infoPanel" 
								  class="formInfoPanel" orient="vertical">
							<xul:description>&amp;modules.uixul.bo.general.Mandatory-fields-notice;</xul:description>
						</xul:groupbox>
					</xul:vbox>
				</content>
				<implementation>
					<field name="mFieldNames"><xsl:value-of select="php:function('uixul_PropertyGridBindingService::XSLFieldsName')"/></field>					
					<field name="isReady">true</field>
					<xsl:apply-templates select="block/xul/javascript" />
				</implementation>
			</binding>
		</bindings>
	</xsl:template>	
	
	<xsl:template match="parameter">
		<xsl:choose>
			<xsl:when test="@hidden='true'">
			</xsl:when>
			<xsl:otherwise>
				<xul:vbox>
					<xsl:attribute name="anonid"><xsl:value-of select="php:function('uixul_PropertyGridBindingService::XSLSetDefaultFieldInfo', .)"/></xsl:attribute>
					<xul:box class="header">
						<xul:pglabel>
							<xsl:attribute name="id"><xsl:value-of select="@id" />_label</xsl:attribute>
							<xsl:attribute name="control"><xsl:value-of select="@id" /></xsl:attribute>
							<xsl:attribute name="value">&amp;<xsl:value-of select="@labeli18n"/>;</xsl:attribute>
						</xul:pglabel>
					</xul:box>
					<xul:box class="control">
						<xul:cfield>
							<!-- functional attribute -->
							<xsl:copy-of select="@name"/>
							<xsl:copy-of select="@id"/>
							<xsl:copy-of select="@anonid"/>
							<xsl:attribute name="fieldtype">
								<xsl:value-of select="@type"/>
							</xsl:attribute>
							<xsl:copy-of select="@required"/>
							<xsl:copy-of select="@listid"/>
							<xsl:copy-of select="@nocache"/>
							<xsl:copy-of select="@emptylabel"/>
							<xsl:copy-of select="@allow"/>
							<xsl:copy-of select="@allowfile"/>
							<xsl:copy-of select="@mediafoldername"/>
							<xsl:copy-of select="@allowunits"/>
							<xsl:copy-of select="@moduleselector"/>
											
							<!-- common presentation attribute -->
							<xsl:copy-of select="@initialvalue"/>
							<xsl:attribute name="newvalue"><xsl:value-of select="@initialvalue"/></xsl:attribute>
							<xsl:copy-of select="@disabled"/>
							<xsl:copy-of select="@hidehelp"/>
							<xsl:attribute name="shorthelp">&amp;<xsl:value-of select="@shorthelpi18n"/>;</xsl:attribute>
							
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
							
							<xsl:apply-templates select="constraint"/>
							<xsl:apply-templates select="fieldlistitem"/>
						</xul:cfield>				
					</xul:box>				
				</xul:vbox>			
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="constraint">
		<xul:cconstraint>
			<xsl:copy-of select="@name"/>
			<xsl:copy-of select="@parameter"/>
		</xul:cconstraint>
	</xsl:template>
	
	<xsl:template match="fieldlistitem">
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