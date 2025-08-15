<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:hostcms="http://www.hostcms.ru/"
	exclude-result-prefixes="hostcms">
	
	<xsl:output xmlns="http://www.w3.org/TR/xhtml1/strict" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" encoding="utf-8" indent="yes" method="html" omit-xml-declaration="no" version="1.0" media-type="text/xml"/>
	
	<!-- СписокЭлементовИнфосистемы -->
	
	<xsl:template match="/">
		<xsl:apply-templates select="/informationsystem"/>
	</xsl:template>
	
	<xsl:variable name="n" select="number(3)"/>
	
	<xsl:template match="/informationsystem">
		<xsl:variable name="group" select="group"/>
		<div>
			<xsl:apply-templates select="informationsystem_group" mode="Crumbs"/>
		</div>
	</xsl:template>
	
	<xsl:template match="informationsystem_group" mode="Crumbs">
		<xsl:variable name="group_id" select="@id"/>

		<h2><xsl:value-of select="name"/></h2>

		<div class="info_block">
			<xsl:apply-templates select="//informationsystem_item[informationsystem_group_id=$group_id]"/>
		</div>
		
		<xsl:if test="shop_group">
			<xsl:apply-templates select="informationsystem_group" mode="Crumbs"/>
		</xsl:if>
		
	</xsl:template>
	
	
	<!-- Шаблон вывода информационного элемента -->
	<xsl:template match="informationsystem_item">
		<div>
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="position() mod 2 = 0">right info_block__item</xsl:when>
					<xsl:otherwise>left info_block__item</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>

			<div>
				<xsl:if test="image_large!=''">
					<div class="info_block__img" style="background-image: url({dir}{image_large})"></div>
				</xsl:if>
			</div>
			<div class="info_block__title">
				<a href="{url}" title="{name}"><xsl:value-of select="name"/></a>
			</div>
		</div>
	</xsl:template>

	
</xsl:stylesheet>