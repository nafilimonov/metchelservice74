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
$iAdmin_Form_Id = 97;
$sAdminFormAction = '/{admin}/counter/visitors/index.php';

$sCounterPath = '/{admin}/counter/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Counter.visitors_title'))
	->pageTitle(Core::_('Counter.visitors_title'));

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
		->name(Core::_('Counter.visitors_title'))
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
	Core_Entity::factory('Counter_Visit')
);

$aSetting = Core_Array::get(Core::$config->get('counter_setting'), 'setting', array());
$iFromTimestamp = strtotime("-{$aSetting['showDays']} day");

empty($oAdmin_Form_Controller->request['admin_form_filter_from_408'])
	&& empty($oAdmin_Form_Controller->request['topFilter_from_408'])
	&& $oAdmin_Form_Controller->request['admin_form_filter_from_408'] = Core_Date::timestamp2date($iFromTimestamp) . ' 00:00:00';

empty($oAdmin_Form_Controller->request['admin_form_filter_to_408'])
	&& empty($oAdmin_Form_Controller->request['topFilter_to_408'])
	&& $oAdmin_Form_Controller->request['admin_form_filter_to_408'] = Core_Date::timestamp2date(time()) . ' 23:59:59';

// Ограничение по сайту
$oAdmin_Form_Dataset->addCondition(
	array('select' => array('counter_visits.*'/*, 'counter_pages.page', 'counter_referrers.referrer'*/))
)
/*->addCondition(
	array('where' => array('counter_visits.site_id', '=', CURRENT_SITE))
)*/;

if (!isset($oAdmin_Form_Controller->request['admin_form_filter_2320'])
	|| $oAdmin_Form_Controller->request['admin_form_filter_2320'] == '')
{
	// Ограничение по сайту
	$oAdmin_Form_Dataset->addCondition(
		array('where' => array('counter_visits.site_id', '=', CURRENT_SITE))
	);
}

if (isset($oAdmin_Form_Controller->request['admin_form_filter_406'])
		&& $oAdmin_Form_Controller->request['admin_form_filter_406'] != ''
	|| isset($oAdmin_Form_Controller->request['topFilter_406'])
		&& $oAdmin_Form_Controller->request['topFilter_406'] != ''
	|| $oAdmin_Form_Controller->sortingFieldId == 406
)
{
	$oAdmin_Form_Dataset->addCondition(
		array('leftJoin' => array('counter_pages', 'counter_visits.counter_page_id', '=', 'counter_pages.id'))
	);
}

if (isset($oAdmin_Form_Controller->request['admin_form_filter_407'])
		&& $oAdmin_Form_Controller->request['admin_form_filter_407'] != ''
	|| isset($oAdmin_Form_Controller->request['topFilter_407'])
		&& $oAdmin_Form_Controller->request['topFilter_407'] != ''
	|| $oAdmin_Form_Controller->sortingFieldId == 407
)
{
	$oAdmin_Form_Dataset->addCondition(
		array('leftJoin' => array('counter_referrers', 'counter_visits.counter_referrer_id', '=', 'counter_referrers.id'))
	);
}

if (isset($oAdmin_Form_Controller->request['admin_form_filter_1459'])
		&& $oAdmin_Form_Controller->request['admin_form_filter_1459'] != ''
	|| isset($oAdmin_Form_Controller->request['topFilter_1459'])
		&& $oAdmin_Form_Controller->request['topFilter_1459'] != ''
	|| $oAdmin_Form_Controller->sortingFieldId == 1459
)
{
	$oAdmin_Form_Dataset->addCondition(
		array('join' => array('counter_sessions', 'counter_visits.counter_session_id', '=', 'counter_sessions.id'))
	)->addCondition(
		array('join' => array('counter_useragents', 'counter_sessions.counter_useragent_id', '=', 'counter_useragents.id'))
	)->addCondition(
		array('select' => array(array('counter_useragents.crawler', 'crawler')))
	);
}

$aList = array('0' => Core::_('Counter.visitor'), '1' => Core::_('Counter.crawler'));

$oAdmin_Form_Dataset
	->changeField('counter_useragents.crawler', 'type', 8)
	->changeField('counter_useragents.crawler', 'list', $aList);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

/**
 * Возвращает ID пользователя по логину, введенному в фильтре
 */
/*function correctSiteuserName($sSiteuserLogin, $oAdmin_Form_Field)
{
	$sSiteuserLogin = trim(strval($sSiteuserLogin));
	if (Core::moduleIsActive('siteuser') && $sSiteuserLogin != '')
	{
		$oSiteuser = Core_Entity::factory('Site', CURRENT_SITE)->Siteusers->getByLogin($sSiteuserLogin);

		if (!is_null($oSiteuser))
		{
			return $oSiteuser->id;
		}
	}

	return 0;
}*/

function correctIp($sIp, $oAdmin_Form_Field)
{
	return !is_null($sIp)
		? Core_Str::ip2hex(trim(str_replace(',', '.', $sIp)))
		: '';
}

class Counter_Visitors_Observer
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

Core_Event::attach('Admin_Form_Controller.onAfterSetConditionsAndLimits', array('Counter_Visitors_Observer', 'onAfterSetConditionsAndLimits'));


$oAdmin_Form_Controller->addFilter('siteuser_id', array($oAdmin_Form_Controller, '_filterCallbackSiteuser'));

$oAdmin_Form_Controller
	//->addFilterCallback('siteuser_id', 'correctSiteuserName')
	->addFilterCallback('ip', 'correctIp')
	->execute();