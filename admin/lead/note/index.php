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
$iAdmin_Form_Id = 272;
$sAdminFormAction = '/{admin}/lead/note/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$iLeadId = intval(Core_Array::getGet('lead_id', 0));
$oLead = Core_Entity::factory('Lead', $iLeadId);

// var_dump(Core_Array::getGet('form'));

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Lead_Note.lead_notes_title'))
	->pageTitle(Core::_('Lead_Note.lead_notes_title'))
	->Admin_View(
		Admin_View::getClassName('Admin_Internal_View')
	)
	->addView('note', 'Lead_Controller_Note')
	->view('note');

$oAdmin_Form_Controller->addExternalReplace('{lead_id}', $oLead->id);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oLead_Note_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Lead_Note_Controller_Edit', $oAdmin_Form_Action
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oLead_Note_Controller_Edit);
}

// Добавление заметки
$oAdmin_Form_Action_Add_Lead_Note = $oAdmin_Form->Admin_Form_Actions->getByName('addNote');

if ($oAdmin_Form_Action_Add_Lead_Note && $oAdmin_Form_Controller->getAction() == 'addNote')
{
	$oLead_Note_Controller_Add = Admin_Form_Action_Controller::factory(
		'Lead_Note_Controller_Add', $oAdmin_Form_Action_Add_Lead_Note
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oLead_Note_Controller_Add);
}

// Действие "Удалить файл"
$oAdminFormActionDeleteFile = $oAdmin_Form->Admin_Form_Actions->getByName('deleteFile');

if ($oAdminFormActionDeleteFile && $oAdmin_Form_Controller->getAction() == 'deleteFile')
{
	$oController_Type_Delete_File = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Delete_File', $oAdminFormActionDeleteFile
	);

	$oController_Type_Delete_File
		->methodName('deleteFile')
		->dir($oLead->getPath())
		->divId('file_' . $oAdmin_Form_Controller->getOperation());

	// Добавляем контроллер удаления файла контроллеру формы
	$oAdmin_Form_Controller->addAction($oController_Type_Delete_File);
}

// Действие "Отметить удаленным"
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('markDeleted');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'markDeleted')
{
	$oLead_Note_Controller_Markdeleted = Admin_Form_Action_Controller::factory(
		'Lead_Note_Controller_Markdeleted', $oAdmin_Form_Action
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oLead_Note_Controller_Markdeleted);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Crm_Note')
);

$oAdmin_Form_Dataset
	/*->addCondition(
		array('where' => array('lead_notes.lead_id', '=', $oLead->id))
	)->addCondition(
		array('orderBy' => array('lead_notes.id', 'DESC'))
	)*/
	->addCondition(
		array('select' => array('crm_notes.*'))
	)
	->addCondition(
		array('leftJoin' => array('lead_crm_notes', 'crm_notes.id', '=', 'lead_crm_notes.crm_note_id'))
	)
	->addCondition(
		array('where' => array('lead_crm_notes.lead_id', '=', $oLead->id))
	);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

Core_Event::attach('Admin_Form_Controller.onAfterShowContent', array('User_Controller', 'onAfterShowContentPopover'), array($oAdmin_Form_Controller));
Core_Event::attach('Admin_Form_Action_Controller_Type_Edit.onAfterRedeclaredPrepareForm', array('User_Controller', 'onAfterRedeclaredPrepareForm'));

// Показ формы
$oAdmin_Form_Controller->execute();