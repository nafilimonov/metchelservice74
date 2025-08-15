{* Shop Item *}

{$oShop = $controller->getEntity()}
{$oShop_Item = Core_Entity::factory('Shop_Item', $controller->item)}

{$oCurrentSiteuser = Core_Entity::factory('Siteuser')->getCurrent()}
{$siteuser_id = (!is_null($oCurrentSiteuser)) ? $oCurrentSiteuser->id : 0}

{capture name="item_url"}{$oShop_Item->Shop->Structure->getPath()|escape}{$oShop_Item->getPath()|escape}{/capture}

{$captcha_id = ($oShop->use_captcha) ? Core_Captcha::getCaptchaId() : 0}

{function name=currencyCode oShop_Currency=NULL value=0}
	{if !is_null($oShop_Currency)}
		{$value = number_format($value, 2, '.', ' ')}

		{switch $oShop_Currency->code}
			{case "USD"}${$value}{/case}
			{case "USD"}€{$value}{/case}
			{case "GBP"}£{$value}{/case}
			{case "RUB"}{$value}<i class="fa fa-ruble"></i>{/case}
			{case "AUD"}AU${$value}{/case}
			{case "CNY"}{$value}元{/case}
			{case "JPY"}{$value}¥{/case}
			{case "KRW"}{$value}₩{/case}
			{case "PHP"}{$value}₱{/case}
			{case "THB"}{$value}฿{/case}
			{case "BRL"}R${$value}{/case}
			{case "INR"}{$value}<i class="fa fa-inr"></i>{/case}
			{case "TRY"}{$value}<i class="fa fa-try"></i>{/case}
			{case "ILS"}{$value}<i class="fa fa-ils">{/case}
			{default}
				{$value}{$oShop_Currency->code}
		{/switch}
	{/if}
{/function}

{* Declension of the numerals *}
{function name=declension number=0}
	{* Nominative case / Именительный падеж *}
	{$nominative = "просмотр"}

	{* Genitive singular / Родительный падеж, единственное число *}
	{$genitive_singular = "просмотра"}

	{* Genitive singular / Родительный падеж, множественное число *}
	{$genitive_plural = "просмотров"}

	{$last_digit = $number%10}
	{$last_two_digits = $number%100}

	{if $last_digit == 1 && $last_two_digits != 11}
		{$nominative}
	{elseif ($last_digit == 2 && $last_two_digits != 12) || ($last_digit == 3 && $last_two_digits != 13) || ($last_digit == 4 && $last_two_digits != 14)}
		{$genitive_singular}
	{else}
		{$genitive_plural}
	{/if}
{/function}

