<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counter_Session_Model
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Counter_Session_Model extends Core_Entity
{
	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array(),
		'counter_display' => array(),
		'counter_useragent' => array(),
		'counter_os' => array(),
		'counter_browser' => array(),
		'counter_device' => array(),
	);

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'counter_page' => array(),
		'counter_visit' => array()
	);

	/**
	 * Backend property
	 * @var mixed
	 */
	public $date = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $adminCount = NULL;

	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event counter_session.onBeforeGetRelatedSite
	 * @hostcms-event counter_session.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function infoBackend()
	{
		ob_start();

		$oCounter_Useragent = $this->Counter_Useragent;

		$sUseragent = $oCounter_Useragent->useragent;

		if ($sUseragent != '')
		{
			?><span class="user-agent pointer" title="<?php echo htmlspecialchars($sUseragent)?>"><?php
			if ($oCounter_Useragent->crawler && Counter_Controller::checkBot($sUseragent))
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

		if ($this->counter_os_id)
		{
			?><span class="label label-sm label-success"><?php echo htmlspecialchars($this->Counter_Os->os)?></span> <?php
		}

		if ($this->counter_display_id)
		{
			?><span class="label label-sm label-warning"><?php echo htmlspecialchars($this->Counter_Display->display)?></span> <?php
		}

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function idBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$additionalParam = 'admin_form_filter_2320=' . $this->id;

		$href = $oAdmin_Form_Controller->getAdminLoadHref('/{admin}/counter/visitors/index.php', NULL, NULL, $additionalParam);
		$onclick = $oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/counter/visitors/index.php', NULL, NULL, $additionalParam);

		return '<a href="' . $href . '" onclick="' . $onclick . '">' . htmlspecialchars($this->id) . '</a>';
	}

	/**
	 * Count
	 * @var int
	 */
	//static protected $_count = NULL;

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controler $oAdmin_Form_Controler
	 * @return string
	 */
	/*public function adminCount($oAdmin_Form_Field, $oAdmin_Form_Controler)
	{
		if (is_null(self::$_count))
		{
			$aObjects = $oAdmin_Form_Controler->getDataset(0)->getObjects();

			foreach ($aObjects as $oObject)
			{
				self::$_count += $oObject->adminCount;
			}
		}

		if (self::$_count > 0)
		{
			return $this->adminCount . '(' . sprintf("%.2f%%", $this->adminCount * 100 / self::$_count) . ')';
		}

		return '';
	}*/

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event counter_session.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Counter_Visits->deleteAll(FALSE);

		return parent::delete($primaryKey);
	}
}