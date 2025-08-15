<?php
/**
 * Support
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../bootstrap.php');

Core_Auth::authorization($sModule = 'support');

// Код формы
$iAdmin_Form_Id = 383;
$sAdminFormAction = '/{admin}/support/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Support.title'))
	->pageTitle(Core::_('Support.title'));

$oSupport_Controller = Support_Controller::instance();
$oSupport_Controller->setSupportOptions();

$aReturn = $oSupport_Controller->getSupport();

$sDatetime = isset($aReturn['datetime']) && !is_null($aReturn['datetime'])
	? Core_Date::strftime(DATE_TIME_FORMAT, strtotime($aReturn['datetime']))
	: '';

if (isset($aReturn['error']))
{
	if (is_numeric($aReturn['error']))
	{
		if ($aReturn['error'] > 0 && $aReturn['error'] < 4 || $aReturn['error'] == 5)
		{
			$oAdmin_Form_Controller->addMessage(
				Core_Message::get(Core::_('Update.server_error_respond_' . $aReturn['error'], $sDatetime), 'error')
			);
		}
	}
	else
	{
		$oAdmin_Form_Controller->addMessage(Core_Message::get($aReturn['error'], 'error'));
	}
}

if (isset($aReturn['expiration_of_support'])
	&& $aReturn['expiration_of_support'] !== FALSE
	&& Core_Date::sql2timestamp($aReturn['expiration_of_support']) > time()
)
{
	// Меню формы
	$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

	// Элементы меню
	$oAdmin_Form_Entity_Menus->add(
		Admin_Form_Entity::factory('Menu')
			->name(Core::_('Support.add_ticket'))
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
}

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Support.menu'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, '')
		)
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form
	->Admin_Form_Actions
	->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oSupport_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Support_Controller_Edit', $oAdmin_Form_Action
	);

	$oSupport_Controller_Edit->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSupport_Controller_Edit);
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
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Support')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset($oAdmin_Form_Dataset);

// Показ формы
$oAdmin_Form_Controller->execute();