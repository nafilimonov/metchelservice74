<?php
/**
 * Site users.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'siteuser');

// Код формы
$iAdmin_Form_Id = 230;
$sAdminFormAction = '/{admin}/siteuser/representative/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$iSiteuserId = intval(Core_Array::getGet('siteuser_id'));

$oSiteuser = Core_Entity::factory('Siteuser')->find($iSiteuserId);

$sTitle = Core::_('Siteuser.su_people_companies_title', $oSiteuser->login);

$show = Core_Array::getGet('show');

if (!$iSiteuserId)
{
	switch ($show)
	{
		case 'company':
			$sTitle = Core::_('Siteuser.companies');
		break;
		case 'person':
			$sTitle = Core::_('Siteuser.people');
		break;
	}
}

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title($sTitle)
	->pageTitle($sTitle);

if ($iSiteuserId)
{
	// Меню формы
	$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

	// Элементы меню
	$oAdmin_Form_Entity_Menus->add(
		Admin_Form_Entity::factory('Menu')
			->name(Core::_('Siteuser_Person.siteuser_person_sub_menu_link1'))
			->icon('fa fa-plus')
			->href(
				$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 1, 0)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 1, 0)
			)
	)
	->add(
		Admin_Form_Entity::factory('Menu')
			->name(Core::_('Siteuser_Company.siteuser_company_sub_menu_link1'))
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
}

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Путь к контроллерам предыдущих форм
$sSiteuserGroupPath = '/{admin}/siteuser/index.php';

// Первая хлебная крошка будет всегда
$oAdmin_Form_Entity_Breadcrumbs
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.siteusers'))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserGroupPath)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserGroupPath)
			)
	);

if ($iSiteuserId)
{
	$additionalParams = 'siteuser_id=' . $iSiteuserId;

	$oAdmin_Form_Entity_Breadcrumbs
		->add(
			Admin_Form_Entity::factory('Breadcrumb')
				->name($sTitle)
				->href(
					$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParams)
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParams)
				)
		);
}
else
{
	switch ($show)
	{
		case 'company':
			$oAdmin_Form_Entity_Breadcrumbs
				->add(
					Admin_Form_Entity::factory('Breadcrumb')
						->name(Core::_('Siteuser.companies'))
						->href(
							$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, 'show=company')
						)
						->onclick(
							$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, 'show=company')
						)
				);
		break;
		case 'person':
			$oAdmin_Form_Entity_Breadcrumbs
				->add(
					Admin_Form_Entity::factory('Breadcrumb')
						->name(Core::_('Siteuser.people'))
						->href(
							$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, 'show=person')
						)
						->onclick(
							$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, 'show=person')
						)
				);
		break;
	}
}

// Хлебные крошки добавляем контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action/* && $oAdmin_Form_Controller->getAction() == 'edit'*/)
{
	$oSiteuserPersonControllerEdit = Admin_Form_Action_Controller::factory(
		'Siteuser_Person_Controller_Edit', $oAdmin_Form_Action
	);

	$oSiteuserPersonControllerEdit
		->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteuserPersonControllerEdit);
}

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oSiteuserPersonControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteuserPersonControllerApply);
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

// Действие "Удаление файла большого изображения"
$oAction = $oAdmin_Form->Admin_Form_Actions->getByName('deleteImageFile');

if ($oAction && $oAdmin_Form_Controller->getAction() == 'deleteImageFile')
{
	$oDeleteImageFileController = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Delete_File', $oAction
	);

	$oDeleteImageFileController
		->methodName('deleteImageFile')
		->divId(array('preview_large_image', 'delete_large_image'));

	// Добавляем контроллер удаления изображения к контроллеру формы
	$oAdmin_Form_Controller->addAction($oDeleteImageFileController);
}

// Действие "Просмотр"
$oAdminFormActionView = $oAdmin_Form->Admin_Form_Actions->getByName('view');

if ($oAdminFormActionView && $oAdmin_Form_Controller->getAction() == 'view')
{
	$oSiteuserRepresentativeControllerView = Admin_Form_Action_Controller::factory(
		'Siteuser_Representative_Controller_View', $oAdminFormActionView
	);

	$oSiteuserRepresentativeControllerView
		->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteuserRepresentativeControllerView);
}

$oAdminFormActionMerge = $oAdmin_Form->Admin_Form_Actions->getByName('merge');

if ($oAdminFormActionMerge && $oAdmin_Form_Controller->getAction() == 'merge')
{
	$oSiteuserRepresentativeControllerMerge = Admin_Form_Action_Controller::factory(
		'Siteuser_Representative_Controller_Merge', $oAdminFormActionMerge
	);

	$oAdmin_Form_Controller->addAction($oSiteuserRepresentativeControllerMerge);
}

$bFilter = isset($oAdmin_Form_Controller->request['admin_form_filter_1270'])
	&& $oAdmin_Form_Controller->request['admin_form_filter_1270'] != ''
|| isset($oAdmin_Form_Controller->request['admin_form_filter_1271'])
	&& $oAdmin_Form_Controller->request['admin_form_filter_1271'] != ''
|| isset($oAdmin_Form_Controller->request['admin_form_filter_1272'])
	&& $oAdmin_Form_Controller->request['admin_form_filter_1272'] != ''
|| isset($oAdmin_Form_Controller->request['topFilter_1270'])
	&& $oAdmin_Form_Controller->request['topFilter_1270'] != ''
|| isset($oAdmin_Form_Controller->request['topFilter_1271'])
	&& $oAdmin_Form_Controller->request['topFilter_1271'] != ''
|| isset($oAdmin_Form_Controller->request['topFilter_1272'])
	&& $oAdmin_Form_Controller->request['topFilter_1272'] != '';