{function name=showShopItem oShop_Item=NULL}
	{if !is_null($oShop_Item)}
		{$id = $oShop_Item->id}

		{$aPrices = $oShop_Item->getPrices()}

		<div class="item col-xs-12 col-sm-6 col-lg-4">
			<div class="grid_wrap match-height">
				<div class="item-img">
					<a href="{$oShop_Item->Shop->Structure->getPath()|escape}{$oShop_Item->getPath()|escape}" title="{$oShop_Item->name|escape}">
						<img class="img-responsive" src="{$oShop_Item->getItemHref()|escape}{$oShop_Item->image_small|escape}" alt="{$oShop_Item->name|escape}" />
					</a>

					{* Discount *}
					{if $aPrices['discount'] != 0 && count($aPrices['discounts'])}
						<span class="product-label">
							<span class="label-sale">
								<span class="sale-text">-{($aPrices['discounts'][0]->type == 0) ? $aPrices['discounts'][0]->value : 0}%</span>
							</span>
						</span>
						<span class="product-label left">
							<span class="label-sale">
								<span class="sale-text">СКИДКА</span>
							</span>
						</span>
					{/if}
					<div class="hover-box">
						<button class="btn btn-button cart-button" title="Add to cart" data-toggle="tooltip" data-placement="top" type="button" onclick="return $.bootstrapAddIntoCart('/shop/cart/', {$id}, 1)">в корзину</button>
						<button class="btn btn-button cart-button button-fast-order" title="Быстрый заказ" data-placement="bottom" type="button" onclick="return $.oneStepCheckout('/shop/cart/', {$id}, 1)" data-toggle="modal" data-target="#oneStepCheckout{$id}">Быстрый заказ</button>
						<ul class="product-buttons">
							<li>
								<button class="btn btn-button button-wishlist lagoon-blue-bg" title="wishlist" data-toggle="tooltip" data-placement="top" type="button" onclick="return $.addFavorite('/shop/favorite/', {$id}, this)">
									<i class="fa fa-heart"></i>
									Избранное
								</button>
							</li>
							<li>
								<button class="btn btn-button button-compare lagoon-blue-bg" title="compare" data-toggle="tooltip" data-placement="top" type="button" onclick="return $.addCompare('/shop/', {$id}, this)">
									<i class="fa fa-retweet"></i>
									Сравнение
								</button>
							</li>
						</ul>
					</div>
				</div>
				<div class="product-content">
					<div class="product-content-inner">
						<h5 class="product-name">
							<a href="{$oShop_Item->Shop->Structure->getPath()|escape}{$oShop_Item->getPath()|escape}" title="{$oShop_Item->name|escape}">{$oShop_Item->name|escape}</a>
						</h5>
						<div class="rating">
							<div style="clear:both"></div>
						</div>
						<div class="price-box">
							<span id="product-price-12-new" class="regular-price">
								<span class="price">
									{currencyCode oShop_Currency=$oShop->Shop_Currency value=$aPrices['price_discount']}
								</span>
								{if $aPrices['discount'] != 0}
									<span class="old-price">
										{currencyCode oShop_Currency=$oShop->Shop_Currency value=$aPrices['price_discount']+$aPrices['discount']}
									</span>
								{/if}
							</span>
						</div>

						<div class="product-add-buttons">
							<a class="product-add-button-cart" onclick="return $.bootstrapAddIntoCart('/shop/cart/', {$id}, 1)" href="#" title="В корзину">В корзину</a>
							<a class="product-add-button-fast-order" onclick="return $.oneStepCheckout('/shop/cart/', {$id}, 1)" data-toggle="modal" data-target="#oneStepCheckout{$id}" href="#" title="Быстрый заказ">Быстрый заказ</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	{/if}
{/function}

{* Show rating stars *}
{function name=showAverageGrade grade=0 const_grade=0}
	{* To avoid loops *}
	{$current_grade = $grade * 1}

	{if floor($current_grade) == $current_grade && !($const_grade > ceil($current_grade))}
		{if $current_grade - 1 > 0}
			{showAverageGrade grade=$current_grade-1 const_grade=$const_grade-1}
		{/if}

		{if $current_grade != 0}
			<img src="/images/star-full.png"/>
		{/if}
	{elseif $current_grade != 0 and !($const_grade > ceil($current_grade))}
		{if $current_grade - 0.5 > 0}
			{showAverageGrade grade=$current_grade-0.5 const_grade=$const_grade-1}

			<img src="/images/star-half.png"/>
		{/if}
	{* Show the gray stars until the current position does not reach the value increased to an integer *}
	{else}
		{showAverageGrade grade=$current_grade const_grade=$const_grade-1}

		<img src="/images/star-empty.png"/>
	{/if}
{/function}

{function name=showDiscount oShop_Discount=NULL}
	{if !is_null($oShop_Discount)}
		<div class="shop-item-discounts">
			<i class="fa fa-tag"></i> {$oShop_Discount->name|escape} {($oShop_Discount->type == 0) ? $oShop_Discount->value : 0}%
		</div>
	{/if}
{/function}

{function name=paramList aData=array()}
	{foreach $aData as $oShop_Item}
		<option value="{$oShop_Item->id}" data-price="{$oShop_Item->price|escape} {$oShop->Shop_Currency->name|escape}">
			{$oShop_Item->name|escape} — {$oShop_Item->price|escape} {$oShop->Shop_Currency->name|escape}
		</option>
	{/foreach}
{/function}

