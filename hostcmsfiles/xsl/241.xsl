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
		
	<div class="fond-huse-group"><b><xsl:value-of select="name"/></b></div>
		
		<ol class="fond-huse">
			<xsl:apply-templates select="//informationsystem_item[informationsystem_group_id=$group_id]"/>
		</ol>
		
		<xsl:if test="shop_group">
			<xsl:apply-templates select="informationsystem_group" mode="Crumbs"/>
		</xsl:if>
		
	</xsl:template>
	
	
	<!-- Шаблон вывода информационного элемента -->
	<xsl:template match="informationsystem_item">
		<xsl:variable name="dir" select="dir" />
		<li>
			<span><xsl:value-of select="name"/></span>
			
			<xsl:if test="property_value[tag_name='OH']/file != ''">
				
				<xsl:for-each select="property_value[tag_name = 'OH']">
					
					<!-- Определение типа файла -->
					<xsl:variable name="file_type">
						<xsl:call-template name="file_type">
							<xsl:with-param name="str" select="file" />
						</xsl:call-template>
					</xsl:variable>
					
					<img src="/hostcmsfiles/images/icons/{$file_type}" class="img" /><xsl:text> </xsl:text>
					<a href="{$dir}{file}" target="_blank">
						<xsl:value-of disable-output-escaping="yes" select="file_description"/>
					</a>
					<br/>
					
				</xsl:for-each>
			</xsl:if>
			
		</li>
	</xsl:template>
	
	<!-- Цикл для определения типа файла -->
	<xsl:template name="file_type">
		<xsl:param name="str"/>
		
		<xsl:variable name="sub_str">
			<xsl:value-of select="substring-after($str, '.')" />
		</xsl:variable>
		
		<xsl:choose>
			<xsl:when test="$sub_str = ''">file.gif</xsl:when>
			<xsl:when test="$sub_str = 'sql'">sql.gif</xsl:when>
			<xsl:when test="$sub_str = 'css'">css.gif</xsl:when>
			<xsl:when test="$sub_str = 'gif'">gif.gif</xsl:when>
			<xsl:when test="$sub_str = 'bmp'">bmp.gif</xsl:when>
			<xsl:when test="$sub_str = 'png'">png.gif</xsl:when>
			<xsl:when test="$sub_str = 'ico'">image.gif</xsl:when>
			<xsl:when test="$sub_str = 'xml'">xml.gif</xsl:when>
			<xsl:when test="$sub_str = 'xsl'">xsl.gif</xsl:when>
			<xsl:when test="$sub_str = 'rar'">rar.gif</xsl:when>
			<xsl:when test="$sub_str = 'pdf'">pdf.gif</xsl:when>
			<xsl:when test="$sub_str = 'rb'">rb.gif</xsl:when>
			<xsl:when test="$sub_str = 'mdb'">mdb.gif</xsl:when>
			<xsl:when test="$sub_str = 'h'">h.gif</xsl:when>
			<xsl:when test="$sub_str = 'xls' or $sub_str = 'xlsx'">xls.gif</xsl:when>
			<xsl:when test="$sub_str = 'cpp'">cpp.gif</xsl:when>
			<xsl:when test="$sub_str = 'chm'">chm.gif</xsl:when>
			<xsl:when test="$sub_str = 'doc' or $sub_str = 'docx'">doc.gif</xsl:when>
			<xsl:when test="$sub_str = 'htm' or $sub_str = 'html'">html.gif</xsl:when>
			<xsl:when test="$sub_str = 'php' or $sub_str = 'php3'">php.gif</xsl:when>
			<xsl:when test="$sub_str = 'jpg' or $sub_str = 'jpeg'">jpg.gif</xsl:when>
			<xsl:when test="$sub_str = 'fla' or $sub_str = 'fla'">flash.gif</xsl:when>
			<xsl:when test="$sub_str = 'zip' or $sub_str = 'gz' or $sub_str = '7z'">zip.gif</xsl:when>
			<xsl:when test="$sub_str = 'cdr' or $sub_str = 'ai' or $sub_str = 'eps'">vector.gif</xsl:when>
			<xsl:when test="$sub_str = 'ppt' or $sub_str = 'pptx' or $sub_str = 'pptm'">ppt.gif</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="file_type">
					<xsl:with-param name="str" select="$sub_str"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	
</xsl:stylesheet>