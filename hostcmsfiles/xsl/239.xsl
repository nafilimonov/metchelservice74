<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:hostcms="http://www.hostcms.ru/"
	exclude-result-prefixes="hostcms">
	<xsl:output xmlns="http://www.w3.org/TR/xhtml1/strict" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" encoding="utf-8" indent="yes" method="html" omit-xml-declaration="no" version="1.0" media-type="text/xml"/>
	
	<!-- СписокНовостейНаГлавной -->
	
	<xsl:template match="/">
		<xsl:apply-templates select="/informationsystem"/>
	</xsl:template>
	
	<xsl:template match="/informationsystem">
		<!-- Выводим название информационной системы -->
		<p class="h2" hostcms:id="{@id}" hostcms:field="name" hostcms:entity="informationsystem">
			<xsl:value-of select="name"/>
		</p>
		
		<!-- Отображение записи информационной системы -->
		<xsl:if test="informationsystem_item">
			<p class="gallery">
				<xsl:apply-templates select="informationsystem_item"/>
			</p>
		</xsl:if>
		
	</xsl:template>
	
	<!-- Шаблон вывода информационного элемента -->
	<xsl:template match="informationsystem_item">
		<a target="_blank" href="{dir}{image_large}" rel="noopener noreferrer">
			<img src="{dir}{image_small}" border="0" alt="" width="49%"/>
		</a>
	</xsl:template>
</xsl:stylesheet>