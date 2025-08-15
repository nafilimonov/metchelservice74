<?php
/**
 * Cache.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'cache');

// Код формы
$iAdmin_Form_Id = 131;
$sAdminFormAction = '/{admin}/cache/cache/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$cache = Core_Array::getGet('cache');
$aConfig = Core::$config->get('core_cache');

$cacheName = isset($aConfig[$cache]['name'])
	? $aConfig[$cache]['name']
	: 'undefined';

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Cache.title_list', $cacheName))
	->pageTitle(Core::_('Cache.title_list', $cacheName));

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Cache.menu'))
		->icon('fa fa-archive')
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Cache.sub_menu'))
				->icon('fa fa-trash')
				->href(
					$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'clearAll', NULL, 0, 0)
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'clearAll', NULL, 0, 0)
				)
		)
);

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

$sCachePath = '/{admin}/cache/index.php';
$sAdditionalParams = 'cache=' . rawurlencode($cache);

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Cache.title'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($sCachePath, NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($sCachePath, NULL, NULL, '')
	)
)->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Cache.title_list', $cacheName, FALSE))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, $sAdditionalParams)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, $sAdditionalParams)
	)
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Источник данных 0
$oAdmin_Form_Dataset = new Cache_Dataset($cache);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Показ формы
$oAdmin_Form_Controller->execute();