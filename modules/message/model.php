<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Message_Model
 *
 * @package HostCMS
 * @subpackage Message
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Message_Model extends Core_Entity
{
	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'message_topic' => array(),
		'siteuser' => array(),
		'user' => array()
	);

	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Count of unreaded messages
	 * @var int
	 */
	protected $_countUnread = 0;

	/**
	 * Message is readed
	 * @var int
	 */
	protected $_read = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $adminSiteuser = NULL;

	/**
	 * Forbidden tags. If list of tags is empty, all tags will show.
	 * @var array
	 */
	protected $_forbiddenTags = array(
		'deleted',
		'user_id',
		'datetime',
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'read' => 0,
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

			$this->_preloadValues['siteuser_id'] = Core::moduleIsActive('siteuser') && isset($_SESSION['siteuser_id'])
				? intval($_SESSION['siteuser_id'])
				: 0;

			$this->_preloadValues['datetime'] = Core_Date::timestamp2sql(time());
		}

		$this->_read = $this->read;
	}

	/**
	 * Mark message as readed by site user
	 * @param Siteuser_Model $oSiteuser site user
	 * @return self
	 */
	public function markRead(Siteuser_Model $oSiteuser)
	{
		if ($this->read == 0
			&& $this->Message_Topic->access($oSiteuser)
			&& (
				// вывели не автору
				$this->siteuser_id != $oSiteuser->id
				// или отправитель и получатель одно лицо
				|| $this->Message_Topic->sender_siteuser_id == $this->Message_Topic->recipient_siteuser_id
			)
		)
		{
			$this->read = 1;

			$this->Message_Topic->sender_siteuser_id == $this->siteuser_id
				? $this->Message_Topic->count_recipient_unread -= 1
				: $this->Message_Topic->count_sender_unread -= 1;

			$this->Message_Topic->save();
		}
		return $this->save();
	}

	/**
	 * Mark message as unreaded by site user
	 * @param Siteuser_Model $oSiteuser site user
	 * @return self
	 */
	public function markUnread(Siteuser_Model $oSiteuser)
	{
		if ($this->read == 1 && $this->Message_Topic->access($oSiteuser)
			 && ($this->siteuser_id != $oSiteuser->id || $this->Message_Topic->sender_siteuser_id == $this->Message_Topic->recipient_siteuser_id))
		{
			$this->read = 0;

			$this->Message_Topic->sender_siteuser_id == $this->siteuser_id
				? $this->Message_Topic->count_recipient_unread += 1
				: $this->Message_Topic->count_sender_unread += 1;

			$this->Message_Topic->save();
		}
		return $this->save();
	}

	/**
	 * Get unread message count
	 * @param Siteuser_Model $oSiteuser site user
	 * @return int
	 */
	public function getCountUnread(Siteuser_Model $oSiteuser)
	{
		$this->queryBuilder()
			// ->clear()
			->select(array('COUNT(*)', '_countUnread'))
			->where('siteuser_id', '=', $oSiteuser->id)
			->where('read', '=', 0)
			->limit(1);

		$aCount = $this->findAll(FALSE);

		return isset($aCount[0])
			? $aCount[0]->_countUnread
			: 0;
	}

	/**
	 * Get date of the latest message
	 * @return datetime|NULL
	 */
	public function getLastDatetime()
	{
		$this->queryBuilder()
			// ->clear()
			->orderBy('datetime', 'DESC')
			->limit(1);

		$aMessages = $this->findAll();

		return isset($aMessages[0])
			? $aMessages[0]->datetime
			: NULL;
	}

	/**
	 * Save object.
	 *
	 * @return Core_Entity
	 */
	public function save()
	{
		$bChanged = $this->changed();

		if ($bChanged)
		{
			if (Core_Date::sql2timestamp($this->datetime) > Core_Date::sql2timestamp($this->Message_Topic->datetime))
			{
				$this->Message_Topic->datetime = $this->datetime;
			}
		}

		return parent::save();
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event message.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$return = parent::delete($primaryKey);

		$this->Message_Topic->recount();

		return $return;
	}

	/**
	 * Get XML for entity and children entities
	 * @return string
	 * @hostcms-event message.onBeforeRedeclaredGetXml
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
	 * @hostcms-event message.onBeforeRedeclaredGetStdObject
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
			->addXmlTag('datetime', Core_Date::strftime($this->Siteuser->Site->date_time_format, Core_Date::sql2timestamp($this->datetime)));

		return $this;
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function adminSiteuserBackend()
	{
		$oSiteuser = $this->Siteuser;

		$currentColor = Core_Str::createColor($oSiteuser->id);

		?>
		<span class="badge badge-square" style="color: <?php echo $currentColor?>; background-color: <?php echo Core_Str::hex2lighter($currentColor, 0.88)?>"><?php echo htmlspecialchars($oSiteuser->login)?></span><?php
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function readBackend()
	{
		return $this->read
			? '<i class="fa fa-check-circle-o green">'
			: '';
	}
}