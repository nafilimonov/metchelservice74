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
$iAdmin_Form_Id = 85;
$sAdminFormAction = '/{admin}/siteuser/group/list/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$siteuser_group_id = intval(Core_Array::getGet('siteuser_group_id', 0));
$oSiteuser_Group = Core_Entity::factory('Siteuser_Group', $siteuser_group_id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Siteuser_Group_Siteuser.users_group_title', $oSiteuser_Group->name))
	->pageTitle(Core::_('Siteuser_Group_Siteuser.users_group_title', $oSiteuser_Group->name));

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

$sSiteuserGroupPath = '/{admin}/siteuser/siteuser/index.php';
$sSiteuserPath = '/{admin}/siteuser/index.php';

$additionalParams = 'siteuser_group_id=' . $siteuser_group_id;

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
			->name(Core::_('Siteuser_Group.title'))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserGroupPath, NULL, NULL, '')
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserGroupPath, NULL, NULL, '')
			)
	)
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name($oSiteuser_Group->name)
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParams)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParams)
			)
	);


// Добавляем все хлебные крошки контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oSiteuserControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteuserControllerApply);
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
	Core_Entity::factory('Siteuser_Group_Siteuser')
);

// Ограничение источника 0
$oAdmin_Form_Dataset->addCondition(
	array('select' => array('siteusers.*', array('siteuser_group_lists.siteuser_id', 'in_group'), array(Core_QueryBuilder::expression('CONCAT_WS(" ", GROUP_CONCAT(`siteuser_companies`.`name`), GROUP_CONCAT(CONCAT_WS(" ", `siteuser_people`.`surname`, `siteuser_people`.`name`, `siteuser_people`.`patronymic`)))'), 'counterparty')))
)->addCondition(
	array('leftJoin' => array('siteuser_group_lists', 'siteuser_group_lists.siteuser_id', '=', 'siteusers.id',
		array(
				array('AND' => array('siteuser_group_lists.siteuser_group_id', '=', $siteuser_group_id))
			)
		)
	)
)->addCondition(
	array('leftJoin' => array('siteuser_companies', 'siteusers.id', '=', 'siteuser_companies.siteuser_id', array(
			array('AND' => array('siteuser_companies.deleted', '=', 0))
		))
	)
)
->addCondition(
	array('leftJoin' => array('siteuser_people', 'siteusers.id', '=', 'siteuser_people.siteuser_id',
		array(
			array('AND' => array('siteuser_people.deleted', '=', 0))
		))
	)
)
->addCondition(
	array('where' => array('siteusers.site_id', '=', CURRENT_SITE))
)
->addCondition(
	array('groupBy' => array('siteusers.id'))
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Показ формы
$oAdmin_Form_Controller->execute();