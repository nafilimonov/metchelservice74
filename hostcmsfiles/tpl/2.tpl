{* Show Informationsystem *}

{$oInformationsystem = $controller->getEntity()}

{* Show Breadcrumbs *}
{function name=showBreadcrumbs oInformationsystem_Group=NULL}
	{if !is_null($oInformationsystem_Group)}
		{$parent_id = $oInformationsystem_Group->parent_id}

		{if $parent_id == 0}
			<a href="{$oInformationsystem->Structure->getPath()|escape}" hostcms:id="{$oInformationsystem->id|escape}" hostcms:field="name" hostcms:entity="informationsystem">
				{$oInformationsystem->name|escape}
			</a>
		{else}
			{$oInformationsystem_Group_Parent = Core_Entity::factory('Informationsystem_Group', $parent_id)}
			{showBreadcrumbs oInformationsystem_Group=$oInformationsystem_Group_Parent}
		{/if}

		<span> → </span>

		<a href="{$oInformationsystem_Group->Informationsystem->Structure->getPath()|escape}{$oInformationsystem_Group->getPath()|escape}" hostcms:id="{$oInformationsystem_Group->id}" hostcms:field="name" hostcms:entity="informationsystem_group">
			{$oInformationsystem_Group->name|escape}
		</a>
	{/if}
{/function}

{* Show information system groups *}
{function name=showGroups parentId=0}
	{if isset($aInformationsystem_Groups[$parentId])}
		<ul>
			{foreach $aInformationsystem_Groups[$parentId] as $oInformationsystem_Group}
				<li>
					{if $oInformationsystem_Group->image_small != ''}
						<a href="{$oInformationsystem_Group->Informationsystem->Structure->getPath()|escape}{$oInformationsystem_Group->getPath()|escape}" target="_blank"><img src="{$oInformationsystem_Group->getGroupHref()|escape}{$oInformationsystem_Group->image_small|escape}" align="middle"/></a>
					{/if}
					<a href="{$oInformationsystem_Group->Informationsystem->Structure->getPath()|escape}{$oInformationsystem_Group->getPath()|escape}">{$oInformationsystem_Group->name|escape}</a> <span class="count">({$oInformationsystem_Group->items_total_count})</span>
				</li>
			{/foreach}
		</ul>
	{/if}
{/function}