{function name=showPropertyValues aProperty_Values=array}
	{foreach $aProperty_Values as $oProperty_Value}
		<div class="shop_property item-margin-left">
			{$oProperty = $oProperty_Value->Property}

			{$oProperty->name|escape}:
			<span>
				{switch $oProperty->type}
					{case 2}
						<a href="{$Shop_Item->getItemHref()|escape}{$oProperty_Value->file|escape}" target="_blank">{$oProperty_Value->file_name|escape}</a>
					{/case}
					{case 3}
						{if $oProperty_Value->value != 0}
							{$oList_Item = $oProperty_Value->List_Item}
							{$oList_Item->value|escape}
						{/if}
					{/case}
					{case 7}
						{$checked = ($oProperty_Value->value == 1) ? "checked='checked'" : ""}
						<input type="checkbox" disabled="disabled" {$checked} />
					{/case}
					{default}
						{$oProperty_Value->value|escape}

						{* Единица измерения свойства *}
						{if isset($oProperty->shop_measure_id)}
							{$oProperty->Shop_Measure->name|escape}
						{/if}
				{/switch}
			</span>
		</div>
	{/foreach}
{/function}

{function name=showTags aTags=array()}
	{foreach $aTags as $oTag name=tags}
		<a href="{$oShop->Structure->getPath()|escape}tag/{rawurlencode($oTag->path)|escape}/">
			{$oTag->name|escape}
		</a>

		{if !$smarty.foreach.tags.last}, {/if}
	{/foreach}
{/function}

{function name=commentNode oComment=NULL}
	<div class="media-left">
		<div class="avatar-inner">
			{if $oComment->siteuser_id && Core::moduleIsActive('siteuser')}
				{$oCommentSiteuser = $oComment->Siteuser}

				{$oProperty_Value = Core_Entity::factory('Property')->getByTagName('avatar')}

				{if !is_null($oProperty_Value) && $oProperty_Value->file != ''}
					<img src="{$oCommentSiteuser->getDirHref()|escape}{$oProperty_Value->file|escape}" />
				{else}
					<img alt="{$oCommentSiteuser->login|escape}" src="/hostcmsfiles/forum/avatar.gif" />
				{/if}
			{else}
				<img alt="" src="/hostcmsfiles/forum/avatar.gif" />
			{/if}
		</div>
		<div class="rating">
			{* Grade *}
			{if $oComment->grade != 0}
				<span>
					{showAverageGrade grade=$oComment->grade const_grade=5}
				</span>
			{/if}
		</div>
	</div>
	<div class="media-body">
		<h4 class="media-heading">
			{if $oComment->subject != ''}
				{$oComment->subject|escape}
			{else}
				Без темы
			{/if}
		</h4>

		<p>{$oComment->text}</p>

		<div class="review-info">
			{if isset($show_add_comments) && ($show_add_comments == 1 && $siteuser_id > 0 || $show_add_comments == 2)}
				<span class="review-answer" onclick="$('.review-button').hide();$('#AddComment input[name=\'parent_id\']').val('{$id}');$('#AddComment').show()">Ответить</span>
			{/if}

			{$datetime = strftime($oShop->format_datetime, Core_Date::sql2timestamp($oComment->datetime))}
			<span>{$datetime}</span>

			<i class="fa fa-user"></i>
			<span>
				{* Комментарий добавил авторизированный пользователь *}
				{if $oComment->siteuser_id && Core::moduleIsActive('siteuser')}
					{$oCommentSiteuser = $oComment->Siteuser}
					<a href="/users/info/{$oCommentSiteuser->login|escape}/">{$oCommentSiteuser->login|escape}</a>
				{* Комментарй добавил неавторизированный пользователь *}
				{else}
					<span>{$oComment->author|escape}</span>
				{/if}
			</span>
		</div>

		{$aSubComments = $oComment->Comments->getAllByActive(1)}
		{if count($aSubComments)}
			{foreach $aSubComments as $oSubComment}
				{call name=showSubComments oComment=$oSubComment}
			{/foreach}
		{/if}
	</div>
{/function}

