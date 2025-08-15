<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Email_Attachment_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Siteuser_Email_Attachment_Model extends Core_Entity
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
		'siteuser_email' => array(),
		'user' => array()
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
		}
	}

	/**
	 * Get attachment file path
	 * @return string
	 */
	public function getFilePath()
	{
		return $this->Siteuser_Email->getPath() . $this->filename;
	}

	/**
	 * Get attachment file href
	 * @return string
	 */
	public function getFileHref()
	{
		return '/' . $this->Siteuser_Email->getHref() . rawurlencode($this->filename);
	}

	/**
	 * Delete attachment file
	 * @return self
	 * @hostcms-event siteuser_email_attachment.onAfterDeleteFile
	 */
	public function deleteFile()
	{
		try
		{
			$path = $this->getFilePath();
			Core_File::isFile($path) && Core_File::delete($path);
		} catch (Exception $e) {}

		Core_Event::notify($this->_modelName . '.onAfterDeleteFile', $this);

		return $this;
	}

	/**
	 * Save attachment file
	 * @param string $fileSourcePath source path
	 * @param string $fileName file name
	 * @return self
	 */
	public function saveFile($fileSourcePath, $fileName)
	{
		$this->Siteuser_Email->createDir();

		$fileName = Core_File::filenameCorrection($fileName);
		$dir = $this->Siteuser_Email->getPath();

		// Delete old file
		if ($this->filename != '' && Core_File::isFile($dir . $this->filename))
		{
			$this->deleteFile();
		}

		$this->name = $fileName;
		$this->filename = $this->id . '.' . Core_File::getExtension($fileName);
		$this->save();

		Core_File::upload($fileSourcePath, $dir . $this->filename);

		return $this;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event siteuser_email_attachment.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->deleteFile();

		return parent::delete($primaryKey);
	}

	/**
	 * Get attachments size
	 * @return string
	 */
	public function getTextSize()
	{
		$size = Core_File::filesize($this->getFilePath());

		return !is_null($size)
			? Core_Str::getTextSize($size)
			: '';
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event siteuser_email_attachment.onBeforeGetRelatedSite
	 * @hostcms-event siteuser_email_attachment.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Siteuser_Email->Siteuser->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}