<?php
/**
* Модуль мультизагрузки картинок
*
* Версия для HostCMS v.6x
* @author KAD
* artem.kuts@gmail.com
* Copyright © 2010-2011 ООО "Интернет-Эксперт" http://www.internet-expert.ru
*/

if (isset($_POST['sc'])) {
	$session_name = ini_get('session.name');
	$_COOKIE[$session_name] = $_POST['sc'];
	$_REQUEST[$session_name] = $_POST['sc'];
}

/**
 * Установить идентификатор сайта
 */
 
$module_name = "multiload";
require_once('../../../bootstrap.php');
Core_Auth::authorization($module_name);
$oSite = Core_Entity::factory('Site', CURRENT_SITE);
Core::initConstants($oSite);

//Флаг успешного завершения
$success_flag = 0;

if (!isset($_SERVER['HTTP_CONTENT_TYPE'])) $_SERVER['HTTP_CONTENT_TYPE'] = '';
if (!isset($_SERVER['CONTENT_TYPE'])) $_SERVER['CONTENT_TYPE'] = '';

if((strpos($_SERVER['HTTP_CONTENT_TYPE'], 'multipart/form-data;')!==false||strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data;')!==false)&&$_FILES['Filedata']['size']<10485760) 
{
	
    $filename = trim(mb_convert_encoding($_FILES['Filedata']['name'], 'utf8', mb_detect_encoding($_FILES['Filedata']['name'])));
    $file = $_FILES['Filedata']['tmp_name'];
    if(empty($filename)) {
        $filename = 'foto'.time();
    }	
	// Определяем расширение файла
	$ext = Core_File::getExtension($_FILES['Filedata']['name']);
	
	$type = Core_Array::getPost('loadtype');

	// Интернет - магазин
	if ($type == 2 && Core_Array::getPost('shop_id'))
	{
        $shop_id = Core_Array::getPost('shop_id', 0);
		$shop_group_id = Core_Array::getPost('shop_group_id', 0);
		$shop_item_id = Core_Array::getPost('shop_item_id', 0);
		$shop_prop_id = Core_Array::getPost('shop_prop_id', 0);	
		
		$oShop = Core_Entity::factory('shop', $shop_id);
	
		if ($shop_item_id && $shop_prop_id)
		{
			// Загружаем в доп. свойство
			$oShop_Item = Core_Entity::factory("shop_item", $shop_item_id);
			$oShop_Item->createDir();
			
			$oProperty = Core_Entity::factory('Property', $shop_prop_id);
			
			$oPropertyValue = $oProperty->createNewValue($oShop_Item->id);
			$oPropertyValue->save();
			
			$ext_item_image = pathinfo($_FILES['Filedata']['name'], PATHINFO_EXTENSION);
			
			$large_image = 'property_value_' . $oPropertyValue->id . '.' . $ext;
			$small_image = 'small_' . $large_image;
						
			$oShop_Item->createDir();
			Core_File::upload($file, $oShop_Item->getItemPath() . $large_image);						
			Core_File::copy($oShop_Item->getItemPath() . $large_image, $oShop_Item->getItemPath() . $small_image);
			// Ресайз
			$aspect_ratio = $oShop->preserve_aspect_ratio;
			
			Core_Image::resizeImage(
					$oShop_Item->getItemPath() . $large_image,
					$oProperty->image_large_max_width,
					$oProperty->image_large_max_height,
					$oShop_Item->getItemPath() . $large_image,
					'100',
					1//$aspect_ratio 
				);
			Core_Image::resizeImage(
					$oShop_Item->getItemPath() . $small_image,
					$oProperty->image_small_max_width,
					$oProperty->image_small_max_height,
					$oShop_Item->getItemPath() . $small_image,
					'100',
					$aspect_ratio 
				);
				
			$oPropertyValue->file = $large_image;
			$oPropertyValue->file_small = $small_image;
			$oPropertyValue->file_description = $filename;
			$oPropertyValue->save();
				
			$success_flag = 1;
		}	
	}
	
	// Информационная системы
    if($type == 1 && Core_Array::getPost('information_system_id'))
	{
        $information_system_id = Core_Array::getPost('information_system_id', 0);
		$information_system_group_id = Core_Array::getPost('information_system_group_id', 0);
		$informationsystem_item_id = Core_Array::getPost('information_system_item_id', 0);
		$informationsystem_prop_id = Core_Array::getPost('information_system_prop_id', 0);
				
		$oInformationSystem = Core_Entity::factory('Informationsystem', $information_system_id);
		
		if ($informationsystem_item_id && $informationsystem_prop_id)
		{
			// Загружаем в доп. свойство
			$oInformationSystem_item = Core_Entity::factory("Informationsystem_item", $informationsystem_item_id);
			$oInformationSystem_item->createDir();
			
			$oProperty = Core_Entity::factory('Property', $informationsystem_prop_id);
			
			$oPropertyValue = $oProperty->createNewValue($oInformationSystem_item->id);
			$oPropertyValue->save();
			
			$ext_item_image = pathinfo($_FILES['Filedata']['name'], PATHINFO_EXTENSION);
			
			$large_image = 'property_value_' . $oPropertyValue->id . '.' . $ext;
			$small_image = 'small_' . $large_image;
						
			$oInformationSystem_item->createDir();
			Core_File::upload($file, $oInformationSystem_item->getItemPath() . $large_image);						
			Core_File::copy($oInformationSystem_item->getItemPath() . $large_image, $oInformationSystem_item->getItemPath() . $small_image);
			// Ресайз
			$aspect_ratio = $oInformationSystem->preserve_aspect_ratio;
			
			Core_Image::resizeImage(
					$oInformationSystem_item->getItemPath() . $large_image,
					$oProperty->image_large_max_width,
					$oProperty->image_large_max_height,
					$oInformationSystem_item->getItemPath() . $large_image,
					'100',
					1//$aspect_ratio 
				);
			Core_Image::resizeImage(
					$oInformationSystem_item->getItemPath() . $small_image,
					$oProperty->image_small_max_width,
					$oProperty->image_small_max_height,
					$oInformationSystem_item->getItemPath() . $small_image,
					'100',
					$aspect_ratio 
				);
				
			$oPropertyValue->file = $large_image;
			$oPropertyValue->file_small = $small_image;
			$oPropertyValue->file_description = $filename;
			$oPropertyValue->save();
				
			$success_flag = 1;
		} else
		{
			echo "!{$information_system_group_id}!";
		
			// Загружаем в инф. элемент
			// создаем инф. элемент
			$oInformationSystem_item = Core_Entity::factory("Informationsystem_item");
			$oInformationSystem_item->informationsystem_id = $information_system_id;
			$oInformationSystem_item->informationsystem_group_id = $information_system_group_id;
			$oInformationSystem_item->name = $filename;
			$oInformationSystem_item->save();	
					
			$temp_file = tempnam(CMS_FOLDER . "hostcmsfiles" . DIRECTORY_SEPARATOR . "tmp", "rss") . '.' . $ext;
			Core_File::upload($file, $temp_file);	

			$param = array();

			// Путь к файлу-источнику большого изображения;
			$param['large_image_source'] = $temp_file;

			$large_image = 'information_items_' . $oInformationSystem_item->id . '.' . $ext;
			$small_image = 'small_' . $large_image;

			// Оригинальное имя файла большого изображения
			$param['large_image_name'] = $large_image;
			
			// Оригинальное имя файла малого изображения
			$param['small_image_name'] = $small_image;

			// Путь к создаваемому файлу большого изображения;
			$param['large_image_target'] = $oInformationSystem_item->getItemPath() . Core_File::convertFileNameToLocalEncoding($large_image);

			// Путь к создаваемому файлу малого изображения;
			$param['small_image_target'] = $oInformationSystem_item->getItemPath() . Core_File::convertFileNameToLocalEncoding($small_image);

			// Использовать большое изображение для создания малого
			$param['create_small_image_from_large'] = TRUE;
			$param['watermark_file_path'] = $oInformationSystem->getWatermarkFilePath();
			$param['watermark_position_x'] = $oInformationSystem->watermark_default_position_x;
			$param['watermark_position_y'] = $oInformationSystem->watermark_default_position_y;
			$param['large_image_preserve_aspect_ratio'] = $oInformationSystem->preserve_aspect_ratio;
			$param['small_image_max_width'] = $oInformationSystem->image_small_max_width;
			$param['small_image_max_height'] = $oInformationSystem->image_small_max_height;
			$param['small_image_watermark'] = $oInformationSystem->watermark_default_use_small_image;
			$param['small_image_preserve_aspect_ratio'] = $oInformationSystem->preserve_aspect_ratio_small;
			$param['large_image_max_width'] = $oInformationSystem->image_large_max_width;
			$param['large_image_max_height'] = $oInformationSystem->image_large_max_height;
			$param['large_image_watermark'] = $oInformationSystem->watermark_default_use_large_image;

			$oInformationSystem_item->createDir();

			$result = Core_File::adminUpload($param);

			if ($result['large_image'])
			{
				$oInformationSystem_item->image_large = $large_image;
				$oInformationSystem_item->setLargeImageSizes();
			}

			if ($result['small_image'])
			{
				$oInformationSystem_item->image_small = $small_image;
				$oInformationSystem_item->setSmallImageSizes();
			}

			$oInformationSystem_item->save();
			
			Core_File::delete($temp_file);	
		
		}
			
		$success_flag = 1;
	}

}
echo $success_flag;
?>