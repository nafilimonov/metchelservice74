<?php
/**
 * Online cloud service.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../bootstrap.php');

Core_Auth::authorization($sModule = 'cloud');

// Код формы
$iAdmin_Form_Id = 194;

$sAdminFormAction = '/{admin}/cloud/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('cloud.menu'))
	->pageTitle(Core::_('cloud.menu'));

$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Admin_Form.add'))
		->icon('fa fa-plus')
		->href($oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0))
		->onclick($oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0))
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oCloudControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oCloudControllerApply);
}

$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');
	$oAdmin_Form_Entity_Breadcrumbs->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('cloud.menu'))
			->href($oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, ''))
			->onclick($oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, ''))
	);

	$Cloud_Controller_Edit = Admin_Form_Action_Controller::factory('Cloud_Controller_Edit', $oAdmin_Form_Action);
	$Cloud_Controller_Edit->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	$oAdmin_Form_Controller->addAction($Cloud_Controller_Edit);
}

$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('loadOAuthCode');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'loadOAuthCode')
{
	$oCloudControllerLoadkey = Admin_Form_Action_Controller::factory('Cloud_Controller_Loadkey', $oAdmin_Form_Action);
	$oAdmin_Form_Controller->addAction($oCloudControllerLoadkey);
}

$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('loadOAuthAccessToken');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'loadOAuthAccessToken')
{
	$oCloudControllerLoadaccesstoken = Admin_Form_Action_Controller::factory('Cloud_Controller_Loadaccesstoken', $oAdmin_Form_Action);
	$oAdmin_Form_Controller->addAction($oCloudControllerLoadaccesstoken);
}

$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Cloud')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

// Ограничение источника 1 по родительской группе
$oAdmin_Form_Dataset->addCondition(
	array('where' =>
		array('site_id', '=', CURRENT_SITE)
	)
)
->changeField('active', 'list', "1=" . Core::_('Admin_Form.yes') . "\n" . "0=" . Core::_('Admin_Form.no'));

$oAdmin_Form_Controller->addDataset($oAdmin_Form_Dataset);

$oAdmin_Form_Controller->execute();