<?php
/**
 * Shortlink
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../bootstrap.php');

Core_Auth::authorization($sModule = 'shortlink');

// Код формы
$iAdmin_Form_Id = 260;
$sAdminFormAction = '/{admin}/shortlink/index.php';

$oShortlink_Dir = Core_Entity::factory('Shortlink_Dir', Core_Array::getGet('shortlink_dir_id', 0));

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Shortlink.title'))
	->pageTitle(Core::_('Shortlink.title'));

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Shortlink.add_shortlink'))
		->icon('fa fa-plus')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 1, 0)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 1, 0)
		)
)->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Shortlink.add_shortlink_dir'))
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

// Глобальный поиск
$additionalParams = "shortlink_dir_id={$oShortlink_Dir->id}";

$sGlobalSearch = Core_Array::getGet('globalSearch', '', 'trim');

$oAdmin_Form_Controller->addEntity(
	Admin_Form_Entity::factory('Code')
		->html('
			<div class="row search-field margin-bottom-20">
				<div class="col-xs-12">
					<form action="' . $oAdmin_Form_Controller->getPath() . '" method="GET">
						<input type="text" name="globalSearch" class="form-control" placeholder="' . Core::_('Admin.placeholderGlobalSearch') . '" value="' . htmlspecialchars($sGlobalSearch) . '" />
						<i class="fa fa-times-circle no-margin" onclick="' . $oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), '', '', $additionalParams) . '"></i>
						<button type="submit" class="btn btn-default global-search-button" onclick="' . $oAdmin_Form_Controller->getAdminSendForm(array('action' => '', 'operation' => '', 'additionalParams' => $additionalParams)) . '"><i class="fa-solid fa-magnifying-glass fa-fw"></i></button>
					</form>
				</div>
			</div>
		')
);

$sGlobalSearch = str_replace(' ', '%', Core_DataBase::instance()->escapeLike($sGlobalSearch));

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Shortlink.menu'))
		->href($oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, ''))
		->onclick($oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, ''))
);

// Крошки по группам
if ($oShortlink_Dir->id)
{
	$oShortlink_Dir_Breadcrumbs = $oShortlink_Dir;

	$aBreadcrumbs = array();

	do
	{
		$aBreadcrumbs[] = Admin_Form_Entity::factory('Breadcrumb')
			->name($oShortlink_Dir_Breadcrumbs->name)
			->href($oAdmin_Form_Controller->getAdminLoadHref('/{admin}/shortlink/index.php', NULL, NULL, "shortlink_dir_id={$oShortlink_Dir_Breadcrumbs->id}"))
			->onclick($oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/shortlink/index.php', NULL, NULL, "shortlink_dir_id={$oShortlink_Dir_Breadcrumbs->id}"));
	}
	while ($oShortlink_Dir_Breadcrumbs = $oShortlink_Dir_Breadcrumbs->getParent());

	$aBreadcrumbs = array_reverse($aBreadcrumbs);

	foreach ($aBreadcrumbs as $oBreadcrumb)
	{
		$oAdmin_Form_Entity_Breadcrumbs->add($oBreadcrumb);
	}
}

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form
	->Admin_Form_Actions
	->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oShortlink_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Shortlink_Controller_Edit', $oAdmin_Form_Action
	);

	$oShortlink_Controller_Edit->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oShortlink_Controller_Edit);
}

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form
	->Admin_Form_Actions
	->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oControllerApply);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(Core_Entity::factory('Shortlink_Dir'));
$oAdmin_Form_Dataset->changeField('name', 'class', 'semi-bold');
$oAdmin_Form_Dataset
	->addCondition(array('select' => array('shortlink_dirs.*', array('shortlink_dirs.name', 'source'))))
	->addCondition(array('where' => array('shortlink_dirs.site_id', '=', CURRENT_SITE)));

if (strlen($sGlobalSearch))
{
	$oAdmin_Form_Dataset
		->addCondition(array('open' => array()))
			->addCondition(array('where' => array('shortlink_dirs.id', '=', is_numeric($sGlobalSearch) ? intval($sGlobalSearch) : 0)))
			->addCondition(array('setOr' => array()))
			->addCondition(array('where' => array('shortlink_dirs.name', 'LIKE', '%' . $sGlobalSearch . '%')))
		->addCondition(array('close' => array()));
}
else
{
	$oAdmin_Form_Dataset
		->addCondition(array('where' => array('shortlink_dirs.parent_id', '=', $oShortlink_Dir->id)));
}

$oAdmin_Form_Controller->addDataset($oAdmin_Form_Dataset);

// Источник данных 1
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Shortlink')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

// Ограничение источника 1 по родительской группе
$oAdmin_Form_Dataset
	->addCondition(array('where' => array('shortlinks.site_id', '=', CURRENT_SITE)));

if (strlen($sGlobalSearch))
{
	$oAdmin_Form_Dataset
		->addCondition(array('open' => array()))
		->addCondition(array('where' => array('shortlinks.id', '=', is_numeric($sGlobalSearch) ? intval($sGlobalSearch) : 0)))
		->addCondition(array('setOr' => array()))
		->addCondition(array('where' => array('shortlinks.source', 'LIKE', '%' . $sGlobalSearch . '%')))
		->addCondition(array('setOr' => array()))
		->addCondition(array('where' => array('shortlinks.shortlink', 'LIKE', '%' . $sGlobalSearch . '%')))
		->addCondition(array('close' => array()));
}
else
{
	$oAdmin_Form_Dataset
		->addCondition(array('where' => array('shortlinks.shortlink_dir_id', '=', $oShortlink_Dir->id)));
}

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset($oAdmin_Form_Dataset);

$oAdmin_Form_Controller->addExternalReplace('{shortlink_dir_id}', $oShortlink_Dir->id);

// Показ формы
$oAdmin_Form_Controller->execute();