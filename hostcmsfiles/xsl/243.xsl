<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:hostcms="http://www.hostcms.ru/"
	exclude-result-prefixes="hostcms">
	<xsl:output xmlns="http://www.w3.org/TR/xhtml1/strict" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" encoding="utf-8" indent="no" method="xml" omit-xml-declaration="yes" version="1.0" media-type="text/xml"/>

	<xsl:template match="/shop">
		<html>
			<head>
				<title>Печать ценников</title>
				<meta http-equiv="Content-Language" content="ru"/>
				<meta content="text/html; charset=UTF-8" http-equiv="Content-Type"/>
				<link type="text/css" href="/modules/skin/bootstrap/css/font-awesome.min.css" rel="stylesheet" />
				<style>
					.container { padding: 2px; border: 1px dashed #000; display: inline-block; }
					.container .item {
						width: <xsl:value-of select="width" />mm;
						font-size: <xsl:value-of select="font" />px;
						overflow: hidden;
						font-family: Arial, sans-serif;
						border: 1px solid #000;
						margin: 0;
						line-height: 150%
					}
					.container .item .title { font-size: 9px; }
					.container .item .barcode-image { min-height: 47px; text-align: center; margin-top: 5px; border-bottom: 1px solid #000; }
					.container .item .barcode-image img { height: 25px; width: 90%; }
					.container .item .title { border-bottom: 1px solid #000; text-align: center; }
					.container .item .name { height: 42px; }
					.container .item .name { border-bottom: 1px solid #000; text-align: center; font-weight: bold; font-size: 1.4em; overflow: hidden}
					.container .item .price-container { border-bottom: 1px solid #000; text-align: right; }
					.container .item .price-container div { display: inline-block; font-size: 20px; }
					.container .item .price-container .price { font-size: 20px; padding-right: 5px; }
					.container .item .price-container .currency { border-left: 1px solid #000; padding: 5px 10px; }
					.container .item .bottom-container { height: 16px; }
					.container .item .bottom-container div { display: inline-block; padding: 0 10px; text-align: center; overflow: hidden; }
					.container .item .bottom-container div:not(:last-child) { border-right: 1px solid #000; }
					.container .item .bottom-container .marking { width: 30%; }
					.container .item .bottom-container .date { width: 30%; }
					.container .item .bottom-container .measure { max-width: 35px; }

					.pagebreak { page-break-before: always; }
				</style>
			</head>
			<body>
				<xsl:apply-templates select="shop_item" />
			</body>
		</html>
	</xsl:template>

	<xsl:template match="shop_item"><div class="container">
			<div class="item">
				<div class="title"><xsl:value-of select="/shop/company/name" /></div>
				<div class="name"><xsl:value-of select="name" /></div>
				<div class="barcode-image">
					<xsl:choose>
						<xsl:when test="barcode_image !=''">
							<img src="data:image/png;base64,{barcode_image}"/>
						</xsl:when>
						<xsl:otherwise></xsl:otherwise>
					</xsl:choose>
					<div><xsl:value-of select="barcode" /></div>
				</div>
				<div class="price-container">
					<div class="price"><xsl:value-of select="price" /></div>
					<div class="currency">
						<xsl:choose>
							<xsl:when test="shop_currency/code = 'RUB' or shop_currency/code = 'RUR'">
								<i class="fa fa-rub"></i>
							</xsl:when>
							<xsl:when test="shop_currency/code = 'USD'">
								<i class="fa fa-usd"></i>
							</xsl:when>
							<xsl:when test="shop_currency/code = 'EUR'">
								<i class="fa fa-eur"></i>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="shop_currency/code" />
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
				<div class="bottom-container">
					<div class="marking">
						<xsl:choose>
							<xsl:when test="marking != ''">
							<xsl:value-of select="marking" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="@id" />
							</xsl:otherwise>
						</xsl:choose>
					</div>
					<div class="date"><xsl:value-of select="/shop/date"/></div>
					<div class="measure"><xsl:value-of select="shop_measure/name" /></div>
				</div>
			</div>
		</div>
		<!-- По горизонтали -->
		<xsl:if test="position() mod /shop/horizontal = 0">
			<div style="clear:both;"></div>
		</xsl:if>
		
		<!-- Всего на страницу -->
		<xsl:if test="position() mod (/shop/horizontal * /shop/vertical) = 0">
			<div class="pagebreak"> </div>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>