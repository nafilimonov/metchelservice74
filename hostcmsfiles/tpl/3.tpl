{* Show Informationsystem Item *}

{$oInformationsystem = $controller->getEntity()}
{$oInformationsystem_Item = Core_Entity::factory('Informationsystem_Item', $controller->item)}

{$oCurrentSiteuser = Core_Entity::factory('Siteuser')->getCurrent()}
{$siteuser_id = (!is_null($oCurrentSiteuser)) ? $oCurrentSiteuser->id : 0}

{capture name="item_url"}{$oInformationsystem_Item->Informationsystem->Structure->getPath()|escape}{$oInformationsystem_Item->getPath()|escape}{/capture}

{$captcha_id = ($oInformationsystem->use_captcha) ? Core_Captcha::getCaptchaId() : 0}

{* Show information system item tags *}
{function name=showTags aTags=array()}
	{foreach $aTags as $oTag name=tags}
		<a href="{$oInformationsystem->Structure->getPath()|escape}tag/{rawurlencode($oTag->path)|escape}/" class="tag">
			{$oTag->name|escape}
		</a>

		{if !$smarty.foreach.tags.last}, {/if}
	{/foreach}
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

{function name=showPagination limit=0 page="" link="" i=0 items_count=0 visible_pages=0 prefix=""}
	{$n = $items_count / $limit}

	{* Store in the variable $group ID of the current group *}
	{$group = $controller->group}

	{* Links before current *}
	{if $page > ($n - (round($visible_pages / 2) - 1))}
		{$pre_count_page = $visible_pages - ($n - $page)}
	{else}
		{$pre_count_page = round($visible_pages / 2) - 1}
	{/if}

	{* Links after current *}
	{if 0 > $page - (round($visible_pages / 2) - 1)}
		{$post_count_page = $visible_pages - $page}
	{else}
		{if round($visible_pages / 2) == ($visible_pages / 2)}
			{$post_count_page = $visible_pages / 2}
		{else}
			{$post_count_page = round($visible_pages / 2) - 1}
		{/if}
	{/if}

	{if $items_count > $limit && $n > $i}
		{* Pagination item *}
		{if $i != $page}
			{* Tag Link *}
			{$oTag = (strlen($controller->tag)) ? {Core_Entity::factory('Tag')->getByPath($controller->tag)} : NULL}

			{if strlen($controller->tag) && !is_null($oTag)}
				{$tag_link = "tag/{rawurlencode($oTag->path)|escape}/"}
			{else}
				{$tag_link = ""}
			{/if}

			{* Set $link variable *}
			{$number_link = ($i != 0) ? "{$prefix}-{$i + 1}/": ""}

			{* First pagination item *}
			{if $page - $pre_count_page > 0 && $i == 0}
				<a href="{$link}" class="page_link" style="text-decoration: none;">←</a>
			{/if}

			{if $i >= ($page - $pre_count_page) && ($page + $post_count_page) >= $i}
				{* Pagination item *}
				<a href="{$link}{$tag_link}{$number_link}" class="page_link">{$i + 1}</a>
			{/if}

			{* Last pagination item *}
			{if $i + 1 >= $n && $n > ($page + 1 + $post_count_page)}
				{if $n > round($n)}
					{* Last pagination item *}
					<a href="{$link}{$prefix}-{round($n+1)}/" class="page_link" style="text-decoration: none">→</a>
				{else}
					<a href="{$link}{$prefix}-{round($n)}/" class="page_link" style="text-decoration: none">→</a>
				{/if}
			{/if}
		{/if}

		{* Current pagination item *}
		{if $i == $page}
			<span class="current">
				{$i+1}
			</span>
		{/if}

		{* Recursive Function *}
		{showPagination limit=$limit page=$page link=$link i=$i+1 items_count=$items_count visible_pages=$visible_pages prefix=$prefix}
	{/if}
{/function}

