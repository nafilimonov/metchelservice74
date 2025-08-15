<?php
/**
 * Administration center users.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'siteuser');

// Код формы
$iAdmin_Form_Id = 391;
$sAdminFormAction = '/{admin}/siteuser/accessdenied/index.php';
$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Siteuser_Accessdenied.title'))
	->pageTitle(Core::_('Siteuser_Accessdenied.title'));

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

$secret_csrf = Core_Security::getCsrfToken();
$additionalParams = "secret_csrf={$secret_csrf}";

$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser_Accessdenied.delete_all'))
		->icon('fa fa-times')
		->class('btn btn-danger')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'deleteAll', NULL, 0, 0, $additionalParams)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'deleteAll', NULL, 0, 0, $additionalParams)
		)
);

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Siteuser.siteusers'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/siteuser/index.php', NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/siteuser/index.php', NULL, NULL, '')
	)
)
->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Siteuser_Accessdenied.title'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath())
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath())
	)
);

// Добавляем все хлебные крошки контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

$oAdminFormActionDeleteAll = $oAdmin_Form
	->Admin_Form_Actions
	->getByName('deleteAll');

if ($oAdminFormActionDeleteAll && $oAdmin_Form_Controller->getAction() == 'deleteAll')
{
	$oSiteuser_Accessdenied_Delete_Controller = Admin_Form_Action_Controller::factory(
		'Siteuser_Accessdenied_Delete_Controller', $oAdminFormActionDeleteAll
	);

	$oAdmin_Form_Controller->addAction($oSiteuser_Accessdenied_Delete_Controller);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Siteuser_Accessdenied')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset($oAdmin_Form_Dataset);

// Показ формы
$oAdmin_Form_Controller->execute();