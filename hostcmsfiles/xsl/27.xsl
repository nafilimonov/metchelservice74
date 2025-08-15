<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:hostcms="http://www.hostcms.ru/"
	exclude-result-prefixes="hostcms">
	<xsl:output xmlns="http://www.w3.org/TR/xhtml1/strict" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" encoding="utf-8" indent="yes" method="html" omit-xml-declaration="no" version="1.0" media-type="text/xml"/>
	
	<xsl:template match="/siteuser">
		
		<xsl:choose>
			<!-- Авторизованный пользователь -->
			<xsl:when test="@id > 0">
				<h1>Пользователь <xsl:value-of select="login" /></h1>
				
				<!-- Выводим меню -->
				<ul class="users">
                    <li>
                        <a href="registration/">Личная информация</a>
                    </li>
                    <li>
                        <a href="?action=exit">Выход</a>
                    </li>
				</ul>

			</xsl:when>
			<!-- Неавторизованный пользователь -->
			<xsl:otherwise>
				<div class="authorization">
					<h1>Личный кабинет</h1>
					
					<!-- Выводим ошибку, если она была передана через внешний параметр -->
					<xsl:if test="error/node()">
						<div id="error">
							<xsl:value-of select="error"/>
						</div>
					</xsl:if>
					
					<form action="/users/" method="post">
						<p>Пользователь:
							<br /><input name="login" type="text" size="30" class="large" />
						</p>
						<p>Пароль:
							<br /><input name="password" type="password" size="30" class="large" />
						</p>
						<p>
							<label><input name="remember" type="checkbox" /> Запомнить меня на сайте.</label>
						</p>
						<input name="apply" type="submit" value="Войти" class="button" />
						
						<!-- Страница редиректа после авторизации -->
						<xsl:if test="location/node()">
							<input name="location" type="hidden" value="{location}" />
						</xsl:if>
					</form>
					
				<p>Первый раз на сайте? — <a href="/users/registration/">Зарегистрируйтесь</a>!</p>
					
				<p>Забыли пароль? Мы можем его <a href="/users/restore_password/">восстановить</a>.</p>
				</div>

			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="item">
		<li style="background: url('{image}') no-repeat 11px 5px">
			<a href="{path}">
				<xsl:value-of select="name"/>
			</a>
		</li>
	</xsl:template>
</xsl:stylesheet>