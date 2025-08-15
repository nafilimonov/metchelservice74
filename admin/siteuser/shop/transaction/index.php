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
$iAdmin_Form_Id = 134;
$sAdminFormAction = '/{admin}/siteuser/shop/transaction/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$siteuser_id = Core_Array::getGet('siteuser_id');
$shop_id = Core_Array::getGet('shop_id');

$oSiteuser = Core_Entity::factory('Siteuser', $siteuser_id);
$oShop = Core_Entity::factory('Shop', $shop_id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Shop_Siteuser_Transaction.transaction_siteuser_title', $oSiteuser->login, $oShop->name))
	->pageTitle(Core::_('Shop_Siteuser_Transaction.transaction_siteuser_title', $oSiteuser->login, $oShop->name));

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Shop_Siteuser_Transaction.transaction_menu_header'))
		->icon('fa fa-bolt')
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Shop_Siteuser_Transaction.transaction_submenu'))
				->icon('fa fa-plus')
				->href(
					$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
				)
		)
);

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

$sSiteuserPath = '/{admin}/siteuser/index.php';
$sSiteuserGroupPath = '/{admin}/siteuser/siteuser/index.php';
$sSiteuserShopPath = '/{admin}/siteuser/shop/index.php';

$additionalParams = 'siteuser_id=' . $siteuser_id . '&shop_id=' . $shop_id;

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
			->name(Core::_('Siteuser.shop_siteuser_transaction_title', $oSiteuser->login, FALSE))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserShopPath, NULL, NULL, $additionalParams)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserShopPath, NULL, NULL, $additionalParams)
			)
		)
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Shop_Siteuser_Transaction.transaction_siteuser_title', $oSiteuser->login, $oShop->name, FALSE))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath())
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath())
			)
		);

// Добавляем все хлебные крошки контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oSiteuser_Shop_Transaction_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Siteuser_Shop_Transaction_Controller_Edit', $oAdmin_Form_Action
	);

	$oSiteuser_Shop_Transaction_Controller_Edit
		->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteuser_Shop_Transaction_Controller_Edit);
}

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

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Shop_Siteuser_Transaction')
);

// Ограничение источника 0
$oAdmin_Form_Dataset->addCondition(
	array('select' => array('shop_siteuser_transactions.*'))
)->addCondition(
	array('where' => array('shop_id', '=', $shop_id))
)->addCondition(
	array('where' => array('siteuser_id', '=', $siteuser_id))
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Показ формы
$oAdmin_Form_Controller->execute();