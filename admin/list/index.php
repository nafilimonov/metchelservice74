<?php
/**
 * Lists.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../bootstrap.php');

Core_Auth::authorization($sModule = 'list');

// Код формы
$iAdmin_Form_Id = 20;
$sAdminFormAction = '/{admin}/list/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$oList_Dir = Core_Entity::factory('List_Dir', Core_Array::getGet('list_dir_id', 0, 'int'));

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('List.title'))
	->pageTitle(Core::_('List.title'));

if (!is_null(Core_Array::getGet('autocomplete'))
	&& !is_null(Core_Array::getGet('show_dir'))
	&& !is_null(Core_Array::getGet('queryString'))
)
{
	$sQuery = trim(Core_DataBase::instance()->escapeLike(Core_Str::stripTags(strval(Core_Array::getGet('queryString')))));
	$iSiteId = Core_Array::getGet('site_id', 0, 'int');
	$oSite = Core_Entity::factory('Site', $iSiteId);

	$aJSON[0] = array(
		'id' => 0,
		'label' => Core::_('List_Dir.root'),
	);

	if (strlen($sQuery))
	{
		$sQueryLike = '%' . str_replace(' ', '%', $sQuery) . '%';

		$oList_Dirs = $oSite->List_Dirs;
		$oList_Dirs->queryBuilder()
			->where('list_dirs.name', 'LIKE', $sQueryLike)
			->limit(Core::$mainConfig['autocompleteItems']);

		$aList_Dirs = $oList_Dirs->findAll(FALSE);

		foreach ($aList_Dirs as $oList_Dir)
		{
			$aParentDirs = array();

			$aTmpDir = $oList_Dir;

			// Добавляем все директории от текущей до родителя.
			do {
				$aParentDirs[] = $aTmpDir->name;
			} while ($aTmpDir = $aTmpDir->getParent());

			$sParents = implode(' → ', array_reverse($aParentDirs));

			$aJSON[] = array(
				'id' => $oList_Dir->id,
				'label' => $sParents
			);
		}
	}

	Core::showJson($aJSON);
}

if (!is_null(Core_Array::getGet('autocomplete'))
	&& !is_null(Core_Array::getGet('show_list'))
	&& !is_null(Core_Array::getGet('queryString'))
)
{
	$sQuery = trim(Core_DataBase::instance()->escapeLike(Core_Str::stripTags(strval(Core_Array::getGet('queryString')))));
	$iSiteId = Core_Array::getGet('site_id', 0, 'int');
	$oSite = Core_Entity::factory('Site', $iSiteId);

	$aJSON = array();

	if (strlen($sQuery))
	{
		$sQueryLike = '%' . str_replace(' ', '%', $sQuery) . '%';

		$oLists = $oSite->Lists;
		$oLists->queryBuilder()
			->where('lists.name', 'LIKE', $sQueryLike)
			->limit(Core::$mainConfig['autocompleteItems']);

		$aLists = $oLists->findAll(FALSE);

		foreach ($aLists as $oList)
		{
			$aJSON[] = array(
				'id' => $oList->id,
				'label' => $oList->name
			);
		}
	}

	Core::showJson($aJSON);
}

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('List.main_menu'))
		->icon('fa fa-plus')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 1, 0)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 1, 0)
		)
)
->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('List_Dir.menu'))
		->icon('fa fa-plus')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
		)
);

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Строка навигации
$list_dir_id = Core_Array::getGet('list_dir_id', 0, 'int');

// Глобальный поиск
$additionalParams = "list_dir_id={$list_dir_id}";

$sGlobalSearch = Core_Array::getGet('globalSearch', '', 'trim');

$oAdmin_Form_Controller->addEntity(
	Admin_Form_Entity::factory('Code')
		->html('
			<div class="row search-field margin-bottom-20">
				<div class="col-xs-12">
					<form action="' . $oAdmin_Form_Controller->getPath() . '" method="GET">
						<input type="text" name="globalSearch" class="form-control" placeholder="' . Core::_('Admin.placeholderGlobalSearch') . '" value="' . htmlspecialchars($sGlobalSearch) . '" />
						<i class="fa fa-times-circle no-margin" onclick="' . $oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), '', '', $additionalParams) . '"></i>
						<button type="submit" class="btn btn-default global-search-button" onclick="' . $oAdmin_Form_Controller->getAdminSendForm('', '', $additionalParams) . '"><i class="fa-solid fa-magnifying-glass fa-fw"></i></button>
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
		->name(Core::_('List.title'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, '')
	)
);

if ($list_dir_id)
{
	// Если передана родительская группа - строим хлебные крошки
	$oListDir = Core_Entity::factory('List_Dir')->find($list_dir_id);

	if (!is_null($oListDir->id))
	{
		$aBreadcrumbs = array();

		do
		{
			$additionalParams = 'list_dir_id=' . intval($oListDir->id);

			$aBreadcrumbs[] = Admin_Form_Entity::factory('Breadcrumb')
				->name($oListDir->name)
				->href(
					$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParams)
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParams)
				);
		} while ($oListDir = $oListDir->getParent());

		$aBreadcrumbs = array_reverse($aBreadcrumbs);

		foreach ($aBreadcrumbs as $oAdmin_Form_Entity_Breadcrumb)
		{
			$oAdmin_Form_Entity_Breadcrumbs->add(
				$oAdmin_Form_Entity_Breadcrumb
			);
		}

		// Добавляем все хлебные крошки контроллеру
		$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);
	}
}

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oList_Controller_Edit = Admin_Form_Action_Controller::factory(
		'List_Controller_Edit', $oAdmin_Form_Action
	);

	$oList_Controller_Edit
		->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oList_Controller_Edit);
}

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oControllerApply);
}

// Действие "Копировать"
$oAdminFormActionCopy = $oAdmin_Form->Admin_Form_Actions->getByName('copy');

if ($oAdminFormActionCopy && $oAdmin_Form_Controller->getAction() == 'copy')
{
	$oControllerCopy = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Copy', $oAdminFormActionCopy
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oControllerCopy);
}

// Действие "Объединить"
$oAdminFormActionMerge = $oAdmin_Form->Admin_Form_Actions->getByName('merge');

if ($oAdminFormActionMerge && $oAdmin_Form_Controller->getAction() == 'merge')
{
	$oAdmin_Form_Action_Controller_Type_Merge = new Admin_Form_Action_Controller_Type_Merge($oAdminFormActionMerge);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oAdmin_Form_Action_Controller_Type_Merge);
}

// Действие "Перенести"
$oAdminFormActionMove = $oAdmin_Form->Admin_Form_Actions->getByName('move');

if ($oAdminFormActionMove && $oAdmin_Form_Controller->getAction() == 'move')
{
	$Admin_Form_Action_Controller_Type_Move = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Move', $oAdminFormActionMove
	);

	$oSite = Core_Entity::factory('Site', CURRENT_SITE);

	$Admin_Form_Action_Controller_Type_Move
		->title(Core::_('List.move_items_groups_title'))
		->selectCaption(Core::_('List.move_items_groups_list_dir_id'))
		->value($oList_Dir->id)
		->autocompletePath(Admin_Form_Controller::correctBackendPath('/{admin}/list/index.php?autocomplete=1&show_dir=1&site_id=') . $oSite->id)
		->autocompleteEntityId($oSite->id);

	$iCount = $oSite->List_Dirs->getCount();

	if ($iCount < Core::$mainConfig['switchSelectToAutocomplete'])
	{
		$aExclude = array();

		$aChecked = $oAdmin_Form_Controller->getChecked();

		foreach ($aChecked as $datasetKey => $checkedItems)
		{
			// Exclude just dirs
			if ($datasetKey == 0)
			{
				foreach ($checkedItems as $key => $value)
				{
					$aExclude[] = $key;
				}
			}
		}

		$List_Controller_Edit = new List_Controller_Edit($oAdminFormActionMove);

		$Admin_Form_Action_Controller_Type_Move
			// Список директорий генерируется другим контроллером
			->selectOptions(array(' … ') + $List_Controller_Edit->fillListDir(0, $aExclude));
	}
	else
	{
		$Admin_Form_Action_Controller_Type_Move->autocomplete(TRUE);
	}

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($Admin_Form_Action_Controller_Type_Move);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('List_Dir')
);
$oAdmin_Form_Dataset->changeField('name', 'class', 'semi-bold');

// Ограничение источника 0
$oAdmin_Form_Dataset
	->addCondition(array('where' => array('site_id', '=', CURRENT_SITE)));

if (strlen($sGlobalSearch))
{
	$oAdmin_Form_Dataset
		->addCondition(array('open' => array()))
			->addCondition(array('where' => array('list_dirs.id', '=', is_numeric($sGlobalSearch) ? intval($sGlobalSearch) : 0)))
			->addCondition(array('setOr' => array()))
			->addCondition(array('where' => array('list_dirs.name', 'LIKE', '%' . $sGlobalSearch . '%')))
		->addCondition(array('close' => array()));
}
else
{
	$oAdmin_Form_Dataset
		->addCondition(array('where' => array('parent_id', '=', $list_dir_id)));
}

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Источник данных 1
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('List')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

// Ограничение источника 1
$oAdmin_Form_Dataset
	->addCondition(array('where' => array('site_id', '=', CURRENT_SITE)));

if (strlen($sGlobalSearch))
{
	$oAdmin_Form_Dataset
		->addCondition(array('open' => array()))
			->addCondition(array('where' => array('lists.id', '=', is_numeric($sGlobalSearch) ? intval($sGlobalSearch) : 0)))
			->addCondition(array('setOr' => array()))
			->addCondition(array('where' => array('lists.name', 'LIKE', '%' . $sGlobalSearch . '%')))
		->addCondition(array('close' => array()));
}
else
{
	$oAdmin_Form_Dataset->addCondition(
		array('where' => array('list_dir_id', '=', $list_dir_id))
	);
}

$oAdmin_Form_Dataset->changeField('name', 'type', 1);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Показ формы
$oAdmin_Form_Controller->execute();
