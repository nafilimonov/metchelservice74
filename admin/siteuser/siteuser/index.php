<?php
/**
 * Site users.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'siteuser');

$iAdmin_Form_Id = 28;
$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Путь к контроллеру формы ЦА
$sAdminFormAction = '/{admin}/siteuser/siteuser/index.php';

// Путь к контроллеру предыдущей формы
$sSiteuserGroupPath = '/{admin}/siteuser/index.php';

// Идентификатор сайта
$iSiteuserGroupId = intval(Core_Array::getRequest('siteuser_group_id', 0));

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Siteuser_Group.title'))
	->pageTitle(Core::_('Siteuser_Group.title'));

$sSiteuserProperties = Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/property/index.php');
$sSiteuserTypes = Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/type/index.php');
$sSiteuserStatuses = Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/status/index.php');

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

// Элементы меню
$oAdmin_Form_Entity_Menus
->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Admin_Form.add'))
		->icon('fa fa-plus')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
		)
)
->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser.identity_providers_list'))
		->icon('fa fa-exchange')
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/siteuser/provider/index.php', NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/siteuser/provider/index.php', NULL, NULL, '')
		)
)->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser.relationship_types'))
		->icon('fa fa-coffee')
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/siteuser/relationship/type/index.php', NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/siteuser/relationship/type/index.php', NULL, NULL, '')
		)
)->add(
	Admin_Form_Entity::factory('Menu')
	->name(Core::_('Shop.affiliate_menu_title'))
	->icon('fa fa-money')
	->href(
		$oAdmin_Form_Controller->getAdminLoadHref($sAffiliatePlanFormPath = '/{admin}/affiliate/plan/index.php', NULL, NULL, '')
	)
	->onclick(
		$oAdmin_Form_Controller->getAdminLoadAjax($sAffiliatePlanFormPath, NULL, NULL, '')
	)
);

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Построение хлебных крошек
$oAdminFormEntityBreadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Первая хлебная крошка будет всегда
$oAdminFormEntityBreadcrumbs
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.siteusers'))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserGroupPath)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserGroupPath)
			))
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser_Group.title'))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath())
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath())
			)
		);

// Хлебные крошки добавляем контроллеру
$oAdmin_Form_Controller->addEntity($oAdminFormEntityBreadcrumbs);

// Действие редактирования
$oAdminFormAction = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdminFormAction && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oSiteuser_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Siteuser_Group_Controller_Edit', $oAdminFormAction
	);

	// Добавляем контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteuser_Controller_Edit);

	// Крошки при редактировании
	$oSiteuser_Controller_Edit->addEntity($oAdminFormEntityBreadcrumbs);
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

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Siteuser_Group')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

$oAdmin_Form_Dataset->addCondition(
	array('where' =>
		array('site_id', '=', CURRENT_SITE)
	)
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Показ формы
$oAdmin_Form_Controller->execute();
