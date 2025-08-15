<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media_Controller_View
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Media_Controller_View extends Admin_Form_Controller_View
{
	/**
	 * Executes the business logic.
	 * @return self
	 */
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

		$entity_id = Core_Array::getGet('entity_id', 0, 'int');

		// При показе формы могут быть добавлены сообщения в message, поэтому message показывается уже после отработки формы
		ob_start();
		?>
		<div class="table-toolbar">
			<?php
			if ($entity_id)
			{
				?><div class="page-breadcrumbs margin-bottom-20"><ul class="breadcrumb"><?php $this->_Admin_Form_Controller->showFormBreadcrumbs()?></ul></div><?php
			}
			?>

			<?php $this->_Admin_Form_Controller->showFormMenus()?>
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
		$this->_showFooter();

		$content = ob_get_clean();

		$oAdmin_View
			->content($content)
			->message($oAdmin_Form_Controller->getMessage())
			->show();

		$oAdmin_Form_Controller->applyEditable();
		$oAdmin_Form_Controller->showSettings();

		return $this;
	}

	/**
	 * Show form content in administration center
	 * @return self
	 */
	protected function _showContent()
	{
		$entity_id = Core_Array::getGet('entity_id', 0, 'int');
		$entity_type = Core_Array::getGet('entity_type', '', 'trim');

		$media_group_id = Core_Array::getGet('media_group_id', 0, 'int');

		$oAdmin_Form_Controller = $this->_Admin_Form_Controller;
		$oAdmin_Form = $oAdmin_Form_Controller->getAdminForm();

		// $oAdmin_Language = $oAdmin_Form_Controller->getAdminLanguage();

		$aAdmin_Form_Fields = $oAdmin_Form->Admin_Form_Fields->findAll();

		// $oSortingField = $oAdmin_Form_Controller->getSortingField();

		if (empty($aAdmin_Form_Fields))
		{
			throw new Core_Exception('Admin form does not have fields.');
		}

		// $parentWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('parentWindowId', '', 'str'));
		// $windowId = $parentWindowId ? $parentWindowId : $oAdmin_Form_Controller->getWindowId();

		$windowId = $oAdmin_Form_Controller->getWindowId();

		// $oCurrentUser = Core_Auth::getCurrentUser();

		// Устанавливаем ограничения на источники
		$oAdmin_Form_Controller->setDatasetLimits()->setDatasetConditions();

		$additionalParams = '';

		$media_group_id
			&& $additionalParams .= "media_group_id={$media_group_id}";

		$entity_id
			&& $additionalParams .= "&showMediaModal=1&entity_id={$entity_id}&entity_type={$entity_type}";

		$parentWindowId = Core_Array::getGet('parentWindowId', '', 'trim');
		$parentWindowId !== ''
			&& $additionalParams .= "&parentWindowId={$parentWindowId}";

		$modalWindowId = Core_Array::getGet('modalWindowId', '', 'trim');
		$modalWindowId !== ''
			&& $additionalParams .= "&modalWindowId={$modalWindowId}";

		?>
		<div class="row margin-top-10 media-dropzone">
			<div class="col-xs-12">
				<div id="dropzone">
					<div class="dz-message needsclick"><i class="fa fa-arrow-circle-o-up"></i> <?php echo Core::_('Admin_Form.upload_file')?></div>
				</div>
			</div>
		</div>

		<script>
		$(function() {
			$("#<?php echo $windowId?> #dropzone").dropzone({
				url: hostcmsBackend + '/media/index.php?hostcms[action]=uploadFiles&hostcms[checked][0][0]=1&<?php echo $additionalParams?>',
				parallelUploads: 10,
				maxFilesize: <?php echo Core::$mainConfig['dropzoneMaxFilesize']?>,
				paramName: 'file',
				uploadMultiple: true,
				// autoProcessQueue: false,
				autoDiscover: false,
				init: function() {
					var dropzone = this;

					$(".formButtons #action-button-apply").on("click", function(e) {
						e.preventDefault();
						e.stopPropagation();

						if (dropzone.getQueuedFiles().length)
						{
							dropzone.processQueue();
						}
					});
				},
				success : function(file, response){
					$.adminLoad({ path: hostcmsBackend + '/media/index.php', additionalParams: '<?php echo $additionalParams?>', windowId: '<?php echo $windowId?>' });
				}
			});
		});
		</script>
		<?php

		$oAdmin_Form_Controller->addAdditionalParam('secret_csrf', Core_Security::getCsrfToken());

		$aDatasets = $oAdmin_Form_Controller->getDatasets();

		$aMedia_Groups = $aDatasets[0]->load();

		if (count($aMedia_Groups))
		{
			$aAdmin_Form_Actions = $oAdmin_Form->Admin_Form_Actions->findAll();

			$oAdmin_Form_Field_Group_Name = $oAdmin_Form->Admin_Form_Fields->getByName('name');

			?><div class="media-groups-wrapper"><?php
			foreach ($aMedia_Groups as $oMedia_Group)
			{
				?><div class="media-groups-item">
					<div class="media-groups-item-image"><i class="fa-solid fa-folder"></i></div>
					<div class="media-groups-name">
						<?php echo $oMedia_Group->nameBackend($oAdmin_Form_Field_Group_Name, $oAdmin_Form_Controller)?>
						<div class="media-groups-item-info small gray">
							<span> <?php echo Core::_('Media.groups', $oMedia_Group->getGroupsChildCount())?></span>, <span><?php echo Core::_('Media.files', $oMedia_Group->getChildCount())?></span>
						</div>
					</div>
					<div class="media-groups-item-actions">
						<?php
						foreach ($aAdmin_Form_Actions as $key => $oAdmin_Form_Action)
						{
							if (!in_array($oAdmin_Form_Action->name, array('edit', 'markDeleted')))
							{
								continue;
							}

							$datasetKey = 0;
							$entityKey = $oMedia_Group->id;

							$onclick = /*$entity_id
								? $oAdmin_Form_Controller->getAdminActionModalLoad(array(
									'path' => $oAdmin_Form_Controller->getPath(), 'action' => $oAdmin_Form_Action->name,
									'operation' => 'modal',
									'datasetKey' => $datasetKey, 'datasetValue' => $entityKey,
									'width' => '90%'
								))
								: */$oAdmin_Form_Controller->getAdminActionLoadAjax(array(
									'path' => $oAdmin_Form_Controller->getPath(), 'action' => $oAdmin_Form_Action->name,
									'datasetKey' => $datasetKey, 'datasetValue' => $entityKey
								));

							switch ($oAdmin_Form_Action->name)
							{
								case 'edit':
									//$onclick = preg_replace('/parentWindowId=(.*?)[&\']/', '', $onclick);
									$onclick = preg_replace('/parentWindowId=/iu', 'tmpMediaSource=', $onclick);

									?><i class="fa fa-pen" onclick="<?php echo $onclick?>;"></i><?php
								break;
								case 'markDeleted':
									$deleteOnclick = "res = confirm(i18n['confirm_delete']); if (res) { {$onclick} }";

									?><i class="fa fa-trash" onclick="<?php echo $deleteOnclick?>"></i><?php
								break;
							}
						}
						?>
					</div>
				</div><?php
			}
			?></div><?php
		}

		$aMedia_Items = $aDatasets[1]->load();

		?><div class="media-wrapper">
			<?php
			foreach ($aMedia_Items as $oMedia_Item)
			{
				$oDiv = Media_Controller::getMediaItemBlock($windowId, $oMedia_Item, $oAdmin_Form_Controller);
				$oDiv->execute();
			}
			?>
		</div><?php

		return $this;
	}

	/**
	 * Show form footer
	 * @hostcms-event Admin_Form_Controller.onBeforeShowFooter
	 * @hostcms-event Admin_Form_Controller.onAfterShowFooter
	 */
	public function _showFooter()
	{
		$bShowNavigation = $this->showPageNavigation
			&& $this->_Admin_Form_Controller->getTotalCount() > $this->_Admin_Form_Controller->limit;

		Core_Event::notify('Admin_Form_Controller.onBeforeShowFooter', $this->_Admin_Form_Controller, array($this));

		?><div class="DTTTFooter">
			<div class="row">
				<div class="col-xs-12 <?php echo $bShowNavigation ? 'col-sm-6 col-md-7 col-lg-8' : ''?>">
					<?php $this->bottomActions()?>
				</div>
				<?php
				if ($bShowNavigation)
				{
					?><div class="col-xs-12 col-sm-6 col-md-5 col-lg-4">
						<?php $this->_Admin_Form_Controller->pageNavigation()?>
					</div><?php
				}
				?>
			</div>
		</div>
		<script>
			$(function (){
				// Sticky actions
				$('.DTTTFooter').addClass('sticky-actions');

				$(document).on("scroll", function () {
					// to bottom
					if ($(window).scrollTop() + $(window).height() == $(document).height()) {
						$('.DTTTFooter').removeClass('sticky-actions');
					}

					// to top
					if ($(window).scrollTop() + $(window).height() < $(document).height()) {
						$('.DTTTFooter').addClass('sticky-actions');
					}
				});
			});
		</script>
		<?php

		Core_Event::notify('Admin_Form_Controller.onAfterShowFooter', $this->_Admin_Form_Controller, array($this));

		return $this;
	}

	/**
	 * Show action panel in administration center
	 * @return self
	 */
	public function bottomActions()
	{
		$oAdmin_Form_Controller = $this->_Admin_Form_Controller;
		$oAdmin_Form = $oAdmin_Form_Controller->getAdminForm();

		$oAdmin_Language = $oAdmin_Form_Controller->getAdminLanguage();

		// Строка с действиями
		//if ($this->_showBottomActions)
		//{
			// $windowId = $oAdmin_Form_Controller->getWindowId();

			// Текущий пользователь
			$oUser = Core_Auth::getCurrentUser();

			if (is_null($oUser))
			{
				return FALSE;
			}

			// Доступные действия для пользователя
			$aAllowed_Admin_Form_Actions = $oAdmin_Form_Controller->getAdminFormActions();

			// Групповые операции
			if ($oAdmin_Form->show_group_operations && !empty($aAllowed_Admin_Form_Actions))
			{
				?><div class="dataTables_actions"><?php
				$sActionsFullView = $sActionsShortView = '';

				$iGroupCount = 0;

				// $oAdmin_Form_Controller->addAdditionalParam('secret_csrf', Core_Security::getCsrfToken());

				foreach ($aAllowed_Admin_Form_Actions as $oAdmin_Form_Action)
				{
					if ($oAdmin_Form_Action->group)
					{
						$iGroupCount++;

						$text = htmlspecialchars($oAdmin_Form_Action->getCaption($oAdmin_Language->id));

						$href = $oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), $oAdmin_Form_Action->name);
						$onclick = $oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), $oAdmin_Form_Action->name);

						// Нужно подтверждение для действия
						if ($oAdmin_Form_Action->confirm)
						{
							$onclick = "res = confirm('" . Core::_('Admin_Form.confirm_dialog', htmlspecialchars($text)) . "'); if (res) { {$onclick} } else {return false}";

							$link_class = 'admin_form_action_alert_link';
						}
						else
						{
							$link_class = 'admin_form_action_link';
						}

						// ниже по тексту alt-ы и title-ы не выводятся, т.к. они дублируются текстовыми
						// надписями и при отключении картинок текст дублируется
						/* alt="<?php echo htmlspecialchars($text)?>"*/

						$sActionsFullView .= '<li><a title="' . htmlspecialchars($text) . '" href="' . $href . '" onclick="mainFormLocker.unlock(); ' . $onclick .'"><i class="' . htmlspecialchars($oAdmin_Form_Action->icon) . ' fa-fw btn-sm btn-' . htmlspecialchars($oAdmin_Form_Action->color) . '"></i>' . htmlspecialchars($text) . '</a></li>';

						$sActionsShortView .= '<a href="' . htmlspecialchars($href) . '" onclick="mainFormLocker.unlock(); ' . $onclick . '" class="btn-labeled btn btn-'. htmlspecialchars($oAdmin_Form_Action->color) . '"><i class="btn-label ' . htmlspecialchars($oAdmin_Form_Action->icon) . '"></i>' . htmlspecialchars($text) . '</a>';
					}
				}

				if ($iGroupCount > 1)
				{
					?><div class="visible-sm visible-xs">
						<div class="btn-group dropup">
							<a class="btn btn-palegreen dropdown-toggle" data-toggle="dropdown">
								<i class="fa fa-bars icon-separator"></i>
								<?php echo Core::_('Admin_Form.actions')?>
							</a>
							<ul class="dropdown-menu">
							<?php echo $sActionsFullView?>
							</ul>
						</div>
					</div><?php
				}
				?><div <?php echo $iGroupCount > 1 ? 'class="hidden-sm hidden-xs"' : ''?>>
					<?php echo $sActionsShortView?>
				</div>
			</div>
			<?php
			}
		//}

		return $this;
	}
}