if ($iSiteuserId || $show == 'company')
{
	// Источник данных 0
	$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
		Core_Entity::factory('Siteuser_Company')
	);

	$oAdmin_Form_Dataset->addCondition(
			array('select' => array('siteuser_companies.*', array('siteuser_companies.name', 'namePersonCompany')))
		);

	if ($iSiteuserId)
	{
		$oAdmin_Form_Dataset->addCondition(
			array('where' => array('siteuser_companies.siteuser_id', '=', $iSiteuserId))
		);
	}
	else
	{
		$oAdmin_Form_Dataset->addCondition(
			array('join' => array('siteusers', 'siteusers.id', '=', 'siteuser_companies.siteuser_id'))
		)->addCondition(
			array('where' => array('siteusers.site_id', '=', CURRENT_SITE))
		);
	}

	//$oAdmin_Form_Dataset->changeField('name', 'class', 'semi-bold');

	if ($bFilter)
	{
		$oAdmin_Form_Dataset->addCondition(
			array(
				'select' => array(
					//'siteuser_companies.*',
					//array('siteuser_companies.name', 'namePersonCompany'),
					array(Core_QueryBuilder::expression('GROUP_CONCAT(`directory_phones`.`value`)'), 'phone'),
					array(Core_QueryBuilder::expression('GROUP_CONCAT(`directory_emails`.`value`)'), 'email')
				)
			)
		)
		->addCondition(
			array('leftJoin' => array('siteuser_company_directory_phones', 'siteuser_companies.id', '=', 'siteuser_company_directory_phones.siteuser_company_id'))
		)
		->addCondition(
			array('leftJoin' => array('directory_phones', 'siteuser_company_directory_phones.directory_phone_id', '=', 'directory_phones.id'))
		)
		->addCondition(
			array('leftJoin' => array('siteuser_company_directory_emails', 'siteuser_companies.id', '=', 'siteuser_company_directory_emails.siteuser_company_id'))
		)
		->addCondition(
			array('leftJoin' => array('directory_emails', 'siteuser_company_directory_emails.directory_email_id', '=', 'directory_emails.id'))
		)
		->addCondition(
			array('groupBy' => array('siteuser_companies.id'))
		);
	}

	// Добавляем источник данных контроллеру формы
	$oAdmin_Form_Controller->addDataset(
		$oAdmin_Form_Dataset
	);
}

if ($iSiteuserId || $show == 'person')
{
	// Источник данных 1
	$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
		Core_Entity::factory('Siteuser_Person')
	);

	$oAdmin_Form_Dataset->addCondition(
			array(
				'select' => array(
					'siteuser_people.*',
					array(Core_QueryBuilder::expression('CONCAT_WS(" ", `siteuser_people`.`surname`, `siteuser_people`.`name`, `siteuser_people`.`patronymic`)'), 'namePersonCompany')
				)
			)
		);

	if ($iSiteuserId)
	{
		$oAdmin_Form_Dataset->addCondition(
			array('where' => array('siteuser_people.siteuser_id', '=', $iSiteuserId))
		);
	}
	else
	{
		$oAdmin_Form_Dataset->addCondition(
			array('join' => array('siteusers', 'siteusers.id', '=', 'siteuser_people.siteuser_id'))
		)->addCondition(
			array('where' => array('siteusers.site_id', '=', CURRENT_SITE))
		);
	}

	if ($bFilter)
	{
		$oAdmin_Form_Dataset->addCondition(
			array(
				'select' => array(
					//siteuser_people.*',
					//array(Core_QueryBuilder::expression('CONCAT_WS(" ", `siteuser_people`.`surname`, `siteuser_people`.`name`, `siteuser_people`.`patronymic`)'), 'namePersonCompany'),
					array(Core_QueryBuilder::expression('GROUP_CONCAT(`directory_phones`.`value`)'), 'phone'),
					array(Core_QueryBuilder::expression('GROUP_CONCAT(`directory_emails`.`value`)'), 'email')
				)
			)
		)
		->addCondition(
			array('leftJoin' => array('siteuser_people_directory_phones', 'siteuser_people.id', '=', 'siteuser_people_directory_phones.siteuser_person_id'))
		)
		->addCondition(
			array('leftJoin' => array('directory_phones', 'siteuser_people_directory_phones.directory_phone_id', '=', 'directory_phones.id'))
		)
		->addCondition(
			array('leftJoin' => array('siteuser_people_directory_emails', 'siteuser_people.id', '=', 'siteuser_people_directory_emails.siteuser_person_id'))
		)
		->addCondition(
			array('leftJoin' => array('directory_emails', 'siteuser_people_directory_emails.directory_email_id', '=', 'directory_emails.id'))
		)
		->addCondition(
			array('groupBy' => array('siteuser_people.id'))
		);
		/*$oAdmin_Form_Dataset->addCondition(
			array('select' => array('siteuser_people.*', array(Core_QueryBuilder::expression('CONCAT_WS(" ", `siteuser_people`.`name`, `siteuser_people`.`surname`, `siteuser_people`.`patronymic`)')   , 'namePersonCompany')))
		)
		->addCondition(
			array('join' => array('siteusers', 'siteusers.id', '=', 'siteuser_people.siteuser_id'))
		)
		->addCondition(
			array('having' =>
				array('namePersonCompany', 'LIKE', $admin_form_filter_1135)
			)
		)*/;
	}

	// Добавляем источник данных контроллеру формы
	$oAdmin_Form_Controller->addDataset(
		$oAdmin_Form_Dataset
	);

	// remove TIN
	$show == 'person'
		&& $oAdmin_Form_Controller->deleteAdminFormFieldById(2028);
}

// Показ формы
$oAdmin_Form_Controller->execute();