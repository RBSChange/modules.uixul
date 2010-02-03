<?xml version="1.0" encoding="ISO-8859-1"?>

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
	<xsl:variable name="title"></xsl:variable>
	<html>
		<head>
			<title>Bindings: 
			<xsl:for-each select="bindings/binding">
				<xsl:value-of select="concat(@id, ' ')" />
			</xsl:for-each>			
			</title>
			<link rel="stylesheet" href="/index.php?module=uixul&amp;action=Doc&amp;binding=css" />
		</head>
		<body>
			<div class="toc">
				<ul>
				<xsl:for-each select="bindings/binding">
					<xsl:variable name="bindingId"><xsl:value-of select="@id"></xsl:value-of></xsl:variable>
					<li>
						<a href="#binding-{$bindingId}"><xsl:value-of select="@id" /></a>
						<br />
						<xsl:if test="implementation/*">
							<select onchange="document.location=this.options[this.selectedIndex].value">
							<xsl:for-each select="implementation/*">
								<xsl:variable name="methodName"><xsl:value-of select="@name" /></xsl:variable>
								<option value="#{$bindingId}-{$methodName}"><xsl:value-of select="concat(name(), ' ')" /><xsl:value-of select="@name" /></option>
							</xsl:for-each>
							<!-- intbonjf - 2006-03-27: add the handlers here -->
							</select>
						</xsl:if>
					</li>
				</xsl:for-each>
				</ul>
			</div>
			<xsl:apply-templates select="bindings/binding" />
		</body>
	</html>
</xsl:template>

<xsl:template match="bindings">
  <html>
  <body>
     <xsl:apply-templates />
  </body>
  </html>
</xsl:template>

<xsl:template match="binding">
	<xsl:variable name="bindingId"><xsl:value-of select="@id"></xsl:value-of></xsl:variable>
	<a name="binding-{$bindingId}" />
	<div class="binding">
		<h1>
			<xsl:value-of select="@id"></xsl:value-of>
		</h1>
		<div class="binding-header">
			<xsl:if test="@extends">
				<xsl:variable name="bindingExtends"><xsl:value-of select="@extends" /></xsl:variable>
				<strong>Extends: </strong>
				<a href="/index.php?module=uixul&amp;action=Doc&amp;binding={$bindingExtends}"><xsl:value-of select="@extends" /></a>
				<br /><br />
			</xsl:if>
			<div class="doc">
				<xsl:value-of select="@doc-text" />
			</div><br />
			Public:
			<a href="#{$bindingId}-public-properties">properties</a>
			| <a href="#{$bindingId}-public-methods">methods</a>
			| <a href="#{$bindingId}-public-fields">fields</a>
			<br />
			Private (and/or protected):
			<a href="#{$bindingId}-private-properties">properties</a>
			| <a href="#{$bindingId}-private-methods">methods</a>
			| <a href="#{$bindingId}-private-fields">fields</a>
			<br />
			<xsl:if test="handlers/handler">
				<br /><a href="#{$bindingId}-handlers">Handlers</a>
			</xsl:if>
		</div>
		<xsl:apply-templates select="implementation" />
		<div class="implementation">
			<a name="{$bindingId}-handlers" />
			<h2>Handlers</h2>
			<xsl:apply-templates select="handlers/handler" />
		</div>
	</div>
</xsl:template>

<xsl:template match="implementation">
	<xsl:variable name="bindingId"><xsl:value-of select="../@id"></xsl:value-of></xsl:variable>
	<div class="implementation">
		<xsl:apply-templates select="constructor" />
		<a name="{$bindingId}-public-properties" /><h2>Public properties</h2>
		<xsl:apply-templates select="property[not(@doc-access='private')]" />
		<a name="{$bindingId}-public-methods" /><h2>Public methods</h2>
		<xsl:apply-templates select="method[not(@doc-access='private')]" />
		<a name="{$bindingId}-public-fields" /><h2>Public fields</h2>
		<xsl:apply-templates select="field[not(@doc-access='private')]" />
		<a name="{$bindingId}-private-properties" /><h2>Private properties</h2>
		<xsl:apply-templates select="property[@doc-access='private']" />
		<a name="{$bindingId}-private-methods" /><h2>Private methods</h2>
		<xsl:apply-templates select="method[@doc-access='private']" />
		<a name="{$bindingId}-private-fields" /><h2>Private fields</h2>
		<xsl:apply-templates select="field[@doc-access='private']" />
		<xsl:apply-templates select="destructor" />
	</div>
</xsl:template>

<xsl:template match="property">
	<xsl:variable name="anchor"><xsl:value-of select="concat(../../@id, '-', @name)" /></xsl:variable>
	<a name="{$anchor}" />
	<div class="property">
		<h2>
			<xsl:if test="@doc-final"><span class="modifier" title="Must NOT be overriden in child bindings!">final</span></xsl:if>
			<xsl:if test="@doc-overridable"><span class="modifier" title="May be overriden in child bindings">overridable</span></xsl:if>
			<strong><xsl:value-of select="@name"></xsl:value-of></strong>
			<xsl:if test="@doc-type">
				<span class="type">(<xsl:value-of select="@doc-type" />)</span>
			</xsl:if>
		</h2>
		
		<div class="features">
			<xsl:if test="@readonly">readonly </xsl:if>
			<xsl:if test="@doc-access='private'">private </xsl:if>
		</div>
		
		<div class="doc">
			<xsl:value-of select="@doc-text"></xsl:value-of>
		</div>
		
		<xsl:if test="@doc-see">
			<xsl:variable name="anchorid"><xsl:value-of select="concat(../../@id, '-', @doc-see)" /></xsl:variable>
			<div class="see">See <a href="#{$anchorid}"><xsl:value-of select="@doc-see" /></a></div>
		</xsl:if>
		<!--
		<xsl:choose>
			<xsl:when test="@onget">
				<div class="code">
					<xsl:value-of select="@onget" />
				</div>
			</xsl:when>
			<xsl:when test="getter">
				<div class="code">
					<xsl:value-of select="getter" />
				</div>
			</xsl:when>
		</xsl:choose>
		<xsl:choose>
			<xsl:when test="@onset">
				<div class="code">
					<xsl:value-of select="@onset" />
				</div>
			</xsl:when>
			<xsl:when test="setter">
				<div class="code">
					<xsl:value-of select="setter" />
				</div>
			</xsl:when>
		</xsl:choose>
		-->
	</div>
