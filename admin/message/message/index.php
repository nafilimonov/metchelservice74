<?php
/**
 * Messages.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'message');

// Код формы
$iAdmin_Form_Id = 187;
$sAdminFormAction = '/{admin}/message/message/index.php';
$sTopicMessagesPath = '/{admin}/message/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$message_topic_id = intval(Core_Array::getGet('message_topic_id', 0));

$pageTitle = Core::_('Message.menu');

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title($pageTitle)
	->pageTitle($pageTitle);

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Admin_Form.add'))
		->icon('fa fa-plus')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
		)
);

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);



// Построение хлебных крошек
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Первая хлебная крошка будет всегда
$oAdmin_Form_Entity_Breadcrumbs
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('message_topic.title'))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($sTopicMessagesPath)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($sTopicMessagesPath)
			))
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name($pageTitle)
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath())
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath())
			)
		);

// Хлебные крошки добавляем контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oMaillist_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Message_Controller_Edit', $oAdmin_Form_Action
	);

	$oMaillist_Controller_Edit
		->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oMaillist_Controller_Edit);
}

// Источник данных
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Message')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

// Ограничение по пользователям сайта
$oAdmin_Form_Dataset->addCondition(
		array('select' => array('messages.*',
			array('siteusers.login', 'adminSiteuser')
		)
	)
)->addCondition(
		array('leftJoin' => array('siteusers', 'siteusers.id', '=', 'messages.siteuser_id')
		)
)->addCondition(
	array('where' => array('message_topic_id', '=', $message_topic_id))
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$oAdmin_Form_Controller->execute();