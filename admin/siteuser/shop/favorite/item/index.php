<?php
/**
 * Site users.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../../../bootstrap.php');

Core_Auth::authorization($sModule = 'siteuser');

// Код формы
$iAdmin_Form_Id = 345;
$sAdminFormAction = '/{admin}/siteuser/shop/favorite/item/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$shop_favorite_list_id = Core_Array::getGet('shop_favorite_list_id', 0, 'int');
$oShop_Favorite_List = Core_Entity::factory('Shop_Favorite_List', $shop_favorite_list_id);

$siteuser_id = Core_Array::getGet('siteuser_id');
$oSiteuser = Core_Entity::factory('Siteuser', $siteuser_id);

$shop_id = Core_Array::getGet('shop_id');
$oShop = Core_Entity::factory('Shop', $shop_id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Siteuser.shop_siteuser_favorite_item_title', $oSiteuser->login))
	->pageTitle(Core::_('Siteuser.shop_siteuser_favorite_item_title', $oSiteuser->login));

$sSiteuserPath = '/{admin}/siteuser/index.php';

$additionalParams = 'siteuser_id=' . intval($siteuser_id);
$additionalParamItems = $additionalParamItemsList = 'siteuser_id=' . intval($siteuser_id) . '&shop_id=' . intval($shop_id);

if ($shop_favorite_list_id)
{
	$additionalParamItemsList .= '&shop_favorite_list_id=' . $shop_favorite_list_id;
}

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

$dataset = $shop_favorite_list_id ? 0 : 1;

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Admin_Form.add'))
		->icon('fa fa-plus')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, $dataset, 0, $additionalParamItemsList)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, $dataset, 0, $additionalParamItemsList)
		)
);

if (!$shop_favorite_list_id)
{
	$oAdmin_Form_Entity_Menus->add(
		Admin_Form_Entity::factory('Menu')
			->name(Core::_('Shop_Favorite_List.add'))
			->icon('fa fa-plus')
			->href(
				$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0, $additionalParamItemsList)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0, $additionalParamItemsList)
			)
	);
}

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

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
			->name(Core::_('Siteuser.shop_siteuser_favorite_title', $oSiteuser->login, FALSE))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/siteuser/shop/favorite/index.php', NULL, NULL, $additionalParams)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/siteuser/shop/favorite/index.php', NULL, NULL, $additionalParams)
			)
	)->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.shop_siteuser_favorite_item_title', $oSiteuser->login, FALSE))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParamItems)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParamItems)
			)
	);

if ($shop_favorite_list_id)
{
	$oAdmin_Form_Entity_Breadcrumbs->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.shop_siteuser_favorite_list_title', $oShop_Favorite_List->name, FALSE))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParamItemsList)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParamItemsList)
			)
	);
}

// Добавляем все хлебные крошки контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oShop_Favorite_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Shop_Favorite_Controller_Edit', $oAdmin_Form_Action
	);

	// Хлебные крошки для контроллера редактирования
	$oShop_Favorite_Controller_Edit
		->addEntity(
			$oAdmin_Form_Entity_Breadcrumbs
		);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oShop_Favorite_Controller_Edit);
}

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oControllerApply);
}

if (!$shop_favorite_list_id)
{
	// Источник данных 0
	$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
		Core_Entity::factory('Shop_Favorite_List')
	);

	$oAdmin_Form_Dataset
		->addCondition(
			array(
				'select' => array(
					'shop_favorite_lists.*',
					array('shop_favorite_lists.name', 'dataName'),
				)
			)
		)
		->addCondition(array('where' => array('shop_favorite_lists.siteuser_id', '=', $oSiteuser->id)))
		->addCondition(array('where' => array('shop_favorite_lists.shop_id', '=', $oShop->id)));

	$oAdmin_Form_Controller->addDataset($oAdmin_Form_Dataset);
}

// Источник данных 1
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Shop_Favorite')
);

$oAdmin_Form_Dataset
	->addCondition(
		array(
			'select' => array(
				'shop_favorites.*',
				array('shop_items.name', 'dataName'),
				array('shop_items.marking', 'dataMarking'),
				array('shop_items.price', 'dataPrice')
			)
		)
	)
	->addCondition(
		array('join' =>
			array('shop_items', 'shop_favorites.shop_item_id', '=', 'shop_items.id')
		)
	)
	->addCondition(array('where' => array('shop_favorites.shop_favorite_list_id', '=', $shop_favorite_list_id)))
	->addCondition(array('where' => array('shop_favorites.siteuser_id', '=', $oSiteuser->id)))
	->addCondition(array('where' => array('shop_favorites.shop_id', '=', $oShop->id)));

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$oAdmin_Form_Controller->addExternalReplace('{siteuser_id}', $siteuser_id);

// Показ формы
$oAdmin_Form_Controller->execute();