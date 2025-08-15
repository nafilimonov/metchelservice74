{* Shop items *}

{$oShop = $controller->getEntity()}

{* Show shop system groups *}
{function name=showGroups parentId=0}
	{if isset($aShop_Groups[$parentId])}
		{foreach $aShop_Groups[$parentId] as $oShop_Group}
			<a href="{$oShop_Group->Shop->Structure->getPath()|escape}{$oShop_Group->getPath()|escape}">{$oShop_Group->name|escape}</a>
		{/foreach}
	{/if}
{/function}

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

{* Show shop items *}
{function name=showItems}
	{foreach $aShop_Items as $oShop_Item}
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
								<span class="sale-text">-{($aPrices['discounts'][0]->type == 0) ? round($aPrices['discounts'][0]->value) : 0}%</span>
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
						<div class="product-description">
							{$oShop_Item->description}
						</div>
						<div class="product-add-buttons">
							<a class="product-add-button-cart" onclick="return $.bootstrapAddIntoCart('/shop/cart/', {$id}, 1)" href="#" title="В корзину">В корзину</a>
							<a class="product-add-button-fast-order" onclick="return $.oneStepCheckout('/shop/cart/', {$id}, 1)" data-toggle="modal" data-target="#oneStepCheckout{$id}" href="#" title="Быстрый заказ">Быстрый заказ</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	{/foreach}
{/function}

{function name=showPagination limit=0 page=0 pre_count_page=0 post_count_page=0 i=0 items_count=0 visible_pages=0}
	{$n = ceil($items_count / $limit)}

	{if $page + 1 == $n}
		{$start_page = $page - $visible_pages + 1}
	{elseif $page - $pre_count_page > 0}
		{$start_page = $page - $pre_count_page}
	{else}
		{$start_page = 0}
	{/if}

	{*
	{if $i == $start_page && $page != 0}
		<span class="ctrl">
			← Ctrl
		</span>
	{/if}

	{if $i == ($page + $post_count_page + 1) && $n != ($page+1)}
		<span class="ctrl">
			Ctrl →
		</span>
	{/if}
	*}

	{* Filter String *}
	{if isset($filter)}
		{$item_filter = "?filter=1&sorting=$sorting&price_from=$price_from&price_to=$price_to"}

		{getTemplateVars}

		{foreach $templateVars as $key => $value}
			{if strpos($key, 'property_') === 0}
				{$item_filter = "`$item_filter`&`$key`[]=`$value`"}
			{/if}
		{/foreach }
	{else}
		{$item_filter = ""}
	{/if}

	{if isset($on_page) && $on_page > 0}
		{$prefix = (isset($filter)) ? "&" : "?"}
		{$items_on_page = "{$prefix}on_page=$on_page"}
	{else}
		{$items_on_page = ""}
	{/if}

	{if $items_count > $limit && ($page + $post_count_page + 1) > $i}
		{* Store in the variable $group ID of the current group *}
		{$group = $controller->group}

		{* Tag Path *}
		{$oTag = (strlen($controller->tag)) ? {Core_Entity::factory('Tag')->getByPath($controller->tag)} : NULL}

		{if strlen($controller->tag) && !is_null($oTag)}
			{$tag_path = "tag/{rawurlencode($oTag->path)|escape}/"}
		{else}
			{$tag_path = ""}
		{/if}

		{* Producer Path *}
		{$oShop_Producer = (strlen($controller->producer)) ? {Core_Entity::factory('Shop_Producer', $controller->producer)} : NULL}

		{if strlen($controller->producer) && !is_null($oShop_Producer)}
			{$shop_producer_path = "producer-{$oShop_Producer->id|escape}/"}
		{else}
			{$shop_producer_path = ""}
		{/if}

		{* Choose Group Path *}
		{if $group != 0}
			{$oShop_Group = Core_Entity::factory('Shop_Group', $group)}

			{capture name="group_link"}{$oShop_Group->Shop->Structure->getPath()|escape}{$oShop_Group->getPath()|escape}{/capture}

			{$group_link = $smarty.capture.group_link}
		{else}
			{$group_link = $oShop->Structure->getPath()|escape}
		{/if}

		{* Set $link variable *}
		{$number_link = ($i != 0) ? "page-{$i + 1}/": ""}

		{* First pagination item *}
		{if $page - $pre_count_page > 0 and $i == $start_page}
			<li>
				<a href="{$group_link}{$tag_path}{$shop_producer_path}{$item_filter}{$items_on_page}" class="page_link" style="text-decoration: none">←</a>
			</li>
		{/if}

		{* Pagination item *}
		{if $i != $page}
			{if ($page - $pre_count_page) <= $i && $i < $n}
				{* Pagination item *}
				<li>
					<a href="{$group_link}{$number_link}{$tag_path}{$shop_producer_path}{$item_filter}{$items_on_page}" class="page_link">{$i + 1}</a>
				</li>
			{/if}

			{* Last pagination item *}
			{if $i + 1 >= ($page + $post_count_page + 1) && $n > ($page + 1 + $post_count_page)}
				<li>
					<a href="{$group_link}page-{$n}/{$tag_path}{$shop_producer_path}{$item_filter}{$items_on_page}" class="page_link" style="text-decoration: none">→</a>
				</li>
			{/if}
		{/if}

		{* Ctrl+left link *}
		{if $page != 0 && $i == $page}
			{$prev_number_link = ($page > 1) ? "page-{$i}/" : ""}

			<li class="hidden"><a href="{$group_link}{$prev_number_link}{$tag_path}{$shop_producer_path}{$item_filter}{$items_on_page}" id="id_prev"></a></li>
		{/if}

		{* Ctrl+right link *}
		{if ($n - 1) > $page && $i == $page}
			<li class="hidden"><a href="{$group_link}page-{$page + 2}/{$tag_path}{$shop_producer_path}{$item_filter}{$items_on_page}" id="id_next"></a></li>
		{/if}

		{* Current pagination item *}
		{if $i == $page}
			<li class="active">
				<a href="#">{$i+1}</a>
			</li>
		{/if}

		{* Recursive Function *}
		{showPagination limit=$limit page=$page pre_count_page=$pre_count_page post_count_page=$post_count_page i=$i+1 items_count=$items_count visible_pages=$visible_pages}
	{/if}
{/function}

