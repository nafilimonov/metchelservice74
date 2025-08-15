<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Identity_Provider_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Siteuser_Identity_Provider_Model extends Core_Entity
{
	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array(),
		'user' => array(),
	);

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'siteuser_identity' => array(),
	);

	/**
	 * Sorting type
	 * @var array
	 */
	protected $_sorting = array(
		'siteuser_identity_providers.sorting' => 'ASC'
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'sorting' => 0,
		'active' => 1,
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
	 * Get Siteuser by `identity`
	 * @param string $identity
	 * @return mixed Siteuser_Model|NULL
	 */
	public function getSiteuserByIdentity($identity)
	{
		$oSiteuser_Identities = $this->Siteuser_Identities;
		$oSiteuser_Identities->queryBuilder()
			->select('siteuser_identities.*')
			->join('siteusers', 'siteusers.id', '=', 'siteuser_identities.siteuser_id')
			->where('siteuser_identities.identity', '=', $identity)
			->where('siteusers.deleted', '=', 0)
			->limit(1);

		$aSiteuser_Identity = $oSiteuser_Identities->findAll();

		return isset($aSiteuser_Identity[0])
			? $aSiteuser_Identity[0]->Siteuser
			: NULL;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return Core_Entity
	 * @hostcms-event siteuser_identity_provider.onBeforeRedeclaredDelete
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
	 * Get directory href
	 * @return string
	 */
	public function getDirHref()
	{
		return $this->Site->uploaddir . 'providers' . '/';
	}

	/**
	 * Get directory path
	 * @return string
	 */
	public function getDirPath()
	{
		return CMS_FOLDER . $this->getDirHref();
	}

	/**
	 * Get XML for entity and children entities
	 * @return string
	 * @hostcms-event siteuser_identity_provider.onBeforeRedeclaredGetXml
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
	 * @hostcms-event siteuser_identity_provider.onBeforeRedeclaredGetStdObject
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
		$this->clearXmlTags()
			->addXmlTag('dir', '/' . $this->getDirHref());

		return $this;
	}

	/**
	 * Change provider status
	 * @return self
	 */
	public function changeStatus()
	{
		$this->active = 1 - $this->active;
		return $this->save();
	}

	/**
	 * Get file path
	 * @return string
	 */
	public function getFilePath()
	{
		return $this->getDirPath() . $this->image;
	}

	/**
	 * Save provider image file
	 * @param string $fileSourcePath source file
	 * @param string $fileName file name
	 * @return self
	 */
	public function saveFile($fileSourcePath, $fileName)
	{
		// Delete old file
		$this->deleteFile();

		$this->image = $this->id . '.' . Core_File::getExtension(basename($fileName));
		$this->save();

		if (!Core_File::isDir($this->getDirPath()))
		{
			try
			{
				Core_File::mkdir($this->getDirPath(), CHMOD, TRUE);
			} catch (Exception $e) {}
		}

		Core_File::upload($fileSourcePath, $this->getFilePath());

		return $this;
	}

	/**
	 * Delete provider image file
	 * @return self
	 * @hostcms-event siteuser_identity_provider.onAfterDeleteFile
	 */
	public function deleteFile()
	{
		// Delete old file
		if ($this->image != '' && Core_File::isFile($this->getFilePath()))
		{
			try
			{
				Core_File::delete($this->getFilePath());
			} catch (Exception $e) {}

			Core_Event::notify($this->_modelName . '.onAfterDeleteFile', $this);

			$this->image = '';
			$this->save();
		}

		return $this;
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event siteuser_identity_provider.onBeforeGetRelatedSite
	 * @hostcms-event siteuser_identity_provider.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}