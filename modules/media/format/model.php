<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media_Format_Model
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Media_Format_Model extends Core_Entity
{
	/**
	 * Model name
	 * @var mixed
	 */
	protected $_modelName = 'media_format';

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'media_item_format' => array()
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array(),
		'user' => array()
	);

	/**
	 * Forbidden tags. If list of tags is empty, all tags will show.
	 * @var array
	 */
	protected $_forbiddenTags = array(
		'deleted',
		'user_id'
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'width' => 800,
		'height' => 800,
		'watermark_default_position_x' => '50%',
		'watermark_default_position_y' => '100%',
		'preserve_aspect_ratio' => 1,
		'watermark_file' => '',
	);

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
		}
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function nameBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		if ($this->change_format != '')
		{
			echo ' <span class="badge badge-primary margin-left-5">' . htmlspecialchars(strtoupper($this->change_format)) . '</span>';
		}
	}

	/**
	 * Get shop href
	 * @return string
	 */
	public function getHref()
	{
		return $this->Site->uploaddir . "media_" . intval($this->site_id);
	}

	/**
	 * Get shop path include CMS_FOLDER
	 * @return string
	 */
	public function getPath()
	{
		return CMS_FOLDER . $this->getHref();
	}

	/**
	 * Get watermark file path
	 * @return string|NULL
	 */
	public function getWatermarkFilePath()
	{
		return $this->watermark_file != ''
			? $this->getPath() . '/watermarks/' . $this->watermark_file
			: NULL;
	}

	/**
	 * Get watermark file href
	 * @return string
	 */
	public function getWatermarkFileHref()
	{
		return '/' . $this->getHref() . '/watermarks/' . $this->watermark_file;
	}

	/**
	 * Save watermark file
	 * @param string $fileSourcePath file to upload
	 */
	public function saveWatermarkFile($fileSourcePath)
	{
		$this->watermark_file = 'watermark_' . $this->id . '.png';
		$this->save();
		Core_File::upload($fileSourcePath, $this->getWatermarkFilePath());
	}

	/**
	 * Delete watermark file
	 * @return self
	 * @hostcms-event media_format.onAfterDeleteFile
	 */
	public function deleteWatermarkFile()
	{
		$filePath = $this->getWatermarkFilePath();

		if (Core_File::isFile($filePath))
		{
			try
			{
				Core_File::delete($filePath);
			} catch (Exception $e) {}

			Core_Event::notify($this->_modelName . '.onAfterDeleteWatermarkFile', $this);

			$this->watermark_file = '';
			$this->save();
		}

		return $this;
	}

	/**
	 * Save image
	 * @param Media_Item_Model $oMedia_Item
	 * @param boolean $bForceUpdate
	 * @return self
	 */
	public function saveImage(Media_Item_Model $oMedia_Item, $bForceUpdate = TRUE)
	{
		$oMedia_Item_Format = $oMedia_Item->Media_Item_Formats->getByMedia_format_id($this->id, FALSE);

		if (!$oMedia_Item_Format || $bForceUpdate)
		{
			$filepath = $oMedia_Item->getFilePath();

			$this->change_format != ''
				&& $param['large_image_output_format'] = $this->change_format;

			$extSource = Core_File::getExtension($filepath);

			$ext = $this->change_format != ''
				? $this->change_format
				: $extSource;

			$filename = basename($oMedia_Item->file, '.' . $extSource) . '-' . $this->id . '.' . $ext;

			$target_filepath = $oMedia_Item->getPath() . $filename;

			// Путь к файлу-источнику большого изображения;
			$param['large_image_source'] = $filepath;

			// Оригинальное имя файла большого изображения
			$param['large_image_name'] = $oMedia_Item->file;

			// Путь к создаваемому файлу большого изображения;
			$param['large_image_target'] = $target_filepath;

			// Значение максимальной ширины большого изображения
			$param['large_image_max_width'] = $this->width;

			// Значение максимальной высоты большого изображения
			$param['large_image_max_height'] = $this->height;

			// Сохранять пропорции изображения для большого изображения
			$param['large_image_preserve_aspect_ratio'] = $this->preserve_aspect_ratio;

			if ($this->watermark_file != '')
			{
				// Путь к файлу с "водяным знаком"
				$param['watermark_file_path'] = $this->getWatermarkFilePath();

				// Позиция "водяного знака" по оси X
				$param['watermark_position_x'] = $this->watermark_default_position_x;

				// Позиция "водяного знака" по оси Y
				$param['watermark_position_y'] = $this->watermark_default_position_y;

				// Наложить "водяной знак" на большое изображение (true - наложить (по умолчанию), FALSE - не наложить);
				$param['large_image_watermark'] =TRUE;
			}

			$result = Core_File::adminUpload($param);

			if ($result['large_image'])
			{
				if (is_null($oMedia_Item_Format))
				{
					$oMedia_Item_Format = Core_Entity::factory('Media_Item_Format');
					$oMedia_Item_Format->media_item_id = $oMedia_Item->id;
					$oMedia_Item_Format->media_format_id = $this->id;
				}

				$oMedia_Item_Format->file = $filename;

				$picsize = Core_Image::instance()->getImageSize($target_filepath);

				if ($picsize)
				{
					//throw new Core_Exception("Get the size of an image error.");
					$oMedia_Item_Format->width = $picsize['width'];
					$oMedia_Item_Format->height = $picsize['height'];
				}
				else
				{
					$oMedia_Item_Format->width = $oMedia_Item_Format->height = 0;
				}

				clearstatcache();

				$oMedia_Item_Format->size = Core_File::filesize($target_filepath);
				$oMedia_Item_Format->save();
			}
		}

		return $this;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event media_format.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		// Delete watermark
		$this->deleteWatermarkFile();

		$this->Media_Item_Formats->deleteAll(FALSE);

		return parent::delete($primaryKey);
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event media_format.onBeforeGetRelatedSite
	 * @hostcms-event media_format.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}