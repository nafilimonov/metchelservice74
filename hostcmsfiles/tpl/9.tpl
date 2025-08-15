{* МагазинСписокПроизводителей *}

{function name=shopProducer oShop_Producer=NULL iteration=0}
	{if !is_null($oShop_Producer)}
		<td width="33%" align="center" valign="top">
			{if $oShop_Producer->image_small != ''}
				<p><a href="{$oShop_Producer->Shop->Structure->getPath()|escape}producers/{$oShop_Producer->path}/"><img src="{$oShop_Producer->getProducerHref()|escape}{$oShop_Producer->image_small|escape}" class="image" /></a></p>
			{/if}
			<p><a href="{$oShop_Producer->Shop->Structure->getPath()|escape}producers/{$oShop_Producer->path|escape}/">{$oShop_Producer->name|escape}</a></p>
		</td>
		{if $iteration%3 == 0 && $smarty.foreach.producers.last == FALSE}
			</tr>
			<tr valign="top">
		{/if}
	{/if}
{/function}

{function name=showPagination link="" limit=0 page=0 prefix="page" i=0 total=0 visible_pages=0}
	{$n = ceil($total / $limit)}

	{* Links before current *}
	{if $page > ($n - round($visible_pages / 2) - 1)}
		{$pre_count_page = $visible_pages - ($n - $page)}
	{else}
		{$pre_count_page = round($visible_pages / 2) - 1}
	{/if}

	{* Links after current *}
	{if 0 > $page - (round($visible_pages / 2) - 1)}
		{$post_count_page = $visible_pages - $page}
	{elseif round($visible_pages / 2) == ($visible_pages / 2)}
		{$post_count_page = $visible_pages / 2}
	{else}
		{$post_count_page = round($visible_pages / 2) - 1}
	{/if}

	{if $i == 0 && $page != 0}
		<span class="ctrl">
			← Ctrl
		</span>
	{/if}

	{if $i >= $n && ($n - 1) > $page}
		<span class="ctrl">
			Ctrl →
		</span>
	{/if}

	{if $total > $limit && $n > $i}
		{* Set $link variable *}
		{$number_link = ($i != 0) ? "{$prefix}-{$i + 1}/": ""}

		{* Pagination item *}
		{if $i != $page}
			{* First pagination item *}
			{if $page - $pre_count_page > 0 and $i == 0}
				<a href="{$link}" class="page_link" style="text-decoration: none">←</a>
			{/if}

			{if $i >= ($page - $pre_count_page) && ($page + $post_count_page) >= $i}
				{* Pagination item *}
				<a href="{$link}{$number_link}" class="page_link">{$i + 1}</a>
			{/if}

			{* Last pagination item *}
			{if $i + 1 >= $n && $n > ($page + 1 + $post_count_page)}
				{if $n > round($n)}
					{* Last pagination item *}
					<a href="{$link}{$prefix}-{round($n+1)}/" class="page_link" style="text-decoration: none;">→</a>
				{else}
					<a href="{$link}{$prefix}-{round($n)}/" class="page_link" style="text-decoration: none;">→</a>
				{/if}
			{/if}
		{/if}

		{* Ctrl+left link *}
		{if $page != 0 && $i == $page}
			{$prev_number_link = ($page > 1) ? "page-{$i}/" : ""}

			<a href="{$link}{$prev_number_link}" id="id_prev"></a>
		{/if}

		{* Ctrl+right link *}
		{if ($n - 1) > $page && $i == $page}
			<a href="{$link}page-{$page+2}/" id="id_next"></a>
		{/if}

		{* Current pagination item *}
		{if $i == $page}
			<span class="current">
				{$i+1}
			</span>
		{/if}

		{* Recursive Function *}
		{showPagination i=$i+1 prefix=$prefix link=$link limit=$limit page=$page total=$total visible_pages=$visible_pages}
	{/if}
{/function}

<h1>{#labelProducers#}</h1>

{if count($aShop_Producers) == 0}
	<div id="error">{#labelNone#}</div>
{else}
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			{foreach $aShop_Producers as $oShop_Producer name=producers}
				{shopProducer oShop_Producer=$oShop_Producer iteration=$oShop_Producer@iteration}
			{/foreach}
		</tr>
	</table>

	{* Current page link *}
	{$link = "{$oShop_Producer->Shop->Structure->getPath()|escape}producers/"}

	{showPagination link=$link limit=intval($controller->limit) page=intval($controller->page) total=intval($controller->total) visible_pages=5}
{/if}