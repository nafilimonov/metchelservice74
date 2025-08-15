<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cloud Dir Dataset.
 *
 * @package HostCMS
 * @subpackage Cloud
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Cloud_Dir_Dataset extends Admin_Form_Dataset
{
	/**
	 * Cloud id
	 * @var mixed
	 */
	protected $_cloudId = NULL;

	/**
	 * Count objects
	 * @var mixed
	 */
	protected $_count = NULL;

	/**
	 * Dir id
	 * @var mixed
	 */
	protected $_dirId = NULL;

	/**
	 * Constructor.
	 * @param int $type type
	 */
	public function __construct($iCloudID, $sDirId)
	{
		$this->_cloudId = $iCloudID;
		$this->_dirId = $sDirId;
	}

	/**
	 * Get count of finded objects
	 * @return int
	 */
	public function getCount()
	{
		if (!$this->_loaded)
		{
			$this->_fillObjectsArray();

			$this->_count = count($this->_objects);
		}

		return $this->_count;
	}

	/**
	 * Load objects
	 * @return array
	 */
	public function load()
	{
		!$this->_loaded && $this->_fillObjectsArray();

		return array_slice($this->_objects, $this->_offset, $this->_limit);
	}

	/**
	 * Get typical entity
	 * @return object
	 */
	public function getEntity()
	{
		$oCloud_Dir = new Cloud_Dir();
		$oCloud_Dir->hash = Cloud_Controller::encode('EMPTY HASH');
		$oCloud_Dir->name = Cloud_Controller::encode('EMPTY NAME');
		return $oCloud_Dir;
	}

	/**
	 * Get object
	 * @param int $primaryKey ID
	 * @return object
	 */
	public function getObject($primaryKey)
	{
		!$this->_loaded && $this->_fillObjectsArray();

		if (isset($this->_objects[$primaryKey]))
		{
			return $this->_objects[$primaryKey];
		}
		elseif ($primaryKey == 0)
		{
			return $this->getEntity();
		}

		return NULL;
	}

	/**
	 * Get new object
	 * @return object
	 */
	protected function _newObject(){}

	/**
	 * Fill objects
	 */
	protected function _fillObjectsArray()
	{
		$this->_objects = array();
		$this->_loaded = TRUE;

		$oCloud_Controller = Cloud_Controller::factory($this->_cloudId);

		if ($oCloud_Controller)
		{
			!is_null($this->_dirId) && $oCloud_Controller->dirId($this->_dirId);

			$aResponse = $oCloud_Controller->listDir();

			foreach ($aResponse as $oResponse)
			{
				if ($oResponse->is_dir)
				{
					$oCloud_Dir = new Cloud_Dir();
					$oCloud_Dir->id = $oResponse->id;
					$oCloud_Dir->hash = Cloud_Controller::encode($oResponse->path);
					$oCloud_Dir->name = $oResponse->name;
					$oCloud_Dir->cloudController($oCloud_Controller);
					$this->_objects[$oCloud_Dir->hash] = $oCloud_Dir;
				}
			}
		}
	}

	/**
	 * Get breadcrumbs
	 * @return array
	 */
	public function getBreadCrumbs()
	{
		$oCloud_Controller = Cloud_Controller::factory($this->_cloudId);

		if (!$oCloud_Controller)
		{
			throw new Core_Exception("Can't find cloud provider's class");
		}

		!is_null($this->_dirId) && $oCloud_Controller->dirId($this->_dirId);

		return $oCloud_Controller->getBreadCrumbs();
	}
}