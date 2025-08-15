<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media_Item_Format_Model
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Media_Item_Format_Model extends Core_Entity
{
	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Model name
	 * @var mixed
	 */
	protected $_modelName = 'media_item_format';

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'media_item' => array(),
		'media_format' => array()
	);

	/**
	 * Forbidden tags. If list of tags is empty, all tags will show.
	 * @var array
	 */
	protected $_forbiddenTags = array(
		'media_item_id'
	);

	/**
	 * Get shop href
	 * @return string
	 */
	public function getHref()
	{
		return $this->Media_Item->getHref();
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
	 * Get media item format path
	 * @return string
	 */
	public function getFilePath()
	{
		return $this->getPath() . '/' . $this->file;
	}

	/**
	 * Get media item format href
	 * @return string
	 */
	public function getFileHref()
	{
		return $this->getHref() . '/' . rawurlencode($this->file);
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function media_format_idBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$oCore_Html_Entity_Div = Core_Html_Entity::factory('Div');

		$oCore_Html_Entity_Div
			->class('d-flex align-items-center')
			->value(htmlspecialchars($this->Media_Format->name));

		if (is_file($this->Media_Item->getPath() . $this->file))
		{
			$oCore_Html_Entity_Div->add(
				Core_Html_Entity::factory('A')
					->href($this->Media_Item->getHref() . $this->file)
					->target('_blank')
					->add(
						Core_Html_Entity::factory('I')->class('fa fa-external-link margin-left-5')
					)
			);
		}

		$oCore_Html_Entity_Div->execute();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function imgBackend()
	{
		if (is_file($this->Media_Item->getPath() . $this->file))
		{
			$srcImg = $this->Media_Item->getHref() . $this->file;

			$dataContent = '<img class="backend-preview" src="' . $srcImg . '?rand=' . time() . '" />';

			return '<img data-toggle="popover" data-trigger="hover" data-html="true" data-placement="top" data-content="' . htmlspecialchars($dataContent) . '" class="backend-thumbnail" src="' . $srcImg . '?rand=' . time() . '" />';
		}
		else
		{
			return '<i class="fa-solid fa-photo-film"></i>';
		}
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function sizeBackend()
	{
		return Core_Str::getTextSize($this->size);
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event media_item_format.onBeforeRedeclaredDelete
	 * @hostcms-event media_item_format.onAfterDeleteFile
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$filePath = $this->getFilePath();

		if (Core_File::isFile($filePath))
		{
			try
			{
				Core_File::delete($filePath);
			} catch (Exception $e) {}

			Core_Event::notify($this->_modelName . '.onAfterDeleteFile', $this);
		}

		return parent::delete($primaryKey);
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event media_item_format.onBeforeGetRelatedSite
	 * @hostcms-event media_item_format.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Media_Item->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}