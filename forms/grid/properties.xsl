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
										<xsl:attribute name="src">{IconsBase}/small/<xsl:value-of select="/block/@icon"/>.png</xsl:attribute>
									</xul:image>
									<xul:label class="toolbarLabel" style="font-size: 1.3em" crop="center" anonid="propertyGridLabel" flex="1" value="Image">
										<xsl:attribute name="value"><xsl:value-of select="/block/@label"/></xsl:attribute>
									</xul:label>					
									<xul:toolbarbutton oncommand="closePropertyGrid()" tooltiptext="Fermer" image="{{IconsBase}}/small/delete.png" />
								</xul:toolbar>
								<xul:toolbar class="change-toolbar">
									<xul:vbox flex="1">
										<xul:checkbox anonid="cbToggleRealtimeUpdate" oncommand="toggleRealtimeUpdate(this)">
											<xsl:attribute name="label">${transui:m.uixul.bo.general.auto-refresh-block,ucf,attr}</xsl:attribute>
										</xul:checkbox>
										<xul:hbox>
											<xul:toolbarbutton anonid="btnValidateBlockParameters" oncommand="validateBlockParameters()" image="{{IconsBase}}/small/check.png">
												<xsl:attribute name="label">${transui:m.uixul.bo.general.apply-values-to-block,ucf,attr}</xsl:attribute>
											</xul:toolbarbutton>			
											<xul:spacer flex="1"/>
											<xul:toolbarbutton anonid="btnUndo" oncommand="undoBlockParameters()" image="{{IconsBase}}/small/undo.png">
												<xsl:attribute name="tooltiptext">${transui:m.uixul.bo.general.back-to-previous-parameters,ucf,attr}</xsl:attribute>
											</xul:toolbarbutton>
											<xul:toolbarbutton anonid="btnRestore" oncommand="restoreDefaultBlockParameters()" image="{{IconsBase}}/small/nav_plain_blue.png">
												<xsl:attribute name="tooltiptext">${transui:m.uixul.bo.general.reset-block-parameters,ucf,attr}</xsl:attribute>
											</xul:toolbarbutton>
										</xul:hbox>
									</xul:vbox>
								</xul:toolbar>
							</xul:toolbox>			          
							<xul:vbox flex="1" anonid="mainContainer" class="parametersContainers">
								<xsl:apply-templates select="/block/parameters/parameter" />
							</xul:vbox>
						</xul:vbox>
						
						<xul:groupbox anonid="infoPanel" class="formInfoPanel" orient="vertical">
							<xul:description>${transui:m.uixul.bo.general.mandatory-fields-notice,ucf}</xul:description>
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
					<xsl:variable name="elem" select="php:function('uixul_PropertyGridBindingService::XSLSetDefaultFieldInfo', .)" />
					<xsl:attribute name="anonid"><xsl:value-of select="$elem/@cntanonid"/></xsl:attribute>
					<xul:box class="header">
						<xul:pglabel>
							<xsl:attribute name="id"><xsl:value-of select="$elem/@id" />_label</xsl:attribute>
							<xsl:attribute name="control"><xsl:value-of select="$elem/@id" /></xsl:attribute>
							<xsl:attribute name="value">${transui:<xsl:value-of select="$elem/@labeli18n"/>,attr,ucf}</xsl:attribute>
						</xul:pglabel>
					</xul:box>
					<xul:box class="control">
						<xul:cfield>
							<!-- functional attribute -->
							<xsl:copy-of select="$elem/@name"/>
							<xsl:copy-of select="$elem/@id"/>
							<xsl:copy-of select="$elem/@anonid"/>
							<xsl:attribute name="fieldtype">
								<xsl:value-of select="$elem/@type"/>
							</xsl:attribute>
							<xsl:copy-of select="$elem/@required"/>
							<xsl:copy-of select="$elem/@listid"/>
							<xsl:copy-of select="$elem/@nocache"/>
							<xsl:copy-of select="$elem/@emptylabel"/>
							<xsl:if test="$elem/@allow">
								<xsl:attribute name="allow">
									<xsl:value-of select="php:function('uixul_DocumentEditorService::XSLExpandAllowAttribute', $elem/@allow)"/>
								</xsl:attribute>
							</xsl:if>
							<xsl:copy-of select="$elem/@allowfile"/>
							<xsl:copy-of select="$elem/@mediafoldername"/>
							<xsl:copy-of select="$elem/@allowunits"/>
							<xsl:copy-of select="$elem/@moduleselector"/>
							<xsl:copy-of select="$elem/@dialog"/>
											
							<!-- common presentation attribute -->
							<xsl:copy-of select="$elem/@initialvalue"/>
							<xsl:attribute name="newvalue"><xsl:value-of select="$elem/@initialvalue"/></xsl:attribute>
							<xsl:copy-of select="$elem/@disabled"/>
							<xsl:copy-of select="$elem/@hidehelp"/>
							<xsl:if test="$elem/@shorthelpi18n">
								<xsl:attribute name="shorthelp">${transui:<xsl:value-of select="$elem/@shorthelpi18n"/>,attr,ucf}</xsl:attribute>
							</xsl:if>
							
							<!-- extra presentation attributes -->
							<xsl:copy-of select="$elem/@size"/>
							<xsl:copy-of select="$elem/@maxlength"/>
							
							<xsl:copy-of select="$elem/@cols"/>
							<xsl:copy-of select="$elem/@rows"/>
							
							<xsl:copy-of select="$elem/@editwidth"/>
							<xsl:copy-of select="$elem/@editheight"/>
							<xsl:copy-of select="$elem/@blankUrlParams"/>
							
							<xsl:copy-of select="$elem/@hidespinbuttons"/>
							<xsl:copy-of select="$elem/@increment"/>
							
							<xsl:copy-of select="$elem/@hideorder"/>
							<xsl:copy-of select="$elem/@hidedelete"/>
							<xsl:copy-of select="$elem/@hideselector"/>
							
							<xsl:copy-of select="$elem/@hidetime"/>
							<xsl:copy-of select="$elem/@timeoffset"/>
							
							<xsl:copy-of select="$elem/@orient"/>
							
							<xsl:copy-of select="$elem/@hidehours"/>
							
							<xsl:copy-of select="$elem/@compact"/>
							
							<xsl:apply-templates select="$elem/constraint"/>
							<xsl:apply-templates select="$elem/fieldlistitem"/>
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
				<xsl:attribute name="label">${transui:<xsl:value-of select="@labeli18n"/>,attr,ucf}</xsl:attribute>
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