</xsl:template>

<xsl:template match="field">
	<xsl:variable name="anchor"><xsl:value-of select="concat(../../@id, '-', @name)" /></xsl:variable>
	<a name="{$anchor}" />
	<div class="field">
		<h2>
			<strong><xsl:value-of select="@name" /></strong>
			<xsl:if test="@doc-type">
				<span class="type">(<xsl:value-of select="@doc-type" />)</span>
			</xsl:if>
		</h2>
		
		<div class="features">
			<xsl:if test="@readonly">readonly </xsl:if>
			<xsl:if test="@doc-access='private'">private </xsl:if>
		</div>
		
		<div class="doc">
			<xsl:value-of select="@doc-text"></xsl:value-of>
		</div>
		
		<xsl:if test="@doc-see">
			<xsl:variable name="anchorid"><xsl:value-of select="concat(../../@id, '-', @doc-see)" /></xsl:variable>
			<div class="see">See <a href="#{$anchorid}"><xsl:value-of select="@doc-see" /></a></div>
		</xsl:if>
		
	</div>
</xsl:template>

<xsl:template match="method">
	<xsl:variable name="anchor"><xsl:value-of select="concat(../../@id, '-', @name)" /></xsl:variable>
	<a name="{$anchor}" />
	<div class="method">
		<h2>
			<xsl:if test="@doc-final"><span class="modifier" title="Must NOT be overriden in child bindings!">final</span></xsl:if>
			<xsl:if test="@doc-overridable"><span class="modifier" title="May be overriden in child bindings">overridable</span></xsl:if>
			<xsl:value-of select="@name"></xsl:value-of>()
			<xsl:if test="@doc-type">
				<span class="type">(<xsl:value-of select="@doc-type" />)</span>
			</xsl:if>
		</h2>

		<div class="features">
			<xsl:if test="@readonly">readonly </xsl:if>
			<xsl:if test="@doc-access='private'">private </xsl:if>
		</div>
		
		<div class="doc">
			<xsl:value-of select="@doc-text"></xsl:value-of>
		</div>
		
		<xsl:apply-templates select="parameter" />

		<xsl:if test="@doc-see">
			<xsl:variable name="anchorid"><xsl:value-of select="concat(../../@id, '-', @doc-see)" /></xsl:variable>
			<div class="see">See <a href="#{$anchorid}"><xsl:value-of select="@doc-see" /></a></div>
		</xsl:if>
		
		<xsl:apply-templates select="body" />
        <xsl:apply-templates select="doc-example" />
	</div>
</xsl:template>

<xsl:template match="constructor">
	<div class="constructor">
		<h2>constructor</h2>
		<div class="doc">
			<xsl:value-of select="@doc-text"></xsl:value-of>
		</div>
		<!--
		<div class="code">
			<xsl:value-of select="." />
		</div>
		-->
	</div>
</xsl:template>

<xsl:template match="destructor">
	<div class="destructor">
		<h2>destructor</h2>
		<div class="doc">
			<xsl:value-of select="@doc-text"></xsl:value-of>
		</div>
		<!--
		<div class="code">
			<xsl:value-of select="." />
		</div>
		-->
	</div>
</xsl:template>

<xsl:template match="parameter">
	<div class="parameter">
		<h3>
			<xsl:value-of select="@name"></xsl:value-of>
			<xsl:if test="@doc-type">
				<span class="type">(<xsl:value-of select="@doc-type" />)</span>
			</xsl:if>
		</h3>
		<div class="doc">
			<xsl:value-of select="@doc-text"></xsl:value-of>
		</div>
	</div>
</xsl:template>

<xsl:template match="doc-example">
	<div class="example"><pre><xsl:value-of select="." /></pre></div>
</xsl:template>

<xsl:template match="handler">
	<xsl:variable name="anchor"><xsl:value-of select="concat(../../@id, '-', @event, translate(@modifiers,' ', '-'), @key)" /></xsl:variable>
	<a name="{$anchor}" />
	<div class="handler">
		<h2><xsl:value-of select="@event" /></h2>

		<div class="handler-modifiers">
			<xsl:if test="@modifiers">
				<xsl:value-of select="translate(@modifiers, ' ,', '++')" />
				<xsl:if test="@key">
					+ <xsl:value-of select="@key"/>
				</xsl:if>
			</xsl:if>
			
			<xsl:if test="not(@modifiers)">
				<xsl:value-of select="@key"/>
			</xsl:if>
		</div>
		
		<xsl:if test="@phase">
			Phase: <strong><xsl:value-of select="@phase"/></strong>
		</xsl:if>

		<div class="doc">
			<xsl:value-of select="@doc-text"></xsl:value-of>
		</div>

	</div>
</xsl:template>
	
<xsl:template match="body">
</xsl:template>


</xsl:stylesheet>
