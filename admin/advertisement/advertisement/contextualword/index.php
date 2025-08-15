<?php
/**
 * Advertisement.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../../bootstrap.php');

Core_Auth::authorization($sModule = 'advertisement');

// Код формы
$iAdmin_Form_Id = 158;
$sAdminFormAction = '/{admin}/advertisement/advertisement/contextualword/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Advertisement_Contextualword.title'))
	->pageTitle(Core::_('Advertisement_Contextualword.title'));

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Admin_Form.add'))
		->icon('fa fa-plus')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref(array('path' => $oAdmin_Form_Controller->getPath(), 'action' => 'edit', 'datasetKey' => 0, 'datasetValue' => 0))
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminActionLoadAjax(array('path' => $oAdmin_Form_Controller->getPath(), 'action' => 'edit', 'datasetKey' => 0, 'datasetValue' => 0))
		)
);

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

$sAdvertisementGroupPath = '/{admin}/advertisement/index.php';
$sAdvertisementPath = '/{admin}/advertisement/advertisement/index.php';

// Идентификатор баннера
$iAdvertisement = Core_Array::getGet('advertisement_id', 0, 'int');

// Строка навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Advertisement_Group.group_link'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref(array('path' => $sAdvertisementGroupPath))
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax(array('path' => $sAdvertisementGroupPath))
		)
)->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Advertisement.banners_list'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref(array('path' => $sAdvertisementPath))
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax(array('path' => $sAdvertisementPath))
		)
)->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Advertisement_Contextualword.contextualwords_link', Core_Entity::factory('Advertisement')->find($iAdvertisement)->name, FALSE))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref(array('path' => $oAdmin_Form_Controller->getPath(), 'additionalParams' => "advertisement_id={$iAdvertisement}"))
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax(array('path' => $oAdmin_Form_Controller->getPath(), 'additionalParams' => "advertisement_id={$iAdvertisement}"))
		)
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oAdvertisement_Contextualword_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Advertisement_Contextualword_Controller_Edit', $oAdmin_Form_Action
	);

	$oAdvertisement_Contextualword_Controller_Edit
		->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oAdvertisement_Contextualword_Controller_Edit);
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

// Источник данных
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Advertisement_Contextualword')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

// Ограничение по баннеру
$oAdmin_Form_Dataset->addCondition(
	array('where' =>
		array('advertisement_id', '=', $iAdvertisement)
	)
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$oAdmin_Form_Controller->execute();