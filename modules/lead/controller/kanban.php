<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Controller_Kanban
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Lead_Controller_Kanban extends Skin_Bootstrap_Admin_Form_Controller_List
{
	/**
	 * Executes the business logic.
	 * @return self
	 */
	public function execute()
	{
		$oAdmin_Form_Controller = $this->_Admin_Form_Controller;
		//$oAdmin_Form = $oAdmin_Form_Controller->getAdminForm();

		$oAdmin_View = Admin_View::create($oAdmin_Form_Controller->Admin_View)
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

		// $this->showFilter = TRUE;

		?>
		<div class="table-toolbar">
			<?php $this->_Admin_Form_Controller->showFormMenus()?>
			<div class="table-toolbar-right pull-right">
				<?php $this->_pageSelector()?>
				<?php $this->_Admin_Form_Controller->showChangeViews()?>
			</div>
			<div class="clear"></div>
		</div>
		<?php
		foreach ($aAdminFormControllerChildren as $oAdmin_Form_Entity)
		{
			$oAdmin_Form_Entity->execute();
		}

		$this->_showContent();
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

		// $oAdmin_Language = $oAdmin_Form_Controller->getAdminLanguage();

		$aAdmin_Form_Fields = $oAdmin_Form->Admin_Form_Fields->findAll();

		if ($this->_filterAvailable())
		{
			$this->_showTopFilter();
		}

		// $oSortingField = $oAdmin_Form_Controller->getSortingField();

		if (empty($aAdmin_Form_Fields))
		{
			throw new Core_Exception('Admin form does not have fields.');
		}

		$windowId = $oAdmin_Form_Controller->getWindowId();

		$oLead_Statuses = Core_Entity::factory('Lead_Status');
		$oLead_Statuses->queryBuilder()
			->where('lead_statuses.type', '=', 0)
			->where('lead_statuses.site_id', '=', CURRENT_SITE)
			->clearOrderBy()
			->orderBy('lead_statuses.sorting', 'ASC');

		$aLead_Statuses = $oLead_Statuses->findAll(FALSE);

		$aStatuses = array();

		?><style><?php
		foreach ($aLead_Statuses as $oLead_Status)
		{
			switch ($oLead_Status->type)
			{
				case 1:
					$statusClass = ' finish';
				break;
				case 2:
					$statusClass = ' failed';
				break;
				default:
					$statusClass = '';
			}

			$aStatuses[$oLead_Status->id] = array(
				'name' => $oLead_Status->name,
				'color' => $oLead_Status->color,
				'class' => $statusClass
			);

			?>.lead-status-<?php echo $oLead_Status->id?> .well.bordered-left { border-left-color: <?php echo htmlspecialchars($oLead_Status->color)?>} <?php
		}
		?></style><?php
		// Устанавливаем ограничения на источники
		$oAdmin_Form_Controller->setDatasetConditions();
		$oAdmin_Form_Controller->setDatasetLimits();

		$aDatasets = $oAdmin_Form_Controller->getDatasets();

		$aFields = $this->_getFields($oAdmin_Form_Controller);

		$aEntities = $aDatasets[0]->load();

		$aCounts = array();
		foreach ($aEntities as $oEntity)
		{
			isset($aCounts[$oEntity->lead_status_id])
				? $aCounts[$oEntity->lead_status_id]++
				: $aCounts[$oEntity->lead_status_id] = 1;
		}
		?>
		<div class="kanban-board">
			<div class="horizon-prev"><img src="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/images/scroll/l-arrow.png')?>"></div>
			<div class="horizon-next"><img src="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/images/scroll/r-arrow.png')?>"></div>
			<div class="kanban-wrapper">

			<?php
			foreach ($aStatuses as $iLeadStatusId => $aLeadStatus)
			{
				?><div class="kanban-col">
					<!-- <h5 style="color: <?php echo htmlspecialchars($aLeadStatus['color'])?>; padding-bottom: 5px; border-bottom: 2px solid <?php echo htmlspecialchars($aLeadStatus['color'])?>"><?php echo htmlspecialchars($aLeadStatus['name'])?></h5> -->

					<div id="data-<?php echo $iLeadStatusId?>" class="kanban-board-header margin-bottom-10">
						<?php
						$aHSL = Core_Str::hex2hsl($aLeadStatus['color']);
						?>
						<h5 style="background-color: <?php echo $aLeadStatus['color']?>" class="text-align-center no-margin-bottom <?php echo $aHSL['lightness'] > 200 ? ' dark' : ''?>">
							<?php
								echo htmlspecialchars($aLeadStatus['name']);

								if (Core_Array::get($aCounts, $iLeadStatusId, 0))
								{
									?><span class="kanban-deals-count"><?php echo Core_Array::get($aCounts, $iLeadStatusId, 0)?></span><?php
								}
							?>
						</h5>
						<span class="triangle" style="border-left-color: <?php echo $aLeadStatus['color']?>"></span>
						<span class="add" style="background-color: <?php echo htmlspecialchars($aLeadStatus['color'])?>" onclick="$.modalLoad({path: hostcmsBackend + '/lead/index.php', action: 'edit', operation: 'modal', additionalParams: 'hostcms[checked][0][0]=1&lead_status_id=<?php echo $iLeadStatusId?>', windowId: '<?php echo $windowId?>'}); return false"><i class="fa fa-plus-circle"></i></span>
					</div>

					<ul id="entity-list-<?php echo $iLeadStatusId?>" data-step-id="<?php echo $iLeadStatusId?>" class="kanban-list connectedSortable lead-status-<?php echo $iLeadStatusId?>">
					<?php
					foreach ($aEntities as $oEntity)
					{
						if ($oEntity->lead_status_id == $iLeadStatusId)
						{
						?>
						<li id="lead-<?php echo $oEntity->id?>" data-id="<?php echo $oEntity->id?>" class="<?php echo $aLeadStatus['class']?>" data-lead-id="<?php echo $oEntity->id?>" ondblclick="$.modalLoad({path: hostcmsBackend + '/lead/index.php', action: 'edit', operation: 'modal', additionalParams: 'hostcms[checked][0][<?php echo $oEntity->id?>]=1&parentWindowId=id_content', windowId: 'id_content', width: '90%'});">
							<div class="well">
								<div class="row">
									<div class="col-xs-12 col-sm-8">
										<a class="name" onclick="$.modalLoad({path: hostcmsBackend + '/lead/index.php', action: 'edit', operation: 'modal', additionalParams: 'hostcms[checked][0][<?php echo $oEntity->id?>]=1&parentWindowId=id_content', windowId: 'id_content', width: '90%'});"><?php echo htmlspecialchars($oEntity->getFullName())?></a>
									</div>
									<div class="col-xs-12 col-sm-4 text-align-right">
										<?php echo $oEntity->entityBackend()?>
									</div>
								</div>
								<div class="crm-company small"><?php
									if ($oEntity->company != '')
									{
										?><span class="semi-bold"><?php echo htmlspecialchars($oEntity->company)?></span><?php
									}
									if ($oEntity->post != '')
									{
										echo ', ', htmlspecialchars($oEntity->post);
									}
								?></div>
								<?php
								$oEntity->commentBackend();

								if (!is_null($oEntity->datetime) && $oEntity->datetime != '0000-00-00 00:00:00')
								{
									?><div class="crm-description">
										<span class="crm-date"><?php echo Event_Controller::getDateTime($oEntity->datetime)?></span>
									</div><?php
								}
								?><div class="crm-contacts"><?php
									$oEntity->showPhones();
									$oEntity->showEmails();
								?></div><?php

								if ($oEntity->crm_source_id)
								{
									?><div class="lead-source-wrapper"><?php
										echo $oEntity->showSource();
									?></div><?php
								}
								?>
								<?php
									if (Core::moduleIsActive('tag'))
									{
										?><div class="row kanban-tags"><div class="col-xs-12"><?php
										$aTags = $oEntity->Tags->findAll(FALSE);

										foreach ($aTags as $oTag)
										{
											Core_Html_Entity::factory('Code')
												->value('<span class="badge badge-square badge-tag badge-max-width badge-lightgray margin-right-5" title="' . htmlspecialchars($oTag->name) . '"><i class="fa fa-tag"></i> ' . htmlspecialchars($oTag->name) . '</span>')
												->execute();
										}
										?></div></div><?php
									}

									foreach ($aFields as $oField)
									{
										$aField_Values = $oField->getValues($oEntity->id, FALSE);
										if (count($aField_Values))
										{
											?><div class="kanban-user-field-wrapper"><?php
											echo htmlspecialchars($oField->name), ': ';

											$aValues = array();
											foreach ($aField_Values as $oField_Value)
											{
												$aValues[] = htmlspecialchars($this->_getFieldValue($oField, $oField_Value, $oEntity));
											}

											?><span><?php echo implode(',', $aValues)?></span></div><?php
										}
									}
								?>
								<div class="row">
									<div class="col-xs-12 col-sm-6">
										<?php
										if ($oEntity->amount > 0)
										{
											?>
											<div class="small semi-bold"><?php echo htmlspecialchars(Core_Entity::factory('Shop_Currency', $oEntity->shop_currency_id)->formatWithCurrency($oEntity->amount))?></div>
											<?php
										}
										?>
									</div>
									<div class="col-xs-12 col-sm-6 text-align-right kanban-list-deals">
										<?php
										if (Core::moduleIsActive('event'))
										{
											echo Event_Controller::showRelatedEvents($oEntity);
										}
										?>
									</div>
								</div>
							</div>
						</li>
						<?php
						}
					}
					?>
					</ul>
				</div><?php
			}
			?>
			</div>

			<div class="kanban-action-wrapper hidden">
				<div class="kanban-actions text-align-center">
					<?php
					$oLead_Statuses = Core_Entity::factory('Lead_Status');
					$oLead_Statuses->queryBuilder()
						->where('lead_statuses.type', '!=', 0)
						->where('lead_statuses.site_id', '=', CURRENT_SITE)
						->clearOrderBy()
						->orderBy('lead_statuses.sorting', 'ASC');

					$aLead_Statuses = $oLead_Statuses->findAll(FALSE);

					$count = count($aLead_Statuses);

					$width = $count
						? 90 / $count
						: 100;

					$deleteWidth = $width == 100
						? 100
						: 10;

					foreach ($aLead_Statuses as $oLead_Status)
					{
						?>
						<ul id="entity-list-<?php echo $oLead_Status->id?>" data-hover-bg="<?php echo htmlspecialchars(Core_Str::hex2lighter($oLead_Status->color, 0.8))?>" data-step-id="<?php echo $oLead_Status->id?>" data-id="<?php echo $oLead_Status->id?>" style="width: <?php echo $width?>%; background-color: <?php echo htmlspecialchars(Core_Str::hex2lighter($oLead_Status->color, 0.27))?>; border-top: 3px solid <?php echo htmlspecialchars($oLead_Status->color)?>; color: #fff" class="connectedSortable kanban-action-item"><div class="kanban-action-item-name"><?php echo htmlspecialchars($oLead_Status->name)?></div><div class="return hidden"><i class="fa fa-undo"></i> <?php echo htmlspecialchars($oLead_Status->name)?></div></ul>
						<?php
					}
					?>

					<ul data-id="-1" data-hover-bg="<?php echo htmlspecialchars(Core_Str::hex2lighter('#e5e5e5', 0.8))?>" style="width: <?php echo $deleteWidth?>%; background-color: #e5e5e5; border-top: 3px solid #777; color: #777;" class="connectedSortable kanban-action-item"><div class="kanban-action-item-name"><i class="fa fa-trash"></i></div><div class="return hidden"><i class="fa fa-undo"></i></div></ul>
				</div>
			</div>
		</div>
		<script>
		$(function() {
			$.sortableKanban({path: hostcmsBackend + '/lead/index.php', container: '.kanban-board', updateData: true, windowId: '<?php echo $windowId?>', moveCallback: $._kanbanStepMoveLeadCallback, handle: '.well'});
			$.showKanban('.kanban-board');
		});
		</script>
		<?php

		if (Core_Array::get($oAdmin_Form_Controller->filterSettings, 'show'))
		{
			?><script>$.toggleFilter();</script><?php
		}

		return $this;
	}
}