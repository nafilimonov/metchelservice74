<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:hostcms="http://www.hostcms.ru/"
	exclude-result-prefixes="hostcms">
	
	<xsl:output xmlns="http://www.w3.org/TR/xhtml1/strict" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" encoding="utf-8" indent="yes" method="html" omit-xml-declaration="no" version="1.0" media-type="text/xml"/>
	
	<!-- ВыводЕдиницыИнформационнойСистемы  -->
	
	<xsl:template match="/">
		<xsl:apply-templates select="/informationsystem/informationsystem_item"/>
	</xsl:template>
	
	<xsl:template match="/informationsystem/informationsystem_item">
		
		<!-- Получаем ID родительской группы и записываем в переменную $group -->
		<!-- <xsl:variable name="group" select="informationsystem_group_id"/> -->
		
		<h1 hostcms:id="{@id}" hostcms:field="name" hostcms:entity="informationsystem_item"><xsl:value-of select="name"/></h1>
		
		<!-- Путь к группе -->
		<!-- <xsl:apply-templates select="//informationsystem_group[@id=$group]" mode="breadCrumbs"/> -->
		
		<!-- Выводим сообщение -->
		<xsl:if test="/informationsystem/message/node()">
			<xsl:value-of disable-output-escaping="yes" select="/informationsystem/message"/>
		</xsl:if>
		
		<!-- Фотогафия к информационному элементу
		<xsl:if test="image_small!=''">
			<xsl:choose>
				<xsl:when test="image_large!=''">
					<div class="info_block__img" style="background-image: url({dir}{image_large})"></div>
				</xsl:when>
				<xsl:otherwise>
					<div class="info_block__img" style="background-image: url({dir}{image_small})"></div>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
		-->
		<div class="shop_item_detail__photo">
			<!-- Определяем Изображение для товара -->
			<xsl:variable name="image_large_src">
				<xsl:choose>
					<xsl:when test="image_large != ''">
						<xsl:value-of disable-output-escaping="yes" select="dir"/>
						<xsl:value-of disable-output-escaping="yes" select="image_large"/>
					</xsl:when>
					<xsl:otherwise>
						/templates/template1/images/no_photo.jpg
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>

			<div class="more_photo gallery">
				<div class="info_block__slide">
					<a target="_blank" class="info_block__img" href="{$image_large_src}" style="background-image: url({$image_large_src})">
						<img itemprop="image" src="{$image_large_src}" alt="{name}" />
					</a>
				</div>
				<xsl:if test="count(property_value[tag_name='photo' and ( value != '' or file != '')])">
					<xsl:apply-templates select="property_value[tag_name='photo']" mode='photo_more'/>
				</xsl:if>
			</div>

		</div>

		
		<!-- Текст информационного элемента -->
		<xsl:choose>
			<xsl:when test="parts_count > 1">
				<xsl:value-of disable-output-escaping="yes" select="text"/>
			</xsl:when>
			<xsl:otherwise>
				<div hostcms:id="{@id}" hostcms:field="text" hostcms:entity="informationsystem_item" hostcms:type="wysiwyg">
					<xsl:value-of disable-output-escaping="yes" select="text"/>
				</div>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:value-of select="prorerty_value[tag_name = 'date']/value"/>	
		
		<!-- Ссылка 1-2-3 на части документа -->
		<xsl:if test="parts_count &gt; 1">
			<div class="read_more">Читать дальше:</div>
			
			<xsl:call-template name="for">
				<xsl:with-param name="limit">1</xsl:with-param>
				<xsl:with-param name="page" select="/informationsystem/part"/>
				<xsl:with-param name="link" select="/informationsystem/informationsystem_item/url"/>
				<xsl:with-param name="items_count" select="parts_count"/>
				<xsl:with-param name="visible_pages">6</xsl:with-param>
				<xsl:with-param name="prefix">part</xsl:with-param>
			</xsl:call-template>
			
			<div style="clear: both"></div>
		</xsl:if>


		<xsl:if test="count(property_value[property_dir_id = 2 and informationsystem_item/active = 1 and informationsystem_item != ''])">
			<div class="object-table">
				<xsl:apply-templates mode="phones" select="property_value[property_dir_id = 2 and informationsystem_item/active = 1 and informationsystem_item != '']"/>
			</div>
		</xsl:if>

		<xsl:if test="count(property_value[property_dir_id = 1 and (value != '' and value != 0) ])">
			<div class="object-characteristics">
				<h3>Характеристики дома</h3>
				<xsl:for-each select="/informationsystem/informationsystem_item_properties//property[property_dir_id = 1 ]">
					<xsl:variable name="propertyId" select="@id" />
					<xsl:apply-templates mode="specifications" select="/informationsystem/informationsystem_item/property_value[property_id = $propertyId and (value != '' and value != 0)]"/>
				</xsl:for-each>
			</div>
		</xsl:if>



		<xsl:if test="count(property_value[property_dir_id = 3 and ( value != '' or file != '')])">
			<h3>Документы и материалы</h3>
			<xsl:apply-templates select="property_value[property_dir_id = 3 and ( value != '' or file != '')]"/>
		</xsl:if>
		
		<!-- Если указано отображать комментарии -->
		<xsl:if test="/informationsystem/show_comments/node() and /informationsystem/show_comments = 1">
			
			<!-- Отображение комментариев  -->
			<xsl:if test="count(comment) &gt; 0">
			<p class="h1"><a name="comments"></a>Комментарии</p>
				<xsl:apply-templates select="comment"/>
			</xsl:if>
		</xsl:if>
		
		<!-- Если разрешено отображать формы добавления комментария
		1 - Только авторизированным
		2 - Всем
		-->
		<xsl:if test="/informationsystem/show_add_comments/node() and ((/informationsystem/show_add_comments = 1 and /informationsystem/siteuser_id &gt; 0)  or /informationsystem/show_add_comments = 2)">
			
			<p class="button" onclick="$('.comment_reply').hide('slow');$('#AddComment').toggle('slow')">
				Добавить комментарий
			</p>
			
			<div id="AddComment" class="comment_reply">
				<xsl:call-template name="AddCommentForm"></xsl:call-template>
			</div>
		</xsl:if>
	</xsl:template>
	
	<!-- Метки -->
	<xsl:template match="tag">
		<a href="{/informationsystem/url}tag/{urlencode}/" class="tag">
			<xsl:value-of select="name"/>
		</a>
	<xsl:if test="position() != last()"><xsl:text>, </xsl:text></xsl:if>
	</xsl:template>
	
	<!-- Шаблон для вывода звездочек (оценки) -->
	<xsl:template name="for_star">
		<xsl:param name="i" select="0"/>
		<xsl:param name="n"/>
		<br/>
		<xsl:if test="$n &gt; $i and $n &gt; 1">
			<xsl:call-template name="for_star">
				<xsl:with-param name="i" select="$i + 1"/>
				<xsl:with-param name="n" select="$n"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	
	<!-- Вывод рейтинга -->
	<xsl:template name="show_average_grade">
		<xsl:param name="grade" select="0"/>
		<xsl:param name="const_grade" select="0"/>
		
		<!-- Чтобы избежать зацикливания -->
		<xsl:variable name="current_grade" select="$grade * 1"/>
		
		<xsl:choose>
			<!-- Если число целое -->
			<xsl:when test="floor($current_grade) = $current_grade and not($const_grade &gt; ceiling($current_grade))">
				
				<xsl:if test="$current_grade - 1 &gt; 0">
					<xsl:call-template name="show_average_grade">
						<xsl:with-param name="grade" select="$current_grade - 1"/>
						<xsl:with-param name="const_grade" select="$const_grade - 1"/>
					</xsl:call-template>
				</xsl:if>
				
				<xsl:if test="$current_grade != 0">
					<img src="/images/star-full.png"/>
				</xsl:if>
			</xsl:when>
			<xsl:when test="$current_grade != 0 and not($const_grade &gt; ceiling($current_grade))">
				
				<xsl:if test="$current_grade - 0.5 &gt; 0">
					<xsl:call-template name="show_average_grade">
						<xsl:with-param name="grade" select="$current_grade - 0.5"/>
						<xsl:with-param name="const_grade" select="$const_grade - 1"/>
					</xsl:call-template>
				</xsl:if>
				
				<img src="/images/star-half.png"/>
			</xsl:when>
			
			<!-- Выводим серые звездочки, пока текущая позиция не дойдет то значения, увеличенного до целого -->
			<xsl:otherwise>
				<xsl:call-template name="show_average_grade">
					<xsl:with-param name="grade" select="$current_grade"/>
					<xsl:with-param name="const_grade" select="$const_grade - 1"/>
				</xsl:call-template>
				<img src="/images/star-empty.png"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<!-- Шаблон выводит рекурсивно ссылки на группы инф. элемента -->
	<xsl:template match="informationsystem_group" mode="breadCrumbs">
		<xsl:variable name="parent_id" select="parent_id"/>
		
		<!-- Выбираем рекурсивно вышестоящую группу -->
		<xsl:apply-templates select="//informationsystem_group[@id=$parent_id]" mode="breadCrumbs"/>
		
		<xsl:if test="parent_id=0">
			<a href="{/informationsystem/url}">
				<xsl:value-of select="/informationsystem/name"/>
			</a>
		</xsl:if>
		
	<span><xsl:text> → </xsl:text></span>
		
		<a href="{url}">
			<xsl:value-of select="name"/>
		</a>
	</xsl:template>
	
	<!-- Отображение комментариев -->
	<xsl:template match="comment">
		<!-- Отображаем комментарий, если задан текст или тема комментария -->
		<xsl:if test="text != '' or subject != ''">
			<a name="comment{@id}"></a>
			<div class="comment" id="comment{@id}">
				<xsl:if test="subject != ''">
					<div class="subject" hostcms:id="{@id}" hostcms:field="subject" hostcms:entity="comment"><xsl:value-of select="subject"/></div>
				</xsl:if>
				
				<div hostcms:id="{@id}" hostcms:field="text" hostcms:entity="comment" hostcms:type="wysiwyg"><xsl:value-of select="text" disable-output-escaping="yes"/></div>
				
				<p class="tags">
					<!-- Оценка комментария -->
					<xsl:if test="grade != 0">
						<span><xsl:call-template name="show_average_grade">
								<xsl:with-param name="grade" select="grade"/>
								<xsl:with-param name="const_grade" select="5"/>
						</xsl:call-template></span>
					</xsl:if>
					
					<img src="/images/user.png" />
					<xsl:choose>
						<!-- Комментарий добавил авторизированный пользователь -->
						<xsl:when test="count(siteuser) &gt; 0">
						<span><a href="/users/info/{siteuser/path}/"><xsl:value-of select="siteuser/login"/></a></span>
						</xsl:when>
						<!-- Комментарй добавил неавторизированный пользователь -->
						<xsl:otherwise>
							<span><xsl:value-of select="author" /></span>
						</xsl:otherwise>
					</xsl:choose>
					
					<xsl:if test="rate/node()">
						<span id="comment_id_{@id}" class="thumbs">
							<xsl:choose>
								<xsl:when test="/informationsystem/siteuser_id > 0">
									<xsl:choose>
										<xsl:when test="vote/value = 1">
											<xsl:attribute name="class">thumbs up</xsl:attribute>
										</xsl:when>
										<xsl:when test="vote/value = -1">
											<xsl:attribute name="class">thumbs down</xsl:attribute>
										</xsl:when>
									</xsl:choose>
									<span id="comment_likes_{@id}"><xsl:value-of select="rate/@likes" /></span>
									<span class="inner_thumbs">
										<a onclick="return $.sendVote({@id}, 1, 'comment')" href="{/informationsystem/url}?id={@id}&amp;vote=1&amp;entity_type=comment" alt="Нравится"></a>
										<span class="rate" id="comment_rate_{@id}"><xsl:value-of select="rate" /></span>
										<a onclick="return $.sendVote({@id}, 0, 'comment')" href="{/informationsystem/url}?id={@id}&amp;vote=0&amp;entity_type=comment" alt="Не нравится"></a>
									</span>
									<span id="comment_dislikes_{@id}"><xsl:value-of select="rate/@dislikes" /></span>
								</xsl:when>
								<xsl:otherwise>
									<xsl:attribute name="class">thumbs inactive</xsl:attribute>
									<span id="comment_likes_{@id}"><xsl:value-of select="rate/@likes" /></span>
									<span class="inner_thumbs">
										<a alt="Нравится"></a>
										<span class="rate" id="comment_rate_{@id}"><xsl:value-of select="rate" /></span>
										<a alt="Не нравится"></a>
									</span>
									<span id="comment_dislikes_{@id}"><xsl:value-of select="rate/@dislikes" /></span>
								</xsl:otherwise>
							</xsl:choose>
						</span>
					</xsl:if>
					
					<img src="/images/calendar.png" /> <span><xsl:value-of select="datetime"/></span>
					
					<xsl:if test="/informationsystem/show_add_comments/node()
						and ((/informationsystem/show_add_comments = 1 and /informationsystem/siteuser_id > 0)
						or /informationsystem/show_add_comments = 2)">
					<span class="red" onclick="$('.comment_reply').hide('slow');$('#cr_{@id}').toggle('slow')">ответить</span></xsl:if>
					
				<span class="red"><a href="{/informationsystem/informationsystem_item/url}#comment{@id}" title="Ссылка на комментарий">#</a></span>
				</p>
			</div>
			
			<!-- Отображаем только авторизированным пользователям -->
			<xsl:if test="/informationsystem/show_add_comments/node() and ((/informationsystem/show_add_comments = 1 and /informationsystem/siteuser_id > 0) or /informationsystem/show_add_comments = 2)">
				<div class="comment_reply" id="cr_{@id}">
					<xsl:call-template name="AddCommentForm">
						<xsl:with-param name="id" select="@id"/>
					</xsl:call-template>
				</div>
			</xsl:if>
			
			<!-- Выбираем дочерние комментарии -->
			<xsl:if test="count(comment) &gt; 0">
				<div class="comment_sub">
					<xsl:apply-templates select="comment"/>
				</div>
			</xsl:if>
		</xsl:if>
	</xsl:template>
	
	<!-- Шаблон вывода добавления комментария -->
	<xsl:template name="AddCommentForm">
		<xsl:param name="id" select="0"/>
		
		<!-- Заполняем форму -->
		<xsl:variable name="subject">
			<xsl:if test="/informationsystem/comment/parent_id/node() and /informationsystem/comment/parent_id/node() and /informationsystem/comment/parent_id= $id">
				<xsl:value-of select="/informationsystem/comment/subject"/>
			</xsl:if>
		</xsl:variable>
		<xsl:variable name="email">
			<xsl:if test="/informationsystem/comment/email/node() and /informationsystem/comment/parent_id/node() and /informationsystem/comment/parent_id= $id">
				<xsl:value-of select="/informationsystem/comment/email"/>
			</xsl:if>
		</xsl:variable>
		<xsl:variable name="phone">
			<xsl:if test="/informationsystem/comment/phone/node() and /informationsystem/comment/parent_id/node() and /informationsystem/comment/parent_id= $id">
				<xsl:value-of select="/informationsystem/comment/phone"/>
			</xsl:if>
		</xsl:variable>
		<xsl:variable name="text">
			<xsl:if test="/informationsystem/comment/text/node() and /informationsystem/comment/parent_id/node() and /informationsystem/comment/parent_id= $id">
				<xsl:value-of select="/informationsystem/comment/text"/>
			</xsl:if>
		</xsl:variable>
		<xsl:variable name="name">
			<xsl:if test="/informationsystem/comment/author/node() and /informationsystem/comment/parent_id/node() and /informationsystem/comment/parent_id= $id">
				<xsl:value-of select="/informationsystem/comment/author"/>
			</xsl:if>
		</xsl:variable>
		
		<div class="comment">
			<!--Отображение формы добавления комментария-->
			<form action="{/informationsystem/informationsystem_item/url}" name="comment_form_0{$id}" method="post">
				<!-- Авторизированным не показываем -->
				<xsl:if test="/informationsystem/siteuser_id = 0">
					
					<div class="row">
						<div class="caption">Имя</div>
						<div class="field">
							<input type="text" size="70" name="author" value="{$name}"/>
						</div>
					</div>
					
					<div class="row">
						<div class="caption">E-mail</div>
						<div class="field">
							<input id="email{$id}" type="text" size="70" name="email" value="{$email}" />
							<div id="error_email{$id}"></div>
						</div>
					</div>
					
					<div class="row">
						<div class="caption">Телефон</div>
						<div class="field">
							<input type="text" size="70" name="phone" value="{$phone}"/>
						</div>
					</div>
				</xsl:if>
				
				<div class="row">
					<div class="caption">Тема</div>
					<div class="field">
						<input type="text" size="70" name="subject" value="{$subject}"/>
					</div>
				</div>
				
				<div class="row">
					<div class="caption">Комментарий</div>
					<div class="field">
						<textarea name="text" cols="68" rows="5" class="mceEditor"><xsl:value-of select="$text"/></textarea>
					</div>
				</div>
				
				<div class="row">
					<div class="caption">Оценка</div>
					<div class="field stars">
						<select name="grade">
							<option value="1">Poor</option>
							<option value="2">Fair</option>
							<option value="3">Average</option>
							<option value="4">Good</option>
							<option value="5">Excellent</option>
						</select>
					</div>
				</div>
				
				<!-- Обработка CAPTCHA -->
				<xsl:if test="//captcha_id != 0 and /informationsystem/siteuser_id = 0">
					<div class="row">
						<div class="caption"></div>
						<div class="field">
							<img id="comment_{$id}" class="captcha" src="/captcha.php?id={//captcha_id}{$id}&amp;height=30&amp;width=100" title="Контрольное число" name="captcha"/>
							
							<div class="captcha">
								<img src="/images/refresh.png" /> <span onclick="$('#comment_{$id}').updateCaptcha('{//captcha_id}{$id}', 30); return false">Показать другое число</span>
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="caption">
					Контрольное число<sup><font color="red">*</font></sup>
						</div>
						<div class="field">
							<input type="hidden" name="captcha_id" value="{//captcha_id}{$id}"/>
							<input type="text" name="captcha" size="15"/>
						</div>
					</div>
				</xsl:if>
				
				<xsl:if test="$id != 0">
					<input type="hidden" name="parent_id" value="{$id}"/>
				</xsl:if>
				
				<div class="row">
					<div class="caption"></div>
					<div class="field">
						<input id="submit_email{$id}" type="submit" name="add_comment" value="Опубликовать" class="button" />
					</div>
				</div>
			</form>
		</div>
	</xsl:template>
	
	<!-- Вывод строки со значением свойства -->
	<xsl:template match="property_value">
		<xsl:variable name="property_id" select="property_id" />
		<xsl:variable name="proprety" select="/informationsystem/informationsystem_item_properties/property[@id=$property_id]" />

		<!-- Определение типа файла -->
		<xsl:variable name="file_type">
			<xsl:call-template name="file_type">
				<xsl:with-param name="str" select="file" />
			</xsl:call-template>
		</xsl:variable>

		<img src="/hostcmsfiles/images/icons/{$file_type}" class="img" /><xsl:text> </xsl:text>
		<a href="{/informationsystem/informationsystem_item/dir}{file}" target="_blank">
			<xsl:value-of disable-output-escaping="yes" select="file_description"/>
		</a>
		<br/>

	</xsl:template>

	<xsl:template match="property_value" mode="specifications">

		<xsl:variable name="property_id" select="property_id" />
		<xsl:variable name="property_dir" select="property_dir_id" />
		<xsl:variable name="proprety" select="/informationsystem/informationsystem_item_properties/property_dir[@id=$property_dir]/property[@id=$property_id]" />

		<div class="object-characteristics__row">
			<div class="object-characteristics__field"><xsl:value-of disable-output-escaping="yes" select="$proprety/name"/></div>
			<div class="object-characteristics__dottes"></div>
			<div class="object-characteristics__value"><xsl:value-of disable-output-escaping="yes" select="value"/></div>
		</div>
	</xsl:template>

	<xsl:template match="property_value" mode="phones">

		<xsl:variable name="property_dir" select="informationsystem_item/informationsystem_group_id" />

		<div class="object-table__item">
			<div class="object-table__row">
				<div class="object-table__field">
					<xsl:value-of disable-output-escaping="yes" select="/informationsystem/phones_groups/phones_group[@id=$property_dir]/name"/>
				</div>
				<div class="object-table__dottes"></div>
				<div class="object-table__phone"><xsl:value-of disable-output-escaping="yes" select="informationsystem_item/description"/></div>
			</div>

			<xsl:if test="informationsystem_item/name != '' and ($property_dir != 39 and  $property_dir != 32)">
				<div class="object-table__description"><xsl:value-of disable-output-escaping="yes" select="informationsystem_item/name"/></div>
			</xsl:if>

		</div>

	</xsl:template>

	<!-- Вывод строки со значением свойства -->
	<xsl:template match="property_value" mode='photo_more'>
		<xsl:variable name="property_id" select="property_id" />
		<xsl:variable name="property" select="/shop/shop_item_properties//property[@id=$property_id]" />

		<div class="info_block__slide">
			<a target="_blank" href="{../dir}{file}" class="info_block__img" style="background-image: url({../dir}{file})">
				<img src="{../dir}{file}" alt=""/>
			</a>
		</div>


	</xsl:template>
	
	<!-- Цикл для вывода строк ссылок -->
	<xsl:template name="for">
		<xsl:param name="i" select="0"/>
		<xsl:param name="prefix">page</xsl:param>
		<xsl:param name="link"/>
		<xsl:param name="limit"/>
		<xsl:param name="page"/>
		<xsl:param name="items_count"/>
		<xsl:param name="visible_pages"/>
		
		<xsl:variable name="n" select="$items_count div $limit"/>
		
		<!-- Заносим в переменную $group идентификатор текущей группы -->
		<xsl:variable name="group" select="/informationsystem/group"/>
		
		<!-- Считаем количество выводимых ссылок перед текущим элементом -->
		<xsl:variable name="pre_count_page">
			<xsl:choose>
				<xsl:when test="$page &gt; ($n - (round($visible_pages div 2) - 1))">
					<xsl:value-of select="$visible_pages - ($n - $page)"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="round($visible_pages div 2) - 1"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<!-- Считаем количество выводимых ссылок после текущего элемента -->
		<xsl:variable name="post_count_page">
			<xsl:choose>
				<xsl:when test="0 &gt; $page - (round($visible_pages div 2) - 1)">
					<xsl:value-of select="$visible_pages - $page"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="round($visible_pages div 2) = ($visible_pages div 2)">
							<xsl:value-of select="$visible_pages div 2"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="round($visible_pages div 2) - 1"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<xsl:if test="$items_count &gt; $limit and $n &gt; $i">
			<!-- Ставим ссылку на страницу-->
			<xsl:if test="$i != $page">
				<!-- Определяем адрес тэга -->
				<xsl:variable name="tag_link">
					<xsl:choose>
						<!-- Если не нулевой уровень -->
						<xsl:when test="count(/informationsystem/tag) != 0">tag/<xsl:value-of select="/informationsystem/tag/urlencode"/>/</xsl:when>
						<!-- Иначе если нулевой уровень - просто ссылка на страницу со списком элементов -->
						<xsl:otherwise></xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				
				<!-- Определяем адрес ссылки -->
				<xsl:variable name="number_link">
					
					<xsl:choose>
						<!-- Если не нулевой уровень -->
						<xsl:when test="$i != 0">
							<xsl:value-of select="$prefix"/>-<xsl:value-of select="$i + 1"/>/</xsl:when>
						<!-- Иначе если нулевой уровень - просто ссылка на страницу со списком элементов -->
						<xsl:otherwise></xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				
				<!-- Выводим ссылку на первую страницу -->
				<xsl:if test="$page - $pre_count_page &gt; 0 and $i = 0">
					<a href="{$link}" class="page_link" style="text-decoration: none;">←</a>
				</xsl:if>
				
				<xsl:choose>
					<xsl:when test="$i &gt;= ($page - $pre_count_page) and ($page + $post_count_page) &gt;= $i">
						
						<!-- Выводим ссылки на видимые страницы -->
						<a href="{$link}{$tag_link}{$number_link}" class="page_link">
							<xsl:value-of select="$i + 1"/>
						</a>
					</xsl:when>
					<xsl:otherwise></xsl:otherwise>
				</xsl:choose>
				
				<!-- Выводим ссылку на последнюю страницу -->
				<xsl:if test="$i+1 &gt;= $n and $n &gt; ($page + 1 + $post_count_page)">
					<xsl:choose>
						<xsl:when test="$n &gt; round($n)">
							<!-- Выводим ссылку на последнюю страницу -->
							<a href="{$link}{$prefix}-{round($n+1)}/" class="page_link" style="text-decoration: none;">→</a>
						</xsl:when>
						<xsl:otherwise>
							<a href="{$link}{$prefix}-{round($n)}/" class="page_link" style="text-decoration: none;">→</a>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:if>
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
				<xsl:with-param name="prefix" select="$prefix"/>
				<xsl:with-param name="link" select="$link"/>
				<xsl:with-param name="limit" select="$limit"/>
				<xsl:with-param name="page" select="$page"/>
				<xsl:with-param name="items_count" select="$items_count"/>
				<xsl:with-param name="visible_pages" select="$visible_pages"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	
	<!-- Склонение после числительных -->
	<xsl:template name="declension">
		
		<xsl:param name="number" select="number"/>
		
		<!-- Именительный падеж -->
	<xsl:variable name="nominative"><xsl:text>просмотр</xsl:text></xsl:variable>
		
		<!-- Родительный падеж, единственное число -->
	<xsl:variable name="genitive_singular"><xsl:text>просмотра</xsl:text></xsl:variable>
		
	<xsl:variable name="genitive_plural"><xsl:text>просмотров</xsl:text></xsl:variable>
		<xsl:variable name="last_digit"><xsl:value-of select="$number mod 10"/></xsl:variable>
		<xsl:variable name="last_two_digits"><xsl:value-of select="$number mod 100"/></xsl:variable>
		
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