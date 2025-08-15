<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Messages.
 *
 * @package HostCMS
 * @subpackage Message
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Message_Controller_Show extends Core_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'topic',
		'limit',
		'page',
		'total',
		'pattern',
		'patternParams',
		'offset',
		'delete',
		'url',
		'ajax',
		'activity',
		'url'
	);

	/**
	 * Constructor.
	 * @param Siteuser_Model $oSiteuser user
	 */
	public function __construct(Siteuser_Model $oSiteuser)
	{
		parent::__construct($oSiteuser->clearEntities());

		$this->topic = NULL;
		
		$this->url = Core::$url['path'];
	}

	/**
	 * Show built data
	 * @return self
	 * @hostcms-event Message_Controller_Show.onBeforeRedeclaredShow
	 */
	public function show()
	{
		Core_Event::notify(get_class($this) . '.onBeforeRedeclaredShow', $this);

		$oSiteuser = $this->getEntity();

		$this->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('page')
				->value(intval($this->page))
		)->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('limit')
				->value(intval($this->limit))
		)->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('url')
				->value($this->url)
		)->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('ajax')
				->value($this->ajax)
		);

		$oSiteuser->showXmlProperties(TRUE);

		if ($this->limit > 0)
		{
			// Load model columns BEFORE FOUND_ROWS()
			Core_Entity::factory('Message_Topic')->getTableColumns();

			// Load user BEFORE FOUND_ROWS()
			$oUserCurrent = Core_Auth::getCurrentUser();

			if ($this->delete)
			{
				$this->topic = NULL;
			}

			$aSiteusers = array();

			// Только сообщения топика
			if ($this->topic)
			{
				$this->total = 0;

				$oMessage_Topics = $this->getSiteuserMessageTopics();

				$oMessage_Topics
					->queryBuilder()
					->where('message_topics.id', '=', intval($this->topic))
					->limit(1);

				$aMessage_Topics = $oMessage_Topics->findAll();

				if (count($aMessage_Topics))
				{
					$oMessage_Topic = $aMessage_Topics[0];

					if ($oMessage_Topic->access($oSiteuser))
					{
						$oMessage_Topic->clearEntities();

						$oMessages = $oMessage_Topic->Messages;

						$oMessages->queryBuilder()
							->orderBy('datetime', 'DESC')
							->offset(intval($this->offset))
							->limit(intval($this->limit));

						$aMessages = $oMessages->findAll(FALSE);

						$oMessage_Topic->addEntities(array_reverse($aMessages));

						$this->addEntity($oMessage_Topic);

						if ($oSiteuser->id != $oMessage_Topic->sender_siteuser_id
							|| $oSiteuser->id != $oMessage_Topic->recipient_siteuser_id)
						{
							$oNewSiteuser = $oMessage_Topic->getConversationalPartner($oSiteuser);
							!is_null($oNewSiteuser) && $aSiteusers[$oNewSiteuser->id] = $oNewSiteuser->showXmlProperties(TRUE);
						}

						$this->total = $oMessages->getCount();
					}
				}
			}
			else // Список переписок
			{
				$oMessage_Topics = $this->getSiteuserMessageTopics();

				$oMessage_Topics
					->queryBuilder()
					->orderBy('datetime', 'DESC')
					->offset(intval($this->offset))
					->limit(intval($this->limit));

				$aMessage_Topics = $oMessage_Topics->findAll(FALSE);
				foreach ($aMessage_Topics as $oMessage_Topic)
				{
					if ($oMessage_Topic->access($oSiteuser))
					{
						$oMessage_Topic->clearEntities();

						$oMessages = $oMessage_Topic->Messages;

						$oMessages->queryBuilder()
							->orderBy('datetime', 'DESC')
							->limit(1);

						$aMessages = $oMessages->findAll();

						$oMessage_Topic->addEntities($aMessages);

						$this->addEntity($oMessage_Topic);

						if ($oSiteuser->id != $oMessage_Topic->sender_siteuser_id || $oSiteuser->id != $oMessage_Topic->recipient_siteuser_id)
						{
							$oNewSiteuser = $oMessage_Topic->getConversationalPartner($oSiteuser);
							!is_null($oNewSiteuser) && $aSiteusers[$oNewSiteuser->id] = $oNewSiteuser->showXmlProperties(TRUE);
						}
					}
				}

				$this->total = $oMessage_Topics->getCount();
			}

			$this->addEntities($aSiteusers);

			// У списка диалогов есть пагинация
			$this->addEntity(
				Core::factory('Core_Xml_Entity')
					->name('total')
					->value($this->total)
			);
		}

		$return = parent::show();

		if ($this->topic && $this->activity && isset($aMessages))
		{
			foreach ($aMessages as $oMessage)
			{
				$oMessage->markRead($oSiteuser);
			}
		}

		return $return;
	}

	/**
	 * Get Message_Topic_Model for Siteuser
	 * @return Message_Topic_Model
	 */
	public function getSiteuserMessageTopics()
	{
		$oSiteuser = $this->getEntity();

		return Core_Entity::factory('Message_Topic')->bySiteuser($oSiteuser);
	}

	/**
	 * Parse URL and set controller properties
	 * @return Message_Controller_Show
	 * @hostcms-event Message_Controller_Show.onBeforeParseUrl
	 * @hostcms-event Message_Controller_Show.onAfterParseUrl
	 */
	public function parseUrl()
	{
		$oSiteuser = $this->getEntity();

		// Named subpatterns {name} can consist of up to 32 alphanumeric characters and underscores, but must start with a non-digit.
		$this->pattern = $this->url . '({topic})(page-{page}/)(delete/{delete})';

		Core_Event::notify(get_class($this) . '.onBeforeParseUrl', $this);

		$Core_Router_Route = new Core_Router_Route($this->pattern);
		$this->patternParams = $matches = $Core_Router_Route->applyPattern($this->url);

		if (isset($matches['topic']) && $matches['topic'])
		{
			$topicId = trim($matches['topic'], '/');
			$oMessage_Topic = Core_Entity::factory('Message_Topic')->find($topicId);

			if (!is_null($oMessage_Topic->id) && $oMessage_Topic->access($oSiteuser))
			{
				$this->topic($oMessage_Topic->id);

				if (isset($matches['delete']))
				{
					$this->delete(TRUE);

					$oMessage_Topic->deleteBySiteuser($oSiteuser);

					$oCore_Response = new Core_Response();

					$oCore_Response
						->status(301)
						->header('Location', $this->url)
						->sendHeaders();
					exit();
				}

				// При входе в тему пересчитываем количество
				!$this->ajax && $oMessage_Topic->recount();

				Core_Page::instance()->title($oMessage_Topic->subject);
				Core_Page::instance()->description($oMessage_Topic->subject);
				Core_Page::instance()->keywords($oMessage_Topic->subject);
			}
			else
			{
				$this->error404();
			}
		}

		if (isset($matches['page']) && $matches['page'] > 1)
		{
			$this->page($matches['page'] - 1)->offset($this->limit * $this->page);
		}

		Core_Event::notify(get_class($this) . '.onAfterParseUrl', $this);

		return $this;
	}

	/**
	 * Create topic
	 * @param string $login
	 * @param string $subject
	 * @param string $text
	 * @return string
	 */
	public function createTopic($login, $subject, $text)
	{
		$oSiteuser = $this->getEntity();

		$oSite = $oSiteuser->Site;

		$oSiteuserRecipient = $oSite->Siteusers->getByLogin(trim($login));

		if (!is_null($oSiteuserRecipient))
		{
			$oMessage_Topic = Core_Entity::factory('Message_Topic');

			// Remove Emoji
			strtolower(Core_Array::get(Core_DataBase::instance()->getConfig(), 'charset')) != 'utf8mb4'
				&& $subject = Core_Str::removeEmoji($subject);

			$oMessage_Topic->subject = Core_Str::stripTags($subject);

			empty($oMessage_Topic->subject) && $oMessage_Topic->subject = Core::_('Message_Topic.no_subject');

			$oMessage_Topic->sender_siteuser_id = $oSiteuser->id;
			$oMessage_Topic->recipient_siteuser_id = $oSiteuserRecipient->id;

			$oMessage_Topic->save();

			// Set topic
			$this->topic = $oMessage_Topic->id;

			$this->addMessage($text);

			// Reset topic
			$this->topic = NULL;
		}
		else
		{
			return 'wrong-login';
		}

		return 'OK';
	}

	/**
	 * Add message
	 * @param string $text
	 * @return string
	 */
	public function addMessage($text)
	{
		$oSiteuser = $this->getEntity();

		if ($this->topic)
		{
			$text = trim($text);

			if ($text == '')
			{
				return 'empty-text';
			}

			// Remove Emoji
			strtolower(Core_Array::get(Core_DataBase::instance()->getConfig(), 'charset')) != 'utf8mb4'
				&& $text = Core_Str::removeEmoji($text);

			$oMessage_Topic = Core_Entity::factory('Message_Topic', $this->topic);

			$oMessage = Core_Entity::factory('Message');

			$allowable_tags = '<b><strong><i><em><br><p><u><strike><ul><ol><li>';
			$oMessage->text = nl2br(Core_Str::stripTags($text, $allowable_tags));

			$oMessage->siteuser_id = $oSiteuser->id;

			// При добавлении сообщения увеличиваем количество непрочитанных
			$oMessage_Topic->sender_siteuser_id == $oSiteuser->id
				? $oMessage_Topic->count_recipient_unread += 1
				: $oMessage_Topic->count_sender_unread += 1;

			$oMessage_Topic->add($oMessage);
		}
		else
		{
			return 'wrong-topic';
		}

		return 'OK';
	}

	/**
	 * Error 410 handler
	 * @return self
	 */
	public function error410()
	{
		Core_Page::instance()->error410();

		return $this;
	}
	
	/**
	 * Error 404 handler
	 * @return self
	 */
	public function error404()
	{
		Core_Page::instance()->error404();

		return $this;
	}
}