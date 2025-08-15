<?php
/**
 * Shortlink.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'shortlink');

// Код формы
$iAdmin_Form_Id = 349;
$sAdminFormAction = '/{admin}/shortlink/stat/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$oShortlink = Core_Entity::factory('Shortlink', Core_Array::getGet('shortlink_id', 0, 'int'));

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Shortlink_Stat.title', $oShortlink->shortlink))
	->pageTitle(Core::_('Shortlink_Stat.title', $oShortlink->shortlink));

// Хлебные крошки
$oBreadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

$oBreadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Shortlink.title'))
		->href($oAdmin_Form_Controller->getAdminLoadHref('/{admin}/shortlink/index.php', NULL, NULL, ''))
		->onclick($oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/shortlink/index.php', NULL, NULL, ''))
);

$oBreadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Shortlink_Stat.title', $oShortlink->shortlink, FALSE))
		->href($oAdmin_Form_Controller->getAdminLoadHref(
			$oAdmin_Form_Controller->getPath(), NULL, NULL, "shortlink_id={$oShortlink->id}"
		))
		->onclick($oAdmin_Form_Controller->getAdminLoadAjax(
			$oAdmin_Form_Controller->getPath(), NULL, NULL, "shortlink_id={$oShortlink->id}"
		))
);

$oAdmin_Form_Controller->addEntity($oBreadcrumbs);

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

$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Shortlink_Stat')
);

$oAdmin_Form_Dataset
	->addCondition(array('where' => array('shortlink_stats.shortlink_id', '=', $oShortlink->id)));

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Показ формы
$oAdmin_Form_Controller->execute();
