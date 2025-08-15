{* ОтображениеБаннера *}

{$oAdvertisement_Group = $controller->getEntity()}

{function name=advertisement oAdvertisement=NULL}
	{if !is_null($oAdvertisement)}
		{$height = ''}
		{$width = ''}
		{$style = ''}

		{* Check banner's height *}
		{if $oAdvertisement->height != 0}
			{$height = "height: {$oAdvertisement->height|escape}px;"}
		{/if}

		{* Check banner's width *}
		{if $oAdvertisement->width != 0}
			{$width = "width: {$oAdvertisement->width|escape}px;"}
		{/if}

		{if $height != '' || $width != ''}
			{$style = "{$height}{$width}"}
		{/if}

		{switch $oAdvertisement->type}
			{* Image *}
			{case 0}
				<div style="{$style}">
					{if $oAdvertisement->href != ''}
						<a href="/showbanner/?id={$oAdvertisement->addAdvertisementShow()->id}">
							<img src="{$oAdvertisement->getHref()|escape}{$oAdvertisement->source|escape}" alt="" />
						</a>
					{else}
						<img src="{$oAdvertisement->getHref()|escape}{$oAdvertisement->source|escape}" alt="" />
					{/if}
				</div>
			{/case}
			{* Text *}
			{case 1}
				<div style="{$style}">
					{$oAdvertisement->html}
				</div>
			{/case}
			{* Popup *}
			{case 2}
				<script type="text/javascript">
					var popUp = 0,
						popURL = "/{$oAdvertisement->Popup_Structure->getPath()}",
						popWidth = {$oAdvertisement->width},
						popHeight = {$oAdvertisement->height};

					popUp =	window.open(popURL, "popup", "width="+popWidth+", height="+popHeight+", status=yes, scrollbars=yes, location=no, menubar=no, directories=no, resizable=no, titlebar=yes");
				</script>
			{/case}
			{* Flash *}
			{case 3}
				{$list_id = $oAdvertisement->Advertisement_Group_Lists->getByAdvertisement_group_id($oAdvertisement_Group->id)}

				<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="{$oAdvertisement->width|escape}" height="{$oAdvertisement->height|escape}">
					<param name="movie" value="{$oAdvertisement->getHref()|escape}{$oAdvertisement->source|escape}"/>
					<param name="quality" value="high"/>
					<param name="href" value="/showbanner/?list_id={$list_id}"/>
					<embed src="{$oAdvertisement->getHref()|escape}{$oAdvertisement->source|escape}" quality="high" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" width="{$oAdvertisement->width|escape}" height="{$oAdvertisement->height|escape}"></embed>
				</object>
			{/case}
		{/switch}
	{/if}
{/function}

{foreach $aAdvertisements as $oAdvertisement}
	{advertisement oAdvertisement=$oAdvertisement}
{/foreach}