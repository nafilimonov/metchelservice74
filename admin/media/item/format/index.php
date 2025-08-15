<?php
/**
 * Media.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../../bootstrap.php');

Core_Auth::authorization($sModule = 'media');

$iAdmin_Form_Id = 377;
$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Путь к контроллеру формы ЦА
$sAdminFormAction = '/{admin}/media/item/format/index.php';

$media_item_id = Core_Array::getGet('media_item_id', 0, 'int');

$pageTitle = Core::_('Media_Item_Format.title');

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction);
	/* ->title($pageTitle)
	->pageTitle($pageTitle) */

// Удаление этапа
$oAdminFormActionDelete = $oAdmin_Form->Admin_Form_Actions->getByName('delete');

if ($oAdminFormActionDelete && $oAdmin_Form_Controller->getAction() == 'delete')
{
	$oMedia_item_Format_Controller_Delete = Admin_Form_Action_Controller::factory(
		'Media_item_Format_Controller_Delete', $oAdminFormActionDelete
	);

	$oAdmin_Form_Controller->addAction($oMedia_item_Format_Controller_Delete);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Media_Item_Format')
);

// Ограничение источника 0 по родительской группе
$oAdmin_Form_Dataset->addCondition(
	array('where' =>
		array('media_item_id', '=', $media_item_id)
));

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Показ формы
$oAdmin_Form_Controller->execute();