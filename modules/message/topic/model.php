<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Message_Topic_Model
 *
 * @package HostCMS
 * @subpackage Message
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Message_Topic_Model extends Core_Entity
{
	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'message' => array()
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'message_topics_by_sender' => array('foreign_key' => 'sender_siteuser_id', 'model' => 'siteuser'),
		'message_topics_by_recipient' => array('foreign_key' => 'recipient_siteuser_id', 'model' => 'siteuser'),
		'user' => array()
	);

	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Forbidden tags. If list of tags is empty, all tags will show.
	 * @var array
	 */
	protected $_forbiddenTags = array(
		'deleted',
		'user_id',
		'datetime'
	);

	/**
	 * Backend property
	 * @var mixed
	 */
	public $unread = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $unread_messages = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $adminSender = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $adminRecipient = NULL;
	// public $siteusers = array();

	/**
	 * Backend callback method
	 * @return string
	 */
	public function adminSenderBackend()
	{
		if ($this->sender_siteuser_id)
		{
			$oSiteuser = $this->Message_Topics_By_Sender;

			$currentColor = Core_Str::createColor($oSiteuser->id);

			?>
			<span class="badge badge-square" style="color: <?php echo $currentColor?>; background-color: <?php echo Core_Str::hex2lighter($currentColor, 0.88)?>"><?php echo htmlspecialchars($oSiteuser->login)?></span><?php
		}
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function adminRecipientBackend()
	{
		if ($this->recipient_siteuser_id)
		{
			$oSiteuser = $this->Message_Topics_By_Recipient;

			$currentColor = Core_Str::createColor($oSiteuser->id);

			?>
			<span class="badge badge-square" style="color: <?php echo $currentColor?>; background-color: <?php echo Core_Str::hex2lighter($currentColor, 0.88)?>"><?php echo htmlspecialchars($oSiteuser->login)?></span><?php
		}
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function subjectBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$count = $this->Messages->getCount();
		$count && Core_Html_Entity::factory('Span')
			->class('badge badge-hostcms badge-square')
			->value($count)
			->execute();
	}

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

			$this->_preloadValues['sender_siteuser_id'] = Core::moduleIsActive('siteuser') && isset($_SESSION['siteuser_id'])
				? intval($_SESSION['siteuser_id'])
				: 0;

			$this->_preloadValues['datetime'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	 * Delete message topic by site user
	 * @param Siteuser_Model $oSiteuser site user
	 * @return self
	 */
	public function deleteBySiteuser(Siteuser_Model $oSiteuser)
	{
		if (!$this->access($oSiteuser))
		{
			throw new Core_Exception('User %userName does not have access to message topic', array('%userName' => $oSiteuser->login));
		}

		$this->sender_siteuser_id == $oSiteuser->id && $this->deleted_by_sender = 1;
		$this->recipient_siteuser_id == $oSiteuser->id && $this->deleted_by_recipient = 1;

		return $this->deleted_by_sender && $this->deleted_by_recipient
			? $this->delete()
			: $this->save();
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event message_topic.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Messages->deleteAll(FALSE);

		return parent::delete($primaryKey);
	}

	/**
	 * Recount uread messages
	 * @return Message_Topic_Model
	 */
	public function recount()
	{
		$this->count_sender_unread = $this->Messages
			->getCountUnread(Core_Entity::factory('Siteuser', $this->recipient_siteuser_id));

		$this->count_recipient_unread = $this->Messages
			->getCountUnread(Core_Entity::factory('Siteuser', $this->sender_siteuser_id));

		$this->datetime = $this->Messages->getLastDatetime();

		return $this->save();
	}

	/**
	 * Check if user has right to access topic
	 * @param Siteuser_Model $oSiteuser site user
	 * @return boolean
	 */
	public function access(Siteuser_Model $oSiteuser)
	{
		return !is_null($oSiteuser->id)
			&& ($this->sender_siteuser_id == $oSiteuser->id && !$this->deleted_by_sender
			|| $this->recipient_siteuser_id == $oSiteuser->id && !$this->deleted_by_recipient);
	}

	/**
	 * Get conversational partner
	 * @param Siteuser_Model $oSiteuser site user
	 * @return Siteuser_Model|NULL
	 */
	public function getConversationalPartner(Siteuser_Model $oSiteuser)
	{
		return $this->access($oSiteuser)
			? ($oSiteuser->id != $this->sender_siteuser_id
				? $this->Message_Topics_By_Sender
				: $this->Message_Topics_By_Recipient)
			: NULL;
	}

	/**
	 * Get Message_Topics by Siteuser, user findAll() to get array of objects
	 *
	 * @param Siteuser_Model $oSiteuser
	 * @return Message_Topic_Model
	 */
	public function bySiteuser(Siteuser_Model $oSiteuser)
	{
		$this
			->queryBuilder()
			->open()
				->where('sender_siteuser_id', '=', $oSiteuser->id)
				->where('deleted_by_sender', '=', 0)
			->setOr()
				->where('recipient_siteuser_id', '=', $oSiteuser->id)
				->where('deleted_by_recipient', '=', 0)
			->close();

		return $this;
	}

	/**
	 * Get XML for entity and children entities
	 * @return string
	 * @hostcms-event message_topic.onBeforeRedeclaredGetXml
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
	 * @hostcms-event message_topic.onBeforeRedeclaredGetStdObject
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
			->addXmlTag('datetime', Core_Date::strftime($this->Message_Topics_By_Sender->Site->date_time_format, Core_Date::sql2timestamp($this->datetime)))
			->addXmlTag('date', Core_Date::strftime($this->Message_Topics_By_Sender->Site->date_format, Core_Date::sql2timestamp($this->datetime)))
			->addXmlTag('name', $this->subject)
			->addXmlTag('total', $this->Messages->getCount());

		return $this;
	}
}