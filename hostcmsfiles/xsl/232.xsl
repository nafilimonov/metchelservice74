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
		
		<!-- Получаем ID родительской группы и записываем в переменную $group -->
		<xsl:variable name="group" select="group"/>
		
		<!-- Если в находимся корне - выводим название информационной системы -->
		
		<h1 hostcms:id="{@id}" hostcms:field="name" hostcms:entity="informationsystem">
			<xsl:value-of select="name"/>
		</h1>
		
		<!-- Описание выводится при отсутствии фильтрации по тэгам -->
		<xsl:if test="count(tag) = 0 and page = 0 and description != ''">
			<div hostcms:id="{@id}" hostcms:field="description" hostcms:entity="informationsystem" hostcms:type="wysiwyg"><xsl:value-of disable-output-escaping="yes" select="description"/></div>
		</xsl:if>
		
		
		<!-- Отображение подгрупп данной группы, только если подгруппы есть и не идет фильтра по меткам -->
		
		<div>
			<xsl:apply-templates select="informationsystem_group" mode="Crumbs"/>
		</div>
		
		
		
		<!-- Строка ссылок на другие страницы информационной системы -->
		<xsl:if test="ОтображатьСсылкиНаСледующиеСтраницы=1">
			<div>
				<!-- Ссылка, для которой дописываются суффиксы page-XX/ -->
				<!-- <xsl:variable name="link">
					<xsl:value-of select="/informationsystem/url"/>
					<xsl:if test="$group != 0">
						<xsl:value-of select="/informationsystem//informationsystem_group[@id = $group]/url"/>
					</xsl:if>
				</xsl:variable> -->
				
				<xsl:if test="total &gt; 0 and limit &gt; 0">
					
					<xsl:variable name="count_pages" select="ceiling(total div limit)"/>
					
					<xsl:variable name="visible_pages" select="5"/>
					
					<xsl:variable name="real_visible_pages"><xsl:choose>
							<xsl:when test="$count_pages &lt; $visible_pages"><xsl:value-of select="$count_pages"/></xsl:when>
							<xsl:otherwise><xsl:value-of select="$visible_pages"/></xsl:otherwise>
					</xsl:choose></xsl:variable>
					
					<!-- Считаем количество выводимых ссылок перед текущим элементом -->
					<xsl:variable name="pre_count_page"><xsl:choose>
							<xsl:when test="page - (floor($real_visible_pages div 2)) &lt; 0">
								<xsl:value-of select="page"/>
							</xsl:when>
							<xsl:when test="($count_pages - page - 1) &lt; floor($real_visible_pages div 2)">
								<xsl:value-of select="$real_visible_pages - ($count_pages - page - 1) - 1"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:choose>
									<xsl:when test="round($real_visible_pages div 2) = $real_visible_pages div 2">
										<xsl:value-of select="floor($real_visible_pages div 2) - 1"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="floor($real_visible_pages div 2)"/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
					</xsl:choose></xsl:variable>
					
					<!-- Считаем количество выводимых ссылок после текущего элемента -->
					<xsl:variable name="post_count_page"><xsl:choose>
							<xsl:when test="0 &gt; page - (floor($real_visible_pages div 2) - 1)">
								<xsl:value-of select="$real_visible_pages - page - 1"/>
							</xsl:when>
							<xsl:when test="($count_pages - page - 1) &lt; floor($real_visible_pages div 2)">
								<xsl:value-of select="$real_visible_pages - $pre_count_page - 1"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="$real_visible_pages - $pre_count_page - 1"/>
							</xsl:otherwise>
					</xsl:choose></xsl:variable>
					
					<xsl:variable name="i"><xsl:choose>
							<xsl:when test="page + 1 = $count_pages"><xsl:value-of select="page - $real_visible_pages + 1"/></xsl:when>
							<xsl:when test="page - $pre_count_page &gt; 0"><xsl:value-of select="page - $pre_count_page"/></xsl:when>
							<xsl:otherwise>0</xsl:otherwise>
					</xsl:choose></xsl:variable>
					
					<p>
						<xsl:call-template name="for">
							<xsl:with-param name="limit" select="limit"/>
							<xsl:with-param name="page" select="page"/>
							<xsl:with-param name="items_count" select="total"/>
							<xsl:with-param name="i" select="$i"/>
							<xsl:with-param name="post_count_page" select="$post_count_page"/>
							<xsl:with-param name="pre_count_page" select="$pre_count_page"/>
							<xsl:with-param name="visible_pages" select="$real_visible_pages"/>
						</xsl:call-template>
					</p>
					<div style="clear: both"></div>
				</xsl:if>
			</div>
		</xsl:if>
		
		<div style="clear: both"></div>
	</xsl:template>
	
	
	<!-- Шаблон выводит рекурсивно ссылки на группы инф. элемента -->
	<xsl:template match="informationsystem_group" mode="breadCrumbs">
		<xsl:variable name="parent_id" select="parent_id"/>
		
		<xsl:apply-templates select="//informationsystem_group[@id=$parent_id]" mode="breadCrumbs"/>
		
		<xsl:if test="parent_id=0">
			<a href="{/informationsystem/url}" hostcms:id="{/informationsystem/@id}" hostcms:field="name" hostcms:entity="informationsystem">
				<xsl:value-of select="/informationsystem/name"/>
			</a>
		</xsl:if>
		
	<span><xsl:text> → </xsl:text></span>
		
		<a href="{url}" hostcms:id="{@id}" hostcms:field="name" hostcms:entity="informationsystem_group">
			<xsl:value-of select="name"/>
		</a>
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
		<xsl:if test="image_small != ''">
			<a href="{url}" title="{name}"><img class="images" src="{dir}{image_small}" alt="{name}" /></a>
		</xsl:if>
	</xsl:template>
	
	
	<!-- Шаблон вывода информационного элемента -->
	<xsl:template match="informationsystem_item">
		<xsl:variable name="dir" select="dir" />
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
			<div class="info_block__text">
				<xsl:if test="count(property_value[property_dir_id = 0 and ( value != '' or file != '')])">
					<xsl:for-each select="property_value[property_dir_id = 0 and ( value != '' or file != '')]">
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
			</div>
		</div>

	</xsl:template>
	
	<!-- Цикл для вывода строк ссылок -->
	<xsl:template name="for">
		
		<xsl:param name="limit"/>
		<xsl:param name="page"/>
		<xsl:param name="pre_count_page"/>
		<xsl:param name="post_count_page"/>
		<xsl:param name="i" select="0"/>
		<xsl:param name="items_count"/>
		<xsl:param name="visible_pages"/>
		
		<xsl:variable name="n" select="ceiling($items_count div $limit)"/>
		
		<xsl:variable name="start_page"><xsl:choose>
				<xsl:when test="$page + 1 = $n"><xsl:value-of select="$page - $visible_pages + 1"/></xsl:when>
				<xsl:when test="$page - $pre_count_page &gt; 0"><xsl:value-of select="$page - $pre_count_page"/></xsl:when>
				<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose></xsl:variable>
		
		<xsl:if test="$i = $start_page and $page != 0">
			<span class="ctrl">
				← Ctrl
			</span>
		</xsl:if>
		
		<xsl:if test="$i = ($page + $post_count_page + 1) and $n != ($page+1)">
			<span class="ctrl">
				Ctrl →
			</span>
		</xsl:if>
		
		<xsl:if test="$items_count &gt; $limit and ($page + $post_count_page + 1) &gt; $i">
			<!-- Заносим в переменную $group идентификатор текущей группы -->
			<xsl:variable name="group" select="/informationsystem/group"/>
			
			<!-- Путь для тэга -->
			<xsl:variable name="tag_path">
				<xsl:choose>
					<!-- Если не нулевой уровень -->
					<xsl:when test="count(/informationsystem/tag) != 0">tag/<xsl:value-of select="/informationsystem/tag/urlencode"/>/</xsl:when>
					<!-- Иначе если нулевой уровень - просто ссылка на страницу со списком элементов -->
					<xsl:otherwise></xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			
			<!-- Определяем группу для формирования адреса ссылки -->
			<xsl:variable name="group_link">
				<xsl:choose>
					<!-- Если группа не корневая (!=0) -->
					<xsl:when test="$group != 0">
						<xsl:value-of select="/informationsystem//informationsystem_group[@id=$group]/url"/>
					</xsl:when>
					<!-- Иначе если нулевой уровень - просто ссылка на страницу со списком элементов -->
					<xsl:otherwise><xsl:value-of select="/informationsystem/url"/></xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			
			<!-- Определяем адрес ссылки -->
			<xsl:variable name="number_link">
				<xsl:choose>
					<!-- Если не нулевой уровень -->
					<xsl:when test="$i != 0">page-<xsl:value-of select="$i + 1"/>/</xsl:when>
					<!-- Иначе если нулевой уровень - просто ссылка на страницу со списком элементов -->
					<xsl:otherwise></xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			
			<!-- Выводим ссылку на первую страницу -->
			<xsl:if test="$page - $pre_count_page &gt; 0 and $i = $start_page">
				<a href="{$group_link}{$tag_path}" class="page_link" style="text-decoration: none;">←</a>
			</xsl:if>
			
			<!-- Ставим ссылку на страницу-->
			<xsl:if test="$i != $page">
				<xsl:if test="($page - $pre_count_page) &lt;= $i and $i &lt; $n">
					<!-- Выводим ссылки на видимые страницы -->
					<a href="{$group_link}{$number_link}{$tag_path}" class="page_link">
						<xsl:value-of select="$i + 1"/>
					</a>
				</xsl:if>
				
				<!-- Выводим ссылку на последнюю страницу -->
				<xsl:if test="$i+1 &gt;= ($page + $post_count_page + 1) and $n &gt; ($page + 1 + $post_count_page)">
					<!-- Выводим ссылку на последнюю страницу -->
					<a href="{$group_link}page-{$n}/{$tag_path}" class="page_link" style="text-decoration: none;">→</a>
				</xsl:if>
			</xsl:if>
			
			<!-- Ссылка на предыдущую страницу для Ctrl + влево -->
			<xsl:if test="$page != 0 and $i = $page">
				<xsl:variable name="prev_number_link">
					<xsl:choose>
						<!-- Если не нулевой уровень -->
						<xsl:when test="($page) != 0">page-<xsl:value-of select="$i"/>/</xsl:when>
						<!-- Иначе если нулевой уровень - просто ссылка на страницу со списком элементов -->
						<xsl:otherwise></xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				
				<a href="{$group_link}{$prev_number_link}{$tag_path}" id="id_prev"></a>
			</xsl:if>
			
			<!-- Ссылка на следующую страницу для Ctrl + вправо -->
			<xsl:if test="($n - 1) > $page and $i = $page">
				<a href="{$group_link}page-{$page+2}/{$tag_path}" id="id_next"></a>
			</xsl:if>
			
			<!-- Не ставим ссылку на страницу-->
			<xsl:if test="$i = $page">
				<span class="current">
					<xsl:value-of select="$i+1"/>
				</span>
			</xsl:if>
			
			<!-- Рекурсивный вызов шаблона. НЕОБХОДИМО ПЕРЕДАВАТЬ ВСЕ НЕОБХОДИМЫЕ ПАРАМЕТРЫ! -->
			<xsl:call-template name="for">
				<xsl:with-param name="i" select="$i + 1"/>
				<xsl:with-param name="limit" select="$limit"/>
				<xsl:with-param name="page" select="$page"/>
				<xsl:with-param name="items_count" select="$items_count"/>
				<xsl:with-param name="pre_count_page" select="$pre_count_page"/>
				<xsl:with-param name="post_count_page" select="$post_count_page"/>
				<xsl:with-param name="visible_pages" select="$visible_pages"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	
	<!-- Склонение после числительных -->
	<xsl:template name="declension">
		
		<xsl:param name="number" select="number"/>
		
		<!-- Именительный падеж -->
		<xsl:variable name="nominative">
			<xsl:text>комментарий</xsl:text>
		</xsl:variable>
		
		<!-- Родительный падеж, единственное число -->
		<xsl:variable name="genitive_singular">
			<xsl:text>комментария</xsl:text>
		</xsl:variable>
		
		
		<xsl:variable name="genitive_plural">
			<xsl:text>комментариев</xsl:text>
		</xsl:variable>
		
		<xsl:variable name="last_digit">
			<xsl:value-of select="$number mod 10"/>
		</xsl:variable>
		
		<xsl:variable name="last_two_digits">
			<xsl:value-of select="$number mod 100"/>
		</xsl:variable>
		
		<xsl:choose>
			<xsl:when test="$last_digit = 1 and $last_two_digits != 11">
				<xsl:value-of select="$nominative"/>
			</xsl:when>
			<xsl:when test="$last_digit = 2 and $last_two_digits != 12
				or $last_digit = 3 and $last_two_digits != 13
				or $last_digit = 4 and $last_two_digits != 14">
				<xsl:value-of select="$genitive_singular"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$genitive_plural"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<!-- Вывод строки со значением свойства -->
	<xsl:template match="property_value">
		<xsl:variable name="entity_id" select="entity_id" />
		<!-- Определение типа файла -->
		<xsl:variable name="file_type">
			<xsl:call-template name="file_type">
				<xsl:with-param name="str" select="file" />
			</xsl:call-template>
		</xsl:variable>
		
		<xsl:variable name="property_id" select="property_id" />
		<xsl:variable name="proprety" select="/informationsystem/informationsystem_item_properties/property[@id=$property_id]" />
		<xsl:variable name="dir" select="/informationsystem/informationsystem_item[@id=$entity_id]/dir" />
		
	<img src="/hostcmsfiles/images/icons/{$file_type}" class="img" /><xsl:text> </xsl:text><a href="{$dir}{file}" target="_blank"><xsl:value-of disable-output-escaping="yes" select="file_description"/></a><br/>
		
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