<?php
/**
 * Search.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'search');

// Код формы
$iAdmin_Form_Id = 103;
$sAdminFormAction = '/{admin}/search/log/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Search_Log.title'))
	->pageTitle(Core::_('Search_Log.title'));

$sSearchPath = '/{admin}/search/index.php';

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Search.title'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($sSearchPath, NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($sSearchPath, NULL, NULL, '')
	)
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

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
	Core_Entity::factory('Search_Log')
);

// Ограничение источника 0 по родительской группе
$oAdmin_Form_Dataset->addCondition(
	array('select' =>
		array('search_logs.*', array('COUNT(*)', 'count'))
	)
)->addCondition(
	array('where' =>
		array('site_id', '=', CURRENT_SITE)
	)
)
->addCondition(
	array('groupBy' => array('query') )
)
->addExternalField('count');

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

if (!isset($oAdmin_Form_Controller->request['admin_form_filter_from_437']))
{
	$oAdmin_Form_Controller->request['admin_form_filter_from_437'] = Core_Date::timestamp2date(strtotime('-1 day'));
}
if (!isset($oAdmin_Form_Controller->request['admin_form_filter_to_437']))
{
	$oAdmin_Form_Controller->request['admin_form_filter_to_437'] = Core_Date::timestamp2date(time());
}

// Показ формы
$oAdmin_Form_Controller->execute();
