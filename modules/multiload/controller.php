<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

 /**
* Модуль мультизагрузки картинок
*
* Версия для HostCMS v.6x
* @author KAD
* http://www.artemkuts.ru/
* artem.kuts@gmail.com
*/
 
class MultiLoad_Controller extends Core_Controller
{
 
	
	public function __construct()
	{
		parent::__construct(Core_Entity::factory('informationsystem'));
	}
		
		

	function GetInformationSystemId($group_id){
		return Core_Entity::factory("Informationsystem_group", $group_id)->informationsystem_id;
    }
	
    function GetInfoInformationSystem($infsysid){
/*
        $result = $this->InformationSystem->GetInformationSystem($infsysid);
        return $result;
*/		
    }
    function GetInfomationSystemGroupPath($group_id){
		return Core_Entity::factory("Informationsystem_group", $group_id)->getGroupPath();
    }
		
/*
* получаем информационные системы
*/	
	function GetInfomationSystems($site_id)
	{
		$infsyss = array();
		$oInfsyss = Core_Entity::factory("Informationsystem")->getAllBysite_id($site_id);
		
		foreach ($oInfsyss as $oInfsys)
		{
			$infsyss[$oInfsys->id] = $oInfsys->name;
		}		
		
		return $infsyss;
	}

/*
* получаем группы
*/
	function GetInfomationSystemGroups($informationsystem_id)
	{
		$oEditAction = Core_Entity::factory('Admin_Form', 12)
			->Admin_Form_Actions
			->getByName('edit');
		$oEditController = new Informationsystem_Item_Controller_Edit($oEditAction);

		$groups = $oEditController->fillInformationsystemGroup($informationsystem_id, 0);

		/*	
		$groups = array();
		$oGroups = Core_Entity::factory("Informationsystem_group")->getAllByinformationsystem_id($infsysid);
		
		foreach ($oGroups as $oGroup)
		{
			$groups[$oGroup->id] = $oGroup->name;
		}*/
		
		return $groups;
	}
	
/*
* получаем элементы
*/	
	function GetInfomationSystemItems($informationsystem_id, $informationsystem_group_id)
	{
		$aInformationsystemItems = array();
		$oInformationsystemItems = Core_Entity::factory("Informationsystem_item");
		
		if ($informationsystem_id != 0)
		{
			$oInformationsystemItems->queryBuilder()->where("informationsystem_id", "=", $informationsystem_id);
		}
		$oInformationsystemItems->queryBuilder()
			->where("informationsystem_group_id", "=", $informationsystem_group_id)
			->orderby("datetime", "DESC")
			;
			
		$oInformationsystemItems = $oInformationsystemItems->findAll();	
		
		foreach ($oInformationsystemItems as $oInformationsystemItem)
		{
			$aInformationsystemItems[$oInformationsystemItem->id] = "[" . $oInformationsystemItem->id . "] " .$oInformationsystemItem->name;
		}		
		
		return $aInformationsystemItems;
	}
	
/*
* получаем доп. свойства информ. элементов типа "Файл"
*/	
	function GetInfomationSystemItemProperties($informationsystem_id)
	{
		$aProperties = array();
		$linkedObject = Core_Entity::factory('Informationsystem_Item_Property_List', $informationsystem_id);
		
		$oProperties = $linkedObject->Properties;
		$oProperties->queryBuilder()->orderby("id");
		$oProperties = $oProperties->findAll();
		
		foreach ($oProperties as $oProperty)
		{
			if ($oProperty->type == 2)
			{
				$aProperties[$oProperty->id] = $oProperty->name;
			}
		}
		
		return $aProperties;
	}
	
	
/*
* получаем магазины
*/	
	function GetShops($site_id)
	{
		$aShops = array();
		$aoShops = Core_Entity::factory("shop")->getAllBysite_id($site_id);
		
		foreach ($aoShops as $oShop)
		{
			$aShops[$oShop->id] = $oShop->name;
		}		
		
		return $aShops;
	}

/*
* получаем группы
*/
	function GetShopGroups($shop_id)
	{
		$oEditAction = Core_Entity::factory('Admin_Form', 12)
			->Admin_Form_Actions
			->getByName('edit');
		$oEditController = new Shop_Item_Controller_Edit($oEditAction);

		$groups = $oEditController->fillShopGroup($shop_id, 0);

		/*	
		$groups = array();
		$oGroups = Core_Entity::factory("Informationsystem_group")->getAllByinformationsystem_id($infsysid);
		
		foreach ($oGroups as $oGroup)
		{
			$groups[$oGroup->id] = $oGroup->name;
		}*/
		
		return $groups;
	}
	
/*
* получаем элементы
*/	
	function GetShopItems($shop_id, $shop_group_id)
	{
		$aShopItems = array();
		$aoShopItems = Core_Entity::factory("shop_item");
		
		if ($shop_id != 0)
		{
			$aoShopItems->queryBuilder()->where("shop_id", "=", $shop_id);
		}
		$aoShopItems->queryBuilder()
			->where("shop_group_id", "=", $shop_group_id)
			->orderby("datetime", "DESC")
			;
			
		$aoShopItems = $aoShopItems->findAll();	
		
		foreach ($aoShopItems as $oShopItem)
		{
			$aShopItems[$oShopItem->id] = "[" . $oShopItem->id . "] " .$oShopItem->name;
		}		
		
		return $aShopItems;
	}

/*
* получаем доп. свойства информ. элементов типа "Файл"
*/	
	function GetShopItemProperties($shop_id, $shop_group_id)
	{
		$aProperties = array();
		
		$linkedObject = Core_Entity::factory('Shop_Item_Property_List', $shop_id);
		$aoProperties = $linkedObject->getPropertiesForGroup($shop_group_id);
		
		/*
		$oProperties = $linkedObject->Properties;
		$oProperties->queryBuilder()->orderby("id");
		$aoProperties = $oProperties->findAll();
		*/	
		
		foreach ($aoProperties as $oProperty)
		{
			if ($oProperty->type == 2)
			{
				$aProperties[$oProperty->id] = $oProperty->name;
			}
		}
		
		return $aProperties;
	}
	
/*
* Обновление
*/
	
