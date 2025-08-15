<?php

/**
 * Counter.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'counter');

// Код формы
$iAdmin_Form_Id = 386;
$sAdminFormAction = '/{admin}/counter/session/index.php';

$sCounterPath = '/{admin}/counter/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Counter_Session.title'))
	->pageTitle(Core::_('Counter_Session.title'));

$sFormPath = $oAdmin_Form_Controller->getPath();

// подключение верхнего меню
include CMS_FOLDER . Admin_Form_Controller::correctBackendPath('/{admin}/counter/menu.php');

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Строка навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Counter.title'))
		->href($oAdmin_Form_Controller->getAdminLoadHref($sCounterPath, NULL, NULL, ''))
		->onclick($oAdmin_Form_Controller->getAdminLoadAjax($sCounterPath, NULL, NULL, ''))
)
->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Counter_Session.title'))
		->href($oAdmin_Form_Controller->getAdminLoadHref($sFormPath, NULL, NULL, ''))
		->onclick($oAdmin_Form_Controller->getAdminLoadAjax($sFormPath, NULL, NULL, ''))
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

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

// Источник данных
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Counter_Session')
);

if (is_null(Core_Array::getRequest('admin_form_filter_2319')))
{
	// Ограничение по сайту
	$oAdmin_Form_Dataset->addCondition(
		array('where' => array('counter_sessions.site_id', '=', CURRENT_SITE))
	);
}

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

class Counter_Session_Observer
{
	static public function onAfterSetConditionsAndLimits($controller)
	{
		?><script>
		$(function() {
			$('.user-agent').click(function() {
				var textToCopy = $(this).attr('title');
				var tempTextarea = $('<textarea>');
				$('body').append(tempTextarea);
				tempTextarea.val(textToCopy).select();
				document.execCommand('copy');
				tempTextarea.remove();
			});
		});
		</script><?php
	}
}

Core_Event::attach('Admin_Form_Controller.onAfterSetConditionsAndLimits', array('Counter_Session_Observer', 'onAfterSetConditionsAndLimits'));

$oAdmin_Form_Controller->execute();