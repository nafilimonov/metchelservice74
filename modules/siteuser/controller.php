<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Controller
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Siteuser_Controller
{
	/**
	 * Current Siteuser
	 * @var Siteuser_Model|NULL|FALSE
	 */
	static protected $_currentSiteuser = FALSE;

	/**
	 * Get Current Siteuser
	 * @param boolean $bCheckSite default FALSE
	 * @return Siteuser_Model|NULL
	 */
	static public function getCurrent($bCheckSite = FALSE)
	{
		if (self::$_currentSiteuser === FALSE)
		{
			self::$_currentSiteuser = NULL;

			// Идентификатор сессии уже был установлен
			if (Core_Session::hasSessionId())
			{
				$isActive = Core_Session::isActive();
				!$isActive && Core_Session::start();

				if (isset($_SESSION['siteuser_id']))
				{
					$oSiteuser = Core_Entity::factory('Siteuser')->find(intval($_SESSION['siteuser_id']));

					if (!is_null($oSiteuser->id))
					{
						// Привязать сессию к IP
						if (isset($_SESSION['siteuser_ip']))
						{
							$ip = Core::getClientIp();

							$aConfig = self::getConfig($oSiteuser->site_id);

							// Wrong network comparison for IPv4
							if (Core_Valid::ipv4($_SESSION['siteuser_ip']) && Core_Valid::ipv4($ip)
								&& Core_Ip::ipv4Network($_SESSION['siteuser_ip'], $aConfig['assignSessionIpMask']) != Core_Ip::ipv4Network($ip, $aConfig['assignSessionIpMask'])
								// or wrong IPv6 comparison
								|| $_SESSION['siteuser_ip'] !== $ip
							)
							{
								// Завершить текущую сессию
								self::unsetCurrent();

								return self::$_currentSiteuser;
							}
						}

						if ($oSiteuser->active
							&& (!$bCheckSite || defined('CURRENT_SITE') && $oSiteuser->site_id == CURRENT_SITE)
						)
						{
							$oSiteuser->updateLastActivity();

							self::$_currentSiteuser = $oSiteuser;
						}
					}
				}
			}
		}

		return self::$_currentSiteuser;
	}

	/**
	 * Set Current Siteuser
	 * @param Siteuser_Model $oSiteuser
	 * @param int $expires default 2678400
	 * @param mixed $assignSessionToIp default NULL
	 */
	static public function setCurrent(Siteuser_Model $oSiteuser, $expires = 2678400, $assignSessionToIp = NULL)
	{
		Core_Session::setMaxLifeTime($expires);

		/*$isActive = Core_Session::isActive();
		!$isActive && */Core_Session::start();

		$_SESSION['siteuser_id'] = $oSiteuser->id;

		if (is_null($assignSessionToIp))
		{
			$aConfig = self::getConfig($oSiteuser->site_id);
			$assignSessionToIp = $aConfig['assignSessionToIp']
				&& !isset($_SERVER['HTTP_CF_IPCOUNTRY'])
				&& strtolower(Core_Array::get($_SERVER, 'HTTP_SAVE_DATA', 'off')) !== 'on';
		}

		// Если привязка адреса к сессии
		$assignSessionToIp
			&& $_SESSION['siteuser_ip'] = Core::getClientIp();

		self::$_currentSiteuser = $oSiteuser;

		// Удаление всех неудачных попыток входа систему за период ранее 24 часов с момента успешного входа
		$ip = Core::getClientIp();
		$limit = 500;
		do {
			$oSiteuser_Accessdenieds = Core_Entity::factory('Siteuser_Accessdenied');
			$oSiteuser_Accessdenieds->queryBuilder()
				->clear()
				->where('datetime', '<', Core_Date::timestamp2sql(time() - 86400))
				// Удаляем все попытки доступа с текущего IP
				->setOr()
				->where('ip', '=', $ip)
				->limit($limit);

			$aSiteuser_Accessdenieds = $oSiteuser_Accessdenieds->findAll(FALSE);
			foreach ($aSiteuser_Accessdenieds as $oSiteuser_Accessdenied)
			{
				$oSiteuser_Accessdenied->delete();
			}
		} while (count($aSiteuser_Accessdenieds) == $limit);

		self::destroyOldSiteuserSessions();
		self::logToSiteuserSession();
	}

	/**
	 * Unset Current Siteuser
	 */
	static public function unsetCurrent()
	{
		$isActive = Core_Session::isActive();
		Core_Session::start();

		if (isset($_SESSION['siteuser_id']))
		{
			unset($_SESSION['siteuser_id']);
		}

		if (isset($_SESSION['siteuser_ip']))
		{
			unset($_SESSION['siteuser_ip']);
		}

		// Not FALSE!
		self::$_currentSiteuser = NULL;

		!$isActive && Core_Session::close();
	}

	/**
	 * Show popover
	 * @param object $object
	 * @param array $args
	 * @param array $options
	 */
	static public function onAfterShowContentPopover($object, $args, $options)
	{
		//$windowId = $oAdmin_Form_Controller->getWindowId();
		$windowId = $options[0]->getWindowId();

		?><script>
		$('#<?php echo $windowId?> [data-popover="hover"]').showSiteuserPopover('<?php echo $windowId?>');
		</script><?php
	}

	/**
	 * Get uniq document ID
	 * @param int $document_id document ID
	 * @param int $type document type
	 * @return int
	 */
	static public function getDocumentId($document_id, $type)
	{
		return ($document_id << 8) | $type;
	}

	/**
	 * Get document type
	 * @param $document_id document id
	 * @return int|NULL
	 */
	static public function getDocumentType($document_id)
	{
		return $document_id
			? Core_Bit::extractBits($document_id, 8, 1)
			: NULL;
	}

	/**
	 * Get Siteuser_Company by phone
	 * @param string $phone
	 * @param boolean $bCache
	 * @return array
	 */
	static public function getCompaniesByPhone($phone, $bCache = TRUE)
	{
		$oSiteuser_Companies = Core_Entity::factory('Siteuser_Company');
		$oSiteuser_Companies->queryBuilder()
			->join('siteuser_company_directory_phones', 'siteuser_companies.id', '=', 'siteuser_company_directory_phones.siteuser_company_id')
			->join('directory_phones', 'siteuser_company_directory_phones.directory_phone_id', '=', 'directory_phones.id')
			// ->where('directory_phones.value', 'LIKE', '%' . Core_DataBase::instance()->escapeLike($phone) . '%')
			->where('directory_phones.value', '=', Directory_Phone_Controller::format($phone))
			->groupBy('siteuser_companies.id');

		return $oSiteuser_Companies->findAll($bCache);
	}

	/**
	 * Get Siteuser_Person by phone
	 * @param string $phone
	 * @param boolean $bCache
	 * @return array
	 */
	static public function getPeopleByPhone($phone, $bCache = TRUE)
	{
		$phone = Directory_Phone_Controller::format($phone);

		$oSiteuser_People = Core_Entity::factory('Siteuser_Person');
		$oSiteuser_People->queryBuilder()
			->join('siteuser_people_directory_phones', 'siteuser_people.id', '=', 'siteuser_people_directory_phones.siteuser_person_id')
			->join('directory_phones', 'siteuser_people_directory_phones.directory_phone_id', '=', 'directory_phones.id')
			// ->where('directory_phones.value', 'LIKE', '%' . Core_DataBase::instance()->escapeLike($phone) . '%')
			->where('directory_phones.value', '=', Directory_Phone_Controller::format($phone))
			->groupBy('siteuser_people.id');

		return $oSiteuser_People->findAll($bCache);
	}

	/**
	 * Log access to the `siteuser_sessions` table
	 */
	static public function logToSiteuserSession()
	{
		$ip = Core::getClientIp();
		$sessionId = session_id();
		$userAgent = Core_Array::get($_SERVER, 'HTTP_USER_AGENT', '', 'str');

		// $oUser = Core_Auth::getCurrentUser();

		$oDataBase = Core_QueryBuilder::update('siteuser_sessions')
			->set('siteuser_id', self::$_currentSiteuser->id)
			->set('time', time())
			->set('user_agent', $userAgent)
			//->set('ip', $ip)
			->where('id', '=', $sessionId)
			->where('ip', '=', $ip)
			->execute();

		// Returns the number of rows affected by the last SQL statement
		// If nothing's really was changed affected rowCount will return 0.
		if ($oDataBase->getAffectedRows() == 0)
		{
			Core_QueryBuilder::insert('siteuser_sessions')
				->ignore()
				->columns('id', 'siteuser_id', 'time', 'user_agent', 'ip')
				->values($sessionId, self::$_currentSiteuser->id, time(), $userAgent, $ip)
				->execute();
		}
	}

	/**
	 * Get config
	 * @param int|NULL $siteId
	 * @return array
	 */
	static public function getConfig($siteId = NULL)
	{
		$aConfig = Core_Config::instance()->get('siteuser_config', array());

		$siteId && is_array($aConfig) && isset($aConfig[$siteId])
			&& $aConfig = $aConfig[$siteId];

		return $aConfig + array(
			'assignSessionToIp' => TRUE,
			'assignSessionIpMask' => '255.255.0.0',
			'csrfLifetime' => 10800,
			'destroySessionDays' => 90,
			'confirmationMailXsl' => 'ПисьмоПодтверждениеРегистрации',
			'confirmationMailContentType' => 'text/plain',
			'generatePasswordLength' => 8
		);
	}

	/**
	 * Destroy Old User Sessions
	 */
	static public function destroyOldSiteuserSessions()
	{
		// Destroy Old session
		$aConfig = self::getConfig();

		if ($aConfig['destroySessionDays'] > 0)
		{
			$days = intval($aConfig['destroySessionDays']);

			$oSiteuser_Sessions = Core_Entity::factory('Siteuser_Session');
			$oSiteuser_Sessions->queryBuilder()
				->where('time', '<', strtotime("-{$days} days"))
				->orderBy('id', 'ASC')
				->limit(100);

			$aSiteuser_Sessions = $oSiteuser_Sessions->findAll(FALSE);
			foreach ($aSiteuser_Sessions as $oSiteuser_Session)
			{
				$oSiteuser_Session->destroy();
			}
		}
	}
}