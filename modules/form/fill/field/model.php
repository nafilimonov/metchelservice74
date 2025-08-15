<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form_Fill_Field_Model
 *
 * @package HostCMS
 * @subpackage Form
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Form_Fill_Field_Model extends Core_Entity
{
	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'form_fill' => array(),
		'form_field' => array()
	);

	/**
	 * Get filled field by field id
	 * @param int $form_field_id field id
	 * @return Form_Fill_Field|NULL
	 */
	public function getByFormFieldId($form_field_id)
	{
		$this->queryBuilder()
			//->clear()
			->where('form_field_id', '=', $form_field_id)
			->limit(1);

		$aForm_Fill_Fields = $this->findAll();
		if (isset($aForm_Fill_Fields[0]))
		{
			return $aForm_Fill_Fields[0];
		}

		return NULL;
	}

	/**
	 * Get stdObject for entity and children entities
	 * @return stdObject
	 * @hostcms-event form_fill_field.onBeforeRedeclaredGetStdObject
	 */
	public function getStdObject($attributePrefix = '_')
	{
		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredGetStdObject', $this);

		if ($this->Form_Field->type == 2 && !isset($this->_forbiddenTags['content']) && Core_File::isFile($this->getPath()))
		{
			$this->addXmlTag('content', base64_encode(Core_File::read($this->getPath())));
		}

		return parent::getStdObject($attributePrefix);
	}

	/**
	 * Get attached file href
	 * @return string
	 */
	public function getHref()
	{
		return $this->Form_Fill->getHref() . '/' . $this->id . '.' . Core_File::getExtension($this->value);
	}

	/**
	 * Get attached file path
	 * @return string
	 */
	public function getPath()
	{
		return CMS_FOLDER . $this->getHref();
	}

	/**
	 * Delete file
	 * @return self
	 * @hostcms-event form_fill_field.onAfterDeleteFile
	 */
	public function deleteFile()
	{
		try
		{
			Core_File::delete($this->getPath());
		} catch (Exception $e){}

		Core_Event::notify($this->_modelName . '.onAfterDeleteFile', $this);

		return $this;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event form_fill_field.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		if ($this->Form_Field->type == 2 && Core_File::isFile($this->getPath()))
		{
			$this->deleteFile();
		}

		return parent::delete($primaryKey);
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event form_fill_field.onBeforeGetRelatedSite
	 * @hostcms-event form_fill_field.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Form_Field->Form->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}