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
$iAdmin_Form_Id = 325;
$sAdminFormAction = '/{admin}/siteuser/timeline/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$iSiteuserId = Core_Array::getRequest('siteuser_id', 0, 'int');
$oSiteuser = Core_Entity::factory('Siteuser', $iSiteuserId);

if (!$oSiteuser->id)
{
	throw new Core_Exception('Siteuser object does not exist. Check siteuser_id.', array(), 0, FALSE);
}

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Siteuser.timeline_title', $oSiteuser->login))
	->pageTitle(Core::_('Siteuser.timeline_title', $oSiteuser->login))
	->Admin_View(
		Admin_View::getClassName('Admin_Internal_View')
	)
	->addView('timeline', 'Siteuser_Controller_Timeline')
	->view('timeline');

// Добавление заметки
$oAdmin_Form_Action_Add_Siteuser_Note = $oAdmin_Form->Admin_Form_Actions->getByName('addNote');

if ($oAdmin_Form_Action_Add_Siteuser_Note && $oAdmin_Form_Controller->getAction() == 'addNote')
{
	$oSiteuser_Note_Controller_Add = Admin_Form_Action_Controller::factory(
		'Siteuser_Note_Controller_Add', $oAdmin_Form_Action_Add_Siteuser_Note
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteuser_Note_Controller_Add);
}

// Действие "Отметить удаленным"
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('markDeleted');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'markDeleted')
{
	$oSiteuser_Timeline_Controller_Markdeleted = Admin_Form_Action_Controller::factory(
		'Siteuser_Timeline_Controller_Markdeleted', $oAdmin_Form_Action
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteuser_Timeline_Controller_Markdeleted);
}

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$controller = NULL;

	$aChecked = $oAdmin_Form_Controller->getChecked();

	if (isset($aChecked[0]))
	{
		list($type, $id) = explode('-', key($aChecked[0]));

		switch ($type)
		{
			case 0:
				$controller = 'Siteuser_Email_Controller_Edit';
			break;
			case 1:
				$controller = 'Siteuser_Note_Controller_Edit';
			break;
			case 2:
				$controller = 'Shop_Order_Controller_Edit';
			break;
			case 3:
				$controller = 'Event_Controller_Edit';
			break;
			case 4:
				$controller = 'Deal_Controller_Edit';
			break;
		}
	}

	if (!is_null($controller))
	{
		$Controller_Edit = Admin_Form_Action_Controller::factory(
			$controller, $oAdmin_Form_Action
		);

		// Добавляем типовой контроллер редактирования контроллеру формы
		$oAdmin_Form_Controller->addAction($Controller_Edit);
	}
}

// Источник данных 0
$oAdmin_Form_Dataset = new Siteuser_Timeline_Dataset($oSiteuser);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$oAdmin_Form_Controller->addExternalReplace('{siteuser_id}', $oSiteuser->id);

Core_Event::attach('Admin_Form_Controller.onAfterShowContent', array('User_Controller', 'onAfterShowContentPopover'), array($oAdmin_Form_Controller));
Core_Event::attach('Admin_Form_Action_Controller_Type_Edit.onAfterRedeclaredPrepareForm', array('User_Controller', 'onAfterRedeclaredPrepareForm'));

// Показ формы
$oAdmin_Form_Controller->execute();