<?php
/**
 * Forms.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'form');

// Код формы
$iAdmin_Form_Id = 27;
$sAdminFormAction = '/{admin}/form/field/index.php';

$oFormFieldDir = Core_Entity::factory('Form_Field_Dir', Core_Array::getGet('form_field_dir_id', 0));
$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$form_id = intval(Core_Array::getGet('form_id', 0));
$oForm = Core_Entity::factory('Form', $form_id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Form_Field.title', $oForm->name))
	->pageTitle(Core::_('Form_Field.title', $oForm->name));

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Form_Field.menu'))
		->icon('fa fa-plus')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 1, 0)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 1, 0)
		)
)->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Form_Field_Dir.menu'))
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

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Строка навигации
$sFormPath = '/{admin}/form/index.php';

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Form_Field.forms'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($sFormPath, NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($sFormPath, NULL, NULL, '')
	)
);

$additionalParams = 'form_id=' . $form_id;

$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Form_Field.form_fields'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParams)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParams)
	)
);

// Крошки по группам
if ($oFormFieldDir->id)
{
	$oGroupBreadcrumbs = $oFormFieldDir;

	$aBreadcrumbs = array();

	do
	{
		$aBreadcrumbs[] = Admin_Form_Entity::factory('Breadcrumb')
			->name($oGroupBreadcrumbs->name)
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/form/field/index.php', NULL, NULL, "form_id={$form_id}&form_field_dir_id={$oGroupBreadcrumbs->id}")
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/form/field/index.php', NULL, NULL, "form_id={$form_id}&form_field_dir_id={$oGroupBreadcrumbs->id}")
			);
	}
	while ($oGroupBreadcrumbs = $oGroupBreadcrumbs->getParent());

	$aBreadcrumbs = array_reverse($aBreadcrumbs);

	foreach ($aBreadcrumbs as $oBreadcrumb)
	{
		$oAdmin_Form_Entity_Breadcrumbs->add($oBreadcrumb);
	}
}

// Добавляем все хлебные крошки контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');


if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oForm_Field_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Form_Field_Controller_Edit', $oAdmin_Form_Action
	);

	$oForm_Field_Controller_Edit
		->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oForm_Field_Controller_Edit);
}

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oFormFieldControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oFormFieldControllerApply);
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
	Core_Entity::factory('Form_Field_Dir')
);
$oAdmin_Form_Dataset->changeField('name', 'class', 'semi-bold');
// Ограничение источника 0 по родительской группе
$oAdmin_Form_Dataset
	->addCondition(array('where' => array('form_id', '=', $form_id)))
	->addCondition(array('where' => array('parent_id', '=', $oFormFieldDir->id)));

// Добавляем источник данных 0 контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Источник данных 1
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Form_Field')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

// Ограничение источника 1 по родительской группе
$oAdmin_Form_Dataset
	->addCondition(array('where' => array('form_id', '=', $form_id)))
	->addCondition(array('where' => array('form_field_dir_id', '=', $oFormFieldDir->id)))
	->changeField('name', 'type', 1)
	->changeField('active', 'list', "1=" . Core::_('Admin_Form.yes') . "\n" . "0=" . Core::_('Admin_Form.no'));

// Список значений для фильтра и поля
$aList = array();
for ($i = 0; $i < 20; $i++)
{
	$aList[$i] = Core::_('Form_Field.type' . $i);
}

$oAdmin_Form_Dataset
	->changeField('type', 'type', 8)
	->changeField('type', 'list', $aList);

// Добавляем источник данных 1 контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Показ формы
$oAdmin_Form_Controller->execute();