	public function load_json_utf8($filename)
	{
		$content_str = file_get_contents($filename);
		$content_str=substr(
			$content_str,      
			min(
				strpos($content_str.'[','['), 
				strpos($content_str.'{','{')
			)
		);
		return json_decode($content_str);   
	}

	public function update($currentVersion)
	{
		if ($currentVersion < 0)
			$currentVersion = 0;
			
		$oldVersion = $currentVersion;
		$oVersions = self::load_json_utf8("http://artemkuts.ru/server/getVersions/?mymodule_id=2");
		$current = '';
		
		foreach ($oVersions as $oVersion)
		{
			if ($oVersion->version > $currentVersion) 
			{ //выполняем обновление
			
				$f_name = CMS_FOLDER . basename($oVersion->file_path);
				$dump_fname = CMS_FOLDER.'/dump.sql';
				if (!is_file($f_name))
				{
					Core_File::write($f_name,
						file_get_contents($oVersion->file_path),
						777
					);
					
					$Core_Tar = new Core_Tar($f_name);
					$Core_Tar->extractModify(CMS_FOLDER, CMS_FOLDER);
					@unlink($f_name);
					
					// импортируем базу
					if (is_file($dump_fname))
					{
						$dumpfile = Core_File::read($dump_fname);
						if ( @!Sql_Controller::instance()->execute($dumpfile))
							echo "Ошибка импорта в базу данных";

						@unlink($dump_fname);	
					}
				}
				$currentVersion = $oVersion->version;
				$current = $oVersion;
			}
		}
		
		if ($oldVersion != $currentVersion)
			return $current;
	}
}
