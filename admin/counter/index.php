<?php

/**
 * Counter.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../bootstrap.php');

Core_Auth::authorization($sModule = 'counter');

// Код формы
$iAdmin_Form_Id = 88;
$sAdminFormAction = '/{admin}/counter/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Counter.title'))
	->pageTitle(Core::_('Counter.title'));

$sFormPath = $oAdmin_Form_Controller->getPath();

// подключение верхнего меню
include CMS_FOLDER . Admin_Form_Controller::correctBackendPath('/{admin}/counter/menu.php');

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Строка навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Counter.title'))
		->href($oAdmin_Form_Controller->getAdminLoadHref($sFormPath, NULL, NULL, ''))
		->onclick($oAdmin_Form_Controller->getAdminLoadAjax($sFormPath, NULL, NULL, ''))
);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oController_Edit = Admin_Form_Action_Controller::factory(
		'Counter_Controller_Edit', $oAdmin_Form_Action
	);

	$oController_Edit->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oController_Edit);
}

// Источник данных
$oAdmin_Form_Controller->addDataset(new Counter_Dataset());

$oCounters = Core_Entity::factory('Site', CURRENT_SITE)->Counters;

$oCounters->queryBuilder()
	->where('date', '<=', Core_Date::timestamp2sql(time()))
	->where('date', '>=', Core_Date::timestamp2sql(strtotime('-3 months')));

//$aObjects = $oCounters->findAll();

if (count($oCounters->findAll()))
{
	$sWindowId = $oAdmin_Form_Controller->getWindowId();

	$oAdmin_Form_Controller->addEntity(
		Admin_Form_Entity::factory('Code')->html(Counter_Controller_Chart::show(6))
	);
}

$oAdmin_Form_Controller->execute();