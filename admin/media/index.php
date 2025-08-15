<?php
/**
 * Media.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../bootstrap.php');

Core_Auth::authorization($sModule = 'media');

// Код формы
$iAdmin_Form_Id = 373;
$sAdminFormAction = '/{admin}/media/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$oMedia_Group = Core_Entity::factory('Media_Group', Core_Array::getGet('media_group_id', 0));

$bAddFile = Core_Array::getGet('entity_id', 0, 'int');
$type = Core_Array::getGet('entity_type', '', 'trim');

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Media.title'))
	->pageTitle(Core::_('Media.title'))
	->addView('media', 'Media_Controller_View')
	->view('media');

if ($bAddFile)
{
	$oAdmin_Form_Controller->Admin_View(
		Admin_View::getClassName('Admin_Internal_View')
	);
}

$additionalParams = "media_group_id={$oMedia_Group->id}";

$additionalParamsBr = '';

if ($bAddFile)
{
	$additionalParams .= "&showMediaModal=1&entity_id={$bAddFile}&entity_type={$type}";

	$parentWindowId = Core_Array::getGet('parentWindowId', '', 'trim');
	$parentWindowId !== ''
		&& $additionalParamsBr = $additionalParams .= "&parentWindowId={$parentWindowId}";

	$modalWindowId = Core_Array::getGet('modalWindowId', '', 'trim');
	$modalWindowId !== ''
		&& $additionalParamsBr = $additionalParams .= "&modalWindowId={$modalWindowId}";
}

if (is_null(Core_Array::getGet('showMediaModal')) && !is_null(Core_Array::getGet('entity_id')))
{
	ob_start();

	$type = Core_Array::getGet('type', '', 'trim');
	$entity_id = Core_Array::getGet('entity_id', 0, 'int');
	$dataset_id = Core_Array::getGet('dataset_id', 0, 'int');

	if ($entity_id && $type != '')
	{
		$oEntity = Core_Entity::factory($type)->getById($entity_id, FALSE);
		if (!is_null($oEntity))
		{
			$oMediaTab = Admin_Form_Entity::factory('Tab')
				->caption(Core::_("Admin_Form.tabMedia"))
				->name('Media');

			Media_Controller_Tab::factory($oAdmin_Form_Controller)
				->setObject($oEntity)
				->setDatasetId($dataset_id)
				->setTab($oMediaTab)
				->fillTab(TRUE);
		}
	}

	Core::showJson(
		array('error' => '', 'form_html' => ob_get_clean())
	);
}

if (!is_null(Core_Array::getPost('add_media_file')))
{
	$aJson = array(
		'status' => 'error'
	);

	$media_item_id = Core_Array::getPost('id', 0, 'int');
	$type = Core_Array::getPost('type', '', 'trim');
	$entity_id = Core_Array::getPost('entity_id', 0, 'int');

	if ($media_item_id && $entity_id && $type != '')
	{
		$oEntities = Core_Entity::factory('Media_' . $type);
		$oEntities->queryBuilder()
			->where('media_' . $type . 's.media_item_id', '=', $media_item_id)
			->where('media_' . $type . 's.' . $type . '_id', '=', $entity_id);

		if (!$oEntities->getCount(FALSE))
		{
			$oEntities = Core_Entity::factory('Media_' . $type);
			$oEntities->queryBuilder()
				->where('media_' . $type . 's.' . $type . '_id', '=', $entity_id)
				->clearOrderBy()
				->orderBy('sorting', 'DESC')
				->limit(1);

			$aLast = $oEntities->findAll(FALSE);

			$prop = $type . '_id';

			$oEntity = Core_Entity::factory('Media_' . $type);
			$oEntity->media_item_id = $media_item_id;
			$oEntity->$prop = $entity_id;
			$oEntity->sorting = isset($aLast[0]) ? $aLast[0]->sorting + 1 : 0;
			$oEntity->save();

			$aJson['status'] = 'success';
		}
	}

	Core::showJson($aJson);
}

if (!is_null(Core_Array::getPost('remove_media_file')))
{
	$aJson = array(
		'status' => 'error'
	);

	$media_item_id = Core_Array::getPost('id', 0, 'int');
	$type = Core_Array::getPost('type', '', 'trim');
	$entity_id = Core_Array::getPost('entity_id', 0, 'int');

	if ($media_item_id && $entity_id && $type != '')
	{
		$oEntities = Core_Entity::factory('Media_' . $type);
		$oEntities->queryBuilder()
			->where('media_' . $type . 's.media_item_id', '=', $media_item_id)
			->where('media_' . $type . 's.' . $type . '_id', '=', $entity_id);

		$oEntity = $oEntities->getLast(FALSE);

		if (!is_null($oEntity))
		{
			$oEntity->delete();

			$aJson['status'] = 'success';
		}
	}

	Core::showJson($aJson);
}

if (!is_null(Core_Array::getPost('remove_original_media_file')))
{
	$aJson = array(
		'status' => 'error'
	);

	$id = Core_Array::getPost('id', 0, 'int');

	$oMedia_Item = Core_Entity::factory('Media_Item')->getById($id, FALSE);

	if (!is_null($oMedia_Item))
	{
		$oMedia_Item->markDeleted();

		$aJson['status'] = 'success';
	}

	Core::showJson($aJson);
}

if (!is_null(Core_Array::getPost('refresh_sorting_media_file')))
{
	$aJson = array(
		'status' => 'error'
	);

	$modelName = Core_Array::getPost('modelName', '', 'trim');

	if ($modelName != '')
	{
		$aData = array();

		$aInputs = Core_Array::getPost('inputs', array(), 'array');
		foreach ($aInputs as $aInput)
		{
			$aData[$aInput['name']] = $aInput['value'];
		}

		Media_Controller_Tab::factory($oAdmin_Form_Controller)
			->setSorting($modelName, $aData);

		$aJson['status'] = 'success';
	}

	Core::showJson($aJson);
}

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

$onclick = $oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 1, 0);
$onclick = preg_replace('/parentWindowId=/iu', 'tmpMediaSource=', $onclick);

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Media.add_file'))
		->icon('fa fa-plus')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 1, 0)
		)
		->onclick($onclick)
);

$groupOnclick = $oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0);
$groupOnclick = preg_replace('/parentWindowId=/iu', 'tmpMediaSource=', $groupOnclick);

$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Media.add_group'))
		->icon('fa fa-plus')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
		)
		->onclick($groupOnclick)
);

!$bAddFile && $oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Media.formats'))
		->icon('fa-solid fa-icons')
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/media/format/index.php', NULL, NULL)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/media/format/index.php', NULL, NULL)
		)
);

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Глобальный поиск
$sGlobalSearch = Core_Array::getGet('globalSearch', '', 'trim');

$oAdmin_Form_Controller->addEntity(
	Admin_Form_Entity::factory('Code')
		->html('
			<div class="row search-field margin-bottom-20">
				<div class="col-xs-12">
					<form class="form-inline" action="' . $oAdmin_Form_Controller->getPath() . '" method="GET">
						<div class="row">
							<div class="col-xs-12">
								<input type="text" name="globalSearch" class="form-control w-100" placeholder="' . Core::_('Admin.placeholderGlobalSearch') . '" value="' . htmlspecialchars($sGlobalSearch) . '" />
								<i class="fa fa-times-circle no-margin" onclick="' . $oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), '', '', $additionalParams) . '"></i>
								<button type="submit" class="btn btn-default global-search-button" onclick="' . $oAdmin_Form_Controller->getAdminSendForm('', '', $additionalParams) . '"><i class="fa-solid fa-magnifying-glass fa-fw"></i></button>
							</div>
						</div>
					</form>
				</div>
			</div>
		')
);

$sGlobalSearch = str_replace(' ', '%', Core_DataBase::instance()->escapeLike($sGlobalSearch));

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Media.title'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, ($bAddFile ? '&showMediaModal=1&entity_id=' . $bAddFile . $additionalParamsBr : ''))
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, ($bAddFile ? '&showMediaModal=1&entity_id=' . $bAddFile . $additionalParamsBr : ''))
		)
);

// Крошки по группам
if ($oMedia_Group->id)
{
	$oMediaGroupBreadcrumbs = $oMedia_Group;

	$aBreadcrumbs = array();

	do
	{
		$name = $oMediaGroupBreadcrumbs->name != ''
			? $oMediaGroupBreadcrumbs->name
			: Core::_('Admin.no_title');

		$aBreadcrumbs[] = Admin_Form_Entity::factory('Breadcrumb')
			->name($name)
			->href($oAdmin_Form_Controller->getAdminLoadHref
			(
				'/{admin}/media/index.php', NULL, NULL, "media_group_id={$oMediaGroupBreadcrumbs->id}" . ($bAddFile ? '&showMediaModal=1&entity_id=' . $bAddFile .$additionalParamsBr : '')
			))
			->onclick($oAdmin_Form_Controller->getAdminLoadAjax
			(
				'/{admin}/media/index.php', NULL, NULL, "media_group_id={$oMediaGroupBreadcrumbs->id}" . ($bAddFile ? '&showMediaModal=1&entity_id=' . $bAddFile .$additionalParamsBr : '')
			));
	} while ($oMediaGroupBreadcrumbs = $oMediaGroupBreadcrumbs->getParent());

	$aBreadcrumbs = array_reverse($aBreadcrumbs);

	foreach ($aBreadcrumbs as $oBreadcrumb)
	{
		$oAdmin_Form_Entity_Breadcrumbs->add($oBreadcrumb);
	}
}

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oMedia_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Media_Controller_Edit', $oAdmin_Form_Action
	);

	$oMedia_Controller_Edit
		->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oMedia_Controller_Edit);
}

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oAdmin_Form_Action_Controller_Type_Apply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oAdmin_Form_Action_Controller_Type_Apply);
}

// Действие добавить
$oAdminFormActionAdd = $oAdmin_Form->Admin_Form_Actions->getByName('add');

if ($oAdminFormActionAdd && $oAdmin_Form_Controller->getAction() == 'add')
{
	$oMediaControllerAdd = Admin_Form_Action_Controller::factory(
		'Media_Controller_Add', $oAdminFormActionAdd
	);

	$oMediaControllerAdd->get($_GET);

	$oAdmin_Form_Controller->addAction($oMediaControllerAdd);
}

$oAdminFormActionUploadFiles = $oAdmin_Form->Admin_Form_Actions->getByName('uploadFiles');

if ($oAdminFormActionUploadFiles && $oAdmin_Form_Controller->getAction() == 'uploadFiles')
{
	$oMedia_Controller_Upload = Admin_Form_Action_Controller::factory(
		'Media_Controller_Upload', $oAdminFormActionUploadFiles
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oMedia_Controller_Upload);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Media_Group')
);

// Ограничение источника 0 по родительской группе
$oAdmin_Form_Dataset->addCondition(
	array('where' =>
		array('media_groups.site_id', '=', CURRENT_SITE)
	)
);

if (strlen($sGlobalSearch))
{
	$oAdmin_Form_Dataset
		->addCondition(array('open' => array()))
			->addCondition(array('where' => array('media_groups.id', '=', is_numeric($sGlobalSearch) ? intval($sGlobalSearch) : 0)))
			->addCondition(array('setOr' => array()))
			->addCondition(array('where' => array('media_groups.name', 'LIKE', '%' . $sGlobalSearch . '%')))
		->addCondition(array('close' => array()));
}
else
{
	$oAdmin_Form_Dataset->addCondition(array('where' => array('media_groups.parent_id', '=', $oMedia_Group->id)));
}

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Источник данных 1
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Media_Item')
);

$oAdmin_Form_Dataset->addCondition(
	array('where' =>
		array('media_items.site_id', '=', CURRENT_SITE)
	)
);

if (strlen($sGlobalSearch))
{
	$oAdmin_Form_Dataset
		->addCondition(array('open' => array()))
			->addCondition(array('where' => array('media_items.id', '=', is_numeric($sGlobalSearch) ? intval($sGlobalSearch) : 0)))
			->addCondition(array('setOr' => array()))
			->addCondition(array('where' => array('media_items.name', 'LIKE', '%' . $sGlobalSearch . '%')))
			->addCondition(array('setOr' => array()))
			->addCondition(array('where' => array('media_items.caption', 'LIKE', '%' . $sGlobalSearch . '%')))
			->addCondition(array('setOr' => array()))
			->addCondition(array('where' => array('media_items.alt', 'LIKE', '%' . $sGlobalSearch . '%')))
		->addCondition(array('close' => array()));
}
else
{
	$oAdmin_Form_Dataset->addCondition(array('where' => array('media_items.media_group_id', '=', $oMedia_Group->id)));
}

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$oAdmin_Form_Controller->addExternalReplace('{media_group_id}', $oMedia_Group->id);

if (!$bAddFile)
{
	$oAdmin_Form_Controller->deleteAdminFormFieldById(2212);
	$oAdmin_Form_Controller->deleteAdminFormActionById(1527);
}

// Показ формы
$oAdmin_Form_Controller->execute();