{* Get text date *}
{function name=getDate date=''}
	{if strlen(date)}
		{$aDate = explode('.', $date)}

		{* Day *}
		{$aDate[0]}

		{* Month *}
		{$month = intval($aDate[1])}
		{switch $month}
			{case 1}
				{#labelMonth1#}
			{/case}
			{case 2}
				{#labelMonth2#}
			{/case}
			{case 3}
				{#labelMonth3#}
			{/case}
			{case 4}
				{#labelMonth4#}
			{/case}
			{case 5}
				{#labelMonth5#}
			{/case}
			{case 6}
				{#labelMonth6#}
			{/case}
			{case 7}
				{#labelMonth7#}
			{/case}
			{case 8}
				{#labelMonth8#}
			{/case}
			{case 9}
				{#labelMonth9#}
			{/case}
			{case 10}
				{#labelMonth10#}
			{/case}
			{case 11}
				{#labelMonth11#}
			{/case}
			{case 12}
				{#labelMonth12#}
			{/case}
		{/switch}

		{* Year *}
		{$aDate[2]}
	{/if}
{/function}

{* Show information system item tags *}
{function name=showTags aTags=array()}
	{foreach $aTags as $oTag name=tags}
		<a href="{$oInformationsystem->Structure->getPath()|escape}tag/{rawurlencode($oTag->path)|escape}/" class="tag">
			{$oTag->name|escape}
		</a>

		{if !$smarty.foreach.tags.last}, {/if}
	{/foreach}
{/function}

{* Declension of the numerals *}
{function name=declension number=0}
	{* Nominative case / Именительный падеж *}
	{$nominative = {#labelNominative#}}

	{* Genitive singular / Родительный падеж, единственное число *}
	{$genitive_singular = {#labelGenitiveSingular#}}

	{* Genitive singular / Родительный падеж, множественное число *}
	{$genitive_plural = {#labelGenitivePlural#}}

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

{* Show information system items *}
{function name=showItems}
	{foreach $aInformationsystem_Items as $oInformationsystem_Item}
		<dt>
			{$date = strftime($oInformationsystem->format_date, Core_Date::sql2timestamp($oInformationsystem_Item->datetime))}

			{getDate date=$date}
		</dt>

		<dd>
			<a href="{$oInformationsystem_Item->Informationsystem->Structure->getPath()|escape}{$oInformationsystem_Item->getPath()|escape}" hostcms:id="{$oInformationsystem_Item->id}" hostcms:field="name" hostcms:entity="informationsystem_item">
				{$oInformationsystem_Item->name|escape}
			</a>

			{if $oInformationsystem_Item->image_small != ''}
				<a href="{$oInformationsystem_Item->Informationsystem->Structure->getPath()|escape}{$oInformationsystem_Item->getPath()|escape}" class="news_title">
					<img src="{$oInformationsystem_Item->getItemHref()|escape}{$oInformationsystem_Item->image_small|escape}" class="news_img" alt="" align="left"/>
				</a>
			{/if}

			{if $oInformationsystem_Item->description != ''}
				<div hostcms:id="{$oInformationsystem_Item->id}" hostcms:field="description" hostcms:entity="informationsystem_item" hostcms:type="wysiwyg">{$oInformationsystem_Item->description}</div>
			{/if}
		</dd>

		{$iCommentsCount = ($controller->comments) ? $oInformationsystem_Item->Comments->getCountByActive(1) : 0}
		{$iTagsCount = ($controller->tags) ? $oInformationsystem_Item->Tags->getCount() : 0}

		{if $iTagsCount || $iCommentsCount || $oInformationsystem_Item->siteuser_id}
			<p class="tags">
				{if $iTagsCount}
					<img src="/images/tag.png" /><span>{showTags aTags=$oInformationsystem_Item->Tags->findAll()}</span>
				{/if}

				{if $oInformationsystem_Item->siteuser_id}
					{$oSiteuser = $oInformationsystem_Item->Siteuser}

					<img src="/images/user.png" /><span><a href="/users/info/{$oSiteuser->login|escape}/">{$oSiteuser->login|escape}</a></span>
				{/if}

				{if $iCommentsCount}
					<img src="/images/comment.png" />
					<span>
						<a href="{$oInformationsystem_Item->Informationsystem->Structure->getPath()|escape}{$oInformationsystem_Item->getPath()|escape}#comments">
						{$iCommentsCount}
						{declension number=$iCommentsCount}
						</a>
					</span>
				{/if}
			</p>
		{/if}

		<hr />
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

		{* Choose Group Path *}
		{if $group != 0}
			{$oInformationsystem_Group = Core_Entity::factory('Informationsystem_Group', $group)}

			{capture name="group_link"}{$oInformationsystem_Group->Informationsystem->Structure->getPath()|escape}{$oInformationsystem_Group->getPath()|escape}{/capture}

			{$group_link = $smarty.capture.group_link}
		{else}
			{$group_link = $oInformationsystem->Structure->getPath()|escape}
		{/if}

		{* Set $link variable *}
		{$number_link = ($i != 0) ? "page-{$i + 1}/": ""}

		{* First pagination item *}
		{if $page - $pre_count_page > 0 and $i == $start_page}
			<a href="{$group_link}{$tag_path}" class="page_link" style="text-decoration: none">←</a>
		{/if}

		{* Pagination item *}
		{if $i != $page}
			{if ($page - $pre_count_page) <= $i && $i < $n}
				{* Pagination item *}
				<a href="{$group_link}{$number_link}{$tag_path}" class="page_link">{$i + 1}</a>
			{/if}

			{* Last pagination item *}
			{if $i + 1 >= ($page + $post_count_page + 1) && $n > ($page + 1 + $post_count_page)}
				<a href="{$group_link}page-{$n}/{$tag_path}" class="page_link" style="text-decoration: none">→</a>
			{/if}
		{/if}

		{* Ctrl+left link *}
		{if $page != 0 && $i == $page}
			{$prev_number_link = ($page > 1) ? "page-{$i}/" : ""}

			<a href="{$group_link}{$prev_number_link}{$tag_path}" id="id_prev"></a>
		{/if}

		{* Ctrl+right link *}
		{if ($n - 1) > $page && $i == $page}
			<a href="{$group_link}page-{$page + 2}/{$tag_path}" id="id_next"></a>
		{/if}

		{* Current pagination item *}
		{if $i == $page}
			<span class="current">
				{$i+1}
			</span>
		{/if}

		{* Recursive Function *}
		{showPagination limit=$limit page=$page pre_count_page=$pre_count_page post_count_page=$post_count_page i=$i+1 items_count=$items_count visible_pages=$visible_pages}
	{/if}
{/function}

{if $controller->group == 0}
	<h1 hostcms:id="{$oInformationsystem->id|escape}" hostcms:field="name" hostcms:entity="informationsystem">
		{$oInformationsystem->name|escape}
	</h1>

	{* Description displays if there is no filtering by tags *}
	{if strlen($controller->tag) == 0 && $controller->page == 0 and $oInformationsystem->description != ''}
		<div hostcms:id="{$oInformationsystem->id|escape}" hostcms:field="description" hostcms:entity="informationsystem" hostcms:type="wysiwyg">{$oInformationsystem->description}</div>
	{/if}
{else}
	<h1 hostcms:id="{$controller->group}" hostcms:field="name" hostcms:entity="informationsystem_group">
		{$oInformationsystem_Group = Core_Entity::factory('Informationsystem_Group', $controller->group)}
		{$oInformationsystem_Group->name|escape}
	</h1>

	{* Description displayed only in the first page *}
	{if $controller->page == 0 && $oInformationsystem_Group->description != ''}
		<div hostcms:id="{$controller->group}" hostcms:field="description" hostcms:entity="informationsystem_group" hostcms:type="wysiwyg">{$oInformationsystem_Group->description}</div>
	{/if}

	{* Breadcrumbs *}
	<p>
		{showBreadcrumbs oInformationsystem_Group=$oInformationsystem_Group}
	</p>
{/if}

{* Processing of the selected tag *}
{if strlen($controller->tag)}
	{$oTag = Core_Entity::factory('Tag')->getByPath($controller->tag)}

	<p class="h2">{#labelTag#} — <strong>{$oTag->name|escape}</strong>.</p>

	{if $oTag->description != ''}
		<p>{$oTag->description}</p>
	{/if}
{/if}

{* Show subgroups if there are subgroups and not processing of the selected tag *}
{$aInformationsystem_Groups = $controller->getInformationsystemGroups()}
{if strlen($controller->tag) == 0 && isset($aInformationsystem_Groups[$controller->group]) && count($aInformationsystem_Groups[$controller->group])}
	<div class="group_list">
		{showGroups parentId=$controller->group}
	</div>
{/if}

{* Show informationsystem_item *}
<dl class="news_list full_list">
	{showItems}
</dl>

{* Pagination *}
<div>
	{if $controller->total && $controller->limit}
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

		<p>
			{showPagination limit=intval($controller->limit) page=intval($controller->page) pre_count_page=intval($pre_count_page) post_count_page=intval($post_count_page) i=intval($i) items_count=intval($controller->total) visible_pages=intval($real_visible_pages)}
		</p>

		<div style="clear: both"></div>
	{/if}
</div>

{* Rss *}
<div class="rss">
	<img src="/images/rss.png"/><xsl:text> </xsl:text><a href="{$oInformationsystem->Structure->getPath()|escape}rss/">RSS</a>
</div>