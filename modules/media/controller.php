<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media_Controller
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Media_Controller extends Core_Servant_Properties
{
	/**
	 * Main config
	 */
	static protected $_config = array();

	/**
	 * Get main config
	 * @return array
	 */
	static public function getConfig()
	{
		$aConfig = Core_Config::instance()->get('media_config');
		self::$_config = Core_Array::get($aConfig, CURRENT_SITE, array()) + array(
			'change_filename' => TRUE,
			'create_thumbnails' => TRUE,
			'thumbnail_width' => 250,
			'thumbnail_height' => 250,
			'convert_to_webp' => 0
		);

		return self::$_config;
	}

	/**
	 * Get media item block
	 * @param Media_Item_Model $oMedia_Item
	 * @param object $oEntity
	 * @param object $oObject
	 * @return object
	 */
	static public function getMediaItemBlock($windowId, $oMedia_Item, $oAdmin_Form_Controller, $oEntity = NULL, $oObject = NULL)
	{
		$aConfig = self::getConfig();

		$oMedia_Item = !is_null($oEntity)
			? $oEntity->Media_Item
			: $oMedia_Item;

		$ext = Core_File::getExtension($oMedia_Item->file);

		$type = self::getType($oMedia_Item);

		if ($type == 1)
		{
			$oImage = Core_Html_Entity::factory('Img')
				->src($oMedia_Item->getThumbnailHref());
		}
		else
		{
			$oImage = Core_Html_Entity::factory('I')
				->class(Core_File::getIcon($oMedia_Item->file));
		}

		$filepath = $oMedia_Item->getFilePath();

		$attr = mb_strtoupper($ext);

		$picsize = Core_Image::instance()->getImageSize($filepath);

		if ($picsize)
		{
			$attr .= ' — ' . $picsize['width'] . ' × ' . $picsize['height'];
		}
		else
		{
			$size = Core_File::filesize($filepath);

			$attr .= ' — ' . Core_Str::getTextSize($size);
		}

		$oImageWrapper = Admin_Form_Entity::factory('Div')
			->class("media-item-file media-item-file-type{$type}")
			->style("min-width: {$aConfig['thumbnail_width']}px; height: {$aConfig['thumbnail_height']}px;")
			->add($oImage)
			->add(
				$oActions = Admin_Form_Entity::factory('Div')
					->class('media-item-file-actions')
			);

		if (is_null($oObject) && is_null($oEntity))
		{
			$oImageWrapper->add(
				Admin_Form_Entity::factory('Input')
					->type('checkbox')
					->class('form-control select-file')
					->divAttr(array('class' => ''))
					->id("check_1_{$oMedia_Item->id}")
			);
		}

		if (!is_null(Core_Array::getGet('showMediaModal')))
		{
			$entity_id = Core_Array::getGet('entity_id', 0, 'int');
			$entity_type = Core_Array::getGet('entity_type', '', 'trim');

			$parentWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('parentWindowId', '', 'str'));
			$window = $parentWindowId ? $parentWindowId : $oAdmin_Form_Controller->getWindowId();

			$modalWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('modalWindowId', '', 'str'));

			$oImageWrapper->add(
				Admin_Form_Entity::factory('Div')
					->class('select-file-wrapper')
					->add(
						Core_Html_Entity::factory('I')
							->class('fa-solid fa-check')
							->data('id', $oMedia_Item->id)
							->data('type', $entity_type)
							->data('entity-id', $entity_id)
							->onclick("$.selectMediaFile(this, '{$window}', '{$modalWindowId}')")
					)
			);
		}

		//$oAdmin_Language = $oAdmin_Form_Controller->getAdminLanguage();

		$oAdmin_Form = $oAdmin_Form_Controller->getAdminForm();
		$aAdmin_Form_Actions = $oAdmin_Form->Admin_Form_Actions->findAll();

		foreach ($aAdmin_Form_Actions as $key => $oAdmin_Form_Action)
		{
			if (!in_array($oAdmin_Form_Action->name, array('edit', 'markDeleted')))
			{
				continue;
			}

			$datasetKey = 1;
			$entityKey = $oMedia_Item->id;

			$onclick = !is_null($oObject)
				? $oAdmin_Form_Controller->getAdminActionModalLoad(array(
					'path' => $oAdmin_Form_Controller->getPath(), 'action' => $oAdmin_Form_Action->name,
					'operation' => 'modal',
					'datasetKey' => $datasetKey, 'datasetValue' => $entityKey,
					'width' => '90%'
				))
				: $oAdmin_Form_Controller->getAdminActionLoadAjax(array(
					'path' => $oAdmin_Form_Controller->getPath(), 'action' => $oAdmin_Form_Action->name,
					'datasetKey' => $datasetKey, 'datasetValue' => $entityKey
				));

			// --------------
			switch ($oAdmin_Form_Action->name)
			{
				case 'edit':
					if (is_null($oObject))
					{
						//$onclick = preg_replace('/parentWindowId=(.*?)[&\']/', '', $onclick);
						$onclick = preg_replace('/parentWindowId=/iu', 'tmpMediaSource=', $onclick);

						$oActions->add(Core_Html_Entity::factory('Div')
							->class('media-item-file-action-wrapper edit')
							->add(
								Core_Html_Entity::factory('I')
									->class('fa fa-pen media-item-file-action media-item-file-edit')
									->onclick($onclick)
							)
						);
					}
				break;
				case 'markDeleted':
					if (!is_null($oObject))
					{
						$modelName = $oObject->getModelName();
						$deleteOnclick = "$.removeMediaFile({$oMedia_Item->id}, {$oObject->id}, '{$modelName}', '{$windowId}')";
					}
					else
					{
						$deleteOnclick = "res = confirm(i18n['confirm_delete']); if (res) { {$onclick} }";
					}

					$oActions->add(Core_Html_Entity::factory('Div')
						->class('media-item-file-action-wrapper delete')
						->add(
							Core_Html_Entity::factory('I')
								->class('fa fa-trash media-item-file-action media-item-file-delete')
								->onclick($deleteOnclick)
						)
					);
				break;
			}
		}

		$oDiv = Admin_Form_Entity::factory('Div')
			->class('media-item')
			->add($oImageWrapper)
			->add(
				$oInfo = Admin_Form_Entity::factory('Div')
					->class('media-item-info')
					->add(
						$oAttributes = Admin_Form_Entity::factory('Div')
							->class('media-attributes-wrapper')
							->add(
								Admin_Form_Entity::factory('Div')
									->class('media-attributes-name')
									->title($oMedia_Item->name)
									->value($oMedia_Item->name)
							)
					)
			);

		if ($oMedia_Item->file != '')
		{
			$oAttributes->add(
				Admin_Form_Entity::factory('Div')
					->class('media-attributes-size')
					->value($attr)
			);

			$oInfo->add(
				Admin_Form_Entity::factory('Div')
					->class('media-attributes-type')
					->add(
						Core_Html_Entity::factory('Span')
							->class("badge badge-media-type badge-media-type{$type} margin-left-5")
							->value(Core::_('Media_Item.type' . $type))
					)
			);
		}

		if (!is_null($oObject) && !is_null($oEntity))
		{
			$oDiv->add(
				Admin_Form_Entity::factory('Input')
					->type('hidden')
					->name("media_{$oObject->id}_{$oMedia_Item->id}")
					->value($oEntity->sorting)
					->divAttr(array('class' => ''))
			);
		}

		return $oDiv;
	}

	/**
	 * Get file type
	 * @param Media_Item_Model $oMedia_Item
	 * @return int
	 */
	static public function getType(Media_Item_Model $oMedia_Item)
	{
		$type = 0;

		if ($oMedia_Item->file != '')
		{
			$ext = Core_File::getExtension($oMedia_Item->file);

			switch ($ext)
			{
				case 'jpeg':
				case 'jpg':
				case 'png':
				case 'gif':
				case 'webp':
					$type = 1;
				break;
				case 'mpeg':
				case 'mp4':
				case 'mov':
				case 'wmv':
				case 'avi':
					$type = 2;
				break;
				case 'mp3':
				case 'wav':
				case 'ogg':
				case 'aac':
					$type = 3;
				break;
				case 'txt':
				case 'csv':
				case 'zip':
				case 'pdf':
				case 'doc':
				case 'docx':
				case 'ppt':
				case 'pptx':
				case 'xls':
				case 'xlsx':
				case 'json':
					$type = 4;
				break;
			}
		}

		return $type;
	}
}