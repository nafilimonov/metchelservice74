<?php
/**
 * Advertisement.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'advertisement');

// Код формы
$iAdmin_Form_Id = 46;
$sAdminFormAction = '/{admin}/advertisement/advertisement/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Advertisement.title'))
	->pageTitle(Core::_('Advertisement.title'));

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

$sAdvertisementPath = '/{admin}/advertisement/advertisement/index.php';

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

// Строка навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Путь к контроллеру предыдущей формы
$sAdvertisementGroupPath = '/{admin}/advertisement/index.php';

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Advertisement.group_link'))
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
);

// Добавляем все хлебные крошки контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oAdvertisement_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Advertisement_Controller_Edit', $oAdmin_Form_Action
	);

	$oAdvertisement_Controller_Edit
		->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oAdvertisement_Controller_Edit);
}

// Действие "Удалить файл"
$oAdminFormActionDeleteFile = $oAdmin_Form->Admin_Form_Actions->getByName('deleteFile');

if ($oAdminFormActionDeleteFile && $oAdmin_Form_Controller->getAction() == 'deleteFile')
{
	$oSiteControllerDeleteFile = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Delete_File', $oAdminFormActionDeleteFile
	);

	$oSiteControllerDeleteFile
		->methodName('deleteFile')
		->divId(array('preview_large_source', 'delete_large_source'));

	// Добавляем контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteControllerDeleteFile);
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
	Core_Entity::factory('Advertisement')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

// Ограничение по сайту
$oAdmin_Form_Dataset->addCondition(
	array('where' =>
		array('site_id', '=', CURRENT_SITE)
	)
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$oAdmin_Form_Controller->execute();
