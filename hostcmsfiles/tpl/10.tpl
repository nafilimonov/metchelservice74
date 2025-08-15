{* МагазинПроизводитель *}

{$oShop_Producer = Core_Entity::factory('Shop_Producer', $controller->producer)}

<h1>{#labelProducer#} {$oShop_Producer->name|escape}</h1>

{if $oShop_Producer->image_large != ''}
	<p><img src="{$oShop_Producer->getProducerHref()|escape}{$oShop_Producer->image_large|escape}" vspace="5" border="0"/></p>
{/if}

{if $oShop_Producer->description != ''}
	<p>{$oShop_Producer->description}</p>
{/if}

{if $oShop_Producer->address != ''}
	<p><b>{#labelAddress#}</b> {$oShop_Producer->address|escape}</p>
{/if}

{if $oShop_Producer->phone != ''}
	<p><b>{#labelPhone#}</b> {$oShop_Producer->phone|escape}</p>
{/if}

{if $oShop_Producer->fax != ''}
	<p><b>{#labelFax#}</b> {$oShop_Producer->fax|escape}</p>
{/if}

{if $oShop_Producer->site != ''}
	<p><b>{#labelSite#}</b> {$oShop_Producer->site|escape}</p>
{/if}

{if $oShop_Producer->email != ''}
	<p><b>{#labelEmail#}</b> {$oShop_Producer->email|escape}</p>
{/if}

{if $oShop_Producer->tin != ''}
	<p><b>{#labelINN#}</b> {$oShop_Producer->tin|escape}</p>
{/if}

<p class="button">
	<a href="{$oShop_Producer->Shop->Structure->getPath()|escape}producer-{$oShop_Producer->id}/">{#labelAllItems#} {$oShop_Producer->name|escape}</a>
</p>