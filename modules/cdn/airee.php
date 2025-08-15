<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Airee CDN driver
 *
 * @package HostCMS
 * @subpackage Cdn
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Cdn_Airee extends Cdn_Controller
{
	/**
	 * Get CSS domain
	 * @return string
	 */
	public function getCssDomain()
	{
		return !is_null($this->_cdn_site) && $this->_cdn_site->css_domain != ''
			? $this->_cdn_site->css_domain
			: $this->getCdnDomain();
	}

	/**
	 * Get JS domain
	 * @return string
	 */
	public function getJsDomain()
	{
		return !is_null($this->_cdn_site) && $this->_cdn_site->js_domain != ''
			? $this->_cdn_site->js_domain
			: $this->getCdnDomain();
	}

	/**
	 * Get Informationsystem domain
	 * @return string
	 */
	public function getInformationsystemDomain()
	{
		return !is_null($this->_cdn_site) && $this->_cdn_site->informationsystem_domain != ''
			? $this->_cdn_site->informationsystem_domain
			: $this->getCdnDomain();
	}

	/**
	 * Get Shop domain
	 * @return string
	 */
	public function getShopDomain()
	{
		return !is_null($this->_cdn_site) && $this->_cdn_site->shop_domain != ''
			? $this->_cdn_site->shop_domain
			: $this->getCdnDomain();
	}

	/**
	 * Get Structure domain
	 * @return string
	 */
	public function getStructureDomain()
	{
		return !is_null($this->_cdn_site) && $this->_cdn_site->structure_domain != ''
			? $this->_cdn_site->structure_domain
			: $this->getCdnDomain();
	}

	/**
	 * CDN domain cache
	 * @var array
	 */
	protected $_getCdnDomainCache = array();

	/**
	 * Get CDN domain
	 * @return string
	 */
	public function getCdnDomain()
	{
		if (!isset($this->_getCdnDomainCache[CURRENT_SITE]))
		{
			$domain = $this->_getDomain();

			$this->_getCdnDomainCache[CURRENT_SITE] = !is_null($domain)
				? $domain . '.airee.ru'
				: '';
		}

		return $this->_getCdnDomainCache[CURRENT_SITE];
	}

	/**
	 * Get site domain
	 * @return string|NULL
	 */
	protected function _getDomain()
	{
		$oSite = Core_Entity::factory('Site', CURRENT_SITE);
		$oSiteAlias = $oSite->getCurrentAlias();

		return $oSiteAlias
			? strtolower($oSiteAlias->name)
			: NULL;
	}

	/**
	 * Get CDN balance
	 * @return int
	 */
	public function getBalance()
	{
		$iCheckTime = Core_Date::sql2timestamp(date('Y-m-d 09:00:00'));

		if (time() > $iCheckTime
			&& Core_Date::sql2timestamp($this->_cdn->balance_datetime) < $iCheckTime
		)
		{
			$sUrl = "https://xn--80aqc2a.xn--p1ai/my/site/api/?key={$this->_cdn->key}&action=get.balance";

			try
			{
				$Core_Http = Core_Http::instance('curl')
					->clear()
					->url($sUrl)
					->port(443)
					->timeout(5)
					->execute();

				$aAnswer = json_decode($Core_Http->getDecompressedBody(), TRUE);

				if (isset($aAnswer['error']))
				{
					Core_Message::show($aAnswer['error'], 'error');
				}

				$this->_cdn->balance = strval(Core_Array::get($aAnswer, 'balance'));
				$this->_cdn->balance_datetime = Core_Date::timestamp2sql(time());
				$this->_cdn->save();
			}
			catch (Exception $e){}
		}

		return $this->_cdn->balance;
	}

	/**
	 * Get CDN currency
	 * @return string
	 */
	public function getCurrency()
	{
		return 'руб.';
	}

	/**
	 * Save CDN
	 * @return self
	 */
	public function onSave()
	{
		// Регистрация и получение ключа (Тариф "Земля")
		if (strlen($this->_cdn->key) == 0)
		{
			if ($this->_cdn->login)
			{
				$sUrl = 'https://xn--80aqc2a.xn--p1ai/my/site/api/?action=register&email=' . rawurlencode($this->_cdn->login);

				try
				{
					$Core_Http = Core_Http::instance('curl')
						->clear()
						->url($sUrl)
						->port(443)
						->timeout(5)
						->execute();

					$aAnswer = json_decode($Core_Http->getDecompressedBody(), TRUE);

					if (isset($aAnswer['error']))
					{
						Core_Message::show($aAnswer['error'], 'error');
					}

					$this->_cdn->key = strval(Core_Array::get($aAnswer, 'success'));
					$this->_cdn->save();

					// Get CDN balance
					$this->getBalance();

					$domain = $this->_getDomain();

					if (!is_null($domain))
					{
						if (!$this->addSite($domain))
						{
							$this->_cdn->key = '';
							$this->_cdn->save();
						}
					}
				}
				catch (Exception $e){}
			}
			else
			{
				Core_Message::show(Core::_('Cdn.login_empty'), 'error');
			}
		}

		// Add all domains
		$this->addSites(CURRENT_SITE);

		return $this;
	}

	public function addSites($siteId)
	{
		$sDomainUrl = "https://xn--80aqc2a.xn--p1ai/my/site/api/?key={$this->_cdn->key}&action=get.domains";

		$Core_Http = Core_Http::instance('curl')
			->clear()
			->url($sDomainUrl)
			->port(443)
			->timeout(5)
			->execute();

		$aAnswer = json_decode($Core_Http->getDecompressedBody(), TRUE);

		if (isset($aAnswer['error']))
		{
			Core_Message::show($aAnswer['error'], 'error');
		}

		$cdnDomainsList = array();
		if (isset($aAnswer['domains']) && is_array($aAnswer['domains']))
		{
			foreach ($aAnswer['domains'] as $aCdnDomain)
			{
				// Domains from CDN without www
				$cdnDomainsList[] = strval($aCdnDomain['domain']);
			}
		}

		$oSite = Core_Entity::factory('Site', $siteId);
		$aSite_Aliases = $oSite->Site_Aliases->findAll(FALSE);

		foreach ($aSite_Aliases as $oSite_Alias)
		{
			$domain = $oSite_Alias->alias_name_without_mask;

			// Cut www
			strpos($domain, 'www.') === 0 && $domain = substr($domain, 4);

			if (strpos($domain, '.') !== FALSE && !in_array($domain, $cdnDomainsList))
			{
				//add site
				$this->addSite($domain);

				$cdnDomainsList[] = $domain;
			}
		}

		return TRUE;
	}

	/**
	 * Add site into CDN
	 * @return boolean
	 */
	public function addSite($domain)
	{
		$sUrl = "https://xn--80aqc2a.xn--p1ai/my/site/api/?key=" . rawurlencode($this->_cdn->key)
			. "&domain=" . rawurlencode($domain) . "&action=add";

		try
		{
			$Core_Http = Core_Http::instance('curl')
				->clear()
				->url($sUrl)
				->port(443)
				->timeout(5)
				->execute();

			$aAnswer = json_decode($Core_Http->getDecompressedBody(), TRUE);

			if (isset($aAnswer['error']))
			{
				Core_Message::show($aAnswer['error'], 'error');
			}

			return Core_Array::get($aAnswer, 'success') == 'OK';
		}
		catch (Exception $e){}

		return FALSE;
	}
}