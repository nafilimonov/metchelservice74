<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media_Item_Model
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Media_Item_Model extends Core_Entity
{
	/**
	 * Model name
	 * @var mixed
	 */
	protected $_modelName = 'media_item';

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'media_item_format' => array(),
		'media_informationsystem_group' => array(),
		'media_informationsystem_item' => array(),
		'media_shop_group' => array(),
		'media_shop_item' => array(),
		'media_structure' => array()
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'media_group' => array(),
		'site' => array(),
		'user' => array()
	);

	/**
	 * Forbidden tags. If list of tags is empty, all tags will show.
	 * @var array
	 */
	protected $_forbiddenTags = array(
		'site_id',
		'deleted',
		'user_id'
	);

	/**
	 * CDN path
	 * @var string
	 */
	protected $_cdn = '';

	/**
	 * Set CDN path
	 * @param string $cdn
	 * @return self
	 */
	public function setCDN($cdn)
	{
		$this->_cdn = $cdn;
		return $this;
	}

	/**
	 * Get CDN path
	 * @return string
	 */
	public function getCDN()
	{
		return $this->_cdn;
	}

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (is_null($id) && !$this->loaded())
		{
			$oUser = Core_Auth::getCurrentUser();
			$this->_preloadValues['user_id'] = is_null($oUser) ? 0 : $oUser->id;
			$this->_preloadValues['site_id'] = defined('CURRENT_SITE') ? CURRENT_SITE : 0;
			$this->_preloadValues['datetime'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	 * Get href to the item dir
	 * @return string
	 */
	public function getHref()
	{
		return '/' . $this->Site->uploaddir . "media_" . intval($this->site_id) . '/' . Core_File::getNestingDirPath($this->id, $this->Site->nesting_level) . '/' . $this->id . '/';
	}

	/**
	 * Get item path include CMS_FOLDER
	 * @return string
	 */
	public function getPath()
	{
		return CMS_FOLDER . $this->Site->uploaddir . "media_" . intval($this->site_id) . '/' . Core_File::getNestingDirPath($this->id, $this->Site->nesting_level) . '/' . $this->id . '/';
	}

	/**
	 * Get item small image path
	 * @return string
	 */
	public function getFilePath()
	{
		return $this->getPath() . $this->file;
	}

	/**
	 * Get item small image href
	 * @return string
	 */
	public function getFileHref()
	{
		return $this->getHref() . rawurlencode($this->file);
	}

	/**
	 * Get thumbnail name
	 * @return string
	 */
	public function getThumbnailName()
	{
		return "thumbnail-" . $this->file;
	}

	/**
	 * Get item small image path
	 * @return string
	 */
	public function getThumbnailPath()
	{
		return $this->getPath() . $this->getThumbnailName();
	}

	/**
	 * Get item small image href
	 * @return string
	 */
	public function getThumbnailHref()
	{
		return $this->getHref() . rawurlencode($this->getThumbnailName());
	}

	/**
	 * Save original file
	 * @param string $fileSourcePath file to upload
	 */
	public function saveOriginalFile($filename, $fileSourcePath)
	{
		$this->deleteOriginalFile();

		$ext = Core_File::getExtension($filename);

		$aConfig = Media_Controller::getConfig();

		if (isset($aConfig['change_filename']) && $aConfig['change_filename'])
		{
			$filename = "media{$this->id}." . $ext;
		}

		$this->file = $filename;
		$this->size = Core_File::filesize($fileSourcePath);
		$this->save();

		$targetFilePath = $this->getFilePath();
		Core_File::upload($fileSourcePath, $targetFilePath);

		$type = Media_Controller::getType($this);

		if ($type == 1)
		{
			$picsize = Core_Image::instance()->getImageSize($targetFilePath);

			if ($picsize)
			{
				// throw new Core_Exception("Get the size of an image error.");
				$this->width = $picsize['width'];
				$this->height = $picsize['height'];
				$this->save();
			}

			if (isset($aConfig['create_thumbnails']) && $aConfig['create_thumbnails'])
			{
				if (Core_File::isValidExtension($filename, Core_File::getResizeExtensions()))
				{
					Core_Image::instance()->resizeImage($targetFilePath, $aConfig['thumbnail_width'], $aConfig['thumbnail_height'], $this->getThumbnailPath(), NULL, TRUE);
				}
			}
		}
	}

	/**
	 * Delete original file
	 * @return self
	 * @hostcms-event media_item.onAfterDeleteFile
	 */
	public function deleteOriginalFile()
	{
		try
		{
			Core_File::delete($this->getFilePath());
			Core_File::delete($this->getThumbnailPath());
		} catch (Exception $e) {}

		Core_Event::notify($this->_modelName . '.onAfterDeleteOriginalFile', $this);

		$this->file = '';
		$this->save();

		return $this;
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function nameBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$object = $this;

		$link = $oAdmin_Form_Field->link;
		$onclick = $oAdmin_Form_Field->onclick;

		$link = $oAdmin_Form_Controller->doReplaces($oAdmin_Form_Field, $object, $link);
		$onclick = $oAdmin_Form_Controller->doReplaces($oAdmin_Form_Field, $object, $onclick);

		$oCore_Html_Entity_Div = Core_Html_Entity::factory('Div');

		$oCore_Html_Entity_Div
			->class('d-flex align-items-center')
			->value(htmlspecialchars($object->name));

		$type = Media_Controller::getType($this);

		$oCore_Html_Entity_Div->add(Core_Html_Entity::factory('Span')
			->class("badge badge-media-type badge-media-type{$type} margin-left-5")
			->value(Core::_('Media_Item.type' . $type))
		);

		$oCore_Html_Entity_Div->execute();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function imgBackend()
	{
		if (is_file($this->getThumbnailPath()))
		{
			$srcImg = $this->getThumbnailHref();

			$dataContent = '<img class="backend-preview" src="' . $srcImg . '?rand=' . time() . '" />';

			return '<img data-toggle="popover" data-trigger="hover" data-html="true" data-placement="top" data-content="' . htmlspecialchars($dataContent) . '" class="backend-thumbnail" src="' . $srcImg . '?rand=' . time() . '" />';
		}
		else
		{
			return '<i class="fa-solid fa-photo-film"></i>';
		}
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event media_item.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Media_Item_Formats->deleteAll(FALSE);
		$this->Media_Informationsystem_Groups->deleteAll(FALSE);
		$this->Media_Informationsystem_Items->deleteAll(FALSE);
		$this->Media_Shop_Groups->deleteAll(FALSE);
		$this->Media_Shop_Items->deleteAll(FALSE);
		$this->Media_Structures->deleteAll(FALSE);

		$this->deleteOriginalFile();

		// Удаляем директорию информационного элемента
		$this->deleteDir();

		return parent::delete($primaryKey);
	}

	/**
	 * Delete item's directory for files
	 * @return self
	 */
	public function deleteDir()
	{
		if (Core_File::isDir($this->getPath()))
		{
			try
			{
				Core_File::deleteDir($this->getPath());
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Get XML for entity and children entities
	 * @return string
	 * @hostcms-event media_item.onBeforeRedeclaredGetXml
	 */
	public function getXml()
	{
		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredGetXml', $this);

		$this->_prepareData();

		return parent::getXml();
	}

	/**
	 * Get stdObject for entity and children entities
	 * @return stdObject
	 * @hostcms-event media_item.onBeforeRedeclaredGetStdObject
	 */
	public function getStdObject($attributePrefix = '_')
	{
		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredGetStdObject', $this);

		$this->_prepareData();

		return parent::getStdObject($attributePrefix);
	}

	/**
	 * Prepare entity and children entities
	 * @return self
	 */
	protected function _prepareData()
	{
		$this->clearXmlTags();

		$this->addXmlTag('dir', $this->_cdn . $this->getHref());
		$this->addXmlTag('thumbnail', $this->getThumbnailName());

		if ($this->type == 1)
		{
			$aMedia_Item_Formats = $this->Media_Item_Formats->findAll();
			foreach ($aMedia_Item_Formats as $oMedia_Item_Format)
			{
				$this->addEntity($oMedia_Item_Format);
			}
		}
		else
		{
			$this
				->addForbiddenTag('width')
				->addForbiddenTag('height');
		}

		return $this;
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event media_item.onBeforeGetRelatedSite
	 * @hostcms-event media_item.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}