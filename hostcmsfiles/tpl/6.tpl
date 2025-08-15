{* Shop Cart *}
{$oShop = $controller->getEntity()}

{$oCurrentSiteuser = Core_Entity::factory('Siteuser')->getCurrent()}
{$siteuser_id = (!is_null($oCurrentSiteuser)) ? $oCurrentSiteuser->id : 0}

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

{* Заголовок таблицы *}
{function name=tableHeader}
	<thead>
		<tr>
			<th></th>
			<th>Название</th>
			<th width="110">Цена</th>
			<th width="70">Кол-во</th>
			<th width="150">Сумма</th>
			{if count($oShop->Shop_Warehouses->getCount())}
				<th width="100">Склад</th>
			{/if}
			<th>Отложить</th>
			<th></th>
		</tr>
	</thead>
{/function}

{* Итоговая строка таблицы *}
{function name=tableFooter aNodes=array()}
	{$quantity = 0}
	{$sum = 0}
	{foreach $aNodes as $oNode}
		{$quantity = $quantity + $oNode->quantity}

		{$aPrices = $oNode->Shop_Item->getPrices()}

		{$sum = $sum + ($aPrices['price_discount'] * $oNode->quantity)}
	{/foreach}

	<tr class="total">
		<td> </td>
		<td>Итого:</td>
		<td> </td>
		<td>{$quantity|escape}</td>
		<td>
			{$sum|escape}{$oShop->Shop_Currency->name|escape}
		</td>
		{if count($oShop->Shop_Warehouses->getCount())}
			<td><xsl:text> </xsl:text></td>
		{/if}
		<td> </td>
		<td> </td>
	</tr>
{/function}

{function name=showWarehouseItems aShop_Warehouse_Items=array()}
	{foreach $aShop_Warehouse_Items as $oShop_Warehouse_Item}
		{if $oShop_Warehouse_Item->count != 0}
			{$reserved = $oShop_Warehouse_Item->getReserved()}

			<option value="{$oShop_Warehouse_Item->shop_warehouse_id}">
				{$oShop_Warehouse_Item->Shop_Warehouse->name|escape} ({$oShop_Warehouse_Item->count - $reserved})
			</option>
		{/if}
	{/foreach}
{/function}

{function name=shopCart aShop_Carts=array()}
	{foreach $aShop_Carts as $oShop_Cart}
		{$oShop_Item = $oShop_Cart->Shop_Item}
		{capture name="item_url"}{$oShop_Item->Shop->Structure->getPath()|escape}{$oShop_Item->getPath()|escape}{/capture}

		{$aPrices = $oShop_Cart->Shop_Item->getPrices()}

		<tr>
			<td class="hidden-xs">
				{if $oShop_Item->image_small != ''}
					<img src="{$oShop_Item->getItemHref()|escape}{$oShop_Item->image_small|escape}" alt="{$oShop_Item->name|escape}" height="150"/>
				{/if}
			</td>
			<td>
				{if $oShop_Item->modification_id}
					{$oModification = $oShop_Item->Modification}
					{$url = "{$oModification->Shop->Structure->getPath()|escape}{$oModification->getPath()|escape}"}
				{else}
					{$url = $smarty.capture.item_url}
				{/if}

				<a href="{$url}" target="_blank">
					{$oShop_Item->name|escape}
				</a>
			</td>
			<td>
				{* Цена *}
				{$aPrices['price_discount']} {$oShop_Item->Shop_Currency->name|escape}
			</td>
			<td>
				<input class="form-control" type="text" size="3" name="quantity_{$oShop_Item->id}" id="quantity_{$oShop_Item->id}" value="{$oShop_Cart->quantity}"/>
			</td>
			<td>
				{* Сумма *}
				{$aPrices['price_discount'] * $oShop_Cart->quantity} {$oShop_Item->Shop_Currency->name|escape}
			</td>
			{if count($oShop->Shop_Warehouses->getCount())}
				<td>
					{$sum = 0}
					{$aShop_Warehouse_Items = $oShop_Item->Shop_Warehouse_Items->findAll()}
					{foreach $aShop_Warehouse_Items as $oShop_Warehouse_Item}
						{$sum = $sum + $oShop_Warehouse_Item->count}
					{/foreach}

					{if $sum}
						<select name="warehouse_{$oShop_Item->id}">
							{showWarehouseItems aShop_Warehouse_Items=$aShop_Warehouse_Items}
						</select>
					{else}
						—
					{/if}
				</td>
			{/if}
			<td align="center">
				{* Отложить *}
				{$checked = ($oShop_Cart->postpone == 1) ? "checked='checked'" : ""}
				<div class="squared">
					<input id="postpone_{$oShop_Item->id}" type="checkbox" name="postpone_{$oShop_Item->id}" {$checked} />
					<label for="postpone_{$oShop_Item->id}"></label>
					<span></span>
				</div>
			</td>
			<td align="center">
				<a href="?delete={$oShop_Item->id}" onclick="return confirm('Вы уверены, что хотите удалить?')" title="Удалить товар из корзины" alt="Удалить товар из корзины"><i class="fa fa-times"></i></a>
			</td>
		</tr>
	{/foreach}
{/function}

