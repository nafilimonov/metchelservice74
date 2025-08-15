<?php
/**
* Модуль мультизагрузки картинок Copyright © 2010-2011 ООО "Интернет-Эксперт" http://www.internet-expert.ru
*
* Версия для HostCMS v.6x
* @author KAD
* http://www.artemkuts.ru/
* artem.kuts@gmail.com
*/

require_once('../../../bootstrap.php');

header('Content-Type: text/html; charset=UTF-8');

$module_name = "multiload";
Core_Auth::authorization($module_name);

$informationsystem_id = Core_Array::getGet('infsysid', 0);
$shop_id = Core_Array::getGet('shopid', 0);
$shop_group_id = Core_Array::getGet('shopgroup', 0);

$oController = new Multiload_Controller();

if ($informationsystem_id)
{
	$props = $oController->GetInfomationSystemItemProperties($informationsystem_id);
}
if ($shop_id)
{
	$props = $oController->GetShopItemProperties($shop_id, $shop_group_id);
}
echo '<option value="0">..</option>';
foreach( $props as $id => $name)
{
	echo '<option value="'.$id.'">'.$name.'</option>';
}
?>