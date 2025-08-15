<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Admin forms.
 *
 * @package HostCMS
 * @subpackage Skin
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2018 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Skin_Bootstrap_Event_Admin_Form_Controller extends Skin_Bootstrap_Admin_Form_Controller
{
	/**
	 * Show items count selector
	 */
	/*protected function _pageSelector()
	{
		parent::_pageSelector();

		?><div class="view-selector pull-left"><input type="radio" id="list" data-view="0" name="selector" checked="checked"/><label for="list"><?php echo Core::_('Admin_Form.list')?></label><input type="radio" id="kanban" data-view="1" name="selector"><label for="kanban"><?php echo Core::_('Admin_Form.kanban')?></label></div>

		<script type="text/javascript">
			$(function() {
				$('input[name="selector"][type=radio]').on("change", function () {
					var view = $(this).data('view');

					switch (view) {
					case 0:
					default:
						$('.admin-table.table').removeClass('hidden');
						$('div .DTTTFooter').removeClass('hidden');

						$('.kanban-board').addClass('hidden');
					break;
					case 1:
						$('.admin-table.table').addClass('hidden');
						$('div .DTTTFooter').addClass('hidden');

						$('.kanban-board').removeClass('hidden');
					break;
					}
				});
			});
		</script>
		<?php
	}*/

	/**
	 * Show form content in administration center
	 * @return self
	 */
	/*public function showContent()
	{
		parent::showContent();

		$oUser = Core_Entity::factory('User', 0)->getCurrent();

		$oEvents = Core_Entity::factory('Event');
		$oEvents->queryBuilder()
			->select('events.*')
			->join('event_users', 'events.id', '=', 'event_users.event_id')
			->where('event_users.user_id', '=', $oUser->id)
			->clearOrderBy()
			->orderBy('events.id', 'ASC');

		$aEvents = $oEvents->findAll(FALSE);

		$oEvent_Statuses = Core_Entity::factory('Event_Status');
		$oEvent_Statuses->queryBuilder()
			->clearOrderBy()
			->orderBy('event_statuses.sorting', 'ASC');

		$aEvent_Statuses = $oEvent_Statuses->findAll(FALSE);

		$aColors = array(
			'blue',
			'palegreen',
			'warning',
			'darkorange',
			'danger',
			'maroon',
		);
		$iCountColors = count($aColors);

		?>

		<div class="container kanban-board hidden">
			<div class="row">
			<?php
				foreach ($aEvent_Statuses as $oEvent_Status)
				{
					?>
					<div class="col-xs-12 col-sm-3">
						<h5 class="bold"><?php echo htmlspecialchars($oEvent_Status->name)?></h5>
						<div class="row">
							<div class="col-xs-12">
								<ul id="<?php echo $oEvent_Status->id?>" class="kanban-list">
								<?php
								foreach ($aEvents as $key => $oEvent)
								{
									$color = $iCountColors
										? $aColors[$key % $iCountColors]
										: 'palegreen';

									if ($oEvent->event_status_id == $oEvent_Status->id)
									{
										$oEventCreator = $oEvent->getCreator();
										$userIsEventCreator = $oEventCreator && $oEventCreator->id == $oUser->id;
									?>
									<li id="<?php echo $oEvent->id?>">
										<div class="well bordered-left bordered-<?php echo $color?>">
											<div class="drag-handle"></div>
											<div class="row">
												<div class="col-xs-12 col-sm-6">
													<?php echo $oEvent->showType()?>
												</div>
												<div class="col-xs-12 col-sm-6 well-avatar text-align-right">
													<?php
													if (!$userIsEventCreator)
													{
													?>
														<img src="<?php echo $oEventCreator->getImageHref()?>" title="<?php echo $oEventCreator->getFullName()?>"/>
													<?php
													}
													?>

													<img src="<?php echo $oUser->getImageHref()?>" title="<?php echo $oUser->getFullName()?>"/>
												</div>
											</div>
											<div class="row">
												<div class="col-xs-12 col-md-10 well-body">
													<span><?php echo htmlspecialchars($oEvent->name)?></span>
												</div>
											</div>
											<div class="row">
												<div class="col-xs-12 well-description">
													<span><?php echo htmlspecialchars($oEvent->description)?></span>
												</div>
											</div>
											<div class="row">
												<div class="col-xs-12 well-description">
													<div class="event-date">
													<?php
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
															&& !is_null($oEvent->finish) && $oEvent->finish != '0000-00-00 00:00:00'
														)
														{
															echo ' — ';
														}

														if (!is_null($oEvent->finish) && $oEvent->finish != '0000-00-00 00:00:00')
														{
															?><strong><?php echo Event_Controller::getDate($oEvent->finish);?></strong><?php
														}
													}
													?>
													</div>
												</div>
											</div>
										</div>
									</li>
									<?php
									}
								}
								?>
								</ul>
							</div>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<script type="text/javascript">
			$(function() {
				$.sortableKanban('/admin/event/index.php');
			});
		</script>
		<?php

		return $this;
	}*/
}