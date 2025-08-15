<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counter_Visit_Model
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Counter_Visit_Model extends Core_Entity
{
	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array(),
		'counter_session' => array(),
		'counter_referrer' => array(),
		'counter_page' => array(),
		'siteuser' => array()
	);

	/**
	 * Backend property
	 * @var mixed
	 */
	public $page = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $referrer = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $useragent = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $crawler = NULL;

	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Backend callback method
	 * @return string
	 */
	public function pageBackend()
	{
		ob_start();

		$page = $this->Counter_Page->page;

		Core_Html_Entity::factory('A')
			->href($page)
			->value(
				htmlspecialchars(Core_Str::cut($page, 250))
			)
			->target('_blank')
			->execute();

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function referrerBackend()
	{
		ob_start();

		if ($this->counter_referrer_id == 0)
		{
			echo Core::_('Counter.tab');
		}
		else
		{
			$referrer = $this->Counter_Referrer->referrer;

			Core_Html_Entity::factory('A')
				->href($referrer)
				->value(
					htmlspecialchars(Core_Str::cut($referrer, 250))
				)
				->target('_blank')
				->execute();
		}

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function ipBackend()
	{
		if (is_scalar($this->ip))
		{
			$ip = Core_Str::hex2ip($this->ip);
			return htmlspecialchars($ip)
				. (!Core_Valid::ipv4($ip) || !Core_Valid::localIpv4($ip)
					? ' <span class="gray">' . @gethostbyaddr($ip) . '</span>'
					: ''
				);
		}

		return '';
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function siteuser_idBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		ob_start();

		if ($this->siteuser_id)
		{
			$windowId = $oAdmin_Form_Controller->getWindowId();

			Core_Html_Entity::factory('I')
				->class('fa fa-user')
				->execute();

			Core_Html_Entity::factory('A')
				->href($oAdmin_Form_Controller->getAdminActionLoadHref('/{admin}/siteuser/index.php', 'edit', NULL, 0, intval($this->Siteuser->id)))
				->onclick("$.openWindowAddTaskbar({path: hostcmsBackend + '/siteuser/index.php', additionalParams: '&hostcms[checked][0][{$this->Siteuser->id}]=1&hostcms[action]=edit', shortcutImg: '" . '/modules/skin/' . Core_Skin::instance()->getSkinName() . '/images/module/siteuser.png' . "', shortcutTitle: 'undefined', Minimize: true}); return false")
				->value(htmlspecialchars($this->Siteuser->login))
				->execute();
		}

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function crawlerBackend()
	{
		ob_start();

		if ($this->counter_session_id)
		{
			$oCounter_Useragent = $this->Counter_Session->Counter_Useragent;

			$sUseragent = $oCounter_Useragent->useragent;
			if ($sUseragent != '')
			{
				?><span class="user-agent pointer" title="<?php echo htmlspecialchars($sUseragent)?>"><?php
				if ($oCounter_Useragent->crawler && Core::checkBot($sUseragent))
				{
					$botName = Counter_Bot::getName($sUseragent);
					?><span class="label label-sm label-info" title="<?php echo htmlspecialchars($sUseragent)?>"><?php echo !is_null($botName) ? htmlspecialchars($botName) : Core::_('Counter.crawler')?></span> <?php
				}
				else
				{
					$browser = htmlspecialchars(Core_Browser::getBrowser($sUseragent));

					if ($browser != '-')
					{
						$ico = Core_Browser::getBrowserIco($browser);

						!is_null($ico)
							&& $browser = '<i class="' . $ico . '"></i> ' . $browser;

						echo $browser . ' ';
					}
				}
				?></span><?php
			}

			if ($this->Counter_Session->counter_os_id)
			{
				?><span class="label label-sm label-success"><?php echo htmlspecialchars($this->Counter_Session->Counter_Os->os)?></span> <?php
			}

			if ($this->Counter_Session->counter_display_id)
			{
				?><span class="label label-sm label-warning"><?php echo htmlspecialchars($this->Counter_Session->Counter_Display->display)?></span> <?php
			}
		}

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function lngBackend()
	{
		if ($this->lng != '')
		{
			echo "<img alt='" . htmlspecialchars($this->lng) . "' title='" . htmlspecialchars($this->lng) . "' class='antispam-flag' src='/modules/skin/bootstrap/images/flags/" . htmlspecialchars(strtoupper($this->lng)) . ".png' />";
		}
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event counter_visit.onBeforeGetRelatedSite
	 * @hostcms-event counter_visit.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}