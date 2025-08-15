{* ОтображениеОпросаБезРезультатов *}

{function name=pollResponse oPoll_Response=NULL iteration=0}
	{if !is_null($oPoll_Response)}
		{$oPoll = $oPoll_Response->Poll}

		<p>
			<label>
				{if $oPoll->type == 0}
					{$checked = ($iteration == 1) ? "checked='checked'" : ""}

					<input type="radio" name="poll_response" id="poll_response_{$oPoll_Response->id}" value="{$oPoll_Response->id}" {$checked}>
				{else}
					<input type="checkbox" name="poll_response_{$oPoll_Response->id}" id="poll_response_{$oPoll_Response->id}" value="{$oPoll_Response->id}"></input>
				{/if}

				 <span hostcms:id="{$oPoll_Response->id}" hostcms:field="name" hostcms:entity="poll_response">{$oPoll_Response->name|escape}</span>
			</label>
		</p>
	{/if}
{/function}

{function name=poll oPoll=NULL}
	{if !is_null($oPoll)}
		<div class="survey_block">
			<p class="h1 red" hostcms:id="{$oPoll->id}" hostcms:field="name" hostcms:entity="poll">
				{$oPoll->name|escape}
			</p>

			<form action="{$oPoll->Poll_Group->Structure->getPath()|escape}poll-{$oPoll->id}/" method="post" class="poll">
				{$aPoll_Responses = $oPoll->Poll_Responses->findAll()}
				{foreach $aPoll_Responses as $oPoll_Response}
					{pollResponse oPoll_Response=$oPoll_Response iteration=$oPoll_Response@iteration}
				{/foreach}

				<p><input class="button" type="submit" name="vote" value="{#labelAnswer#}"/></p>

				<div>
					<input type="hidden" name="polls_id" value="{$oPoll->id}"/>
					<input type="hidden" name="show_results_vote" value="{$oPoll->show_results}"/>
				</div>
			</form>
		</div>
	{/if}
{/function}

{foreach $aPolls as $oPoll}
	{poll oPoll=$oPoll}
{/foreach}