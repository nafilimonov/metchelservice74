<?php
/**
 * Administration center users.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'siteuser');

// Код формы
$iAdmin_Form_Id = 381;
$sAdminFormAction = '/{admin}/siteuser/session/index.php';
$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Siteuser_Session.title'))
	->pageTitle(Core::_('Siteuser_Session.title'));

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

$secret_csrf = Core_Security::getCsrfToken();
$additionalParams = "secret_csrf={$secret_csrf}";

$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser_Session.destroy_all'))
		->icon('fa fa-times')
		->class('btn btn-danger')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'destroyAll', NULL, 0, 0, $additionalParams)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'destroyAll', NULL, 0, 0, $additionalParams)
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
		->name(Core::_('Siteuser_Session.title'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath())
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath())
	)
);

// Добавляем все хлебные крошки контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

$oAdminFormActionDestroyAll = $oAdmin_Form
	->Admin_Form_Actions
	->getByName('destroyAll');

if ($oAdminFormActionDestroyAll && $oAdmin_Form_Controller->getAction() == 'destroyAll')
{
	$oSiteuser_Session_Destroy_Controller = Admin_Form_Action_Controller::factory(
		'Siteuser_Session_Destroy_Controller', $oAdminFormActionDestroyAll
	);

	$oAdmin_Form_Controller->addAction($oSiteuser_Session_Destroy_Controller);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Siteuser_Session')
);

$oAdmin_Form_Dataset
	->addCondition(
		array('select' => array('siteuser_sessions.*', array('sessions.id', 'dataSession')))
	)
	->addCondition(
		array('leftJoin' => array('sessions', 'sessions.id', '=', 'siteuser_sessions.id'))
	);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

$oAdmin_Form_Controller->addFilter('siteuser_id', array($oAdmin_Form_Controller, '_filterCallbackSiteuser'));

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset($oAdmin_Form_Dataset);

// Показ формы
$oAdmin_Form_Controller->execute();