<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Search.
 *
 * @package HostCMS
 * @subpackage Search
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
abstract class Search_Controller_Driver extends Core_Servant_Properties {
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'site', // Backward compatibility
		'site_id',
		'offset',
		'page',
		'limit',
		'modules',
		'inner',
		'total',
		'orderField', // weight, datetime
		'orderDirection',
	);

	/**
	 * Set site. Backward compatibility, see $this->site_id
	 * @param Site_Model $oSite
	 * @return self
	 */
	public function site(Site_Model $oSite)
	{
		// Backward compatibility
		$this->site = $oSite;

		$this->site_id = $oSite->id;

		return $this;
	}

	/**
	 * Remove all indexed data
	 * @param int $site_id Site ID
	 * @return self
	 */
	abstract public function truncate($site_id);

	/**
	 * Optimize indexed data
	 * @param int $site_id Site ID
	 * @return self
	 */
	abstract public function optimize($site_id);

	/**
	 * Get pages count
	 * @param int $site_id site ID
	 * @return string count of pages
	 */
	abstract public function getPageCount($site_id);

	/**
	 * Find
	 * @param string $query Search query
	 * @return array Array of Search_Page_Model
	 * @hostcms-event Search_Controller_Hostcms.onBeforeExecuteFind
	 */
	abstract public function find($query);

	/**
	 * Config
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Constructor
	 * @param array $aConfig
	 */
	public function __construct($aConfig)
	{
		parent::__construct();

		$this->setConfig($aConfig);

		$this->offset = 0;
		$this->page = 1;

		$this->orderField = 'weight';
		$this->orderDirection = 'DESC';
	}

	/**
	 * Set config
	 * @param array $aConfig
	 * @return self
	 */
	public function setConfig($aConfig)
	{
		$this->_config = $aConfig;
		return $this;
	}

	/**
	 * @var string
	 */
	protected $_asObject = 'Search_Page_Model';

	/**
	 * Set object
	 * @return self
	 */
	public function asObject($objectName)
	{
		$this->_asObject = $objectName;
		return $this;
	}
}