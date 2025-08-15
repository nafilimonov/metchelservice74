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

$oController = new Multiload_Controller();

if (Core_Array::getGet('infsysid'))
{
	$infsysid = Core_Array::getGet('infsysid', 0);
	$groups = $oController->GetInfomationSystemGroups($infsysid);
}
if (Core_Array::getGet('shopid'))
{
	$infsysid = Core_Array::getGet(shopid);
	$groups = $oController->GetShopGroups($infsysid);
}

echo '<option value="0">..</option>';
foreach( $groups as $id => $name)
{
	echo '<option value="'.$id.'">'.$name.'</option>';
}
?>