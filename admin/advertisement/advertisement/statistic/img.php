<?php
/**
 * Advertisement.
 *
 * @package HostCMS
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2019 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
require_once('../../../../bootstrap.php');

Core_Auth::authorization('advertisement');

$advertisement_id = intval(Core_Array::getGet('advertisement_id'));

$formSettings = Core_Array::getRequest('hostcms', array())
	+ array(
		'limit' => 10,
		'current' => 1,
		'sortingfield' => 181,
		'sortingdirection' => 0,
		'action' => NULL,
		'operation' => NULL,
		'checked' => array()
	);

if ($formSettings['current'] < 1)
{
	$formSettings['current'] = 1;
}

$oAdvertisement_Statistic = Core_Entity::factory('Advertisement_Statistic');
$oAdvertisement_Statistic
	->queryBuilder()
	->where('advertisement_id', '=', $advertisement_id)
	->limit($formSettings['limit'])
	->offset($formSettings['limit'] * ($formSettings['current'] - 1))
	->groupBy('id');

$oAdmin_Form_Field = Core_Entity::factory('Admin_Form_Field')->find($formSettings['sortingfield']);
if (!is_null($oAdmin_Form_Field->id))
{
	$sDirection = $formSettings['sortingdirection'] == 0
		? 'ASC'
		: 'DESC';

	$oAdvertisement_Statistic
		->queryBuilder()
		->orderBy($oAdmin_Form_Field->name, $sDirection);
}

$admin_form_filter_from_181 = Core_Array::getGet('admin_form_filter_from_181');
if ($admin_form_filter_from_181)
{
	$oAdvertisement_Statistic
		->queryBuilder()
		->where('date', '>=', Core_Date::date2sql($admin_form_filter_from_181));
}

$admin_form_filter_to_181 = Core_Array::getGet('admin_form_filter_to_181');
if ($admin_form_filter_to_181)
{
	$oAdvertisement_Statistic
	->queryBuilder()
	->where('date', '<=', Core_Date::date2sql($admin_form_filter_to_181));
}

$aAdvertisement_Statistics = $oAdvertisement_Statistic->findAll();

$data = array();
foreach ($aAdvertisement_Statistics as $key => $oAdvertisement_Statistic)
{
	$data[0][$key] = $oAdvertisement_Statistic->showed;
	$data[1][$key] = $oAdvertisement_Statistic->clicks;
	$data['x'][$key] = Core_Date::sql2date($oAdvertisement_Statistic->date);
}

$oCore_Diagram = new Core_Diagram();
$oCore_Diagram
	->legend(array(
		Core::_('Advertisement.viewed_times'), Core::_('Advertisement.visitors')
	))
	->values($data)
	->horizontalOrientation(1)
	->histogram(500, 300);
