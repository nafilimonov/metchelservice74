<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Показ личного кабинета пользователя.
 *
 * Доступные методы:
 *
 * - properties(TRUE|FALSE) показывать дополнительные свойства, по умолчанию FALSE
 * - sortPropertiesValues(TRUE|FALSE) сортировать значения дополнительных свойств, по умолчанию TRUE.
 * - showDiscountcards(TRUE|FALSE) показывать дисконтные карты, по умолчанию FALSE
 * - showTransactionsAmount(TRUE|FALSE) показывать сумму по лицевым счетам, по умолчанию FALSE
 * - showFriends(TRUE|FALSE) показывать друзей, по умолчанию FALSE
 * - showGroups(TRUE|FALSE) показывать группы, по умолчанию FALSE
 * - showResponsible(TRUE|FALSE) показывать ответственных сотрудников, по умолчанию FALSE
 * - showMaillists(TRUE|FALSE) показывать почтовые рассылки, по умолчанию FALSE
 * - showAffiliats(TRUE|FALSE) показывать аффилиатов, по умолчанию FALSE
 * - showAffiliatsTree(TRUE|FALSE) показывать дерево аффилиатов, по умолчанию FALSE
 * - showForumCounts(TRUE|FALSE) показывать количество сообщений на форуме, по умолчанию FALSE
 * - showConnectedProviders(TRUE|FALSE) показывать связанные социальные сети, по умолчанию TRUE
 * - addAllowedTags('/node/path', array('description')) массив тегов для элементов, указанных в первом аргументе, разрешенных к передаче в генерируемый XML
 * - addForbiddenTags('/node/path', array('description')) массив тегов для элементов, указанных в первом аргументе, запрещенных к передаче в генерируемый XML
 *
 * Доступные пути для методов addAllowedTags/addForbiddenTags:
 *
 * - '/' или '/siteuser' Клиент
 * - '/siteuser/properties/property' Свойство в списке свойст клиентов
 * - '/siteuser/properties/property_dir' Раздел свойств в списке свойств клиента
 * - '/siteuser/shop' Магазин
 * - '/siteuser/shop/shop_discountcard' Дисконтные карты в магазине
 *
 * <code>
 * $oSiteuser = Core_Entity::factory('Siteuser')->getCurrent();
 * is_null($oSiteuser) && $oSiteuser = Core_Entity::factory('Siteuser');
 * $Siteuser_Controller_Show = new Siteuser_Controller_Show(
 * 	$oSiteuser
 * );
 *
 * $Siteuser_Controller_Show
 * 	->xsl(
 * 		Core_Entity::factory('Xsl')->getByName('ЛичныйКабинетПользователя')
 * 	)
 * 	->show();
 * </code>
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Controller_Show extends Core_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'subject',
		'contentType',
		'properties',
		'sortPropertiesValues',
		'showGroups',
		'showResponsible',
		'showDiscountcards',
		'showTransactionsAmount',
		'showFriends',
		'friendsPerGroup',
		'showConnectedProviders',
		'showMaillists',
		'showAffiliats',
		'showAffiliatsTree',
		'showForumCounts',
		'fastRegistration',
		'location',
		'dateFrom',
		'dateTo',
	);

	/**
	 * List of properties
	 * @var array
	 */
	protected $_aProperties = array();

	/**
	 * Property directories
	 * @var array
	 */
	protected $_aProperty_Dirs = array();

	/**
	 * Constructor.
	 * @param Siteuser_Model $oSiteuser user
	 */
	public function __construct(Siteuser_Model $oSiteuser)
	{
		parent::__construct($oSiteuser->clearEntities());

		$this->properties = $this->showDiscountcards = $this->showTransactionsAmount = $this->showFriends = $this->showGroups = $this->showMaillists
			= $this->showAffiliats = $this->showAffiliatsTree = $this->showForumCounts = $this->showResponsible = FALSE;

		$this->showConnectedProviders = $this->sortPropertiesValues = TRUE;

		$this->friendsPerGroup = 6;
		$this->contentType = 'text/plain';
	}

	/**
	 * Check valid CSRF-token
	 * @param string $secret_csrf Token to check
	 * @return boolean
	 */
	public function checkCsrf($secret_csrf)
	{
		$oSiteuser = $this->getEntity();

		$aConfig = Siteuser_Controller::getConfig($oSiteuser->site_id);

		return Core_Security::checkCsrf($secret_csrf, $aConfig['csrfLifetime']);
	}

	/**
	 * Get user by login and password
	 * @param string $login login
	 * @param string $password password
	 * @return Siteuser_Model|NULL|FALSE FALSE if access is temporarily denied, NULL if the siteuser is not found
	 */
	public function getByLoginAndPassword($login, $password)
	{
		$bAvailable = self::checkAuthorizationPossibility();

		if ($bAvailable)
		{
			$oSite = $this->getEntity()->Site;
			
			$oSiteusers = $oSite->Siteusers;
			$oSiteusers->queryBuilder()
				//->clear()
				->where('login', '=', $login)
				->where('password', '=', Core_Hash::instance()->hash($password))
				->limit(1);

			$aSiteusers = $oSiteusers->findAll(FALSE);

			$oSiteuser = isset($aSiteusers[0])
				? $aSiteusers[0]
				: NULL;

			if (!$oSiteuser)
			{
				self::addFailedLoginAttempt();
			}

			return $oSiteuser;
		}

		return FALSE;
	}


	/**
	 * Check the possibility of authorization based on Siteuser_Accessdenied
	 * @return bool
	 */
	static public function checkAuthorizationPossibility()
	{
		$ip = Core::getClientIp();

		$bAvailable = TRUE;

		// Получаем количество неудачных попыток
		$iCountAccessdenied = Core_Entity::factory('Siteuser_Accessdenied')->getCountByIp($ip);

		// Были ли у данного пользователя неудачные попытки входа в систему администрирования за последние 24 часа?
		if ($iCountAccessdenied)
		{
			// Last Siteuser_Accessdenied by IP
			$oSiteuser_Accessdenied = Core_Entity::factory('Siteuser_Accessdenied')->getLastByIp($ip);

			if (!is_null($oSiteuser_Accessdenied))
			{
				// Определяем интервал времени между последней неудачной попыткой входа в систему
				// и текущим временем входа в систему
				$delta = time() - Core_Date::sql2timestamp($oSiteuser_Accessdenied->datetime);

				// Определяем период времени, в течении которого пользователю, имевшему неудачные
				// попытки доступа в систему запрещен вход в систему
				$delta_access_denied = $iCountAccessdenied > 2
					? 5 * exp(2.2 * log($iCountAccessdenied - 1))
					: 5 * $iCountAccessdenied;

				// Если период запрета доступа в систему не истек
				if ($delta_access_denied > $delta)
				{
					$bAvailable = FALSE;
				}
			}
		}
		
		return $bAvailable;
	}

	/**
	 * Check the possibility of authorization based on Siteuser_Accessdenied
	 */
	static public function addFailedLoginAttempt()
	{
		$ip = Core::getClientIp();
		
		// Save attempt
		$oSiteuser_Accessdenied = Core_Entity::factory('Siteuser_Accessdenied');
		$oSiteuser_Accessdenied->datetime = Core_Date::timestamp2sql(time());
		$oSiteuser_Accessdenied->ip = $ip;
		$oSiteuser_Accessdenied->save();
	}

	/**
	 * Show built data
	 * @return self
	 * @hostcms-event Siteuser_Controller_Show.onBeforeRedeclaredShow
	 */
	public function show()
	{
		Core_Event::notify(get_class($this) . '.onBeforeRedeclaredShow', $this);

		$oSiteuser = $this->getEntity();

		if ($oSiteuser->id == 0)
		{
			$this->addEntity(
				Core::factory('Core_Xml_Entity')
					->name('captcha_id')
					->value(Core_Captcha::getCaptchaId())
			)
			->addEntity(
				Core::factory('Core_Xml_Entity')
					->name('csrf_token')
					->value(Core_Security::getCsrfToken())
			);
		}

		$oSiteuser->showXmlProperties(TRUE, $this->sortPropertiesValues);

		$oCurrentSiteuser = Core_Entity::factory('Siteuser')->getCurrent();
		if (!is_null($oCurrentSiteuser))
		{
			$this->addCacheSignature('currentSiteuserId=' . $oCurrentSiteuser);
			$this->addEntity(
				Core::factory('Core_Xml_Entity')
					->name('current_siteuser_id')
					->value($oCurrentSiteuser->id)
			);
		}

		// Редирект после авторизации
		!is_null($this->location) && $this->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('location')
				->value($this->location)
		);

		// Быстрая регистрация
		!is_null($this->fastRegistration) && $this->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('fastRegistration')
				->value($this->fastRegistration)
		);

		// Показывать дополнительные свойства
		if ($this->properties)
		{
			$oSiteuser_Property_List = Core_Entity::factory('Siteuser_Property_List', $oSiteuser->site_id);

			$aProperties = $oSiteuser_Property_List->Properties->findAll();
			foreach ($aProperties as $oProperty)
			{
				$oProperty->clearEntities();
				$this->applyForbiddenAllowedTags('/siteuser/properties/property', $oProperty);
				$this->_aProperties[$oProperty->property_dir_id][] = $oProperty;
			}

			$aProperty_Dirs = $oSiteuser_Property_List->Property_Dirs->findAll();
			foreach ($aProperty_Dirs as $oProperty_Dir)
			{
				$oProperty_Dir->clearEntities();
				$this->applyForbiddenAllowedTags('/siteuser/properties/property_dir', $oProperty_Dir);
				$this->_aProperty_Dirs[$oProperty_Dir->parent_id][] = $oProperty_Dir;
			}

			// Список свойств
			$Siteuser_Properties = Core::factory('Core_Xml_Entity')
				->name('properties');

			$this->addEntity($Siteuser_Properties);

			$this->_addPropertiesList(0, $Siteuser_Properties);
		}

		// Дисконтные карты
		if ($this->showDiscountcards || $this->showTransactionsAmount)
		{
			$aShops = $oSiteuser->Site->Shops->findAll();
			foreach ($aShops as $oShop)
			{
				$oShop->clearEntities();
				$this->applyForbiddenAllowedTags('/siteuser/shop', $oShop);
				$this->addEntity($oShop);

				if ($this->showDiscountcards)
				{
					$aShop_Discountcards = $oSiteuser->Shop_Discountcards->getAllByshop_id($oShop->id);
					foreach ($aShop_Discountcards as $oShop_Discountcard)
					{
						if ($oShop_Discountcard->active)
						{
							$oShop_Discountcard->clearEntities();
							$this->applyForbiddenAllowedTags('/siteuser/shop/shop_discountcard', $oShop_Discountcard);

							$oShop->addEntity(
								$oShop_Discountcard
									->addEntity(
										Core::factory('Core_Xml_Entity')
											->name('bonuses_amount')
											->value(
												$oShop_Discountcard->getBonusesAmount()
											)
									)
							);
						}
					}
				}

				if ($this->showTransactionsAmount)
				{
					$oShop->addEntity(
						Core::factory('Core_Xml_Entity')
							->name('transaction_amount')
							->value(
								$oSiteuser->getTransactionsAmount($oShop)
							)
					);
				}
			}
		}

		// Друзья
		if ($this->showFriends)
		{
			// Отношения пользователя к текущему авторизованному
			if (!is_null($oCurrentSiteuser))
			{
				$oCurrent_Siteuser_Relation = Core::factory('Core_Xml_Entity')
					->name('current_siteuser_relation');

				// Друг
				$oFriend_Relation = $oSiteuser->Siteuser_Relationships->getByRecipient_siteuser_id($oCurrentSiteuser->id);
				!is_null($oFriend_Relation) && $oCurrent_Siteuser_Relation->addEntity($oFriend_Relation);

				// Подписчик
				$oSubscriber_Relation = $oCurrentSiteuser->Siteuser_Relationships->getByRecipient_siteuser_id($oSiteuser->id);
				!is_null($oSubscriber_Relation) && $oCurrent_Siteuser_Relation->addEntity($oSubscriber_Relation);

				$this->addEntity($oCurrent_Siteuser_Relation);
			}

			// N друзей в корне
			$oSiteuser_Relation_Type_0 = Core::factory('Core_Xml_Entity')
				->name('siteuser_relationship_type')
				->addAttribute('id', 0);
			if ($this->friendsPerGroup)
			{
				$aSiteuser_Relationships = $this->_getSiteuserRelationships(0);
				foreach ($aSiteuser_Relationships as $oSiteuser_Relationship)
				{
					$oSiteuser_Relation_Type_0->addEntity(
						$oSiteuser_Relationship->clearEntities()->showXmlRecipientSiteusers(TRUE)
					);
				}
			}
			$this->addEntity($oSiteuser_Relation_Type_0);

			// Список видов друзей + N друзей в каждом виде
			$oSiteuser_Relationship_Types = $oSiteuser->Site->Siteuser_Relationship_Types->findAll();
			foreach ($oSiteuser_Relationship_Types as $oSiteuser_Relationship_Type)
			{
				$this->addEntity($oSiteuser_Relationship_Type->clearEntities());

				if ($this->friendsPerGroup)
				{
					$aSiteuser_Relationships = $this->_getSiteuserRelationships($oSiteuser_Relationship_Type->id);
					foreach ($aSiteuser_Relationships as $oSiteuser_Relationship)
					{
						$oSiteuser_Relationship_Type->addEntity(
							$oSiteuser_Relationship->clearEntities()->showXmlRecipientSiteusers(TRUE)
						);
					}
				}
			}

			// Подписчики
			/*
			SELECT t1.*
			FROM `siteuser_relationships` t1
			LEFT OUTER JOIN `siteuser_relationships` t2
			 ON t1.`siteuser_id` = t2.`recipient_siteuser_id`
				AND t1.`recipient_siteuser_id` = t2.`siteuser_id`
			WHERE t1.`recipient_siteuser_id` = '3' AND t2.id IS NULL
			*/
			$oCore_QueryBuilder_Select = Core_QueryBuilder::select('t1.*')
				->from(array('siteuser_relationships', 't1'))
				->leftJoin(array('siteuser_relationships', 't2'), 't1.siteuser_id', '=', Core_QueryBuilder::expression('`t2`.`recipient_siteuser_id`'),
					array(
						array('AND' => array('t1.recipient_siteuser_id', '=', Core_QueryBuilder::expression('`t2`.`siteuser_id`'))),
					)
				)
				->where('t1.recipient_siteuser_id', '=', $oSiteuser->id)
				->where('t2.id', 'IS', NULL)
				->orderBy('id', 'DESC')
				->limit($this->friendsPerGroup);

			$aSiteuser_Relationships = $oCore_QueryBuilder_Select
				->execute()
				->asObject('Siteuser_Relationship_Model')
				->result();

			$oSiteuser_Subscribers = Core::factory('Core_Xml_Entity')
				->name('siteuser_subscribers');

			foreach ($aSiteuser_Relationships as $oSiteuser_Relationship)
			{
				$oSiteuser_Subscribers->addEntity(
					$oSiteuser_Relationship
						->clearEntities()
						->showXmlSiteusers(TRUE)
				);
			}
			$this->addEntity($oSiteuser_Subscribers);

			// Исходящие подписки
			$oCore_QueryBuilder_Select = Core_QueryBuilder::select('t1.*')
				->from(array('siteuser_relationships', 't1'))
				->leftJoin(array('siteuser_relationships', 't2'), 't1.siteuser_id', '=', Core_QueryBuilder::expression('`t2`.`recipient_siteuser_id`'),
					array(
						array('AND' => array('t1.recipient_siteuser_id', '=', Core_QueryBuilder::expression('`t2`.`siteuser_id`'))),
					)
				)
				->where('t1.siteuser_id', '=', $oSiteuser->id)
				->where('t2.id', 'IS', NULL)
				->orderBy('id', 'DESC')
				->limit($this->friendsPerGroup);

			$aSiteuser_Relationships = $oCore_QueryBuilder_Select
				->execute()
				->asObject('Siteuser_Relationship_Model')
				->result();

			$oSiteuser_Subscribes = Core::factory('Core_Xml_Entity')
				->name('siteuser_subscribes');

			foreach ($aSiteuser_Relationships as $oSiteuser_Relationship)
			{
				$oSiteuser_Subscribes->addEntity(
					$oSiteuser_Relationship->clearEntities()
						->showXmlSiteusers(FALSE)
						->addEntity(
							Core_Entity::factory('Siteuser', $oSiteuser_Relationship->recipient_siteuser_id)
								->clearEntities()
								->showXmlProperties(TRUE, $this->sortPropertiesValues)
						)
				);
			}
			$this->addEntity($oSiteuser_Subscribes);
		}

		// Почтовые рассылки
		if ($this->showMaillists && Core::moduleIsActive('maillist'))
		{
			// Уже подписанные рассылки 'Maillist_Siteusers'
			$oSiteuser->showXmlMaillists(TRUE);

			// Список доступных рассылок 'Maillists'
			$this->addEntities($oSiteuser->getAllowedMaillists());
		}

		$this->addEntity(
			$oSiteuser->Site->clearEntities()->showXmlAlias()->showXmlSiteuserIdentityProviders()
		);

		// Партнерские программы, доступные пользователю
		if ($this->showAffiliats)
		{
			$aShops = $oSiteuser->Site->Shops->findAll();
			foreach ($aShops as $oShop)
			{
				$oAffiliate_Plans = $oShop->Affiliate_Plans;
				$oAffiliate_Plans->queryBuilder()
					->join('siteuser_groups', 'siteuser_groups.id', '=', 'affiliate_plans.siteuser_group_id')
					->join('siteuser_group_lists', 'siteuser_groups.id', '=', 'siteuser_group_lists.siteuser_group_id')
					->where('siteuser_group_lists.siteuser_id', '=', $oSiteuser->id);

				$aAffiliate_Plans = $oAffiliate_Plans->findAll();
				count($aAffiliate_Plans) && $this->addEntity(
					$oShop
						->clearEntities()
						->addEntities($aAffiliate_Plans)
				);
			}
		}

		// Партнерские программы, дерево пользователей
		if ($this->showAffiliatsTree)
		{
			$this->dateFrom && $this->addEntity(
				Core::factory('Core_Xml_Entity')
					->name('date_from')
					->value(Core_Date::sql2date($this->dateFrom))
			);

			$this->dateTo && $this->addEntity(
				Core::factory('Core_Xml_Entity')
					->name('date_to')
					->value(Core_Date::sql2date($this->dateTo))
			);

			$this->_addAffiliatsTree($oSiteuser, $this);
		}

		if ($this->showGroups)
		{
			$aSiteuser_Groups = $oSiteuser->Siteuser_Groups->findAll();
			foreach ($aSiteuser_Groups as $oSiteuser_Group)
			{
				$this->addEntity(
					$oSiteuser_Group->clearEntities()
				);
			}
		}

		if ($this->showForumCounts && Core::moduleIsActive('forum'))
		{
			$count = 0;

			$aForum_Siteuser_Counts = Core_Entity::factory('Forum_Siteuser_Count')->getAllBySiteuser_id($oSiteuser->id);
			foreach ($aForum_Siteuser_Counts as $oForum_Siteuser_Count)
			{
				$count += $oForum_Siteuser_Count->value;
			}

			$this->addEntity(
				Core::factory('Core_Xml_Entity')
					->name('messages_count')
					->value($count)
			);
		}

		if ($this->showConnectedProviders)
		{
			$aSiteuser_Identities = $oSiteuser->Siteuser_Identities->findAll(FALSE);

			count($aSiteuser_Identities) && $this->addEntity(
				Core::factory('Core_Xml_Entity')
					->name('siteuser_identities')
					->addEntities($aSiteuser_Identities)
			);
		}

		if ($this->showResponsible)
		{
			$aUsers = $oSiteuser->Users->findAll(FALSE);

			count($aUsers) && $this->addEntity(
				Core::factory('Core_Xml_Entity')
					->name('responsible_users')
					->addEntities($aUsers)
			);
		}

		return parent::show();
	}

	/**
	 * Get siteuser relationships
	 * @param int $siteuser_relationship_type_id
	 * @return array array of Siteuser_Relationship_Model
	 */
	protected function _getSiteuserRelationships($siteuser_relationship_type_id)
	{
		$oSiteuser = $this->getEntity();

		/*SELECT t1.*
		FROM `siteuser_relationships` t1
		JOIN `siteuser_relationships` t2
		 ON t1.`siteuser_id` = t2.`recipient_siteuser_id`
			AND t1.`siteuser_id` = '3'
			AND t1.`siteuser_relationship_type_id` = 0
			AND t1.`recipient_siteuser_id` = t2.`siteuser_id`*/

		$oCore_QueryBuilder_Select = Core_QueryBuilder::select('t1.*')
			->from(array('siteuser_relationships', 't1'))
			->join(array('siteuser_relationships', 't2'), 't1.siteuser_id', '=', Core_QueryBuilder::expression('`t2`.`recipient_siteuser_id`'),
				array(
					array('AND' => array('t1.siteuser_id', '=', $oSiteuser->id)),
					array('AND' => array('t1.siteuser_relationship_type_id', '=', $siteuser_relationship_type_id)),
					array('AND' => array('t1.recipient_siteuser_id', '=', Core_QueryBuilder::expression('`t2`.`siteuser_id`'))),
				)
			)
			->orderBy('id', 'DESC')
			->limit($this->friendsPerGroup);

		$aSiteuser_Relationships = $oCore_QueryBuilder_Select
			->execute()
			->asObject('Siteuser_Relationship_Model')
			->result();

		return $aSiteuser_Relationships;
	}

	/**
	 * Add tree of affiliats to XML
	 * @param Siteuser_Model $oSiteuser
	 * @param object $parentObject
	 * @return self
	 */
	protected function _addAffiliatsTree($oSiteuser, $parentObject)
	{
		$parentObject->addEntity(
			$subParentObject = Core::factory('Core_Xml_Entity')
				->name('affiliats')
		);

		$aSiteuser_Affiliates = $oSiteuser->Siteuser_Affiliates->findAll();

		if (count($aSiteuser_Affiliates))
		{
			foreach ($aSiteuser_Affiliates as $oSiteuser_Affiliate)
			{
				$subParentObject->addEntity(
					$oSiteuser_Affiliate->referral
						->clearEntities()
						// Дата приглашения пользователя
						->addEntity(
							Core::factory('Core_Xml_Entity')
								->name('invite_date')
								->value(
									Core_Date::strftime($oSiteuser->Site->date_time_format, Core_Date::sql2timestamp($oSiteuser_Affiliate->date))
								)
						)
				);

				$this->_addAffiliatsTree($oSiteuser_Affiliate->referral, $oSiteuser_Affiliate->referral);
			}
		}

		// Транзакции пользователя
		$oShop_Siteuser_Transactions = $oSiteuser->Shop_Siteuser_Transactions;

		$oShop_Siteuser_Transactions
			->queryBuilder()
			->where('active', '=', 1)
			->where('type', '=', 1);

		$this->dateFrom && $oShop_Siteuser_Transactions
			->queryBuilder()
			->where('datetime', '>=', $this->dateFrom);

		$this->dateTo && $oShop_Siteuser_Transactions
			->queryBuilder()
			->where('datetime', '<=', $this->dateTo);

		$aShop_Siteuser_Transactions = $oShop_Siteuser_Transactions->findAll();
		if (count($aShop_Siteuser_Transactions))
		{
			$parentObject->addEntity(
				$transactionsNode = Core::factory('Core_Xml_Entity')
					->name('transactions')
			);

			foreach ($aShop_Siteuser_Transactions as $oShop_Siteuser_Transaction)
			{
				$transactionsNode->addEntity(
					$oShop_Siteuser_Transaction->clearEntities()
				);
			}
		}
		return $this;
	}

	/**
	 * Add list of user's properties to XML
	 * @param int $parent_id parent directory
	 * @param object $parentObject
	 * @return self
	 */
	protected function _addPropertiesList($parent_id, $parentObject)
	{
		if (isset($this->_aProperty_Dirs[$parent_id]))
		{
			foreach ($this->_aProperty_Dirs[$parent_id] as $oProperty_Dir)
			{
				$parentObject->addEntity($oProperty_Dir);
				$this->_addPropertiesList($oProperty_Dir->id, $oProperty_Dir);
			}
		}

		if (isset($this->_aProperties[$parent_id]))
		{
			$parentObject->addEntities($this->_aProperties[$parent_id]);
		}

		return $this;
	}

	/**
	 * Send confirmation e-mail
	 * @param Xsl_Model $oXsl
	 * @return self
	 */
	public function sendConfirmationMail(Xsl_Model $oXsl)
	{
		$oSiteuser = $this->getEntity();

		$oSite = $oSiteuser->Site->clearEntities();

		$this->addEntity(
			$oSiteuser->Site->clearEntities()->showXmlAlias()
		);

		$sXml = $this->getXml();

		$content = Xsl_Processor::instance()
			->xml($sXml)
			->xsl($oXsl)
			->process();

		$this->clearEntities();

		$oCore_Mail = Core_Mail::instance()
			->to($oSiteuser->email)
			->from($oSite->getFirstEmail())
			->subject($this->subject)
			->message(trim($content))
			->contentType($this->contentType)
			->header('X-HostCMS-Reason', 'User-Registration')
			->header('Precedence', 'bulk')
			->messageId();

		$oSite->sender_name != ''
			&& $oCore_Mail->senderName($oSite->sender_name);

		$oCore_Mail->send();

		return $this;
	}

	/**
	 * Check location
	 * @param string $location URL
	 * @return mixed
	 */
	public function checkLocation($location)
	{
		return preg_match('|^[a-zA-Z0-9_\.\-/]*/$|Du', $location);
	}

	/**
	 * Перейти на страницу $location
	 * @param string $location URL
	 */
	public function go($location)
	{
		if ($this->checkLocation($location))
		{
			$oCore_Response = new Core_Response();

			$oCore_Response
				->status(301)
				->header('Location', $location)
				->sendHeaders();
			exit();
		}
	}

	/**
	 * Define handler for 410 error
	 * @return self
	 */
	public function error410()
	{
		Core_Page::instance()->error410();

		return $this;
	}

	/**
	 * Define handler for 404 error
	 * @return self
	 */
	public function error404()
	{
		Core_Page::instance()->error404();

		return $this;
	}

	/**
	 * Apply Affiliate to the Siteuser
	 * @param string $affiliateName Affiliate Name
	 * @return self
	 */
	public function applyAffiliate($affiliateName)
	{
		$oSiteuser = $this->getEntity();

		if (is_scalar($affiliateName) && strlen($affiliateName))
		{
			$oAffiliateSiteuser = $oSiteuser->Site->Siteusers->getByLogin($affiliateName);

			if (!is_null($oAffiliateSiteuser) && $oAffiliateSiteuser->id != $oSiteuser->id)
			{
				$oSiteuser_Affiliate = Core_Entity::factory('Siteuser_Affiliate');
				$oSiteuser_Affiliate->referral_siteuser_id = $oSiteuser->id;
				$oSiteuser_Affiliate->siteuser_id = $oAffiliateSiteuser->id;
				$oSiteuser_Affiliate->active = 1;
				$oSiteuser_Affiliate->save();
			}
		}

		return $this;
	}
}