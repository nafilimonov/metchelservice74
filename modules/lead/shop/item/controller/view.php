<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Shop_Item_Controller_View
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Lead_Shop_Item_Controller_View extends Admin_Form_Controller_View
{
	/**
	 * Executes the business logic.
	 * @return self
	 */
	public function execute()
	{
		$oAdmin_Form_Controller = $this->_Admin_Form_Controller;

		$oAdmin_View = Admin_View::create($this->_Admin_Form_Controller->Admin_View)
			->pageTitle($oAdmin_Form_Controller->pageTitle)
			->module($oAdmin_Form_Controller->module);

		$aAdminFormControllerChildren = array();

		foreach ($oAdmin_Form_Controller->getChildren() as $oAdmin_Form_Entity)
		{
			if ($oAdmin_Form_Entity instanceof Skin_Bootstrap_Admin_Form_Entity_Breadcrumbs
				|| $oAdmin_Form_Entity instanceof Skin_Bootstrap_Admin_Form_Entity_Menus)
			{
				$oAdmin_View->addChild($oAdmin_Form_Entity);
			}
			else
			{
				$aAdminFormControllerChildren[] = $oAdmin_Form_Entity;
			}
		}

		// При показе формы могут быть добавлены сообщения в message, поэтому message показывается уже после отработки формы
		ob_start();

		foreach ($aAdminFormControllerChildren as $oAdmin_Form_Entity)
		{
			$oAdmin_Form_Entity->execute();
		}

		$this->_showContent();

		$total_count = $oAdmin_Form_Controller->getTotalCount();

		if ($total_count)
		{
			?><div class="row margin-bottom-20 margin-top-10">
				<div class="col-xs-12 col-sm-6 col-md-8 text-align-left">
					<?php $this->_Admin_Form_Controller->pageNavigation()?>
				</div>
				<div class="col-xs-12 col-sm-6 col-md-4 text-align-right">
					<?php $this->_Admin_Form_Controller->pageSelector()?>
				</div>
			</div><?php
		}
		$content = ob_get_clean();

		$oAdmin_View
			->content($content)
			->message($oAdmin_Form_Controller->getMessage())
			->show();

		//$oAdmin_Form_Controller->applyEditable();
		$oAdmin_Form_Controller->showSettings();

		return $this;
	}

	/**
	 * Show form content in administration center
	 * @return self
	 */
	protected function _showContent()
	{
		$oAdmin_Form_Controller = $this->_Admin_Form_Controller;
		$oAdmin_Form = $oAdmin_Form_Controller->getAdminForm();

		$oAdmin_Language = $oAdmin_Form_Controller->getAdminLanguage();

		$aAdmin_Form_Fields = $oAdmin_Form->Admin_Form_Fields->findAll();

		if (empty($aAdmin_Form_Fields))
		{
			throw new Core_Exception('Admin form does not have fields.');
		}

		$oUser = Core_Auth::getCurrentUser();

		if (is_null($oUser))
		{
			return FALSE;
		}

		$windowId = $oAdmin_Form_Controller->getWindowId();

		// Устанавливаем ограничения на источники
		$oAdmin_Form_Controller->setDatasetLimits()->setDatasetConditions();

		$aDatasets = $oAdmin_Form_Controller->getDatasets();

		$aEntities = $aDatasets[0]->load();

		$additionalParams = 'lead_id={lead_id}&secret_csrf=' . Core_Security::getCsrfToken();
		$externalReplace = $oAdmin_Form_Controller->getExternalReplace();
		foreach ($externalReplace as $replace_key => $replace_value)
		{
			$additionalParams = str_replace($replace_key, $replace_value, $additionalParams);
		}

		$aColors = array(
			'palegreen',
			'warning',
			'info',
			'maroon',
			'darkorange',
			'blue',
			'danger'
		);
		$iCountColors = count($aColors);
		?>
		<div class="deal-note-board">
			<!-- <div class="row"> -->
			<div>
				<?php
				if (count($aEntities))
				{
					?><ul class="timeline crm-note-list timeline-left timeline-no-vertical"><?php
					$prevDate = NULL;

					$i = 0;

					foreach ($aEntities as $oEntity)
					{
						$color = $aColors[$i % $iCountColors];

						$iDatetime = Core_Date::sql2timestamp($oEntity->datetime);
						$sDate = Core_Date::timestamp2date($iDatetime);

						if ($prevDate != $sDate)
						{
							?><li class="timeline-node">
								<a class="badge badge-<?php echo $color?>"><?php echo Core_Date::timestamp2string(Core_Date::date2timestamp($sDate), FALSE)?></a>
							</li><?php

							$prevDate = $sDate;
							$i++;
						}
						?>
						<li class="timeline-inverted">
							<div class="timeline-badge palegreen">
									<i class="fa fa-shopping-cart"></i>
							</div>
							<div class="timeline-panel">
								<div class="timeline-header bordered-bottom bordered-palegreen">
									<div class="pull-right timeline-entity-actions">
									<?php
									// Отображать в списке действий
									if ($oAdmin_Form->show_operations)
									{
										$aAllowed_Admin_Form_Actions = $oAdmin_Form->Admin_Form_Actions->getAllowedActionsForUser($oUser);

										foreach ($aAllowed_Admin_Form_Actions as $oAdmin_Form_Action)
										{
											$aAllowedActions = array('edit', 'markDeleted');

											// Отображаем действие, только если разрешено.
											if (!$oAdmin_Form_Action->single || !in_array($oAdmin_Form_Action->name, $aAllowedActions))
											{
												continue;
											}

											if (method_exists($oEntity, 'checkBackendAccess') && !$oEntity->checkBackendAccess($oAdmin_Form_Action->name, $oUser))
											{
												continue;
											}

											$Admin_Word_Value = $oAdmin_Form_Action->Admin_Word->getWordByLanguage($oAdmin_Language->id);

											$name = $Admin_Word_Value && strlen($Admin_Word_Value->name) > 0
												? $Admin_Word_Value->name
												: '';

											$href = $oAdmin_Form_Controller->getAdminActionLoadHref($path = '/{admin}/lead/shop/item/index.php', $oAdmin_Form_Action->name, NULL, 0, $oEntity->id, $additionalParams, 10, 1, NULL, NULL, 'list');

											$onclick = $oAdmin_Form_Action->name == 'edit'
												? $oAdmin_Form_Controller->getAdminActionModalLoad(array('path' => $path, 'action' => $oAdmin_Form_Action->name, 'operation' => 'modal', 'datasetKey' => 0, 'datasetValue' => $oEntity->id, 'additionalParams' => $additionalParams, 'width' => '90%'))
												: $oAdmin_Form_Controller->getAdminActionLoadAjax($path, $oAdmin_Form_Action->name, NULL, 0, $oEntity->id, $additionalParams, 10, 1, NULL, NULL, 'list');

											// Добавляем установку метки для чекбокса и строки + добавлем уведомление, если необходимо
											if ($oAdmin_Form_Action->confirm)
											{
												$onclick = "res = confirm('".Core::_('Admin_Form.confirm_dialog', htmlspecialchars($name))."'); if (!res) { $('#{$windowId} #row_0_{$oEntity->id}').toggleHighlight(); } else {mainFormLocker.unlock(); {$onclick}} return res;";
											}
											?><a onclick="<?php echo htmlspecialchars($onclick)?>" href="<?php echo htmlspecialchars($href)?>" title="<?php echo htmlspecialchars($name)?>"><i class="<?php echo htmlspecialchars($oAdmin_Form_Action->icon)?>"></i></a><?php
										}
									}
									?>
									</div>
								</div>
								<div class="timeline-body">
									<?php echo $oEntity->showContent($oAdmin_Form_Controller)?>
									<div class="small gray"><span class="gray"><?php $oEntity->User->showLink($oAdmin_Form_Controller->getWindowId())?></span><span class="pull-right"><?php echo date('H:i', $iDatetime)?></span></div>
								</div>
							</div>
						</li>
						<?php
					}
					?></ul><?php
				}
				?>
			</div>
		</div>
		<?php

		return $this;
	}
}