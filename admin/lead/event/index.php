<?php
/**
 * Leads.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'lead');

// Код формы
$iAdmin_Form_Id = 220;
$sAdminFormAction = '/{admin}/lead/event/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$iLeadId = intval(Core_Array::getGet('lead_id', 0));
$oLead = Core_Entity::factory('Lead', $iLeadId);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Lead_Event.lead_event_title'))
	->pageTitle(Core::_('Lead_Event.lead_event_title'))
	->Admin_View(
		Admin_View::getClassName('Admin_Internal_View')
	)
	->addView('event', 'Event_Controller_Related_Event')
	->view('event');

$oAdmin_Form_Controller->addExternalReplace('{lead_id}', $oLead->id);

$windowId = $oAdmin_Form_Controller->getWindowId();

$additionalParams = Core_Str::escapeJavascriptVariable(
	str_replace(array('"'), array('&quot;'), $oAdmin_Form_Controller->additionalParams)
);

if (is_null(Core_Array::getGet('hideMenu')))
{
	// Меню формы
	$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

	// Элементы меню
	$oAdmin_Form_Entity_Menus
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Admin_Form.add'))
				->icon('fa fa-plus')
				->class('btn btn-gray')
				->onclick(
					// $oAdmin_Form_Controller->getAdminActionModalLoad($oAdmin_Form_Controller->getPath(), 'edit', 'modal', 0, 0, $additionalParams)
					$oAdmin_Form_Controller->getAdminActionModalLoad(array('path' => $oAdmin_Form_Controller->getPath(), 'action' => 'edit', 'operation' => 'modal', 'datasetKey' => 0, 'datasetValue' => 0, 'additionalParams' => $additionalParams, 'width' => '90%'))
				)
		);

	// Добавляем все меню контроллеру
	$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);
}

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oDeal_Event_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Event_Controller_Edit', $oAdmin_Form_Action
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oDeal_Event_Controller_Edit);
}

// Действие "Изменить группу"
$oAdminFormActionChangeGroup = $oAdmin_Form->Admin_Form_Actions->getByName('changeGroup');

if ($oAdminFormActionChangeGroup && $oAdmin_Form_Controller->getAction() == 'changeGroup')
{
	$oEventControllerGroup = Admin_Form_Action_Controller::factory(
		'Event_Controller_Group', $oAdminFormActionChangeGroup
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oEventControllerGroup);
}

// Действие "Изменить статус"
$oAdminFormActionChangeStatus = $oAdmin_Form->Admin_Form_Actions->getByName('changeStatus');

if ($oAdminFormActionChangeStatus && $oAdmin_Form_Controller->getAction() == 'changeStatus')
{
	$oEventControllerStatus = Admin_Form_Action_Controller::factory(
		'Event_Controller_Status', $oAdminFormActionChangeStatus
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oEventControllerStatus);
}

// Действие удаления
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('markDeleted');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'markDeleted')
{
	$oLead_Controller_Markdeleted = Admin_Form_Action_Controller::factory(
		'Lead_Controller_Markdeleted', $oAdmin_Form_Action
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oLead_Controller_Markdeleted);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Event')
);

$oCurrentUser = Core_Auth::getCurrentUser();

$oAdmin_Form_Dataset
	->addCondition(
		array('select' => array('events.*'))
	)
	->addCondition(
		array('join' => array('lead_events', 'lead_events.event_id', '=', 'events.id'))
	)
	->addCondition(
		array('join' => array('event_users', 'events.id', '=', 'event_users.event_id'))
	)
	->addCondition(
		array('where' => array('lead_events.lead_id', '=', $oLead->id))
	)
	->addCondition(
		array('where' => array('event_users.user_id', '=', $oCurrentUser->id))
	)
	->addCondition(
		array('orderBy' => array('lead_events.id', 'DESC'))
	);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

Core_Event::attach('Admin_Form_Controller.onAfterShowContent', array('User_Controller', 'onAfterShowContentPopover'), array($oAdmin_Form_Controller));
Core_Event::attach('Admin_Form_Action_Controller_Type_Edit.onAfterRedeclaredPrepareForm', array('User_Controller', 'onAfterRedeclaredPrepareForm'));

// Показ формы
$oAdmin_Form_Controller->execute();