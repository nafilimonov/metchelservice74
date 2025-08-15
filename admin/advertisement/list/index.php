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
$iAdmin_Form_Id = 45;
$sAdminFormAction = '/{admin}/advertisement/list/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$iAdvertisementGroup = intval(Core_Array::getGet('advertisement_group_id'));
$sAdvertisementGroupName = Core_Entity::factory('Advertisement_Group', $iAdvertisementGroup)->name;

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Advertisement_Group_Advertisement.title', $sAdvertisementGroupName))
	->pageTitle(html_entity_decode(Core::_('Advertisement_Group_Advertisement.title', $sAdvertisementGroupName)));

// Строка навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

$sAdminFormGroups = Admin_Form_Controller::correctBackendPath('/{admin}/advertisement/index.php');

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Advertisement_Group.group_link'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($sAdminFormGroups, NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($sAdminFormGroups, NULL, NULL, '')
		)
)->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name($sAdvertisementGroupName)
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, "advertisement_group_id={$iAdvertisementGroup}")
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, "advertisement_group_id={$iAdvertisementGroup}")
		)
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oAdvertisement_Group_Advertisement_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Advertisement_Group_List_Controller_Edit', $oAdmin_Form_Action
	);

	$oAdvertisement_Group_Advertisement_Controller_Edit
		->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oAdvertisement_Group_Advertisement_Controller_Edit);
}

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oAdmin_Form_Action_Controller_Type_Apply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oAdmin_Form_Action_Controller_Type_Apply);
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
	Core_Entity::factory('Advertisement_Group_Advertisement')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

$oAdmin_Form_Dataset->addCondition(
	array('select' => array(
		'advertisements.*', 'advertisement_group_lists.probability',
		array('advertisement_group_lists.id', 'in_group'),
		/*array('IF(SUM(advertisement_statistics.clicks), SUM(advertisement_statistics.clicks), 0)', 'clicks'),
		array('IF(SUM(advertisement_statistics.showed), ROUND(SUM(advertisement_statistics.clicks)/SUM(advertisement_statistics.showed)*100,2),	0.00) ','ctr')*/
		array(Core_QueryBuilder::expression('IF(advertisements.clicks, advertisements.clicks, 0)'), 'clicks'),
		array(Core_QueryBuilder::expression('IF(advertisements.showed, ROUND(advertisements.clicks/advertisements.showed * 100, 2), 0.00)'), 'ctr')
	))
)->addCondition(
	array('leftJoin' =>
		array('advertisement_group_lists', 'advertisement_id', '=', 'advertisements.id',
			array(array('AND' => array('advertisement_group_id', '=', $iAdvertisementGroup)))
		)
	)
)/*->addCondition(
	array('leftJoin' =>
		array('advertisement_statistics', 'advertisements.id', '=', 'advertisement_statistics.advertisement_id')
	)
)*/->addCondition(
	array('where' =>
		array('site_id', '=', CURRENT_SITE)
	)
)->addCondition(
	array('groupBy' =>
		array('advertisements.id')
	)
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$oAdmin_Form_Controller->execute();