{* Show subgroups if there are subgroups and not processing of the selected tag *}
{$aShop_Groups = $controller->getShopGroups()}
{if strlen($controller->tag) == 0 && isset($aShop_Groups[$controller->group]) && count($aShop_Groups[$controller->group])}
	<div class="row">
		<div class="col-xs-12">
			<div class="groups-list">
				{showGroups parentId=$controller->group}
			</div>
		</div>
	</div>
{/if}

<div class="row">
	<div class="col-xs-12">
		<div class="category-title">
			<div class="list-grid-group">
				<a href="#" id="grid" class="btn btn-default active"><span class="fa fa-th"></span></a>
				<a href="#" id="list" class="btn btn-default"><span class="fa fa-th-list"></span></a>
			</div>
		</div>
	</div>
</div>

{* Show shop items *}
<div class="products-grid">
	<div class="row">
		{showItems}
	</div>
</div>

{* Pagination *}
{if $controller->total > 0 and $controller->limit > 0 and ceil($controller->total / $controller->limit) > 1}
	{$count_pages = ceil($controller->total / $controller->limit)}

	{$visible_pages = 5}

	{$real_visible_pages = ($count_pages < $visible_pages) ? $count_pages : $visible_pages}

	{* Links before current *}
	{if $controller->page - (floor($real_visible_pages / 2)) < 0}
		{$pre_count_page = $controller->page}
	{elseif ($count_pages - $controller->page - 1) < floor($real_visible_pages / 2)}
		{$pre_count_page = $real_visible_pages - ($count_pages - $controller->page - 1) - 1}
	{else}
		{$pre_count_page = (round($real_visible_pages / 2) == $real_visible_pages / 2) ? floor($real_visible_pages / 2) - 1 : floor($real_visible_pages / 2)}
	{/if}

	{* Links after current *}
	{if 0 > $controller->page - (floor($real_visible_pages / 2) - 1)}
		{$post_count_page = $real_visible_pages - $controller->page - 1}
	{elseif ($count_pages - $controller->page - 1) < floor($real_visible_pages / 2)}
		{$post_count_page = $real_visible_pages - $pre_count_page - 1}
	{else}
		{$post_count_page = $real_visible_pages - $pre_count_page - 1}
	{/if}

	{if $controller->page + 1 == $count_pages}
		{$i = $controller->page - $real_visible_pages + 1}
	{elseif $controller->page - $pre_count_page > 0}
		{$i = $controller->page - $pre_count_page}
	{else}
		{$i = 0}
	{/if}

	<div class="row">
		<div class="col-xs-12 text-align-center">
			<nav>
				<ul class="pagination">
					{showPagination limit=intval($controller->limit) page=intval($controller->page) pre_count_page=intval($pre_count_page) post_count_page=intval($post_count_page) i=intval($i) items_count=intval($controller->total) visible_pages=intval($real_visible_pages)}
				</ul>
			</nav>
		</div>
	</div>
{/if}