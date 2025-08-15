<?php

/**
 * Counter.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'counter');

// Код формы
$iAdmin_Form_Id = 122;
$sAdminFormAction = '/{admin}/counter/crawler/index.php';

$sCounterPath = '/{admin}/counter/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$sTitle = Core::_('Counter.crawler_pages');

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title($sTitle)
	->pageTitle($sTitle);

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
		->href($oAdmin_Form_Controller->getAdminLoadHref($sCounterPath, NULL, NULL, ''))
		->onclick($oAdmin_Form_Controller->getAdminLoadAjax($sCounterPath, NULL, NULL, ''))
)
->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name($sTitle)
		->href($oAdmin_Form_Controller->getAdminLoadHref($sFormPath, NULL, NULL, ''))
		->onclick($oAdmin_Form_Controller->getAdminLoadAjax($sFormPath, NULL, NULL, ''))
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Источник данных
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Counter_Visit')
);

(!isset($oAdmin_Form_Controller->request['admin_form_filter_from_545'])
	|| $oAdmin_Form_Controller->request['admin_form_filter_from_545'] == '')
		&& $oAdmin_Form_Controller->request['admin_form_filter_from_545'] = Core_Date::timestamp2date(time()) . ' 00:00:00';

(!isset($oAdmin_Form_Controller->request['admin_form_filter_to_545'])
	|| $oAdmin_Form_Controller->request['admin_form_filter_to_545'] == '')
		&& $oAdmin_Form_Controller->request['admin_form_filter_to_545'] = Core_Date::timestamp2date(time()) . ' 23:59:59';

// Change filter date to single mode
//$oAdmin_Form_Controller->addFilter('datetime', array($oAdmin_Form_Controller, '_filterCallbackDateSingle'));

$oAdmin_Form_Dataset->addCondition(
	array('select' => array('counter_visits.*', 'counter_useragents.useragent', 'counter_pages.page'))
)
->addCondition(
	array('join' => array('counter_sessions', 'counter_sessions.id', '=', 'counter_visits.counter_session_id'))
)
->addCondition(
	array('join' => array('counter_useragents', 'counter_useragents.id', '=', 'counter_sessions.counter_useragent_id',
		array(
			array('AND' => array('counter_useragents.crawler', '=', 1))
		)
	))
)
->addCondition(
	array('join' => array('counter_pages', 'counter_pages.id', '=', 'counter_visits.counter_page_id'))
)
->addCondition(
	array('where' => array('counter_visits.site_id', '=', CURRENT_SITE))
)
/*->addCondition(
	array('where' => array('counter_useragents.crawler', '=', 1))
)*/;

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$oAdmin_Form_Controller->execute();