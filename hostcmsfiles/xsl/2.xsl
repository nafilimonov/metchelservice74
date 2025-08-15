<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:hostcms="http://www.hostcms.ru/"
	exclude-result-prefixes="hostcms">
	<xsl:output xmlns="http://www.w3.org/TR/xhtml1/strict" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" encoding="utf-8" indent="yes" method="html" omit-xml-declaration="no" version="1.0" media-type="text/xml"/>
	
	<xsl:template match="/site">
		<ul class="top_menu">
			<!-- Выбираем узлы структуры первого уровня -->
			<xsl:apply-templates select="structure[show=1]" />
		</ul>
	</xsl:template>
	
	<!-- Запишем в константу ID структуры, данные для которой будут выводиться пользователю -->
	<xsl:variable name="current_structure_id" select="/site/current_structure_id"/>
	
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
			
			<xsl:if test="position() = last()">
				<xsl:attribute name="style">border-right: none</xsl:attribute>
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
			<xsl:choose>
				<xsl:when test="$current_structure_id = @id">
					<xsl:value-of select="name"/>
				</xsl:when>
				<xsl:otherwise>
					<a href="{$link}" title="{name}" hostcms:id="{@id}" hostcms:field="name" hostcms:entity="structure">
						
						<xsl:if test="type = 3 and sorting = 999">
							<xsl:attribute name="class">btn</xsl:attribute>
							<xsl:attribute name="target">_blank</xsl:attribute>
						</xsl:if>
						
						<xsl:value-of select="name"/>
					</a>
				</xsl:otherwise>
			</xsl:choose>
			
			<!-- Выводим подуровни меню -->
			<xsl:if test="count(structure[show = 1]) &gt; 0">
				<ul>
					<xsl:apply-templates select="structure[show=1]" mode="pool"/>
				</ul>
			</xsl:if>
			
			<!-- Выводим подуровни меню -->
			<xsl:if test="informationsystem_group">
				<ul>
					<xsl:apply-templates select="informationsystem_group"/>
				</ul>
			</xsl:if>
		</li>
	</xsl:template>
	
	<xsl:template match="structure" mode="pool">
		<li >
			<nobr>
				<!-- Показывать ссылку, или нет -->
				<xsl:if test="show=1">
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
					
					<a href="{$link}" >
						<xsl:value-of select="name"/>
					</a>
				</xsl:if>
				
				<!-- Если не показывать ссылку - выводим просто имя ссылки -->
				<xsl:if test="show=0">
					&#8226;<xsl:value-of select="name"/>
				</xsl:if>
			</nobr>
		</li>
	</xsl:template>
	
	<xsl:template match="informationsystem_group">
		<li >
			<nobr>
				<!-- Показывать ссылку, или нет -->
				<a href="{link}" >
					<xsl:value-of select="name"/>
				</a>
			</nobr>
		</li>
	</xsl:template>
	
</xsl:stylesheet>