{* Show Top Menu *}

{function name=showInformationsystemGroups parentId=0 level=0}
	{if is_null($controller->level) || $level < $controller->level}
		{$aInformationsystem_Groups = $controller->getInformationsystemGroups()}

		{if isset($aInformationsystem_Groups[$parentId])}
			<ul class="level-{$level}">
				{foreach $aInformationsystem_Groups[$parentId] as $oInformationsystem_Group}
					<li>
						<a href="{$oInformationsystem_Group->Informationsystem->Structure->getPath()|escape}{$oInformationsystem_Group->getPath()|escape}">{$oInformationsystem_Group->name|escape}</a>

						{* Show information system items *}
						{if $controller->showInformationsystemItems}
							{if is_null($controller->level) || $level+1 < $controller->level}
								{$aInformationsystem_Items = $controller->getInformationsystemItems()}

								{if isset($aInformationsystem_Items[$oInformationsystem_Group->id])}
									<ul class="level-{$level+1}">
										{foreach $aInformationsystem_Items[$oInformationsystem_Group->id] as $oInformationsystem_Item}

											{* if shortcut *}
											{if $oInformationsystem_Item->shortcut_id}
												{$oInformationsystem_Item = $oInformationsystem_Item->Informationsystem_Item}
											{/if}

											<li>
												<a href="{$oInformationsystem_Item->Informationsystem->Structure->getPath()|escape}{$oInformationsystem_Item->getPath()|escape}">{$oInformationsystem_Item->name|escape}</a>
											</li>
										{/foreach}
									</ul>
								{/if}
							{/if}
						{/if}

						{* Show sub information system groups *}
						{showInformationsystemGroups parentId=$oInformationsystem_Group->id level=$level+1}
					</li>
				{/foreach}
			</ul>
		{/if}
	{/if}
{/function}

{function name=showInformationsystem oInformationsystem=NULL level=0}
	{if !is_null($oInformationsystem)}
		{$controller->fillInformationsystem($oInformationsystem)|hideOutput}

		{* Show information system groups *}
		{showInformationsystemGroups parentId=0 level=$level}
	{/if}
{/function}

{function name=showShopGroups parentId=0 level=0}
	{if is_null($controller->level) || $level < $controller->level}
		{$aShop_Groups = $controller->getShopGroups()}

		{if isset($aShop_Groups[$parentId])}
			<ul class="level-{$level}">
				{foreach $aShop_Groups[$parentId] as $oShop_Group}
					<li>
						<a href="{$oShop_Group->Shop->Structure->getPath()|escape}{$oShop_Group->getPath()|escape}">{$oShop_Group->name|escape}</a>

						{* Show shop items *}
						{if $controller->showShopItems}
							{if is_null($controller->level) || $level+1 < $controller->level}
								{$aShop_Items = $controller->getShopItems()}

								{if isset($aShop_Items[$oShop_Group->id])}
									<ul class="level-{$level+1}">
										{foreach $aShop_Items[$oShop_Group->id] as $oShop_Item}

											{* if shortcut *}
											{if $oShop_Item->shortcut_id}
												{$oShop_Item = $oShop_Item->Shop_Item}
											{/if}

											<li>
												<a href="{$oShop_Item->Shop->Structure->getPath()|escape}{$oShop_Item->getPath()|escape}">{$oShop_Item->name|escape}</a>
											</li>
										{/foreach}
									</ul>
								{/if}
							{/if}
						{/if}

						{* Show sub shop groups *}
						{showShopGroups parentId=$oShop_Group->id level=$level+1}
					</li>
				{/foreach}
			</ul>
		{/if}
	{/if}
{/function}

{function name=showShop oShop=NULL level=0}
	{if !is_null($oShop)}
		{$controller->fillShop($oShop)|hideOutput}

		{* Show shop groups *}
		{showShopGroups parentId=0 level=$level}
	{/if}
{/function}

{function name=showMenu parentId=0 level=0}
	{if isset($aStructures[$parentId])}
		<ul class="top_menu menu-level-{$level}">
			{foreach $aStructures[$parentId] as $oStructure}
				{if $oStructure->show == 1}
					{assign var="class" value="{if $current_structure_id == $oStructure->id}class='current'{else}{/if}"}

					<li{$class} id="{#foo#}">
						<a href="{$oStructure->getPath()|escape}" title="{$oStructure->name|escape}" hostcms:id="{$oStructure->id|escape}" hostcms:field="name" hostcms:entity="structure">{$oStructure->name|escape}</a>

						{if is_null($controller->level) || $level < $controller->level}
							{showMenu parentId=$oStructure->id level=$level+1}

							{* Get information systems *}
							{$aInformationsystems = $controller->getInformationsystems()}

							{if ($controller->showInformationsystemGroups || $controller->showInformationsystemItems) && isset($aInformationsystems[$oStructure->id])}
								{showInformationsystem oInformationsystem=$aInformationsystems[$oStructure->id] level=$level}
							{/if}

							{* Get shops *}
							{$aShops = $controller->getShops()}

							{if ($controller->showShopGroups || $controller->showShopItems) && isset($aShops[$oStructure->id])}
								{showShop oShop=$aShops[$oStructure->id] level=$level}
							{/if}
						{/if}
					</li>
				{/if}
			{/foreach}
		</ul>
	{/if}
{/function}

{showMenu parentId=0}