<?php
/**
 * Siteuser timeline.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'siteuser');

// Код формы
$iAdmin_Form_Id = 238;
$sAdminFormAction = '/{admin}/siteuser/history/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$iSiteuserId = intval(Core_Array::getGet('siteuser_id', 0));
$oSiteuser = Core_Entity::factory('Siteuser', $iSiteuserId);

$sSiteuserPath = '/{admin}/siteuser/index.php';

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Siteuser.timeline_title', $oSiteuser->login))
	->pageTitle(Core::_('Siteuser.timeline_title', $oSiteuser->login))
	->addView('history', 'Siteuser_Controller_History')
	->view('history');

if (!$oSiteuser->id || $oSiteuser->site_id != CURRENT_SITE)
{
	throw new Core_Exception('Siteuser does not exist.');
}

// Построение хлебных крошек
$oAdminFormEntityBreadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Первая хлебная крошка будет всегда
$oAdminFormEntityBreadcrumbs
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.siteusers'))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserPath, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'list')
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserPath, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'list')
			)
	)
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.timeline_title', $oSiteuser->login, FALSE))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath())
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath())
			)
	);

// Хлебные крошки добавляем контроллеру
$oAdmin_Form_Controller->addEntity($oAdminFormEntityBreadcrumbs);

// Источник данных 0
$oAdmin_Form_Dataset = new Siteuser_History_Dataset($oSiteuser);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Показ формы
$oAdmin_Form_Controller->execute();