{function name=showComments oComment=NULL}
	{* Отображаем комментарий, если задан текст или тема комментария *}
	{if !is_null($oComment) && $oComment->text != '' || $oComment->subject != ''}
		{$id = $oComment->id}

		<a name="comment{$id}"></a>

		<ul class="media-list">
			<li class="media">
				{commentNode oComment=$oComment}
			</li>
		</ul>
	{/if}
{/function}

{function name=showSubComments oComment=NULL}
	<div class="media">
		{commentNode oComment=$oComment}
	</div>
{/function}

{function name=addCommentForm id=0}
	{$author = ""}
	{$email = ""}
	{$phone = ""}
	{$subject = ""}
	{$text = ""}

	{if isset($smarty.post.add_comment)}
		{$author = (isset($smarty.post.author)) ? $smarty.post.author : ""}
		{$email = (isset($smarty.post.email)) ? $smarty.post.email : ""}
		{$phone = (isset($smarty.post.phone)) ? $smarty.post.phone : ""}
		{$subject = (isset($smarty.post.subject)) ? $smarty.post.subject : ""}
		{$text = (isset($smarty.post.text)) ? $smarty.post.text : ""}
	{/if}

	<div class="row">
		<div class="col-xs-12 margin-bottom-20 margin-top-20">
			<form action="{$smarty.capture.item_url}" id="review" class="show" name="comment_form_0{$id}" method="post">
				{* Авторизированным не показываем *}
				{if $siteuser_id == 0}
					<div class="form-group">
						<label for="author">Имя</label>
						<input name="author" type="text" class="form-control" id="name" value="{$author|escape}" />
					</div>

					<div class="form-group">
						<label for="email">E-mail</label>
						<input name="email" type="text" class="form-control" id="email" value="{$email|escape}" />
					</div>

					<div class="form-group">
						<label for="phone">Телефон</label>
						<input name="phone" type="text" class="form-control" id="phone" value="{$phone|escape}" />
					</div>
				{/if}

				<div class="form-group">
					<label for="subject">Тема</label>
					<input name="subject" type="text" class="form-control" id="subject" value="{$subject|escape}" />
				</div>

				<div class="form-group">
					<label for="textarea_text">Комментарий</label>
					<textarea rows="5" name="text" class="form-control" id="textarea_text">{$text|escape}</textarea>
				</div>

				<div class="form-group">
					<div class="row">
						<div class="col-xs-12">
							<div class="stars padding-bottom-10">
								<select name="grade">
									<option value="1">Poor</option>
									<option value="2">Fair</option>
									<option value="3">Average</option>
									<option value="4">Good</option>
									<option value="5">Excellent</option>
								</select>
							</div>
						</div>
					</div>
				</div>

				{* Обработка CAPTCHA *}
				{if $captcha_id != 0 && $siteuser_id == 0}
					<div class="form-group">
						<label for="textarea_text"></label>
						<img id="comment_{$id}" class="captcha" src="/captcha.php?id={$captcha_id}{$id}&height=30&width=100" title="Контрольное число" name="captcha"/>

						<div class="captcha">
							<img src="/images/refresh.png" /> <span onclick="$('#comment_{$id}').updateCaptcha('{$captcha_id}{$id}', 30); return false">Показать другое число</span>
						</div>
					</div>

					<div class="row">
						<div class="form-group col-xs-12 col-md-4">
							<label for="captcha">Контрольное число<sup><font color="red">*</font></sup></label>
							<div class="field">
								<input type="hidden" name="captcha_id" value="{$captcha_id}{$id}"/>
								<input type="text" name="captcha" size="15" class="form-control" minlength="4" title="Введите число, которое указано выше."/>
							</div>
						</div>
					</div>
				{/if}

				<div class="form-group">
					<div class="row">
						<div class="col-xs-12 text-align-center">
							<button id="submit_email{$id}" type="submit" class="btn btn-primary full-width" name="add_comment" value="add_comment">Опубликовать</button>
						</div>
					</div>
				</div>

				<input type="hidden" name="parent_id" value="{$id}"/>
			</form>
		</div>
	</div>
{/function}

