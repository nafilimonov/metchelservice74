<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Abstract CDN
 *
 * @package HostCMS
 * @subpackage Cdn
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2017 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
abstract class Cdn_Controller
{
	/**
	 * CDN
	 * @var Cdn_Model
	 */
	protected $_cdn = NULL;

	/**
	 * Cdn_Site
	 * @var Cdn_Site_Model
	 */
	protected $_cdn_site = NULL;

	/**
	 * The singleton instances.
	 * @var array
	 */
	static public $instance = array();

	/**
	 * Get full driver name
	 * @param string $driver driver name
	 * @return srting
	 */
	static protected function _getDriverName($driver)
	{
		return 'Cdn_' . ucfirst($driver);
	}

	/**
	 * Get default Cdn_Site for current site
	 * @return Cdn_Site_Model|NULL
	 */
	static public function getDefaultCdnSite()
	{
		$oSite = Core_Entity::factory('Site', CURRENT_SITE);
		$oCdn_Site = $oSite->Cdn_Sites->getByDefault(1);

		return $oCdn_Site;
	}

	/**
	 * Register an existing instance as a singleton.
	 * @param string $name driver's name
	 * @return object
	 */
	static public function instance($name)
	{
		if (!is_string($name))
		{
			throw new Core_Exception('Wrong argument type (expected String)');
		}

		if (!isset(self::$instance[$name]))
		{
			$driver = self::_getDriverName($name);

			if (class_exists($driver))
			{
				self::$instance[$name] = new $driver();
			}
			else
			{
				throw new Core_Exception(
					sprintf('CDN driver "%s" does not exist!', htmlspecialchars($name)), array(), 0, FALSE
				);
			}
		}

		return self::$instance[$name];
	}

	/**
	 * Set CDN
	 *
	 * @return self
	 */
	public function setCdn(Cdn_Model $oCdn)
	{
		$this->_cdn = $oCdn;
		return $this;
	}

	/**
	 * Set Cdn_Site
	 *
	 * @return self
	 */
	public function setCdnSite(Cdn_Site_Model $oCdn_Site)
	{
		$this->_cdn_site = $oCdn_Site;
		return $this;
	}

	/**
	 * Get CDN
	 * @return Cdn_Model
	 */
	public function getCdn()
	{
		return $this->_cdn;
	}

	/**
	 * Get CSS domain
	 * @return string
	 */
	abstract public function getCssDomain();

	/**
	 * Get JS domain
	 * @return string
	 */
	abstract public function getJsDomain();

	/**
	 * Get Informationsystem domain
	 * @return string
	 */
	abstract public function getInformationsystemDomain();

	/**
	 * Get Shop domain
	 * @return string
	 */
	abstract public function getShopDomain();

	/**
	 * Get Structure domain
	 * @return string
	 */
	abstract public function getStructureDomain();

	/**
	 * Get CDN balance
	 * @return int
	 */
	abstract public function getBalance();

	/**
	 * Get CDN currency
	 * @return string
	 */
	abstract public function getCurrency();

	/**
	 * Save CDN
	 * @return self
	 */
	abstract public function onSave();
}