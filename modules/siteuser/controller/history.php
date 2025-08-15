<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Controller_History
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Controller_History extends Admin_Form_Controller_View
{
	public function execute()
	{
		$oAdmin_Form_Controller = $this->_Admin_Form_Controller;
		$oAdmin_Form = $oAdmin_Form_Controller->getAdminForm();

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
		?>
		<div class="table-toolbar">
			<div class="table-toolbar-right pull-right">
				<?php $this->_Admin_Form_Controller->pageSelector()?>
			</div>
			<div class="clear"></div>
		</div>
		<?php
		foreach ($aAdminFormControllerChildren as $oAdmin_Form_Entity)
		{
			$oAdmin_Form_Entity->execute();
		}

		$this->_showContent();
		?>
		<div class="row margin-bottom-20 margin-top-10">
			<div class="col-xs-12 col-sm-6 col-md-8 text-align-left timeline-board">
				<?php $this->_Admin_Form_Controller->pageNavigation()?>
			</div>
			<div class="col-xs-12 col-sm-6 col-md-4 text-align-right">
				<?php $this->_Admin_Form_Controller->pageSelector()?>
			</div>
		</div>
		<?php
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

		$oSortingField = $oAdmin_Form_Controller->getSortingField();

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
		$oAdmin_Form_Controller->setDatasetConditions();

		$oAdmin_Form_Controller->setDatasetLimits();

		$aDatasets = $oAdmin_Form_Controller->getDatasets();

		$aEntities = $aDatasets[0]->load();

		$aTmp = array();
		foreach ($aEntities as $oEntity)
		{
			$aTmp[Core_Date::sql2date($oEntity->datetime)][] = $oEntity;
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

		if (count($aTmp))
		{
			?>
			<ul class="timeline">
				<?php
				$i = $j = 0;
				foreach ($aTmp as $datetime => $aTmpEntities)
				{
					$color = $aColors[$i % $iCountColors];

					?>
					<li class="timeline-node">
						<a class="badge badge-<?php echo $color?>"><?php echo $datetime?></a>
					</li>
					<?php
					foreach ($aTmpEntities as $key => $oTmpEntity)
					{
						$inverted = $j % 2 == 0 ? '' : 'class="timeline-inverted"';

						$time = date('H:i', Core_Date::sql2timestamp($oTmpEntity->datetime));

						$paid = $canceled = $paid_text = $canceled_text = '';

						switch ($oTmpEntity->type)
						{
							// Comments
							case 0:
								$badge = 'fa fa-comment-o';

								$oComment = Core_Entity::factory('Comment', $oTmpEntity->id);

								$title = strlen(trim($oComment->subject)) ? htmlspecialchars($oComment->subject) : '';

								$text = Core_Str::cutSentences(strip_tags($oComment->text), 250);

								$iEntityAdminFormId = 52;

								if ($oComment->Comment_Shop_Item->id)
								{
									$oShop_Item = $oComment->Comment_Shop_Item->Shop_Item;

									$path = '/{admin}/shop/item/comment/index.php';
									$additionalParams = "shop_id={$oShop_Item->shop_id}&shop_group_id={$oShop_Item->shop_group_id}";
								}
								else
								{
									$oInformationsystem_Item = $oComment->Comment_Informationsystem_Item->Informationsystem_Item;

									$path = '/{admin}/informationsystem/item/comment/index.php';
									$additionalParams = "informationsystem_id={$oInformationsystem_Item->informationsystem_id}&informationsystem_group_id={$oInformationsystem_Item->informationsystem_group_id}";
								}

								$datasetId = 0;
							break;
							// Shop Orders
							case 1:
								$badge = 'fa fa-shopping-basket';

								$oShop_Order = Core_Entity::factory('Shop_Order', $oTmpEntity->id);

								$title = Core::_('Shop_Order.popover_title', $oShop_Order->invoice, Core_Date::sql2datetime($oShop_Order->datetime));

								$text = $oShop_Order->orderPopover();

								$iEntityAdminFormId = 75;

								$path = '/{admin}/shop/order/index.php';
								$additionalParams = "shop_id={$oShop_Order->shop_id}&shop_group_id=0";
								$datasetId = 0;

								if ($oShop_Order->paid)
								{
									$paid = '<i class="fa fa-check-circle success margin-left-10 margin-right-5" title="' . Core::_('Shop_Order.paid') . '"></i>';
									$paid_text = '<span class="success">' . Core::_('Shop_Order.paid') . '</span>';
								}

								if ($oShop_Order->canceled)
								{
									$canceled = '<i class="fa fa-times-circle danger margin-left-10 margin-right-5" title="' . Core::_('Shop_Order.canceled') . '"></i>';
									$canceled_text = '<span class="danger">' . Core::_('Shop_Order.canceled') . '</span>';
								}
							break;
							// Shop Items
							case 2:
								$badge = 'fa fa-shopping-cart';

								$oShop_Item = Core_Entity::factory('Shop_Item', $oTmpEntity->id);

								$title = htmlspecialchars($oShop_Item->name);

								$text = $oShop_Item->description != ''
									? $oShop_Item->description
									: $oShop_Item->text;

								$text = Core_Str::cutSentences(strip_tags($text), 250);

								$iEntityAdminFormId = 65;

								$path = '/{admin}/shop/item/index.php';
								$additionalParams = "shop_id={$oShop_Item->shop_id}&shop_group_id={$oShop_Item->shop_group_id}&shop_dir_id={$oShop_Item->Shop->shop_dir_id}";
								$datasetId = 1;
							break;
							// Informationsystem Items
							case 3:
								$badge = 'fa fa-newspaper-o';

								$oInformationsystem_Item = Core_Entity::factory('Informationsystem_Item', $oTmpEntity->id);

								$title = htmlspecialchars($oInformationsystem_Item->name);

								$text = strlen($oInformationsystem_Item->description)
									? $oInformationsystem_Item->description
									: $oInformationsystem_Item->text;

								$text = Core_Str::cutSentences(strip_tags($text), 250);

								$iEntityAdminFormId = 12;

								$path = '/{admin}/informationsystem/item/index.php';
								$additionalParams = "informationsystem_id={$oInformationsystem_Item->informationsystem_id}";
								$datasetId = 1;
							break;
							// Helpdesk Tickets
							case 4:
								$badge = 'fa fa-life-ring';

								$oHelpdesk_Ticket = Core_Entity::factory('Helpdesk_Ticket', $oTmpEntity->id);

								// Получаем информацию о первом сообщении тикета
								$oHelpdesk_Message = $oHelpdesk_Ticket->Helpdesk_Messages->getFirstMessage();

								$title = '[' . $oHelpdesk_Ticket->number . '] ';

								if (!is_null($oHelpdesk_Message))
								{
									$title .= strlen(trim($title))
										? htmlspecialchars(Core_Str::cut($oHelpdesk_Message->subject, 75))
										: Core::_('Helpdesk_Ticket.helpdesk_ticket_no_subject');
								}

								$text = strlen($oHelpdesk_Message->message)
									? Core_Str::cut(strip_tags($oHelpdesk_Message->message), 250)
									: '';

								$iEntityAdminFormId = 148;

								$path = '/{admin}/helpdesk/ticket/index.php';
								$additionalParams = "helpdesk_id={$oHelpdesk_Ticket->Helpdesk->id}&helpdesk_category_id={$oHelpdesk_Ticket->helpdesk_category_id}";
								$datasetId = 1;
							break;
							// Events
							case 5:
								$badge = 'fa fa-tasks';

								$oEvent = Core_Entity::factory('Event', $oTmpEntity->id);

								// $title = htmlspecialchars($oEvent->name);
								$title = '';

								ob_start();

								$path = $oAdmin_Form_Controller->getPath();

								$oEventCreator = $oEvent->getCreator();

								// Временая метка создания дела
								$iEventCreationTimestamp = Core_Date::sql2timestamp($oEvent->datetime);

								// Сотрудник - создатель дела
								$userIsEventCreator = !is_null($oEventCreator) && $oEventCreator->id == $oUser->id;

								$oEvent_Type = $oEvent->Event_Type;

								$oEvent->event_type_id && $oEvent->showType();

								// Менять статус дела может только его создатель
								if ($userIsEventCreator)
								{
									// Список статусов дел
									$aEvent_Statuses = Core_Entity::factory('Event_Status')->findAll();

									$aMasEventStatuses = array(array('value' => Core::_('Event.notStatus'), 'color' => '#aebec4'));

									foreach ($aEvent_Statuses as $oEvent_Status)
									{
										$aMasEventStatuses[$oEvent_Status->id] = array('value' => $oEvent_Status->name, 'color' => $oEvent_Status->color);
									}

									$oCore_Html_Entity_Dropdownlist = new Core_Html_Entity_Dropdownlist();

									$oCore_Html_Entity_Dropdownlist
										->value($oEvent->event_status_id)
										->options($aMasEventStatuses)
										//->class('btn-group event-status')
										->onchange("$.adminLoad({path: hostcmsBackend + '/event/index.php', additionalParams: 'hostcms[checked][0][{$oEvent->id}]=0&eventStatusId=' + $(this).find('li[selected]').prop('id'), action: 'changeStatus', windowId: '{$oAdmin_Form_Controller->getWindowId()}'});")
										->execute();
								}
								else
								{
									if ($oEvent->event_status_id)
									{
										$oEvent_Status = Core_Entity::factory('Event_Status', $oEvent->event_status_id);

										$sEventStatusName = htmlspecialchars($oEvent_Status->name);
										$sEventStatusColor = htmlspecialchars($oEvent_Status->color);
									}
									else
									{
										$sEventStatusName = Core::_('Event.notStatus');
										$sEventStatusColor = '#aebec4';
									}
									?>
									<div class="event-status">
										<i class="fa fa-circle" style="margin-right: 5px; color: <?php echo $sEventStatusColor?>"></i><span style="color: <?php echo $sEventStatusColor?>"><?php echo $sEventStatusName?></span>
									</div>
									<?php
								}

								$nameColorClass = $oEvent->deadline()
									? 'event-title-deadline'
									: '';

								$deadlineIcon = $oEvent->deadline()
									? '<i class="fa fa-clock-o event-title-deadline"></i>'
									: '';

								?>
								<div class="event-title <?php echo $nameColorClass?>"><?php echo $deadlineIcon, htmlspecialchars($oEvent->name)?></div>

								<div class="event-description"><?php echo Core_Str::cutSentences(strip_tags($oEvent->description), 250)?></div>

								<div class="crm-date"><?php

								if ($oEvent->all_day)
								{
									echo Event_Controller::getDate($oEvent->start);
								}
								else
								{
									if (!is_null($oEvent->start) && $oEvent->start != '0000-00-00 00:00:00')
									{
										echo Event_Controller::getDateTime($oEvent->start);
									}

									if (!is_null($oEvent->start) && $oEvent->start != '0000-00-00 00:00:00'
										&& !is_null($oEvent->deadline) && $oEvent->deadline != '0000-00-00 00:00:00'
									)
									{
										echo ' — ';
									}

									if (!is_null($oEvent->deadline) && $oEvent->deadline != '0000-00-00 00:00:00')
									{
										?><strong><?php echo Event_Controller::getDateTime($oEvent->deadline);?></strong><?php
									}
								}

								// $iDeltaTime = time() - $iEventCreationTimestamp;

								// ФИО создателя дела, если оным не является текущий сотрудник
								if (!$userIsEventCreator && !is_null($oEventCreator))
								{
									?><div class="<?php echo $oEventCreator->isOnline() ? 'online margin-left-20' : 'offline margin-left-20'?>"></div><?php
									$oEventCreator->showLink($oAdmin_Form_Controller->getWindowId());
								}
								?>
								</div><?php

								$text = ob_get_clean();

								$iEntityAdminFormId = 220;

								$path = '/{admin}/event/index.php';
								$additionalParams = "";
								$datasetId = 0;
							break;
						}
						?>
						<!-- <li <?php echo $inverted?>> -->
						<li class="timeline-inverted">
							<div class="timeline-datetime">
								<span class="timeline-time">
									<?php echo $time?>
								</span>
							</div>
							<div class="timeline-badge <?php echo $color?>">
								<i class="<?php echo $badge?>"></i>
							</div>
							<div class="timeline-panel">
								<div class="timeline-header bordered-bottom bordered-<?php echo $color?>">
									<span class="timeline-title">
										<?php echo $title?><?php echo $paid?><?php echo $paid_text?><?php echo $canceled?><?php echo $canceled_text?>
									</span>
									<div class="pull-right timeline-entity-actions">
										<?php
										$oEntity_Admin_Form = Core_Entity::factory('Admin_Form', $iEntityAdminFormId);

										// Отображать в списке действий
										if ($oEntity_Admin_Form->show_operations)
										{
											$aAllowed_Admin_Form_Actions = $oEntity_Admin_Form->Admin_Form_Actions->getAllowedActionsForUser($oUser);

											foreach ($aAllowed_Admin_Form_Actions as $oAdmin_Form_Action)
											{
												$aAllowedActions = array('edit', 'markDeleted');

												// Отображаем действие, только если разрешено.
												if (!$oAdmin_Form_Action->single || !in_array($oAdmin_Form_Action->name, $aAllowedActions))
												{
													continue;
												}

												if (method_exists($oTmpEntity, 'checkBackendAccess') && !$oTmpEntity->checkBackendAccess($oAdmin_Form_Action->name, $oUser))
												{
													continue;
												}

												$Admin_Word_Value = $oAdmin_Form_Action->Admin_Word->getWordByLanguage($oAdmin_Language->id);

												$name = $Admin_Word_Value && strlen($Admin_Word_Value->name) > 0
													? $Admin_Word_Value->name
													: '';

												$path = Admin_Form_Controller::correctBackendPath($path);

												$href = $oAdmin_Form_Controller->getAdminActionLoadHref($path, $oAdmin_Form_Action->name, NULL, $datasetId, intval($oTmpEntity->id), $additionalParams, 10, 1, NULL, NULL, 'list');

												$onclick = $oAdmin_Form_Action->name == 'edit'
													? $oAdmin_Form_Controller->getAdminActionModalLoad(array('path' => $path, 'action' => $oAdmin_Form_Action->name, 'operation' => 'modal', 'datasetKey' => $datasetId, 'datasetValue' => intval($oTmpEntity->id), 'additionalParams' => $additionalParams, 'window' => ''))
													: $oAdmin_Form_Controller->getAdminActionLoadAjax($path, $oAdmin_Form_Action->name, NULL, $datasetId, intval($oTmpEntity->id), $additionalParams, 10, 1, NULL, NULL, 'list');

												// Добавляем установку метки для чекбокса и строки + добавлем уведомление, если необходимо
												if ($oAdmin_Form_Action->confirm)
												{
													$onclick = "res = confirm('".Core::_('Admin_Form.confirm_dialog', htmlspecialchars($name))."'); if (!res) { $('#{$windowId} #row_0_{$oTmpEntity->id}').toggleHighlight(); } else {{$onclick}} return res;";
												}
												?><a onclick="<?php echo htmlspecialchars($onclick)?>" href="<?php echo htmlspecialchars($href)?>" title="<?php echo htmlspecialchars($name)?>"><i class="<?php echo htmlspecialchars($oAdmin_Form_Action->icon)?>"></i></a><?php
											}
										}
										?>
									</div>
									<p class="timeline-datetime">
										<small class="text-muted">
											<i class="glyphicon glyphicon-time"></i>
											<span class="timeline-time"><?php echo $time?></span>
										</small>
									</p>
								</div>
								<div class="timeline-body">
									<?php echo $text?>
								</div>
							</div>
						</li>
						<?php
						$j++;
					}
					$i++;
				}
				?>
			</ul>
			<?php
		}
		else
		{
			Core_Message::show(Core::_('Admin_Form.timeline_empty'), 'warning');
		}

		return $this;
	}
}