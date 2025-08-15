<?php
/**
 * Advertisement.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../../bootstrap.php');

Core_Auth::authorization($sModule = 'advertisement');

// Код формы
$iAdmin_Form_Id = 47;
$sAdminFormAction = '/{admin}/advertisement/advertisement/statistic/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Идентификатор баннера
$iAdvertisement = intval(Core_Array::getGet('advertisement_id', 0));
$oAdvertisement = Core_Entity::factory('Advertisement')->find($iAdvertisement);

//Экспорт в CSV
if (!is_null(Core_Array::getGet('export')) && $iAdvertisement)
{
	header("Pragma: public");
	header("Content-Description: File Transfer");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename = " . 'export_advetisment_' .date("Y_m_d_H_i_s").'.csv'. ";");
	header("Content-Transfer-Encoding: binary");

	echo 'Day;Showed;Clicks;CTR' . "\n";

	$oAdvertisement_Statistics = $oAdvertisement->Advertisement_Statistics;
	$aAdvertisement_Statistics = $oAdvertisement_Statistics->findAll();
	foreach ($aAdvertisement_Statistics as $oAdvertisement_Statistic)
	{
		$ctr = round($oAdvertisement_Statistic->clicks/$oAdvertisement_Statistic->showed * 100, 2);
		echo '"'. $oAdvertisement_Statistic->date, '";', $oAdvertisement_Statistic->showed, ';', $oAdvertisement_Statistic->clicks, ';', '"' . $ctr . '"', "\n";
	}

	exit();
}

$sTitle = Core::_('Advertisement_Statistic.title', $oAdvertisement->name, FALSE);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title($sTitle)
	->pageTitle($sTitle);

$sAdvertisementGroupPath = '/{admin}/advertisement/index.php';
$sAdvertisementPath = '/{admin}/advertisement/advertisement/index.php';

// Строка навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Advertisement_Group.group_link'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($sAdvertisementGroupPath, NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($sAdvertisementGroupPath, NULL, NULL, '')
		)
)->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Advertisement.banners_list'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($sAdvertisementPath, NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($sAdvertisementPath, NULL, NULL, '')
		)
)->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name($sTitle)
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, "advertisement_id={$iAdvertisement}")
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, "advertisement_id={$iAdvertisement}")
		)
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

ob_start();
$oCore_Html_Entity_Img = new Core_Html_Entity_Img();

$aHostcms = Core_Array::getRequest('hostcms', array());

$aImgAdditional = array();
$aImgAdditional[] = "admin_form_filter_from_181=" . htmlspecialchars(Core_Array::getRequest('admin_form_filter_from_181', '', 'trim'));
$aImgAdditional[] = "admin_form_filter_to_181=" . htmlspecialchars(Core_Array::getRequest('admin_form_filter_to_181', '', 'trim'));

foreach ($aHostcms as $key => $value)
{
	!is_array($value) && $aImgAdditional[] = "hostcms[" . htmlspecialchars($key)  . "]=" . htmlspecialchars($value);
}

$oCore_Html_Entity_Img
	->src(Admin_Form_Controller::correctBackendPath("/{admin}/advertisement/advertisement/statistic/img.php?advertisement_id={$iAdvertisement}&rand=") . rand() . "&" . implode('&', $aImgAdditional))
	->execute();

$oAdmin_Form_Entity_Code = Admin_Form_Entity::factory('Code');
$oAdmin_Form_Entity_Code
	->html(ob_get_clean());

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Code);

// Источник данных
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Advertisement_Statistic')
);

// Ограничение по баннеру
$oAdmin_Form_Dataset
->addCondition(
	array('select' => array('advertisement_statistics.*', array('ROUND(SUM(clicks)/SUM(showed) * 100, 2)', 'ctr')))
)->addCondition(
	array('where' => array('advertisement_id', '=', $iAdvertisement))
)->addCondition(
	array('groupBy' => array('id'))
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$oAdmin_Form_Controller->execute();