{$aProperty_Values = $oShop_Item->getPropertyValues()}

<div class="row">
	{* Show Message *}
	{if isset($message)}
		<div class="alert alert-warning">{$message|escape}</div>
	{/if}

	<div class="col-xs-12 col-md-5">
		<div class="thumbnails">
			{if $oShop_Item->image_large != ''}
				<div class="main-image">
					<a href="{$oShop_Item->getItemHref()|escape}{$oShop_Item->image_large|escape}" class="thumbnail">
						<img id="zoom" src="{$oShop_Item->getItemHref()|escape}{$oShop_Item->image_large|escape}" data-zoom-image="{$oShop_Item->getItemHref()|escape}{$oShop_Item->image_large|escape}"/>
					</a>
				</div>

				<div id="additional-images" class="additional-images-slider">
					<div class="item">
						<a href="{$oShop_Item->getItemHref()|escape}{$oShop_Item->image_large|escape}" class="elevatezoom-gallery active" data-image="{$oShop_Item->getItemHref()|escape}{$oShop_Item->image_large|escape}" data-zoom-image="{$oShop_Item->getItemHref()|escape}{$oShop_Item->image_large|escape}">
							<img class="item-main img-responsive" src="{$oShop_Item->getItemHref()|escape}{$oShop_Item->image_large|escape}" height="150" width="100"/>
						</a>
					</div>

					{$aTmpImages = array()}
					{foreach $aProperty_Values as $oProperty_Value}
						{if $oProperty_Value->Property->tag_name == 'img' && $oProperty_Value->Property->type == 2 && $oProperty_Value->file != ''}
							{$aTmpImages[] = $oProperty_Value}
						{/if}
					{/foreach}

					{if count($aTmpImages)}
						{foreach $aTmpImages as $oImage}
							<div class="item">
								<a href="{$oShop_Item->getItemHref()|escape}{$oImage->file|escape}" class="elevatezoom-gallery" data-image="{$oShop_Item->getItemHref()|escape}{$oImage->file|escape}" data-zoom-image="{$oShop_Item->getItemHref()|escape}{$oImage->file|escape}">
									<img class="img-responsive" height="150" width="100" src="{$oShop_Item->getItemHref()|escape}{$oImage->file|escape}"/>
								</a>
							</div>
						{/foreach}
					{/if}
				</div>
			{/if}
		</div>
	</div>

	<div class="col-xs-12 col-md-7">
		<h2 class="item_title">
			{$oShop_Item->name|escape}
		</h2>

		{if $controller->comments}
			<div class="rating">
				{* Average Grade *}
				{$iCommentsCount = $oShop_Item->Comments->getCountByActive(1)}
				{$comments_grade_sum = 0}
				{$comments_grade_count = 0}

				{if $iCommentsCount}
					{$aComments = $oShop_Item->Comments->getAllByActive(1)}

					{foreach $aComments as $oComment}
						{if $oComment->active && $oComment->grade > 0}
							{$comments_grade_sum = $comments_grade_sum + $oComment->grade}
							{$comments_grade_count = $comments_grade_count + 1}
						{/if}
					{/foreach}

					{* Средняя оценка *}
					{$comments_average_grade = ($comments_grade_count > 0) ? $comments_grade_sum / $comments_grade_count : 0}

					{$fractionalPart = $comments_average_grade - floor($comments_average_grade)}
					{$comments_average_grade = floor($comments_average_grade)}

					{if $fractionalPart >= 0.25 && $fractionalPart < 0.75}
						{$comments_average_grade = $comments_average_grade + 0.5}
					{elseif $fractionalPart >= 0.75}
						{$comments_average_grade = $comments_average_grade + 1}
					{/if}

					{showAverageGrade grade=$comments_average_grade const_grade=5}
				{else}
					<div style="clear:both"></div>
				{/if}
			</div>
		{/if}

		{* Цена товара *}
		{if $oShop_Item->price != 0}
			{$aPrices = $oShop_Item->getPrices()}
			<div id="item-{$oShop_Item->id}">
				<div class="item-price">
					{currencyCode oShop_Currency=$oShop->Shop_Currency value=$aPrices['price_discount']}

					{* Если цена со скидкой - выводим ее *}
					{if $aPrices['discount'] != 0}
						<span class="item-old-price">
							{currencyCode oShop_Currency=$oShop->Shop_Currency value=$aPrices['price_discount']+$aPrices['discount']}
						</span>
					{/if}
				</div>
			</div>

			{* Cкидки *}
			{$aShop_Discounts = $oShop_Item->Shop_Discounts->findAll()}
			{if count($aShop_Discounts)}
				{foreach $aShop_Discounts as $oShop_Discount}
					{showDiscount oShop_Discount=$oShop_Discount}
				{/foreach}
			{/if}
		{/if}

		{* Описание товара *}
		{if $oShop_Item->description != ''}
			<div class="item-description" hostcms:id="{$oShop_Item->id}" hostcms:field="text" hostcms:entity="shop_item" hostcms:type="wysiwyg">{$oShop_Item->description}</div>
		{/if}

		<div class="shop-item-actions margin-top-20">
			<div class="quantity">
				<input id="quantity" class="item-quantity" type="number" value="1" name="quantity" />
				<span class="qty-wrapper">
					<span class="qty-inner">
						<span class="qty-up" data-src="#quantity" title="+">
							<i class="fa fa-plus"></i>
						</span>
						<span class="qty-down" data-src="#quantity" title="-">
							<i class="fa fa-minus"></i>
						</span>
					</span>
				</span>
			</div>

			<a id="cart" class="btn btn-add-cart" data-item-id="{$oShop_Item->id}" onclick="return $.bootstrapAddIntoCart('{$oShop->Structure->getPath()|escape}cart/', $(this).data('item-id'), $('#quantity').val())" type="button" title="Add to Cart">В корзину</a>

			<a id="fast_order" class="btn btn-fast-order" data-item-id="{$oShop_Item->id}" onclick="return $.oneStepCheckout('{$oShop->Structure->getPath()|escape}cart/', $(this).data('item-id'), $('#quantity').val())" type="button" title="Быстрый заказ" data-toggle="modal" data-target="#oneStepCheckout{$oShop_Item->id}">Быстрый заказ</a>

			<a class="btn btn-circle item-wishlist" onclick="return $.addFavorite('{$oShop->Structure->getPath()|escape}favorite/', {$oShop_Item->id}, this)"><i class="fa fa-heart-o"></i></a>
			<a class="btn btn-circle item-compare" onclick="return $.addCompare('{$oShop->Structure->getPath()|escape}', {$oShop_Item->id}, this)"><i class="fa fa-bar-chart"></i></a>
		</div>

		{* Список для модификаций *}
		{$aModifications = $oShop_Item->Modifications->findAll()}
		{if count($aModifications)}
			{$aTmpModifications = array()}
			{foreach $aModifications as $oModification}
				{$aModification_Property_Values = $oModification->getPropertyValues()}
				{if count($aModification_Property_Values)}
					{foreach $aModification_Property_Values as $oProperty_Value}
						{if $oProperty_Value->Property->tag_name == 'colors' && $oProperty_Value->value != ''}
							{$aTmpModifications[] = $oModification}
						{/if}
					{/foreach}
				{/if}
			{/foreach}

			{if count($aTmpModifications)}
				<div class="margin-top-20">
					<div class="shop_property">Варианты товара:</div>
					<select class="modification-prices form-control margin-top-5" id="param" name="param" onchange="$.changePrice(this, {$oShop_Item->id})">
						<option value="{$oShop_Item->id}" data-price="{$oShop_Item->price|escape} {$oShop->Shop_Currency->name|escape}">...</option>
						{paramList aData=$aTmpModifications}
					</select>
				</div>
			{/if}
		{/if}

		<div class="shop_item_properties">
			{* Бонусы для товара *}
			{$aPrices = $oShop_Item->getPrices()}
			{$aBonuses = $oShop_Item->getBonuses($aPrices)}

			{if $aBonuses['total']}
				<div class="shop_property product-bonuses">
					+{$aBonuses['total']|escape} бонусов
				</div>
			{/if}

			{if $oShop_Item->marking != ''}
				<div class="shop_property">Артикул: <span hostcms:id="{$oShop_Item->id}" hostcms:field="marking" hostcms:entity="shop_item">{$oShop_Item->marking|escape}</span></div>
			{/if}

			{if $oShop_Item->shop_producer_id > 0}
				<div class="shop_property">Производитель: <span>{$oShop_Item->Shop_Producer->name|escape}</span></div>
			{/if}

			{* Если указан вес товара *}
			{if $oShop_Item->weight != 0}
				<div class="shop_property">Вес товара: <span hostcms:id="{$oShop_Item->id}" hostcms:field="weight" hostcms:entity="shop_item">{$oShop_Item->weight|escape}</span> {$oShop->Shop_Measure->name|escape}</span></div>
			{/if}

			{* Размеры товара *}
			{if $oShop_Item->length != 0 || $oShop_Item->width != 0 || $oShop_Item->height != 0}
				<div class="shop_property">Размеры: <span>{$oShop_Item->length|escape} × {$oShop_Item->width|escape} × {$oShop_Item->height|escape} {Core::_("Shop.size_measure_{$oShop->size_measure}")|escape}</span>
				</div>
			{/if}

			{* Количество на складе для не электронного товара *}
			{$rest = $oShop_Item->getRest()}
			{$reserved = ($oShop->reserve) ? $this->getReserved() : 0}

			{if $rest && $oShop_Item->type != 1}
				<div class="shop_property">В наличии: <span>{$rest - $reserved} {$oShop_Item->Shop_Measure->name|escape}</span>{if $reserved > 0} (зарезервировано: <span>{$reserved|escape} {$oShop_Item->Shop_Measure->name|escape}</span>){/if}</div>
			{/if}

			{* Если электронный товар, выведим доступное количество *}
			{if $oShop_Item->type == 1}
				{$digitals = $oShop_Item->Shop_Item_Digitals->getCountDigitalItems()}

				<div class="shop_property">
					{if $digitals == 0}
						Электронный товар закончился.
					{elseif $digitals == -1}
						Электронный товар доступен для заказа.
					{else}
						На складе осталось: <span>{$digitals|escape} {$oShop_Item->Shop_Measure->name|escape}</span>
					{/if}
				</div>
			{/if}
		</div>

		{$aTmpPropertyValues = array()}

		{if count($aProperty_Values)}
			{foreach $aProperty_Values as $oProperty_Value}
				{if $oProperty_Value->Property->type != 2 && $oProperty_Value->value != ''}
					{$aTmpPropertyValues[] = $oProperty_Value}
				{/if}
			{/foreach}
		{/if}

		{if count($aTmpPropertyValues)}
			<div class="row">
				<div class="col-xs-12">
					{showPropertyValues aProperty_Values=$aTmpPropertyValues}
				</div>
			</div>
		{/if}
	</div>

	{$iCommentsCount = $oShop_Item->Comments->getCountByActive(1)}
	{$iModificationsCount = $oShop_Item->Modifications->getCountByActive(1)}
	{$iAssociatedsCount = $oShop_Item->Item_Associateds->getCountByActive(1)}
	<div class="col-xs-12 margin-top-20">
		{* Nav tabs *}
		<ul class="nav nav-tabs" role="tablist">
			<li class="active" role="presentation"><a href="#text" aria-controls="text" role="tab" data-toggle="tab">Описание</a></li>

			{if $iModificationsCount}
				<li role="presentation"><a href="#modifications" aria-controls="modifications" role="tab" data-toggle="tab">Модификации <span class="tab-badge">{$iModificationsCount}</span></a></li>
			{/if}
			{if $iAssociatedsCount}
				<li role="presentation"><a href="#associated" aria-controls="associated" role="tab" data-toggle="tab">Сопутствующие <span class="tab-badge">{$iAssociatedsCount}</span></a></li>
			{/if}

			<li role="presentation"><a href="#comments" aria-controls="comments" role="tab" data-toggle="tab">Отзывы <span class="tab-badge">{$iCommentsCount}</span></a></li>
		</ul>

		{* Tab panes *}
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="text">
				<div class="row">
					<div class="col-xs-12">
						{* Processing of the selected tag *}
						{$aTags = $oShop_Item->Tags->findAll()}
						{if count($aTags)}
							<div class="tag-list">
								<div>{showTags aTags=$aTags}</div>
							</div>
						{/if}
					</div>
				</div>
				<div class="row">
					<div class="col-xs-12 margin-top-20">
						{* Текст товара *}
						{if $oShop_Item->text != ''}
							<div class="item-text" hostcms:id="{$oShop_Item->id}" hostcms:field="text" hostcms:entity="shop_item" hostcms:type="wysiwyg">{$oShop_Item->text}</div>
						{else}
							<div class="item-text">Подробные данные о товаре отсутствуют.</div>
						{/if}
					</div>
				</div>
			</div>

			{* Модификации *}
			{if $iModificationsCount}
				<div role="tabpanel" class="tab-pane" id="modifications">
					<div class="row">
						<div class="col-xs-12">
							<div class="row products-grid">
								{$aModifications = $oShop_Item->Modifications->getAllByActive(1)}
								{foreach $aModifications as $oModification}
									{showShopItem oShop_Item=$oModification}
								{/foreach}
							</div>
						</div>
					</div>
				</div>
			{/if}

			{if $iAssociatedsCount}
				<div role="tabpanel" class="tab-pane" id="associated">
					<div class="row">
						<div class="col-xs-12">
							<div class="row products-grid">
								{$aItem_Associateds = $oShop_Item->Item_Associateds->getAllByActive(1)}
								{foreach $aItem_Associateds as $oItem_Associated}
									{showShopItem oShop_Item=$oItem_Associated}
								{/foreach}
							</div>
						</div>
					</div>
				</div>
			{/if}

			<div role="tabpanel" class="tab-pane" id="comments">
				<div class="row">
					<div class="col-xs-12">
						{if isset($show_comments) && $show_comments == 1}
							{$aComments = $oShop_Item->Comments->getAllByActive(1)}

							{if count($aComments)}
								<div class="row reviews margin-bottom-30">
									<div class="col-xs-12">
										{foreach $aComments as $oComment}
											{if $oComment->parent_id == 0}
												{showComments oComment=$oComment}
											{/if}
										{/foreach}
									</div>
								</div>
							{else}
								<div class="item-text">Отзывы о товаре отсутствуют.</div>
							{/if}
						{/if}

						{*
						If allowed to display add comment form,
							1 - Only authorized
							2 - All
						*}
						{if isset($show_add_comments) && ($show_add_comments == 1 && $siteuser_id > 0 || $show_add_comments == 2)}
							<div class="actions item-margin-left text-align-center">
								<button class="btn btn-primary review-button full-width" type="button" title="Add Comment" onclick="$('.review-button').hide();$('#AddComment').show()">Добавить отзыв</button>
							</div>

							<div class="row">
								<div class="col-xs-12">
									<div id="AddComment" class="comment_reply">
										{addCommentForm}
									</div>
								</div>
							</div>
						{/if}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

{* Viewed items *}
{if $controller->viewed}
	{$hostcmsViewed = Core_Array::get(Core_Array::getSession('hostcmsViewed', array()), $oShop->id, array())}

	{if count($hostcmsViewed)}
		{$hostcmsViewed = array_slice($hostcmsViewed, 0, $controller->viewedLimit)}

		<h1>Просмотренные товары</h1>
		<ul class="products-grid">
			{* Выводим товары магазина *}
			<div class="row">
				{foreach $hostcmsViewed as $view_item_id}
					{if $view_item_id != $oShop_Item->id}
						{if $view_item_id@iteration < 4}
							{$oShop_Item_Viewed = Core_Entity::factory('Shop_Item', $view_item_id)}
							{showShopItem oShop_Item=$oShop_Item_Viewed}
						{else}
							{break}
						{/if}
					{/if}
				{/foreach}
			</div>
		</ul>
	{/if}
{/if}