{* Show property item *}
{function name=showProperties aProperty_Values=array()}
	{foreach $aProperty_Values as $oProperty_Value}
		{$oProperty = $oProperty_Value->Property}

		<tr>
			<th>
				{$oProperty->name|escape}
			</th>
			<td>
				{switch $oProperty->type}
					{case 2}
						<a href="{$oInformationsystem_Item->getItemHref()|escape}{$oProperty_Value->file|escape}">{#labelDownloadFile#}</a>
					{/case}
					{case 5}
						{$Property_Informationsystem_Item = $oProperty_Value->Informationsystem_Item}

						<a href="{$Property_Informationsystem_Item->Informationsystem->Structure->getPath()|escape}{$Property_Informationsystem_Item->getPath()|escape}" target="_blank">{$Property_Informationsystem_Item->name|escape}</a>
					{/case}
					{case 7}
						{$checked = ($oProperty_Value->value == 1) ? "checked='checked'" : ""}

						<input type="checkbox" disabled="disabled" {$checked} />
					{/case}
					{case 12}
						{$Property_Shop_Item = $oProperty_Value->Shop_Item}
						<a href="{$Property_Shop_Item->Shop->Structure->getPath()|escape}{$Property_Shop_Item->getPath()|escape}" target="_blank">{$Property_Shop_Item->name|escape}</a>
					{/case}
					{default}
						{$oProperty_Value->value|escape}
				{/switch}
			</td>
		</tr>
	{/foreach}
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

	<div class="comment">
		<form action="{$smarty.capture.item_url}" name="comment_form_0{$id}" method="post">
			{* Only for unauthorized users *}
			{if $siteuser_id == 0}
				<div class="row">
					<div class="caption">{#labelCommentName#}</div>
					<div class="field">
						<input type="text" size="70" name="author" value="{$author|escape}"/>
					</div>
				</div>

				<div class="row">
					<div class="caption">{#labelCommentEmail#}</div>
					<div class="field">
						<input id="email{$id}" type="text" size="70" name="email" value="{$email|escape}" />
						<div id="error_email{$id}"></div>
					</div>
				</div>

				<div class="row">
					<div class="caption">{#labelCommentPhone#}</div>
					<div class="field">
						<input type="text" size="70" name="phone" value="{$phone|escape}"/>
					</div>
				</div>
			{/if}

			<div class="row">
				<div class="caption">{#labelCommentSubject#}</div>
				<div class="field">
					<input type="text" size="70" name="subject" value="{$subject|escape}"/>
				</div>
			</div>

			<div class="row">
				<div class="caption">{#labelCommentText#}</div>
				<div class="field">
					<textarea name="text" cols="68" rows="5" class="mceEditor">{$text|escape}</textarea>
				</div>
			</div>

			<div class="row">
				<div class="caption">{#labelGrade#}</div>
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

			{* Showing captcha *}
			{if $captcha_id != 0 && $siteuser_id == 0}
				<div class="row">
					<div class="caption"></div>
					<div class="field">
						<img id="comment_{$id}" class="captcha" src="/captcha.php?id={$captcha_id}{$id}&height=30&width=100" title="{#labelCaptchaId#}" name="captcha"/>

						<div class="captcha">
							<img src="/images/refresh.png" /> <span onclick="$('#comment_{$id}').updateCaptcha('{$captcha_id}{$id}', 30); return false">{#labelUpdateCaptcha#}</span>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="caption">
						{#labelCaptchaId#}<sup><font color="red">*</font></sup>
					</div>
					<div class="field">
						<input type="hidden" name="captcha_id" value="{$captcha_id}{$id}"/>
						<input type="text" name="captcha" size="15"/>
					</div>
				</div>
			{/if}

			{if $id != 0}
				<input type="hidden" name="parent_id" value="{$id}"/>
			{/if}

			<div class="row">
				<div class="caption"></div>
				<div class="field">
					<input id="submit_email{$id}" type="submit" name="add_comment" value="{#labelPublish#}" class="button" />
				</div>
			</div>
		</form>
	</div>
{/function}

{function name=showComments oComment=NULL}
	{* Text or subject is not empty *}
	{if !is_null($oComment) && $oComment->text != '' || $oComment->subject != ''}
		{$id = $oComment->id}

		<a name="comment{$id}"></a>
		<div class="comment" id="comment{$id}">
			{if $oComment->subject != ''}
				<div class="subject" hostcms:id="{$id}" hostcms:field="subject" hostcms:entity="comment">{$oComment->subject|escape}</div>
			{/if}

			<div hostcms:id="{$id}" hostcms:field="text" hostcms:entity="comment" hostcms:type="wysiwyg">{$oComment->text}</div>

			<p class="tags">
				{* Grade *}
				{if $oComment->grade != 0}
					<span>
						{showAverageGrade grade=$oComment->grade const_grade=5}
					</span>
				{/if}

				<img src="/images/user.png" />
				{* Review was added an authorized user *}
				{if $oComment->siteuser_id > 0}
					<span><a href="/users/info/{$oComment->Siteuser->login|escape}/">{$oComment->Siteuser->login|escape}</a></span>
				{* Review was added an unauthorized user *}
				{else}
					<span>{$oComment->author|escape}</span>
				{/if}

				{if ($controller->votes)}
					{$class = "thumbs"}
					{if $siteuser_id}
						{$oVote = $oComment->Votes->getBySiteuser_Id($siteuser_id)}

						{if !is_null($oVote)}
							{if $oVote->value == 1}
								{$class = "thumbs up"}
							{elseif $oVote->value == -1}
								{$class = "thumbs down"}
							{/if}
						{/if}
					{else}
						{$class = "thumbs inactive"}
					{/if}

					{$aRate = Vote_Controller::instance()->getRateByObject($oComment)}
					<span id="comment_id_{$id}" class="{$class}">
						{if $siteuser_id}
							<span id="comment_likes_{$id}">{$aRate['likes']}</span>
							<span class="inner_thumbs">
								<a onclick="return $.sendVote({$id}, 1, 'comment')" href="{$oInformationsystem->Structure->getPath()|escape}?id={$id}&vote=1&entity_type=comment" alt="{#labelLike#}"></a>
								<span class="rate" id="comment_rate_{$id}">{$aRate['rate']}</span>
								<a onclick="return $.sendVote({$id}, 0, 'comment')" href="{$oInformationsystem->Structure->getPath()|escape}?id={$id}&vote=0&entity_type=comment" alt="{#labelDislike#}"></a>
							</span>
							<span id="comment_dislikes_{$id}">{$aRate['dislikes']}</span>
						{else}
							<span id="comment_likes_{$id}">{$aRate['likes']}</span>
							<span class="inner_thumbs">
								<a alt="{#labelLike#}"></a>
								<span class="rate" id="comment_rate_{$id}">{$aRate['rate']}</span>
								<a alt="{#labelDislike#}"></a>
							</span>
							<span id="comment_dislikes_{$id}">{$aRate['dislikes']}</span>
						{/if}
					</span>
				{/if}

				{$datetime = strftime($oInformationsystem->format_datetime, Core_Date::sql2timestamp($oComment->datetime))}
				<img src="/images/calendar.png" /> <span>{$datetime}</span>

				{if isset($show_add_comments) && ($show_add_comments == 1 && $siteuser_id > 0 || $show_add_comments == 2)}
					<span class="red" onclick="$('.comment_reply').hide('slow');$('#cr_{$id}').toggle('slow')">{#labelReply#}</span>
				{/if}

				<span class="red"><a href="{$smarty.capture.item_url}#comment{$id}" title="{#labelCommentLink#}">#</a></span>
			</p>
		</div>

		{* Only for authorized users *}
		{if isset($show_add_comments) && ($show_add_comments == 1 && $siteuser_id > 0 || $show_add_comments == 2)}
			<div class="comment_reply" id="cr_{$id}">
				{addCommentForm id=$id}
			</div>
		{/if}

		{* Child Reviews *}
		{$aChild_Comments = $oComment->Comments->getAllByActive(1)}
		{if count($aChild_Comments)}
			<div class="comment_sub">
				{foreach $aChild_Comments as $oChild_Comment}
					{showComments oComment=$oChild_Comment}
				{/foreach}
			</div>
		{/if}
	{/if}
{/function}

<h1 hostcms:id="{$oInformationsystem_Item->id}" hostcms:field="name" hostcms:entity="informationsystem_item">{$oInformationsystem_Item->name|escape}</h1>

{* Show Message *}
{if isset($message)}
	{$message}
{/if}

{* Image *}
{if $oInformationsystem_Item->image_small != ''}
	{if $oInformationsystem_Item->image_large != ''}
		<div id="gallery">
			<a href="{$oInformationsystem_Item->getItemHref()|escape}{$oInformationsystem_Item->image_large|escape}" target="_blank">
				<img src="{$oInformationsystem_Item->getItemHref()|escape}{$oInformationsystem_Item->image_small|escape}" class="news_img"/>
			</a>
		</div>
	{else}
		<img src="{$oInformationsystem_Item->getItemHref()|escape}{$oInformationsystem_Item->image_small|escape}" class="news_img"/>
	{/if}
{/if}

{* Text *}
{$aParts = $oInformationsystem_Item->getParts()}
{$parts_count = count($aParts)}
{if $parts_count > 1}
	{$aParts[$controller->part - 1]}
{else}
	<div hostcms:id="{$oInformationsystem_Item->id}" hostcms:field="text" hostcms:entity="informationsystem_item" hostcms:type="wysiwyg">
		{$oInformationsystem_Item->text}
	</div>
{/if}

<p class="tags">
	{* Average Grade *}
	{if $controller->comments}
		{$iCommentsCount = $oInformationsystem_Item->Comments->getCountByActive(1)}
		{$comments_grade_sum = 0}
		{$comments_grade_count = 0}

		{if $iCommentsCount}
			{$aComments = $oInformationsystem_Item->Comments->getAllByActive(1)}

			{foreach $aComments as $oComment}
				{if $oComment->grade > 0}
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
		{/if}
	{/if}

	{* Processing of the selected tag *}
	{if $controller->tags}
		{$iTagsCount = $oInformationsystem_Item->Tags->getCount()}
		{if $iTagsCount}
			<img src="/images/tag.png" /><span>{showTags aTags=$oInformationsystem_Item->Tags->findAll()}</span>
		{/if}

		{if $oInformationsystem_Item->siteuser_id}
			{$oSiteuser = $oInformationsystem_Item->Siteuser}

			<img src="/images/user.png" /><span><a href="/users/info/{$oSiteuser->login|escape}/">{$oSiteuser->login|escape}</a></span>
		{/if}
	{/if}

	{if ($controller->votes)}
		{$class = "thumbs"}
		{if $siteuser_id}
			{$oVote = $oInformationsystem_Item->Votes->getBySiteuser_Id($siteuser_id)}

			{if !is_null($oVote)}
				{if $oVote->value == 1}
					{$class = "thumbs up"}
				{elseif $oVote->value == -1}
					{$class = "thumbs down"}
				{/if}
			{/if}
		{else}
			{$class = "thumbs inactive"}
		{/if}

		{$aRate = Vote_Controller::instance()->getRateByObject($oInformationsystem_Item)}
		<span id="informationsystem_item_id_{$oInformationsystem_Item->id}" class="{$class}">
			{if $siteuser_id}
				<span id="informationsystem_item_likes_{$oInformationsystem_Item->id}">{$aRate['likes']}</span>
				<span class="inner_thumbs">
					<a onclick="return $.sendVote({$oInformationsystem_Item->id}, 1, 'informationsystem_item')" href="{$oInformationsystem->Structure->getPath()|escape}?id={$oInformationsystem_Item->id}&vote=1&entity_type=informationsystem_item" alt="{#labelLike#}"></a>
					<span class="rate" id="informationsystem_item_rate_{$oInformationsystem_Item->id}">{$aRate['rate']}</span>
					<a onclick="return $.sendVote({$oInformationsystem_Item->id}, 0, 'informationsystem_item')" href="{$oInformationsystem->Structure->getPath()|escape}?id={$oInformationsystem_Item->id}&vote=0&entity_type=informationsystem_item" alt="{#labelDislike#}"></a>
				</span>
				<span id="informationsystem_item_dislikes_{$oInformationsystem_Item->id}">{$aRate['dislikes']}</span>
			{else}
				<span id="informationsystem_item_likes_{$oInformationsystem_Item->id}">{$aRate['likes']}</span>
				<span class="inner_thumbs">
					<a alt="{#labelLike#}"></a>
					<span class="rate" id="informationsystem_item_rate_{$oInformationsystem_Item->id}">{$aRate['rate']}</span>
					<a alt="{#labelDislike#}"></a>
				</span>
				<span id="informationsystem_item_dislikes_{$oInformationsystem_Item->id}">{$aRate['dislikes']}</span>
			{/if}
		</span>
	{/if}

	{* Date *}
	{$date = strftime($oInformationsystem->format_date, Core_Date::sql2timestamp($oInformationsystem_Item->datetime))}
	<img src="/images/calendar.png" /> {$date}, <span hostcms:id="{$oInformationsystem_Item->id}" hostcms:field="showed" hostcms:entity="informationsystem_item">{$oInformationsystem_Item->showed}</span>{declension number=$oInformationsystem_Item->showed}
</p>

{* Links 1-2-3 to the parts of the document *}
{if $parts_count > 1}
	<div class="read_more">{#labelReadMore#}</div>

	{showPagination limit=1 page=$part link=$smarty.capture.item_url items_count=$parts_count visible_pages=6 prefix=part}

	<div style="clear: both"></div>
{/if}

{* Properties *}
{$aProperty_Values = $oInformationsystem_Item->getPropertyValues()}
{if count($aProperty_Values)}
	{$aTmp = array()}
	{foreach $aProperty_Values as $oProperty_Value}
		{if isset($oProperty_Value->value) && $oProperty_Value->value != '' || $oProperty_Value->Property->type == 2 && $oProperty_Value->file != ''}
			{$aTmp[] = $oProperty_Value}
		{/if}
	{/foreach}

	<p class="h2">{#labelAttributes#}</p>
	<table border="0" class="news_properties">
		{showProperties aProperty_Values=$aTmp}
	</table>
{/if}

{* Comments *}
{if isset($show_comments) && $show_comments == 1}
	{$aComments = $oInformationsystem_Item->Comments->getAllByActive(1)}

	{if count($aComments)}
		<p class="h1"><a name="comments"></a>{#labelReviews#}</p>

		{foreach $aComments as $oComment}
			{showComments oComment=$oComment}
		{/foreach}
	{/if}
{/if}

{*
If allowed to display add comment form,
	1 - Only authorized
	2 - All
*}
{if isset($show_add_comments) && ($show_add_comments == 1 && $siteuser_id > 0 || $show_add_comments == 2)}
	<p class="button" onclick="$('.comment_reply').hide('slow');$('#AddComment').toggle('slow')">
		{#labelAddReview#}
	</p>

	<div id="AddComment" class="comment_reply">
		{addCommentForm}
	</div>
{/if}