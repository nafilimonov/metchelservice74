<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:hostcms="http://www.hostcms.ru/"
	exclude-result-prefixes="hostcms">
	<xsl:output xmlns="http://www.w3.org/TR/xhtml1/strict" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" encoding="utf-8" indent="yes" method="html" omit-xml-declaration="no" version="1.0" media-type="text/xml"/>
	
	<xsl:template match="/site">
		<ul class="right_menu">
			<xsl:apply-templates select="structure[show=1]"/>
			<!-- Выводим подуровни меню -->
			<xsl:if test="informationsystem_group">
				<xsl:apply-templates select="informationsystem_group"/>
			</xsl:if>
		</ul>
	</xsl:template>
	
	<xsl:template match="structure">
		
		<!-- Запишем в константу ID структуры, данные для которой будут выводиться пользователю -->
		<xsl:variable name="current_structure_id" select="/site/current_structure_id"/>
		
		<li>
			<!--
			Выделяем текущую страницу добавлением к li класса current,
			если это текущая страница, либо у нее есть ребенок с атрибутом id, равным текущей группе.
			-->
			<xsl:if test="$current_structure_id = @id or count(.//structure[@id=$current_structure_id]) = 1">
				<xsl:attribute name="class">current</xsl:attribute>
			</xsl:if>
			
			<!-- Определяем адрес ссылки -->
			<xsl:variable name="link">
				<xsl:choose>
					<!-- Если внешняя ссылка -->
					<xsl:when test="url != ''">
						<xsl:value-of disable-output-escaping="yes" select="url"/>
					</xsl:when>
					<!-- Иначе если внутренняя ссылка -->
					<xsl:otherwise>
						<xsl:value-of disable-output-escaping="yes" select="link"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			<!-- Ссылка на пункт меню -->
			<!-- Показывать ссылку, или нет -->
			<xsl:choose>
				<xsl:when test="$current_structure_id = @id or count(.//structure[@id=$current_structure_id]) = 1">
					<xsl:value-of select="name"/>
				</xsl:when>
				<xsl:otherwise>
					<a href="{$link}" title="{name}" hostcms:id="{@id}" hostcms:field="name" hostcms:entity="structure">
						<xsl:value-of select="name"/>
					</a>
				</xsl:otherwise>
			</xsl:choose>
			
		</li>
	</xsl:template>

	<xsl:template match="informationsystem_group">
		<!-- Запишем в константу ID структуры, данные для которой будут выводиться пользователю -->
		<xsl:variable name="current_structure_id" select="/site/informationsystem_group_id"/>
		<li>
			<xsl:if test="$current_structure_id = @id">
				<xsl:attribute name="class">current</xsl:attribute>
			</xsl:if>
			
			<!-- Показывать ссылку, или нет -->
			<xsl:choose>
				<xsl:when test="$current_structure_id = @id">
					<xsl:value-of select="name"/>
				</xsl:when>
				<xsl:otherwise>
					<a href="{link}" >
						<xsl:value-of select="name"/>
					</a>
				</xsl:otherwise>
			</xsl:choose>
		</li>
	</xsl:template>
	
</xsl:stylesheet>