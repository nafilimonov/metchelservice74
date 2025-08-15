<?php
/**
 * Site users.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../../bootstrap.php');

// Код формы
$iAdmin_Form_Id = 351;
$sAdminFormAction = '/{admin}/siteuser/company/contract/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

if (Core_Auth::logged())
{
	Core_Auth::setCurrentSite();
	Core_Auth::checkBackendBlockedIp();

	if (!is_null(Core_Array::getGet('getSiteuserCompanyContracts')))
	{
		$aJSON = array();

		$iCompanyId = Core_Array::getGet('companyId', 0, 'int');
		$iSiteuserCompanyId = Core_Array::getGet('siteuserCompanyId', 0, 'int');

		if ($iCompanyId && $iSiteuserCompanyId)
		{
			$aSiteuserCompanyContracts = Core_Entity::factory('Siteuser_Company_Contract')->getByCompanyAndSiteuserCompany($iCompanyId, $iSiteuserCompanyId);

			$i = 0;

			foreach($aSiteuserCompanyContracts as $oSiteuserCompanyContract)
			{
				$aJSON['contracts'][$i]['id'] = $oSiteuserCompanyContract->id;
				$aJSON['contracts'][$i++]['name'] = $oSiteuserCompanyContract->name;
			}
		}

		Core::showJson($aJSON);
	}
}

Core_Auth::authorization($sModule = 'siteuser');

$siteuser_company_id = Core_Array::getGet('siteuser_company_id', 0, 'int');

if ($siteuser_company_id)
{
	$oSiteuser_Company = Core_Entity::factory('Siteuser_Company', $siteuser_company_id);
	$oSiteuser = Core_Entity::factory('Siteuser', $oSiteuser_Company->siteuser_id);

	if ($oSiteuser->site_id != CURRENT_SITE)
	{
		throw new Core_Exception('Wrong Siteuser', array(), 0, FALSE);
	}
}

$sFormTitle = $siteuser_company_id
	? Core::_('Siteuser_Company_Contract.representative_contracts_form_title', $oSiteuser_Company->name, FALSE)
	: Core::_('Siteuser_Company_Contract.all_contracts_form_title');

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title($sFormTitle)
	->pageTitle($sFormTitle);

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser_Company_Contract.menu_add'))
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

// Путь к контроллерам предыдущих форм
$sSiteuserGroupPath = '/{admin}/siteuser/index.php';

$additionalParams = $siteuser_company_id ?'siteuser_id=' . $oSiteuser->id : '';

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

if ($siteuser_company_id)
{
	$oAdmin_Form_Entity_Breadcrumbs->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.su_people_companies_title', $oSiteuser->login))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserGroupPath = '/{admin}/siteuser/representative/index.php', NULL, NULL, $additionalParams)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserGroupPath, NULL, NULL, $additionalParams)
			)
	);
}

$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name($sFormTitle)
		->href(
				$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL)
		)
);

// Добавляем все хлебные крошки контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oController_Edit = Admin_Form_Action_Controller::factory(
		'Siteuser_Company_Contract_Controller_Edit', $oAdmin_Form_Action
	);

	$oController_Edit
		->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oController_Edit);
}

// Действие "Применить"
/* $oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oInformationsystemItemControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oInformationsystemItemControllerApply);
} */

// Действие "Копировать"
$oAdmin_Form_Action_Copy = $oAdmin_Form->Admin_Form_Actions->getByName('copy');

if ($oAdmin_Form_Action_Copy && $oAdmin_Form_Controller->getAction() == 'copy')
{
	$oController_Copy = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Copy', $oAdmin_Form_Action_Copy
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oController_Copy);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Siteuser_Company_Contract')
);

$aCompanies = Core_Entity::factory('Site', CURRENT_SITE)->Companies->findAll();
$aCompanyIds = [];

foreach($aCompanies as $oCompany)
{
	$aCompanyIds[$oCompany->id] = $oCompany->name;
}

$oAdmin_Form_Dataset
	->changeField('company_id', 'list', $aCompanyIds)
	->addCondition(
		array('select' => array('siteuser_company_contracts.*', array('siteuser_companies.tin', 'siteuserCompanyTin')))
	)
	->addCondition(
		array('join' => array('siteuser_companies', 'siteuser_company_contracts.siteuser_company_id', '=', 'siteuser_companies.id'))
	);

// Только если идет фильтрация, Компания (клиент), фильтр по тексту
if (isset($oAdmin_Form_Controller->request['admin_form_filter_2029'])
	&& $oAdmin_Form_Controller->request['admin_form_filter_2029'] != '')
{
	$oAdmin_Form_Dataset->addCondition(
		array('select' => array(array('siteuser_companies.name', 'siteuserCompanyName')))
	);
}

$oAdmin_Form_Controller->addFilter('dataSiteuserCompanyName', array($oAdmin_Form_Controller, '_filterCallbackCounterparty'));

function dataSiteuserCompanyName($value, $oAdmin_Form_Field)
{
	if (!is_null($value) && $value !== '')
	{
		if (strpos($value, 'person_') === 0)
		{
			// Change where() fieldname
			$oAdmin_Form_Field->name = 'siteuser_company_contracts.siteuser_company_id';
			$value = substr($value, 7);
		}
		elseif (strpos($value, 'company_') === 0)
		{
			// Change where() fieldname
			$oAdmin_Form_Field->name = 'siteuser_company_contracts.siteuser_company_id';
			$value = substr($value, 8);
		}
		else
		{
			//throw new Core_Exception('Wrong `dataCounterparty` value!');
		}
	}

	return $value;
}

$oAdmin_Form_Controller->addFilterCallback('dataSiteuserCompanyName', 'dataSiteuserCompanyName');

if ($siteuser_company_id)
{
	$oAdmin_Form_Dataset
		->addCondition(array('where' => array('siteuser_company_id', '=', $siteuser_company_id)));

	$oAdmin_Form_Controller
		->deleteAdminFormFieldById(2029)
		->deleteAdminFormFieldById(2031);
}
else
{
	$oAdmin_Form_Dataset->addCondition(
		array('join' => array('siteusers', 'siteuser_companies.siteuser_id', '=', 'siteusers.id'))
	)
	->addCondition(array('where' => array('siteusers.site_id', '=', CURRENT_SITE)));
}

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

if (Core::moduleIsActive('company'))
{
	Core_Event::attach('Admin_Form_Controller.onAfterShowContent', array('Company_Controller', 'onAfterShowContentPopover'), array($oAdmin_Form_Controller));
}

// Показ формы
$oAdmin_Form_Controller->execute();