{* Шаблон для скидки от суммы заказа *}
{function name=showShopPurchaseDiscounts aShop_Purchase_Discounts=array()}
	{foreach $aShop_Purchase_Discounts as $oShop_Purchase_Discount}
		<tr>
			<td>
				{$oShop_Purchase_Discount->name|escape}
			</td>
			<td></td>
			<td></td>
			<td>
				{* Сумма *}
				{$oShop_Purchase_Discount->getDiscountAmount() * -1} {$oShop->Shop_Currency->name|escape}
			</td>
			{if count($oShop->Shop_Warehouses->getCount())}
				<td></td>
			{/if}
			<td></td>
			<td></td>
		</tr>
	{/foreach}
{/function}

{function name=showShopItem oShop_Item=NULL}
	{if !is_null($oShop_Item)}
		{$id = $oShop_Item->id}

		{$aPrices = $oShop_Item->getPrices()}

		<div class="item">
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

<div id="WiredWizard" class="wizard wizard-wired" data-target="#WiredWizardsteps">
	<ul class="steps">
		<li data-target="#wiredstep1" class="active"><span class="step">1</span><span class="title">Корзина</span><span class="chevron"></span></li>
		<li data-target="#wiredstep2"><span class="step">2</span><span class="title">Реквизиты</span> <span class="chevron"></span></li>
		<li data-target="#wiredstep3"><span class="step">3</span><span class="title">Доставка</span> <span class="chevron"></span></li>
		<li data-target="#wiredstep4"><span class="step">4</span><span class="title">Форма оплаты</span> <span class="chevron"></span></li>
		<li data-target="#wiredstep5"><span class="step">5</span><span class="title">Заказ оформлен</span> <span class="chevron"></span></li>
	</ul>
</div>

{if count($aShop_Carts) == 0}
	<div class="alert alert-info fade in alert-cart">
		<p>
			{if $siteuser_id > 0 || $siteuser_id == 0}
				Для оформления заказа добавьте товар в корзину.
			{else}
				Вы не авторизированы. Если Вы зарегистрированный пользователь, данные Вашей корзины станут видны после авторизации.
			{/if}
		</p>
	</div>
{else}
	<div class="alert alert-info fade in alert-cart">
		<p>Для оформления заказа, нажмите «Оформить заказ».</p>
	</div>

	{$aShopCartWithoutPostpone = array()}
	{$aShopCartPostpone = array()}
	{$aItem_Associateds = array()}

	{foreach $aShop_Carts as $oShop_Cart}
		{if $oShop_Cart->postpone == 0}
			{$aShopCartWithoutPostpone[] = $oShop_Cart}

			{if $oShop_Cart->Shop_Item->Item_Associateds->getCountByActive(1)}
				{$aItem_Associateds[] = $oShop_Cart->Shop_Item}
			{/if}
		{else}
			{$aShopCartPostpone[] = $oShop_Cart}
		{/if}
	{/foreach}

	<form action="{$controller->cartUrl}" method="post">
		{* Если есть товары *}
		{if count($aShopCartWithoutPostpone)}
			<div class="table-responsive">
				<table class="table shop-cart">
					{tableHeader}
					{shopCart aShop_Carts=$aShopCartWithoutPostpone}
					{tableFooter aNodes=$aShopCartWithoutPostpone}

					{* Скидки *}
					{if count($aShop_Purchase_Discounts)}
						{showShopPurchaseDiscounts aShop_Purchase_Discounts=$aShop_Purchase_Discounts}
						<tr class="total">
							<td>Всего:</td>
							<td></td>
							<td></td>
							<td>
								{$total_amount} {$oShop->Shop_Currency->name|escape}
							</td>
							<td></td>
							{if count($oShop->Shop_Warehouses->getCount())}
								<td></td>
							{/if}
							<td></td>
							<td></td>
						</tr>
					{/if}
				</table>
			</div>
		{/if}

		{* Если есть отложенные товары *}
		{if count($aShopCartPostpone)}
			<div class="transparent">
				<h2>Отложенные товары</h2>
				<table class="shop_cart">
					{tableHeader}
					{shopCart aShop_Carts=$aShopCartPostpone}
					{tableFooter aNodes=$aShopCartPostpone}
				</table>
			</div>
		{/if}

		<div class="row">
			<div class="col-xs-6">
				{* Купон *}
				<div class="shop-coupon">
					<input class="form-control" name="coupon_text" value="" type="text" placeholder="Купон" />
				</div>
			</div>
			<div class="col-xs-6 pull-right">
				<div class="pull-right">
					{* Кнопки *}
					<input name="recount" value="Пересчитать" type="submit" class="btn btn-primary" />

					{* Пользователь авторизован или модуль пользователей сайта отсутствует *}
					{if count($aShopCartWithoutPostpone) && (Core::moduleIsActive('siteuser') || $siteuser_id)}
						<input name="step" value="1" type="hidden" />
						<input value="Оформить заказ" type="submit" class="btn btn-primary bg-lightred"/>
					{/if}
				</div>
			</div>
		</div>

		{if count($aItem_Associateds)}
			<section class="section-block associated_shop_item">
				<div class="section-heading">
					<h2><span>С этим товаром</span> покупают</h2>
				</div>
				<div class="row">
					<div class="col-xs-12">
						<div class="products-grid hot-product">
							{foreach $aItem_Associateds as $oItem_Associated}
								{showShopItem oShop_Item=$oItem_Associated}
							{/foreach}
						</div>
					</div>
				</div>
			</section>
		{/if}
	</form>
{/if}