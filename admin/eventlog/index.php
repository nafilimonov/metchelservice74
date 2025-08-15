<?php
/**
 * Eventlog.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../bootstrap.php');

Core_Auth::authorization($sModule = 'eventlog');

// Код формы
$iAdmin_Form_Id = 104;
$sAdminFormAction = '/{admin}/eventlog/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Eventlog.title'))
	->pageTitle(Core::_('Eventlog.title'));

// Источник данных 0
$oAdmin_Form_Dataset = new Eventlog_Dataset();

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$oAdmin_Form_Controller
	->addFilter('status', array('Eventlog_Event', 'eventFilter'))
	->addFilter('datetime', array('Eventlog_Event', 'datetimeFilter'));

// Показ формы
$oAdmin_Form_Controller->execute();