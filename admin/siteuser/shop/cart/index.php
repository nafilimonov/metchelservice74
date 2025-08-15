<?php
/**
 * Site users.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../../bootstrap.php');

Core_Auth::authorization($sModule = 'siteuser');

// Код формы
$iAdmin_Form_Id = 242;
$sAdminFormAction = '/{admin}/siteuser/shop/cart/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$siteuser_id = Core_Array::getGet('siteuser_id');
$oSiteuser = Core_Entity::factory('Siteuser', $siteuser_id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Siteuser.shop_siteuser_cart_title', $oSiteuser->login))
	->pageTitle(Core::_('Siteuser.shop_siteuser_cart_title', $oSiteuser->login));

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

$sSiteuserPath = '/{admin}/siteuser/index.php';

$additionalParams = 'siteuser_id=' . intval($siteuser_id);

// Строка навигации для пользователей
$oAdmin_Form_Entity_Breadcrumbs
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.siteusers'))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserPath, NULL, NULL, '')
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserPath, NULL, NULL, '')
			)
	)
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.shop_siteuser_cart_title', $oSiteuser->login, FALSE))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParams)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParams)
			)
	);

// Добавляем все хлебные крошки контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Siteuser_Shop_Cart')
);

// Ограничение по сайту
$oAdmin_Form_Dataset
->addCondition(
	array('where' =>
		array('site_id', '=', CURRENT_SITE)
	)
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$oAdmin_Form_Controller->addExternalReplace('{siteuser_id}', $siteuser_id);

// Показ формы
$oAdmin_Form_Controller->execute();