<?php
/**
* Модуль мультизагрузки картинок 
*
* Версия для HostCMS v.6x
* @author KAD
* http://www.artemkuts.ru/
* artem.kuts@gmail.com
* Copyright © 2010-2011 ООО "Интернет-Эксперт" http://www.internet-expert.ru
*/

require_once('../../../bootstrap.php');

header('Content-Type: text/html; charset=UTF-8');

$module_name = "multiload";
Core_Auth::authorization($module_name);

$informationsystem_group_id = Core_Array::getGet('infsysgroup', 0);
$informationsystem_id = Core_Array::getGet('infsysid', 0);
$shop_group_id = Core_Array::getGet('shopgroup', 0);
$shop_id = Core_Array::getGet('shopid', 0);

$oController = new Multiload_Controller();

if ($informationsystem_group_id || $informationsystem_id)
{
	$aItems = $oController->GetInfomationSystemItems($informationsystem_id, $informationsystem_group_id);
}

if ($shop_group_id || $shop_id)
{
	$aItems = $oController->GetShopItems($shop_id, $shop_group_id);
}

echo '<option value="0">..</option>';
foreach($aItems as $id => $name)
{
	echo '<option value="'.$id.'">'.$name.'</option>';
}
?>