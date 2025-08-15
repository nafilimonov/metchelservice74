{* Shop little cart *}

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
		{$aPrices = $oShop_Item->getPrices()}

		<div class="cart-item">
			<a class="cart-item-image" title="{$oShop_Item->name|escape}" href="{$oShop_Item->Shop->Structure->getPath()|escape}{$oShop_Item->getPath()|escape}">

			{if $oShop_Item->image_small != ''}
				<img alt="{$oShop_Item->name|escape}" src="{$oShop_Item->getItemHref()|escape}{$oShop_Item->image_small|escape}" />
			{* Картинка родительского товара *}
			{elseif $oShop_Item->modification_id}
				{$oParentItem = $oShop_Item->Modification}

				{if $oParentItem->image_small != ''}
					<img alt="{$oShop_Item->name|escape}" src="{$oParentItem->getItemHref()|escape}{$oParentItem->image_small|escape}" />
				{/if}
			{/if}
			</a>
			<div class="cart-item-details">
				<div class="cart-item-name">
					<a href="{$oShop_Item->Shop->Structure->getPath()|escape}{$oShop_Item->getPath()|escape}">{$oShop_Item->name|escape}</a>
				</div>
				<div class="cart-price">
					{$aPrices['price_discount']} {$oShop_Item->Shop_Currency->name|escape}
				</div>
			</div>
		</div>
	{/if}
{/function}

{$aShopCartWithoutPostpone = array()}
{$totalQuantity = 0}

{foreach $aShop_Carts as $oShop_Cart}
	{if $oShop_Cart->postpone == 0}
		{$aShopCartWithoutPostpone[] = $oShop_Cart}

		{$totalQuantity = $totalQuantity + $oShop_Cart->quantity}
	{/if}
{/foreach}

<a class="top-cart-link" href="/shop/cart/">
	<span>{$totalQuantity}</span>
</a>

<div class="more-cart-info">
	{if count($aShopCartWithoutPostpone) == 0}
		<div class="cart-item-list-empty">В корзине нет ни одного товара</div>
	{else}
		<div class="cart-item-list">
			{foreach $aShopCartWithoutPostpone as $oShopCart}
				{showShopItem oShop_Item=$oShopCart->Shop_Item}
			{/foreach}
		</div>

		<div class="cart-link"><a href="/shop/cart/">В корзину</a></div>
	{/if}
</div>