<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cookie-Free CDN driver
 *
 * @package HostCMS
 * @subpackage Cdn
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2017 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Cdn_Cookiefree extends Cdn_Controller
{
	/**
	 * Get CSS domain
	 * @return string
	 */
	public function getCssDomain()
	{
		return !is_null($this->_cdn_site)
			? $this->_cdn_site->css_domain
			: '';
	}

	/**
	 * Get JS domain
	 * @return string
	 */
	public function getJsDomain()
	{
		return !is_null($this->_cdn_site)
			? $this->_cdn_site->js_domain
			: '';
	}

	/**
	 * Get Informationsystem domain
	 * @return string
	 */
	public function getInformationsystemDomain()
	{
		return !is_null($this->_cdn_site)
			? $this->_cdn_site->informationsystem_domain
			: '';
	}
	
	/**
	 * Get Shop domain
	 * @return string
	 */
	public function getShopDomain()
	{
		return !is_null($this->_cdn_site)
			? $this->_cdn_site->shop_domain
			: '';
	}

	/**
	 * Get Structure domain
	 * @return string
	 */
	public function getStructureDomain()
	{
		return !is_null($this->_cdn_site)
			? $this->_cdn_site->structure_domain
			: '';
	}

	/**
	 * Get CDN balance
	 * @return int
	 */
	public function getBalance()
	{
		return '∞';
	}

	/**
	 * Get CDN currency
	 * @return string
	 */
	public function getCurrency()
	{
		return '';
	}

	/**
	 * Save CDN
	 * @return self
	 */
	public function onSave()
	{
		return $this;
	}
}