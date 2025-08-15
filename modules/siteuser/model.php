<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Siteuser_Model extends Core_Entity
{
	/**
	 * Backend property
	 * @var string
	 */
	public $img_list_cashes = 0;

	/**
	 * Backend property
	 * @var string
	 */
	public $representative = 0;

	/**
	 * Backend property
	 * @var string
	 */
	public $cart = 0;

	/**
	 * Backend property
	 * @var string
	 */
	public $favorite = 0;

	/**
	 * Backend property
	 * @var string
	 */
	public $timeline = 0;

	/**
	 * Backend property
	 * @var string
	 */
	public $img_list_affiliates = 0;

	/**
	 * Backend property
	 * @var string
	 */
	public $counterparty = NULL;

	/**
	 * Backend property
	 * @var string
	 */
	public $img_list_people_companies = NULL;

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'siteuser_group' => array('through' => 'siteuser_group_list'),
		'siteuser_group_list' => array(),
		// Реферралы пользователя
		'siteuser_affiliate' => array(),
		'siteuser_identity' => array(),
		'shop_cart' => array(),
		'shop_favorite_list' => array(),
		'shop_favorite' => array(),
		'shop_order' => array(),
		'shop_siteuser_transaction' => array(),
		'maillist' => array('through' => 'maillist_siteuser'),
		'maillist_siteuser' => array(),
		'maillist_fascicle_log' => array(),
		'maillist_unsubscriber' => array(),
		'forum_topic_post' => array(),
		'forum_topic_view' => array(),
		'forum_topic_subscriber' => array(),
		'forum_siteuser_count' => array(),
		'helpdesk_ticket' => array(),
		'message_topics_by_sender' => array('model' => 'Message_Topic', 'foreign_key' => 'sender_siteuser_id'),
		'message_topics_by_recipient' => array('model' => 'Message_Topic', 'foreign_key' => 'recipient_siteuser_id'),
		'siteuser_relationship' => array(),
		'siteuser_relationship_recipient' => array('foreign_key' => 'recipient_siteuser_id', 'model' => 'Siteuser_Relationship'),
		'siteuser' => array('through' => 'siteuser_relationship', 'dependent_key' => 'recipient_siteuser_id'),
		'friend' => array(
			'through' => 'siteuser_relationship',
			'dependent_key' => 'recipient_siteuser_id',
			'model' => 'Siteuser',
			'foreign_key' => 'siteuser_id',
		),
		'siteuser_person' => array(),
		'siteuser_company' => array(),
		'siteuser_user' => array(),
		'event' => array('through' => 'event_user'),
		'event_siteuser' => array(),
		'user' => array('through' => 'siteuser_user'),
		'shop_discountcard' => array(),
		'lead' => array(),
		'siteuser_email' => array(),
		'siteuser_session' => array(),
		'siteuser_crm_note' => array(),
		'crm_note' => array('through' => 'siteuser_crm_note'),
	);

	/**
	 * One-to-one relations
	 * @var array
	 */
	protected $_hasOne = array(
		// Реферралом какого пользователя текущий является (кто является для него аффилиатом)
		'affiliate' => array(
			'model' => 'Siteuser',
			'foreign_key' => 'referral_siteuser_id',
			'through' => 'siteuser_affiliate',
			'dependent_key' => 'siteuser_id'
		),
		'siteuser_affiliate' => array(
			'foreign_key' => 'referral_siteuser_id',
		),
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'siteuser_type' => array(),
		'siteuser_status' => array(),
		'crm_source' => array(),
		'site' => array(),
		'user' => array(),
	);

	/**
	 * Forbidden tags. If list of tags is empty, all tags will show.
	 * @var array
	 */
	protected $_forbiddenTags = array(
		'deleted',
		'user_id',
		'datetime',
		'password'
	);

	/**
	 * List of skipped columns from table
	 * @var array
	 */
	protected $_skipColumns = array(
		'icq',
		'name',
		'surname',
		'patronymic',
		'company',
		'phone',
		'fax',
		'website',
		'postcode',
		'country',
		'city',
		'address'
	);

	public $icq = NULL;

	public $name = NULL;

	public $surname = NULL;

	public $patronymic = NULL;

	public $company = NULL;

	public $phone = NULL;

	public $fax = NULL;

	public $website = NULL;

	public $postcode = NULL;

	public $country = NULL;

	public $city = NULL;

	public $address = NULL;

	/**
	 * Column consist item's name
	 * @var string
	 */
	protected $_nameColumn = 'login';

	/**
	 * List of preloaded values
	 * @var array
	 */
	/*protected $_preloadValues = array(
		'active' => 1
	);*/

	/**
	 * Attach session to IP
	 * @boolean
	 */
	protected $_attachSessionToIp;

	public function attachSessionToIp($bAttachSessionToIp)
	{
		$this->_attachSessionToIp = $bAttachSessionToIp;
		return $this;
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
			$this->_preloadValues['site_id'] = defined('CURRENT_SITE') ? CURRENT_SITE : 0;
			$this->_preloadValues['datetime'] = Core_Date::timestamp2sql(time());
			$this->_preloadValues['ip'] = Core::getClientIp();
			$this->_preloadValues['guid'] = Core_Guid::get();
		}

		// Check if the `Save-Data` header not exists and isn't set to a value of "on"
		$this->_attachSessionToIp = !isset($_SERVER['HTTP_SAVE_DATA']) || strtolower($_SERVER['HTTP_SAVE_DATA']) !== 'on';
	}

	/**
	 * Get current user
	 * @param $bCheckSite Check siteuser's site and current site
	 * @return Siteuser_Model|NULL
	 * @hostcms-event siteuser.onBeforeGetCurrent
	 */
	public function getCurrent($bCheckSite = FALSE)
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetCurrent', $this);

		return Siteuser_Controller::getCurrent($bCheckSite);
	}

	/**
	 * Get object directory href
	 * @return string
	 */
	public function getDirHref()
	{
		return $this->Site->uploaddir . 'users' . '/' . Core_File::getNestingDirPath($this->id, $this->Site->nesting_level) . '/';
	}

	/**
	 * Get object directory path
	 * @return string
	 */
	public function getDirPath()
	{
		return CMS_FOLDER . $this->getDirHref();
	}

	/**
	 * Values of all properties of user
	 * @var array
	 */
	protected $_propertyValues = NULL;

	/**
	 * Values of all properties of element
	 * @param boolean $bCache cache mode
	 * @param array $aPropertiesId array of properties' IDs
	 * @param boolean $bSorting sort results, default FALSE
	 * @return array Property_Value
	 */
	public function getPropertyValues($bCache = TRUE, $aPropertiesId = array(), $bSorting = FALSE)
	{
		if ($bCache && !is_null($this->_propertyValues))
		{
			return $this->_propertyValues;
		}

		if (!is_array($aPropertiesId) || !count($aPropertiesId))
		{
			$aProperties = Core_Entity::factory('Siteuser_Property_List', $this->site_id)
				->Properties
				->findAll();

			$aPropertiesId = array();
			foreach ($aProperties as $oProperty)
			{
				$aPropertiesId[] = $oProperty->id;
			}
		}

		$aReturn = Property_Controller_Value::getPropertiesValues($aPropertiesId, $this->id, $bCache, $bSorting);

		// setHref()
		foreach ($aReturn as $oProperty_Value)
		{
			$this->_preparePropertyValue($oProperty_Value);
		}

		$bCache && $this->_propertyValues = $aReturn;

		return $aReturn;
	}

	/**
	 * Prepare Property Value
	 * @param Property_Value_Model $oProperty_Value
	 */
	protected function _preparePropertyValue($oProperty_Value)
	{
		switch ($oProperty_Value->Property->type)
		{
			case 2:
				$oProperty_Value
					->setHref($this->getDirHref())
					->setDir($this->getDirPath());
			break;
			case 8:
				$oProperty_Value->dateFormat($this->Site->date_format);
			break;
			case 9:
				$oProperty_Value->dateTimeFormat($this->Site->date_time_format);
			break;
		}
	}

	/**
	 * Get user by login and password
	 * @param string $login login
	 * @param string $password password
	 * @return Siteuser_Model|NULL
	 * @see Siteuser_Controller_Show::getByLoginAndPassword
	 */
	public function getByLoginAndPassword($login, $password)
	{
		$bAvailable = Siteuser_Controller_Show::checkAuthorizationPossibility();

		if ($bAvailable)
		{
			$this->queryBuilder()
				//->clear()
				->where('login', '=', $login)
				->where('password', '=', Core_Hash::instance()->hash($password))
				->limit(1);

			$aSiteusers = $this->findAll(FALSE);

			$oSiteuser = isset($aSiteusers[0]) ? $aSiteusers[0] : NULL;

			if (!$oSiteuser)
			{
				Siteuser_Controller_Show::addFailedLoginAttempt();
			}

			return $oSiteuser;
		}

		return NULL;
	}

	/**
	 * Get user by login and email
	 * @param string $login login
	 * @param string $email email
	 * @return Siteuser_Model|NULL
	 */
	public function getByLoginAndEmail($login, $email)
	{
		$this->queryBuilder()
			//->clear()
			->where('login', '=', $login)
			->where('email', '=', $email)
			->limit(1);

		$aSiteusers = $this->findAll(FALSE);

		return isset($aSiteusers[0]) ? $aSiteusers[0] : NULL;
	}

	/**
	 * Copy object
	 * @return Core_Entity
	 * @hostcms-event siteuser.onAfterRedeclaredCopy
	 */
	public function copy()
	{
		$newObject = parent::copy();

		$aPropertyValues = $this->getPropertyValues(FALSE);

		// Create destination dir

		count($aPropertyValues) && $newObject->createDir();

		foreach ($aPropertyValues as $oPropertyValue)
		{
			$oNewPropertyValue = clone $oPropertyValue;
			$oNewPropertyValue->entity_id = $newObject->id;
			$oNewPropertyValue->save();

			if ($oNewPropertyValue->Property->type == 2)
			{
				// Копируем файлы
				$oPropertyValue->setDir($this->getDirPath());
				$oNewPropertyValue->setDir($newObject->getDirPath());

				if (is_file($oPropertyValue->getLargeFilePath()))
				{
					try
					{
						Core_File::copy($oPropertyValue->getLargeFilePath(), $oNewPropertyValue->getLargeFilePath());
					} catch (Exception $e) {}
				}

				if (is_file($oPropertyValue->getSmallFilePath()))
				{
					try
					{
						Core_File::copy($oPropertyValue->getSmallFilePath(), $oNewPropertyValue->getSmallFilePath());
					} catch (Exception $e) {}
				}
			}
		}

		Core_Event::notify($this->_modelName . '.onAfterRedeclaredCopy', $newObject, array($this));

		return $newObject;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return Core_Entity
	 * @hostcms-event siteuser.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		// Удаляем значения доп. свойств
		$aPropertyValues = $this->getPropertyValues(FALSE);
		foreach ($aPropertyValues as $oPropertyValue)
		{
			$oPropertyValue->Property->type == 2 && $oPropertyValue->setDir($this->getDirPath());
			$oPropertyValue->delete();
		}

		// Удаляем связи с группами пользователей
		$this->Siteuser_Group_Lists->deleteAll(FALSE);

		// Удаляем аффилиатов
		$this->Siteuser_Affiliates->deleteAll(FALSE);

		// Удаляем запись о реферрале
		$this->Siteuser_Affiliate->delete();

		// Друзья
		$this->Siteuser_Relationships->deleteAll(FALSE);
		$this->Siteuser_Relationship_Recipients->deleteAll(FALSE);

		$this->Siteuser_Identities->deleteAll(FALSE);

		$this->Siteuser_People->deleteAll(FALSE);
		$this->Siteuser_Companies->deleteAll(FALSE);
		$this->Siteuser_Users->deleteAll(FALSE);
		$this->Siteuser_Emails->deleteAll(FALSE);

		// Maillist
		if (Core::moduleIsActive('maillist'))
		{
			$this->Maillist_Siteusers->deleteAll(FALSE);
			$this->Maillist_Fascicle_Logs->deleteAll(FALSE);
			$this->Maillist_Unsubscribers->deleteAll(FALSE);
		}

		if (Core::moduleIsActive('shop'))
		{
			$this->Shop_Carts->deleteAll(FALSE);
			$this->Shop_Favorite_Lists->deleteAll(FALSE);
			$this->Shop_Favorites->deleteAll(FALSE);
			//$this->Shop_Orders->deleteAll(FALSE);
			$this->Shop_Siteuser_Transactions->deleteAll(FALSE);
			$this->Shop_Discountcards->deleteAll(FALSE);
		}

		if (Core::moduleIsActive('forum'))
		{
			$this->Forum_Siteuser_Counts->deleteAll(FALSE);
		}

		if (Core::moduleIsActive('lead'))
		{
			Core_QueryBuilder::update('leads')
				->set('siteuser_id', 0)
				->where('siteuser_id', '=', $this->id)
				->execute();
		}

		$this->Siteuser_Sessions->deleteAll(FALSE);
		$this->Crm_Notes->deleteAll(FALSE);
		$this->Siteuser_Crm_Notes->deleteAll(FALSE);

		return parent::delete($primaryKey);
	}

	/**
	 * Create directory for item
	 * @return self
	 */
	public function createDir()
	{
		if (!Core_File::isDir($this->getDirPath()))
		{
			try
			{
				Core_File::mkdir($this->getDirPath(), CHMOD, TRUE);
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Get path for files
	 * @return string
	 */
	public function getPath()
	{
		return "users/info/{$this->login}/";
	}

	/**
	 * Search indexation
	 * @return Search_Page
	 * @hostcms-event siteuser.onBeforeIndexing
	 * @hostcms-event siteuser.onAfterIndexing
	 */
	public function indexing()
	{
		$oSearch_Page = new stdClass();

		Core_Event::notify($this->_modelName . '.onBeforeIndexing', $this, array($oSearch_Page));

		$eventResult = Core_Event::getLastReturn();

		if (!is_null($eventResult))
		{
			return $eventResult;
		}

		$oSearch_Page->text = htmlspecialchars((string) $this->login) . ' ' . htmlspecialchars((string) $this->email);

		// People
		$aSiteuser_People = $this->Siteuser_People->findAll(FALSE);
		foreach ($aSiteuser_People as $oSiteuser_Person)
		{
			$oSearch_Page->text .= ' ' . htmlspecialchars((string) $oSiteuser_Person->name) . ' ' .
				htmlspecialchars((string) $oSiteuser_Person->surname) . ' ' .
				htmlspecialchars((string) $oSiteuser_Person->patronymic) . ' ' .
				htmlspecialchars((string) $oSiteuser_Person->post) . ' ';

				// htmlspecialchars($oSiteuser_Person->postcode) . ' ' .
				// htmlspecialchars($oSiteuser_Person->country) . ' ' .
				// htmlspecialchars($oSiteuser_Person->city) . ' ' .
				// htmlspecialchars($oSiteuser_Person->address);

			$aDirectory_Addresses = $oSiteuser_Person->Directory_Addresses->findAll(FALSE);
			foreach ($aDirectory_Addresses as $oDirectory_Address)
			{
				$oDirectory_Address->public
					&& $oSearch_Page->text .= ' ' . htmlspecialchars((string) $oDirectory_Address->postcode) . ' ' .
						htmlspecialchars((string) $oDirectory_Address->country) . ' ' .
						htmlspecialchars((string) $oDirectory_Address->city) . ' ' .
						htmlspecialchars((string) $oDirectory_Address->value);
			}

			$aDirectory_Phones = $oSiteuser_Person->Directory_Phones->findAll(FALSE);
			foreach ($aDirectory_Phones as $oDirectory_Phone)
			{
				$oDirectory_Phone->public
					&& $oSearch_Page->text .= ' ' . htmlspecialchars((string) $oDirectory_Phone->value);
			}

			$aDirectory_Websites = $oSiteuser_Person->Directory_Websites->findAll(FALSE);
			foreach ($aDirectory_Websites as $oDirectory_Website)
			{
				$oDirectory_Website->public
					&& $oSearch_Page->text .= ' ' . htmlspecialchars((string) $oDirectory_Website->value);
			}

			$aDirectory_Emails = $oSiteuser_Person->Directory_Emails->findAll(FALSE);
			foreach ($aDirectory_Emails as $oDirectory_Email)
			{
				$oDirectory_Email->public
					&& $oSearch_Page->text .= ' ' . htmlspecialchars((string) $oDirectory_Email->value);
			}

			$aDirectory_Socials = $oSiteuser_Person->Directory_Socials->findAll(FALSE);
			foreach ($aDirectory_Socials as $oDirectory_Social)
			{
				$oDirectory_Social->public
					&& $oSearch_Page->text .= ' ' . htmlspecialchars((string) $oDirectory_Social->value);
			}

			$aDirectory_Messengers = $oSiteuser_Person->Directory_Messengers->findAll(FALSE);
			foreach ($aDirectory_Messengers as $oDirectory_Messenger)
			{
				$oDirectory_Messenger->public
					&& $oSearch_Page->text .= ' ' . htmlspecialchars((string) $oDirectory_Messenger->value);
			}
		}

		// Companies
		$aSiteuser_Companies = $this->Siteuser_Companies->findAll(FALSE);
		foreach ($aSiteuser_Companies as $oSiteuser_Company)
		{
			$oSearch_Page->text .= ' ' . htmlspecialchars((string) $oSiteuser_Company->name);

			$aDirectory_Addresses = $oSiteuser_Company->Directory_Addresses->findAll(FALSE);
			foreach ($aDirectory_Addresses as $oDirectory_Address)
			{
				$oDirectory_Address->public
					&& $oSearch_Page->text .= ' ' . htmlspecialchars((string) $oDirectory_Address->postcode) . ' ' .
						htmlspecialchars((string) $oDirectory_Address->country) . ' ' .
						htmlspecialchars((string) $oDirectory_Address->city) . ' ' .
						htmlspecialchars((string) $oDirectory_Address->value);
			}

			$aDirectory_Phones = $oSiteuser_Company->Directory_Phones->findAll(FALSE);
			foreach ($aDirectory_Phones as $oDirectory_Phone)
			{
				$oDirectory_Phone->public
					&& $oSearch_Page->text .= ' ' . htmlspecialchars((string) $oDirectory_Phone->value);
			}

			$aDirectory_Websites = $oSiteuser_Company->Directory_Websites->findAll(FALSE);
			foreach ($aDirectory_Websites as $oDirectory_Website)
			{
				$oDirectory_Website->public
					&& $oSearch_Page->text .= ' ' . htmlspecialchars((string) $oDirectory_Website->value);
			}

			$aDirectory_Emails = $oSiteuser_Company->Directory_Emails->findAll(FALSE);
			foreach ($aDirectory_Emails as $oDirectory_Email)
			{
				$oDirectory_Email->public
					&& $oSearch_Page->text .= ' ' . htmlspecialchars((string) $oDirectory_Email->value);
			}

			$aDirectory_Socials = $oSiteuser_Company->Directory_Socials->findAll(FALSE);
			foreach ($aDirectory_Socials as $oDirectory_Social)
			{
				$oDirectory_Social->public
					&& $oSearch_Page->text .= ' ' . htmlspecialchars((string) $oDirectory_Social->value);
			}

			$aDirectory_Messengers = $oSiteuser_Company->Directory_Messengers->findAll(FALSE);
			foreach ($aDirectory_Messengers as $oDirectory_Messenger)
			{
				$oDirectory_Messenger->public
					&& $oSearch_Page->text .= ' ' . htmlspecialchars((string) $oDirectory_Messenger->value);
			}
		}

		$oSearch_Page->title = $this->login;

		if (Core::moduleIsActive('property'))
		{
			$aPropertyValues = $this->getPropertyValues(FALSE);
			foreach ($aPropertyValues as $oPropertyValue)
			{
				$oProperty = $oPropertyValue->Property;

				if ($oProperty->indexing)
				{
					// List
					if ($oProperty->type == 3 && Core::moduleIsActive('list'))
					{
						if ($oPropertyValue->value != 0)
						{
							$oList_Item = $oPropertyValue->List_Item;
							$oList_Item->id && $oSearch_Page->text .= htmlspecialchars((string) $oList_Item->value) . ' ' . htmlspecialchars((string) $oList_Item->description) . ' ';
						}
					}
					// Informationsystem
					elseif ($oProperty->type == 5 && Core::moduleIsActive('informationsystem'))
					{
						if ($oPropertyValue->value != 0)
						{
							$oInformationsystem_Item = $oPropertyValue->Informationsystem_Item;
							if ($oInformationsystem_Item->id)
							{
								$oSearch_Page->text .= htmlspecialchars((string) $oInformationsystem_Item->name) . ' ';
							}
						}
					}
					// Other type
					elseif ($oProperty->type != 2 && $oProperty->type != 10)
					{
						$oSearch_Page->text .= htmlspecialchars((string) $oPropertyValue->value) . ' ';
					}
				}
			}
		}

		if (Core::moduleIsActive('field'))
		{
			$aField_Values = Field_Controller_Value::getFieldsValues($this->getFieldIDs(), $this->id);
			foreach ($aField_Values as $oField_Value)
			{
				// List
				if ($oField_Value->Field->type == 3 && Core::moduleIsActive('list'))
				{
					if ($oField_Value->value != 0)
					{
						$oList_Item = $oField_Value->List_Item;
						$oList_Item->id && $oSearch_Page->text .= htmlspecialchars((string) $oList_Item->value) . ' ' . htmlspecialchars((string) $oList_Item->description) . ' ';
					}
				}
				// Informationsystem
				elseif ($oField_Value->Field->type == 5 && Core::moduleIsActive('informationsystem'))
				{
					if ($oField_Value->value != 0)
					{
						$oInformationsystem_Item = $oField_Value->Informationsystem_Item;
						if ($oInformationsystem_Item->id)
						{
							$oSearch_Page->text .= htmlspecialchars((string) $oInformationsystem_Item->name) . ' ' . $oInformationsystem_Item->description . ' ' . $oInformationsystem_Item->text . ' ';
						}
					}
				}
				// Shop
				elseif ($oField_Value->Field->type == 12 && Core::moduleIsActive('shop'))
				{
					if ($oField_Value->value != 0)
					{
						$oShop_Item = $oField_Value->Shop_Item;
						if ($oShop_Item->id)
						{
							$oSearch_Page->text .= htmlspecialchars((string) $oShop_Item->name) . ' ' . $oShop_Item->description . ' ' . $oShop_Item->text . ' ';
						}
					}
				}
				// Wysiwyg
				elseif ($oField_Value->Field->type == 6)
				{
					$oSearch_Page->text .= htmlspecialchars(strip_tags((string) $oField_Value->value)) . ' ';
				}
				// Other type
				elseif ($oField_Value->Field->type != 2)
				{
					$oSearch_Page->text .= htmlspecialchars((string) $oField_Value->value) . ' ';
				}
			}
		}

		$oSiteAlias = $this->Site->getCurrentAlias();
		if ($oSiteAlias)
		{
			$oSearch_Page->url = ($this->Site->https ? 'https://' : 'http://') . $oSiteAlias->name . '/' . $this->getPath();
		}
		else
		{
			return NULL;
		}

		$oSearch_Page->size = mb_strlen($oSearch_Page->text);
		$oSearch_Page->site_id = $this->site_id;
		$oSearch_Page->datetime = !is_null($this->datetime) ? $this->datetime : date('Y-m-d H:i:s');
		$oSearch_Page->module = 5;
		$oSearch_Page->module_id = $this->site_id;
		$oSearch_Page->inner = 0;
		$oSearch_Page->module_value_type = 0; // search_page_module_value_type
		$oSearch_Page->module_value_id = $this->id; // search_page_module_value_id

		$oSearch_Page->siteuser_groups = array(0);

		Core_Event::notify($this->_modelName . '.onAfterIndexing', $this, array($oSearch_Page));

		//$oSearch_Page->save();

		return $oSearch_Page;
	}

	/**
	 * Change site user status
	 * @return Siteuser_Model
	 * @hostcms-event siteuser.onBeforeChangeActive
	 * @hostcms-event siteuser.onAfterChangeActive
	 */
	public function changeActive()
	{
		Core_Event::notify($this->_modelName . '.onBeforeChangeActive', $this);

		$this->active = 1 - $this->active;
		$this->save();

		Core_Event::notify($this->_modelName . '.onAfterChangeActive', $this);

		return $this;
	}

	/**
	 * Show properties in XML
	 * @var mixed
	 */
	protected $_showXmlProperties = FALSE;

	/**
	 * Sort properties values in XML
	 * @var mixed
	 */
	protected $_xmlSortPropertiesValues = TRUE;

	/**
	 * Show properties in XML
	 * @param mixed $showXmlProperties array of allowed properties ID or boolean
	 * @return self
	 */
	public function showXmlProperties($showXmlProperties = TRUE, $xmlSortPropertiesValues = TRUE)
	{
		$this->_showXmlProperties = is_array($showXmlProperties)
			? array_combine($showXmlProperties, $showXmlProperties)
			: $showXmlProperties;

		$this->_xmlSortPropertiesValues = $xmlSortPropertiesValues;

		return $this;
	}

	/**
	 * Show maillists data in XML
	 * @var boolean
	 */
	protected $_showXmlMaillists = FALSE;

	/**
	 * Show maillist in XML
	 * @param boolean $showXmlMaillists
	 * @return self
	 */
	public function showXmlMaillists($showXmlMaillists = TRUE)
	{
		$this->_showXmlMaillists = $showXmlMaillists;
		return $this;
	}

	/**
	 * Show siteuser companies data in XML
	 * @var boolean
	 */
	protected $_showXmlCompanies = TRUE;

	/**
	 * Show siteuser companies in XML
	 * @param boolean $showXmlCompanies
	 * @return self
	 */
	public function showXmlCompanies($showXmlCompanies = TRUE)
	{
		$this->_showXmlCompanies = $showXmlCompanies;
		return $this;
	}

	/**
	 * Show siteuser people data in XML
	 * @var boolean
	 */
	protected $_showXmlPeople = TRUE;

	/**
	 * Show siteuser people in XML
	 * @param boolean $showXmlPeople
	 * @return self
	 */
	public function showXmlPeople($showXmlPeople = TRUE)
	{
		$this->_showXmlPeople = $showXmlPeople;
		return $this;
	}

	/**
	 * Get XML for entity and children entities
	 * @return string
	 * @hostcms-event siteuser.onBeforeRedeclaredGetXml
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
	 * @hostcms-event siteuser.onBeforeRedeclaredGetStdObject
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
	 * @hostcms-event siteuser.onBeforeAddPropertyValues
	 */
	protected function _prepareData()
	{
		$this->clearXmlTags()
			->addXmlTag('dir', '/' . $this->getDirHref())
			->addXmlTag('datetime', Core_Date::strftime($this->Site->date_time_format, Core_Date::sql2timestamp($this->datetime)))
			->addXmlTag('date', Core_Date::strftime($this->Site->date_format, Core_Date::sql2timestamp($this->datetime)))
			->addXmlTag('path', rawurlencode((string) $this->login))
			->addXmlTag('online', intval($this->isOnline()));

		// $bCompany = $this->company != '';
		$bCompany = $this->Siteuser_Companies->getCount() > 0;

		$aSiteuser_People = $this->Siteuser_People->findAll();
		if (count($aSiteuser_People))
		{
			if (!$bCompany || isset($aSiteuser_People[0]))
			{
				$oSiteuser_Person = $aSiteuser_People[0];

				$this
					->addXmlTag('name', $oSiteuser_Person->name)
					->addXmlTag('surname', $oSiteuser_Person->surname)
					->addXmlTag('patronymic', $oSiteuser_Person->patronymic)
					// ->addXmlTag('postcode', $oSiteuser_Person->postcode)
					// ->addXmlTag('country', $oSiteuser_Person->country)
					// ->addXmlTag('city', $oSiteuser_Person->city)
					// ->addXmlTag('address', $oSiteuser_Person->address)
					;

				// Directory_Addresses
				/*$aDirectory_Addresses = $oSiteuser_Person->Directory_Addresses->findAll();
				if (isset($aDirectory_Addresses[0]))
				{
					$this
						->addXmlTag('postcode', $aDirectory_Addresses[0]->postcode)
						->addXmlTag('country', $aDirectory_Addresses[0]->country)
						->addXmlTag('city', $aDirectory_Addresses[0]->city)
						->addXmlTag('address', $aDirectory_Addresses[0]->value);
				}

				// Directory_Phones
				$aDirectory_Phones = $oSiteuser_Person->Directory_Phones->findAll();
				if (isset($aDirectory_Phones[0]))
				{
					$this->addXmlTag('phone', $aDirectory_Phones[0]->value);
				}

				// Directory_Websites
				$aDirectory_Websites = $oSiteuser_Person->Directory_Websites->findAll();
				if (isset($aDirectory_Websites[0]))
				{
					$this->addXmlTag('website', $aDirectory_Websites[0]->value);
				}*/
			}

			foreach ($aSiteuser_People as $oSiteuser_Person)
			{
				$aDirectory_Addresses = $oSiteuser_Person->Directory_Addresses->findAll();
				$aDirectory_Phones = $oSiteuser_Person->Directory_Phones->findAll();
				$aDirectory_Websites = $oSiteuser_Person->Directory_Websites->findAll();
				$aDirectory_Emails = $oSiteuser_Person->Directory_Emails->findAll();
				$aDirectory_Socials = $oSiteuser_Person->Directory_Socials->findAll();
				$aDirectory_Messengers = $oSiteuser_Person->Directory_Messengers->findAll();

				$oSiteuser_Person
					->addEntities($aDirectory_Addresses)
					->addEntities($aDirectory_Phones)
					->addEntities($aDirectory_Emails)
					->addEntities($aDirectory_Socials)
					->addEntities($aDirectory_Messengers)
					->addEntities($aDirectory_Websites);
			}
		}

		$aSiteuser_Companies = $this->Siteuser_Companies->findAll();
		if (count($aSiteuser_Companies))
		{
			if ($bCompany || isset($aSiteuser_Companies[0]))
			{
				$oSiteuser_Company = $aSiteuser_Companies[0];

				$this
					->addXmlTag('company', $oSiteuser_Company->name);

				// Directory_Addresses
				/*$aDirectory_Addresses = $oSiteuser_Company->Directory_Addresses->findAll();
				if (isset($aDirectory_Addresses[0]))
				{
					$this
						->addXmlTag('postcode', $aDirectory_Addresses[0]->postcode)
						->addXmlTag('country', $aDirectory_Addresses[0]->country)
						->addXmlTag('city', $aDirectory_Addresses[0]->city)
						->addXmlTag('address', $aDirectory_Addresses[0]->value);
				}

				// Directory_Phones
				$aDirectory_Phones = $oSiteuser_Company->Directory_Phones->findAll();
				if (isset($aDirectory_Phones[0]))
				{
					$this->addXmlTag('phone', $aDirectory_Phones[0]->value);
				}

				// Directory_Websites
				$aDirectory_Websites = $oSiteuser_Company->Directory_Websites->findAll();
				if (isset($aDirectory_Websites[0]))
				{
					$this->addXmlTag('website', $aDirectory_Websites[0]->value);
				}*/
			}

			foreach ($aSiteuser_Companies as $oSiteuser_Company)
			{
				$aDirectory_Addresses = $oSiteuser_Company->Directory_Addresses->findAll();
				$aDirectory_Phones = $oSiteuser_Company->Directory_Phones->findAll();
				$aDirectory_Websites = $oSiteuser_Company->Directory_Websites->findAll();
				$aDirectory_Emails = $oSiteuser_Company->Directory_Emails->findAll();
				$aDirectory_Socials = $oSiteuser_Company->Directory_Socials->findAll();
				$aDirectory_Messengers = $oSiteuser_Company->Directory_Messengers->findAll();

				$oSiteuser_Company
					->addEntities($aDirectory_Addresses)
					->addEntities($aDirectory_Phones)
					->addEntities($aDirectory_Emails)
					->addEntities($aDirectory_Socials)
					->addEntities($aDirectory_Messengers)
					->addEntities($aDirectory_Websites);
			}
		}

		// Типы телефонов
		$oDirectoryPhoneTypesXmlEntity = Core::factory('Core_Xml_Entity')->name('directory_phone_types');
		$oDirectoryPhoneTypesXmlEntity->addEntities(
			Core_Entity::factory('Directory_Phone_Type')->findAll()
		);
		$this->addEntity($oDirectoryPhoneTypesXmlEntity);

		// Типы e-mail
		$oDirectoryEmailTypesXmlEntity = Core::factory('Core_Xml_Entity')->name('directory_email_types');
		$oDirectoryEmailTypesXmlEntity->addEntities(
			Core_Entity::factory('Directory_Email_Type')->findAll()
		);
		$this->addEntity($oDirectoryEmailTypesXmlEntity);

		// Типы социальных сетей
		$oDirectorySocialTypesXmlEntity = Core::factory('Core_Xml_Entity')->name('directory_social_types');
		$oDirectorySocialTypesXmlEntity->addEntities(
			Core_Entity::factory('Directory_Social_Type')->findAll()
		);
		$this->addEntity($oDirectorySocialTypesXmlEntity);

		// Типы мессенджеров
		$oDirectoryMessengerTypesXmlEntity = Core::factory('Core_Xml_Entity')->name('directory_messenger_types');
		$oDirectoryMessengerTypesXmlEntity->addEntities(
			Core_Entity::factory('Directory_Messenger_Type')->findAll()
		);
		$this->addEntity($oDirectoryMessengerTypesXmlEntity);

		// Типы адресов
		$oDirectoryAddressTypesXmlEntity = Core::factory('Core_Xml_Entity')->name('directory_address_types');
		$oDirectoryAddressTypesXmlEntity->addEntities(
			Core_Entity::factory('Directory_Address_Type')->findAll()
		);
		$this->addEntity($oDirectoryAddressTypesXmlEntity);

		$this->_showXmlPeople
			&& $this->addEntities($aSiteuser_People);

		$this->_showXmlCompanies
			&& $this->addEntities($aSiteuser_Companies);

		if ($this->_showXmlProperties)
		{
			if (is_array($this->_showXmlProperties))
			{
				$aProperty_Values = Property_Controller_Value::getPropertiesValues($this->_showXmlProperties, $this->id, FALSE, $this->_xmlSortPropertiesValues);
				foreach ($aProperty_Values as $oProperty_Value)
				{
					$this->_preparePropertyValue($oProperty_Value);
				}
			}
			else
			{
				$aProperty_Values = $this->getPropertyValues(TRUE, array(), $this->_xmlSortPropertiesValues);
			}

			Core_Event::notify($this->_modelName . '.onBeforeAddPropertyValues', $this, array($aProperty_Values));

			// Add all values
			$this->addEntities($aProperty_Values);
		}

		$this->_showXmlMaillists
			&& $this->addEntities($this->Maillist_Siteusers->findAll());

		return $this;
	}

	/**
	 * Set user as current
	 * @param int $expires cookie lifetime
	 * @return self
	 */
	public function setCurrent($expires = 2678400)
	{
		if (!$this->id)
		{
			throw new Core_Exception('Siteuser_Model::setCurrent() ID is null');
		}

		Siteuser_Controller::setCurrent($this, $expires, $this->_attachSessionToIp);

		return $this;
	}

	/**
	 * Unset current user from session
	 * @return self
	 */
	public function unsetCurrent()
	{
		Siteuser_Controller::unsetCurrent();

		return $this;
	}

	/**
	 * Change activity status to ON
	 * @return self
	 * @hostcms-event siteuser.onBeforeActivate
	 * @hostcms-event siteuser.onAfterActivate
	 */
	public function activate()
	{
		Core_Event::notify($this->_modelName . '.onBeforeActivate', $this);

		$this->active = 1;
		// Create new GUID
		$this->guid = Core_Guid::get();
		$this->save();

		Core_Event::notify($this->_modelName . '.onAfterActivate', $this);

		return $this;
	}

	/**
	 * Список доступных пользователю рассылок 'Maillists'
	 * @return array
	 */
	public function getAllowedMaillists()
	{
		$aSiteuserGroupsId = array();
		$aSiteuser_Groups = $this->Siteuser_Groups->findAll();

		if ($this->id)
		{
			foreach ($aSiteuser_Groups as $oSiteuser_Group)
			{
				$aSiteuserGroupsId[] = $oSiteuser_Group->id;
			}
		}
		else
		{
			// Get default siteuser's group
			$oSiteuser_Group = $this->Site->Siteuser_Groups->getDefault();
			!is_null($oSiteuser_Group) && $aSiteuserGroupsId[] = $oSiteuser_Group->id;
		}

		if (count($aSiteuserGroupsId))
		{
			$oMaillists = $this->Site->Maillists;
			$oMaillists->queryBuilder()
				->select('maillists.*')
				->join('maillist_siteuser_groups', 'maillists.id', '=', 'maillist_siteuser_groups.maillist_id')
				->where('maillist_siteuser_groups.siteuser_group_id', 'IN', $aSiteuserGroupsId)
				->groupBy('maillists.id');

			return $oMaillists->findAll();
		}

		return array();
	}

	/**
	 * Get all shop's transactions
	 * @param Shop_Model $oShop shop
	 * @return array
	 */
	public function getTransactions(Shop_Model $oShop)
	{
		$oShop_Siteuser_Transactions = $this->Shop_Siteuser_Transactions;
		$oShop_Siteuser_Transactions->queryBuilder()
			->where('active', '=', 1)
			->where('deleted', '=', 0)
			->where('shop_id', '=', $oShop->id);

		return $oShop_Siteuser_Transactions->findAll(FALSE);
	}

	/**
	 * Get amount of all shop transactions
	 * @param Shop_Model $oShop shop
	 * @return float
	 */
	public function getTransactionsAmount(Shop_Model $oShop)
	{
		$aTmp = Core_QueryBuilder::select(array('SUM(amount_base_currency)', 'amount'))
			->from('shop_siteuser_transactions')
			->where('shop_id', '=', $oShop->id)
			->where('siteuser_id', '=', $this->id)
			->where('deleted', '=', 0)
			->where('active', '=', 1)
			->execute()->asAssoc()->current();

		return round(floatval($aTmp['amount']), 2);
	}

	/**
	 * Add user to friend list
	 * @param Siteuser_Model $oSiteuser_Friend site user
	 * @return self
	 */
	public function addFriend(Siteuser_Model $oSiteuser_Friend)
	{
		if ($this->id != $oSiteuser_Friend->id)
		{
			$oFriends = $this->Friends;
			$oFriends->queryBuilder()
				->where('siteuser_relationships.recipient_siteuser_id', '=', $oSiteuser_Friend->id)
				->limit(1);
			$aFriends = $oFriends->findAll();

			// Add as friend
			!count($aFriends) && $this->add($oSiteuser_Friend);
		}
		return $this;
	}

	/**
	 * Remove user from friend list
	 * @param Siteuser_Model $oSiteuser_Friend site user
	 * @return self
	 */
	public function removeFriend(Siteuser_Model $oSiteuser_Friend)
	{
		if ($this->id != $oSiteuser_Friend->id)
		{
			$oSiteuser_Relationship = $this->Siteuser_Relationships->getByRecipient_siteuser_id($oSiteuser_Friend->id);

			// Remove friend
			!is_null($oSiteuser_Relationship) && $oSiteuser_Relationship->delete();
		}

		return $this;
	}

	/**
	 * Get entity description
	 * @return string
	 */
	public function getTrashDescription()
	{
		return htmlspecialchars(
			$this->name . ' ' . $this->surname . ' (' . $this->email . ')'
		);
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function counterpartyBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$sResult = '';

		$aSiteuserCompanies = $this->Siteuser_Companies->findAll();
		$aSiteuserPersons = $this->Siteuser_People->findAll();

		if (count($aSiteuserCompanies) || count($aSiteuserPersons))
		{
			$sResult .= '<div class="profile-container tickets-container"><ul class="tickets-list">';

			foreach ($aSiteuserCompanies as $oSiteuserCompany)
			{
				$oSiteuserCompany->id
					&& $sResult .= $oSiteuserCompany->getProfileBlock();
			}

			foreach ($aSiteuserPersons as $oSiteuserPerson)
			{
				$oSiteuserPerson->id
					&& $sResult .= $oSiteuserPerson->getProfileBlock();
			}

			$sResult .= '</ul></div>';
		}

		return $sResult;
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function loginBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$isOnline = $this->isOnline();

		$sStatus = $isOnline ? 'online' : 'offline';

		$lng = $isOnline ? 'siteuser_active' : 'siteuser_last_activity';

		$sStatusTitle = !is_null($this->last_activity)
			? Core::_('Siteuser.' . $lng, Core_Date::sql2datetime($this->last_activity))
			: '';

		$sResult = htmlspecialchars($this->login)
			. '&nbsp;<span title="' . htmlspecialchars($sStatusTitle) . '" class="' . htmlspecialchars($sStatus) . '"></span>';

		if ($this->crm_source_id)
		{
			$oCrm_Source = $this->Crm_Source;

			$sResult .= '&nbsp;<i title="' . htmlspecialchars((string) $oCrm_Source->name) . '" class="fa ' . htmlspecialchars((string) $oCrm_Source->icon) . ' fa-small" style="color: ' . htmlspecialchars((string) $oCrm_Source->color) . '"></i>';
		}

		// fix bug with model Siteuser_Group_Siteuser
		$aSiteuser_Groups = Core_Entity::factory('Siteuser', $this->id)->Siteuser_Groups->findAll(FALSE);

		if (count($aSiteuser_Groups))
		{
			$sListSiteuserGroups = '';

			foreach ($aSiteuser_Groups as $oSiteuser_Group)
			{
				$sListSiteuserGroups .= '<i class="fa-regular fa-folder-open" style="margin-right: 5px"></i><a onclick="'
					. "$.adminLoad({path: hostcmsBackend + '/siteuser/group/list/index.php', additionalParams: 'siteuser_group_id=" . $oSiteuser_Group->id . "', windowId: 'id_content'}); return false"
					. '">' . htmlspecialchars($oSiteuser_Group->name) . "</a><br/>";
			}

			//data-trigger="focus"
			$sResult .= '<a id="siteuser_' . $this->id . '"  class="siteuser_group_list_link" style="margin-right: 5px;" tabindex="0" role="button" data-toggle="popover" data-placement="right" data-content="' . htmlspecialchars($sListSiteuserGroups) . '" data-title="' . Core::_('Siteuser_Group.title') . '" data-titleclass="bordered-darkorange" data-container="#siteuser_' . $this->id . '"><i class="fa fa-folder-o gray"></i></a>';
		}

		$sResult .= ' ' . ($this->Siteuser_Type->name
			? '<span class="badge badge-square badge-max-width margin-right-5" title="' . htmlspecialchars($this->Siteuser_Type->name) . '" style="background-color: ' . htmlspecialchars($this->Siteuser_Type->color) . '">' . htmlspecialchars($this->Siteuser_Type->name) . '</span>'
			: '')
			. ($this->Siteuser_Status->name
				? '<span class="badge badge-square badge-max-width" title="' . htmlspecialchars($this->Siteuser_Status->name) . '" style="background-color: ' . htmlspecialchars($this->Siteuser_Status->color) . '">' . htmlspecialchars($this->Siteuser_Status->name) . '</span>' : '');

		return $sResult;
	}

	/**
	 * Show representatives in line
	 * @param integer $imageSize
	 */
	public function showRepresentativesInLine($imageSize = 20)
	{
		$oAdmin_Form = Core_Entity::factory('Admin_Form', 230); // Форма "Компании и представители"
		$oAdminUser = Core_Auth::getCurrentUser();

		$oCore_Html_Entity_Div = Core_Html_Entity::factory('Div')
			->class('avatar-user-siteuser-wrapper');

		$aSiteuser_Companies = $this->Siteuser_Companies->findAll();
		if (count($aSiteuser_Companies))
		{
			$oCore_Html_Entity_Span = Core_Html_Entity::factory('Span')
				->class('avatar-user avatar-user-siteuser margin-right-10');

			foreach ($aSiteuser_Companies as $oSiteuser_Company)
			{
				$oImageCompany = Core_Html_Entity::factory('Img')
					->src($oSiteuser_Company->getAvatar())
					->width($imageSize)
					->height($imageSize)
					->title($oSiteuser_Company->name);

				if ($oAdmin_Form->Admin_Form_Actions->checkAllowedActionForUser($oAdminUser, 'view'))
				{
					$oCore_Html_Entity_Span->add(
						Core_Html_Entity::factory('A')
							->href(Admin_Form_Controller::correctBackendPath("/{admin}/siteuser/representative/index.php") . "?hostcms[action]=view&hostcms[checked][0][" . $oSiteuser_Company->id . "]=1&show=company")
							->onclick("$.modalLoad({path: hostcmsBackend + '/siteuser/representative/index.php', action: 'view', operation: 'modal', additionalParams: 'hostcms[checked][0][" . $oSiteuser_Company->id . "]=1&show=company', windowId: 'id_content'}); return false")
							->add($oImageCompany)

					);
				}
				else
				{
					$oCore_Html_Entity_Span->add($oImageCompany);
				}
			}

			$oCore_Html_Entity_Span->add(
				Core_Html_Entity::factory('A')
					->href(Admin_Form_Controller::correctBackendPath("/{admin}/siteuser/representative/index.php") . "?hostcms[action]=view&hostcms[checked][0][" . $aSiteuser_Companies[0]->id . "]=1&show=company")
					->onclick("$.modalLoad({path: hostcmsBackend + '/siteuser/representative/index.php', action: 'view', operation: 'modal', additionalParams: 'hostcms[checked][0][" . $aSiteuser_Companies[0]->id . "]=1&show=company', windowId: 'id_content'}); return false")
					->add(
						Core_Html_Entity::factory('Span')
							->value(htmlspecialchars($aSiteuser_Companies[0]->name))
							->class('darkgray margin-left-5')
					)
			);

			$oCore_Html_Entity_Div->add($oCore_Html_Entity_Span);
		}

		$aSiteuser_Persons = $this->Siteuser_People->findAll();
		if (count($aSiteuser_Persons))
		{
			$oCore_Html_Entity_Span2 = Core_Html_Entity::factory('Span')
				->class('avatar-user avatar-user-siteuser');

			foreach ($aSiteuser_Persons as $oSiteuser_Person)
			{
				$oImagePerson = Core_Html_Entity::factory('Img')
					->src($oSiteuser_Person->getAvatar())
					->width($imageSize)
					->height($imageSize)
					->title($oSiteuser_Person->getFullName());

				if ($oAdmin_Form->Admin_Form_Actions->checkAllowedActionForUser($oAdminUser, 'view'))
				{
					$oCore_Html_Entity_Span2->add(
						Core_Html_Entity::factory('A')
							->href(Admin_Form_Controller::correctBackendPath("/{admin}/siteuser/representative/index.php") . "?hostcms[action]=view&hostcms[checked][0][" . $oSiteuser_Person->id . "]=1&show=person")
							->onclick("$.modalLoad({path: hostcmsBackend + '/siteuser/representative/index.php', action: 'view', operation: 'modal', additionalParams: 'hostcms[checked][0][" . $oSiteuser_Person->id . "]=1&show=person', windowId: 'id_content'}); return false")
							->add($oImagePerson)
					);
				}
				else
				{
					$oCore_Html_Entity_Span2->add($oImagePerson);
				}
			}

			$oCore_Html_Entity_Span2->add(
				Core_Html_Entity::factory('A')
					->href(Admin_Form_Controller::correctBackendPath("/{admin}/siteuser/representative/index.php") . "?hostcms[action]=view&hostcms[checked][0][" . $aSiteuser_Persons[0]->id . "]=1&show=person")
					->onclick("$.modalLoad({path: hostcmsBackend + '/siteuser/representative/index.php', action: 'view', operation: 'modal', additionalParams: 'hostcms[checked][0][" . $aSiteuser_Persons[0]->id . "]=1&show=person', windowId: 'id_content'}); return false")
					->add(
						Core_Html_Entity::factory('Span')
							->value(htmlspecialchars($aSiteuser_Persons[0]->getFullName()))
							->class('darkgray margin-left-5')
					)
			);

			$oCore_Html_Entity_Div->add($oCore_Html_Entity_Span2);
		}

		$oCore_Html_Entity_Div->execute();
	}

	/**
	 * Save object. Use self::update() or self::create()
	 * @return self
	 */
	public function save()
	{
		parent::save();

		if (!is_null($this->icq) || !is_null($this->name) || !is_null($this->surname) || !is_null($this->patronymic) || !is_null($this->company) || !is_null($this->phone) || !is_null($this->fax) || !is_null($this->website) || !is_null($this->postcode) || !is_null($this->country) || !is_null($this->city) || !is_null($this->address))
		{
			$oDefault = NULL;

			$bCompany = $this->company != '';

			if ($bCompany)
			{
				$aSiteuser_Companies = $this->Siteuser_Companies->findAll(FALSE);

				if (isset($aSiteuser_Companies[0]))
				{
					$oSiteuser_Company = $aSiteuser_Companies[0];
				}
				else
				{
					$oSiteuser_Company = Core_Entity::factory('Siteuser_Company');
					$oSiteuser_Company->siteuser_id = $this->id;
				}

				$oSiteuser_Company->name = $this->company;
				$oSiteuser_Company->save();

				$oDefault = $oSiteuser_Company;
			}

			$bPerson = $this->name != '' || $this->surname != '' || $this->patronymic != '';

			if ($bPerson)
			{
				$aSiteuser_People = $this->Siteuser_People->findAll(FALSE);

				if (isset($aSiteuser_People[0]))
				{
					$oSiteuser_Person = $aSiteuser_People[0];
				}
				else
				{
					$oSiteuser_Person = Core_Entity::factory('Siteuser_Person');
					$oSiteuser_Person->siteuser_id = $this->id;
				}

				$oSiteuser_Person->name = $this->name;
				$oSiteuser_Person->surname = $this->surname;
				$oSiteuser_Person->patronymic = $this->patronymic;
				$oSiteuser_Person->save();

				!$bCompany && $oDefault = $oSiteuser_Person;
			}

			if (!is_null($oDefault))
			{
				/*if ($bCompany)
				{
					// Адрес

					$oDirectory_Address_Type = Core_Entity::factory('Directory_Address_Type')->getByName('Почтовый');
					if (is_null($oDirectory_Address_Type))
					{
						$oDirectory_Address_Type = Core_Entity::factory('Directory_Address_Type');
						$oDirectory_Address_Type->name = Core::_('Directory_Address_Type.default_name');
						$oDirectory_Address_Type->save();
					}

					$aDirectory_Addresses = $oSiteuser_Company->Directory_Addresses->findAll(FALSE);
					if (isset($aDirectory_Addresses[0]))
					{
						$oDirectory_Address = $aDirectory_Addresses[0];
					}
					else
					{
						$oDirectory_Address = Core_Entity::factory('Directory_Address');
						$oDirectory_Address->directory_address_type_id = $oDirectory_Address_Type->id;
						$oDirectory_Address->public = 0;
					}

					$oDirectory_Address->value = strval($this->address);
					$oDirectory_Address->country = strval($this->country);
					$oDirectory_Address->city = strval($this->city);
					$oDirectory_Address->postcode = intval($this->postcode);
					$oDirectory_Address->save();

					!isset($aDirectory_Addresses[0]) && $oSiteuser_Company->add($oDirectory_Address);
				}

				if ($bPerson)
				{
					$oSiteuser_Person->postcode = $this->postcode;
					$oSiteuser_Person->country = $this->country;
					$oSiteuser_Person->city = $this->city;
					$oSiteuser_Person->address = $this->address;
					$oSiteuser_Person->save();
				}*/

				$oDirectory_Address_Type = Core_Entity::factory('Directory_Address_Type')->getByName('Почтовый');
				if (is_null($oDirectory_Address_Type))
				{
					$oDirectory_Address_Type = Core_Entity::factory('Directory_Address_Type');
					$oDirectory_Address_Type->name = Core::_('Directory_Address_Type.default_name');
					$oDirectory_Address_Type->save();
				}

				$aDirectory_Addresses = $oDefault->Directory_Addresses->findAll(FALSE);
				if (isset($aDirectory_Addresses[0]))
				{
					$oDirectory_Address = $aDirectory_Addresses[0];
				}
				else
				{
					$oDirectory_Address = Core_Entity::factory('Directory_Address');
					$oDirectory_Address->directory_address_type_id = $oDirectory_Address_Type->id;
					$oDirectory_Address->public = 0;
				}

				$oDirectory_Address->value = strval($this->address);
				$oDirectory_Address->country = strval($this->country);
				$oDirectory_Address->city = strval($this->city);
				$oDirectory_Address->postcode = intval($this->postcode);
				$oDirectory_Address->save();

				!isset($aDirectory_Addresses[0]) && $oDefault->add($oDirectory_Address);

				// Телефон
				$oDirectory_Phone_Type = Core_Entity::factory('Directory_Phone_Type')->getByName('Рабочий');
				if (is_null($oDirectory_Phone_Type))
				{
					$oDirectory_Phone_Type = Core_Entity::factory('Directory_Phone_Type');
					$oDirectory_Phone_Type->name = Core::_('Directory_Phone_Type.default_name');
					$oDirectory_Phone_Type->save();
				}

				if (!is_null($this->phone))
				{
					$aDirectory_Phones = $oDefault->Directory_Phones->findAll(FALSE);
					if (isset($aDirectory_Phones[0]))
					{
						$oDirectory_Phone = $aDirectory_Phones[0];
					}
					else
					{
						$oDirectory_Phone = Core_Entity::factory('Directory_Phone');
						$oDirectory_Phone->directory_phone_type_id = $oDirectory_Phone_Type->id;
						$oDirectory_Phone->public = 0;
					}

					$oDirectory_Phone->value = strval($this->phone);
					$oDirectory_Phone->save();

					!isset($aDirectory_Phones[0]) && $oDefault->add($oDirectory_Phone);

					//$bPerson && $oSiteuser_Person->add($oDirectory_Phone);
				}

				// Факс
				$oDirectory_Fax_Type = Core_Entity::factory('Directory_Phone_Type')->getByName('Факс');
				if (is_null($oDirectory_Fax_Type))
				{
					$oDirectory_Fax_Type = Core_Entity::factory('Directory_Phone_Type');
					$oDirectory_Fax_Type->name = Core::_('Directory_Phone_Type.fax');
					$oDirectory_Fax_Type->save();
				}

				if (!is_null($this->fax))
				{
					//$aDirectory_Faxes = $oDefault->Directory_Phones->findAll(FALSE);
					$aDirectory_Faxes = $oDefault->Directory_Phones->getAllBydirectory_phone_type_id($oDirectory_Fax_Type->id, FALSE);

					if (isset($aDirectory_Faxes[0]))
					{
						$oDirectory_Fax = $aDirectory_Faxes[0];
					}
					else
					{
						$oDirectory_Fax = Core_Entity::factory('Directory_Phone');
						$oDirectory_Fax->directory_phone_type_id = $oDirectory_Fax_Type->id;
						$oDirectory_Fax->public = 0;

					}

					$oDirectory_Fax->value = strval($this->fax);
					$oDirectory_Fax->save();

					!isset($aDirectory_Faxes[0]) && $oDefault->add($oDirectory_Fax);

					// $bPerson && $oSiteuser_Person->add($oDirectory_Fax);
				}

				// E-mail
				$oDirectory_Email_Type = Core_Entity::factory('Directory_Email_Type')->getByName('Основной');
				if (is_null($oDirectory_Email_Type))
				{
					$oDirectory_Email_Type = Core_Entity::factory('Directory_Email_Type');
					$oDirectory_Email_Type->name = Core::_('Directory_Email_Type.default_name');
					$oDirectory_Email_Type->save();
				}

				if (!is_null($this->email))
				{
					$aDirectory_Emails = $oDefault->Directory_Emails->findAll(FALSE);
					if (isset($aDirectory_Emails[0]))
					{
						$oDirectory_Email = $aDirectory_Emails[0];
					}
					else
					{
						$oDirectory_Email = Core_Entity::factory('Directory_Email');
						$oDirectory_Email->directory_email_type_id = $oDirectory_Email_Type->id;
						$oDirectory_Email->public = 0;
					}

					$oDirectory_Email->value = strval($this->email);
					$oDirectory_Email->save();

					!isset($aDirectory_Emails[0]) && $oDefault->add($oDirectory_Email);

					// $bPerson && $oSiteuser_Person->add($oDirectory_Email);
				}

				// Сайт
				if (!is_null($this->website))
				{
					$aDirectory_Websites = $oDefault->Directory_Websites->findAll(FALSE);
					if (isset($aDirectory_Websites[0]))
					{
						$oDirectory_Website = $aDirectory_Websites[0];
					}
					else
					{
						$oDirectory_Website = Core_Entity::factory('Directory_Website');
						$oDirectory_Website->public = 0;
					}

					$oDirectory_Website->value = strval($this->website);
					$oDirectory_Website->save();

					!isset($aDirectory_Websites[0]) && $oDefault->add($oDirectory_Website);

					// $bPerson && $oSiteuser_Person->add($oDirectory_Website);
				}
			}

			$this->icq = $this->name = $this->surname = $this->patronymic = $this->company = $this->phone = $this->fax = $this->website = $this->postcode = $this->country = $this->city = $this->address = NULL;
		}

		return $this;
	}

	/**
	 * Update last activity
	 * @return self
	 */
	public function updateLastActivity()
	{
		if (time() - Core_Date::sql2timestamp($this->last_activity) > 5)
		{
			$this->last_activity = Core_Date::timestamp2sql(time());
			$this->save();
		}

		return $this;
	}

	/**
	 * Return number of seconds since last activity
	 * @return int
	 */
	public function getLastActivity()
	{
		return !is_null($this->last_activity)
			? time() - Core_Date::sql2timestamp($this->last_activity)
			: NULL;
	}

	/**
	 * Is user online
	 * @return boolean
	 */
	public function isOnline()
	{
		$lastActivity = $this->getLastActivity();
		return !is_null($lastActivity) && $lastActivity < 60 * 20;
	}

	/**
	 * Merge siteuser with another one
	 * @param Siteuser_Model $oObject siteuser
	 * @return self
	 */
	public function merge(Siteuser_Model $oObject)
	{
		$this->login == ''
			&& $this->login = $oObject->login;

		$this->email == ''
			&& $this->email = $oObject->email;

		$this->siteuser_type_id == 0
			&& $this->siteuser_type_id = $oObject->siteuser_type_id;

		$this->siteuser_status_id == 0
			&& $this->siteuser_status_id = $oObject->siteuser_status_id;

		$this->crm_source_id == 0
			&& $this->crm_source_id = $oObject->crm_source_id;

		// Siteuser Person
		$aSiteuser_People = $oObject->Siteuser_People->findAll(FALSE);
		foreach ($aSiteuser_People as $oSiteuser_Person)
		{
			$oSiteuser_Person_New = Core_Entity::factory('Siteuser_Person');
			$oSiteuser_Person_New->siteuser_id = $this->id;
			$oSiteuser_Person_New->save();

			$oSiteuser_Person_New->merge($oSiteuser_Person);

			$this->add($oSiteuser_Person_New);
		}

		// Siteuser Company
		$aTmpSiteuserCompanies = array();

		$aTmp_Siteuser_Companies = $this->Siteuser_Companies->findAll(FALSE);
		foreach ($aTmp_Siteuser_Companies as $oSiteuser_Company)
		{
			$name = trim($oSiteuser_Company->name);

			$aTmpSiteuserCompanies[$name] = $oSiteuser_Company;
		}

		$aSiteuser_Companies = $oObject->Siteuser_Companies->findAll(FALSE);
		foreach ($aSiteuser_Companies as $oSiteuser_Company)
		{
			$name = trim($oSiteuser_Company->name);

			if (strlen($name) && isset($aTmpSiteuserCompanies[$name]))
			{
				$aTmpSiteuserCompanies[$name]->merge($oSiteuser_Company);
			}
			else
			{
				$this->add(clone $oSiteuser_Company);
			}
		}

		// Main - Additional properties
		$aMainValues = array();
		$aProperty_Values = $this->getPropertyValues();
		foreach ($aProperty_Values as $oProperty_Value)
		{
			$oProperty = $oProperty_Value->Property;
			switch ($oProperty->type)
			{
				case 0: // Int
				case 3: // List
				case 5: // IS
				case 12: // Shop
				case 11: // Float
					$oProperty_Value->value != 0
						&& $aMainValues[$oProperty->id][] = $oProperty_Value->value;
				break;
				case 1: // String
				case 6: // Wysiwyg
				case 4: // Text
				case 10: // Hidden
					$oProperty_Value->value != ''
						&& $aMainValues[$oProperty->id][] = $oProperty_Value->value;
				break;
				case 8: // Date
					$oProperty_Value->value != '0000-00-00'
						&& $aMainValues[$oProperty->id][] = $oProperty_Value->value;
				break;
				case 9: // Datetime
					$oProperty_Value->value != '0000-00-00 00:00:00'
						&& $aMainValues[$oProperty->id][] = $oProperty_Value->value;
				break;
				case 2: // File
					$oProperty_Value->file != ''
						&& $aMainValues[$oProperty->id][] = $oProperty_Value->file;
				break;
			}
		}

		// Additional properties
		$aProperty_Values = $oObject->getPropertyValues();
		foreach ($aProperty_Values as $oProperty_Value)
		{
			$oProperty = $oProperty_Value->Property;

			if ($oProperty->type != 2)
			{
				if ($oProperty->multiple)
				{
					if (!isset($aMainValues[$oProperty->id]) || !in_array($oProperty_Value->value, $aMainValues[$oProperty->id]))
					{
						$clone = clone $oProperty_Value;
						$clone->entity_id = $this->id;
						$clone->save();
					}
				}
				elseif (!isset($aMainValues[$oProperty->id]))
				{
					$clone = clone $oProperty_Value;
					$clone->entity_id = $this->id;
					$clone->save();
				}
			}
			else
			{
				$this->createDir();

				if ($oProperty->multiple)
				{
					if (!isset($aMainValues[$oProperty->id]) || !in_array($oProperty_Value->file, $aMainValues[$oProperty->id]))
					{
						$clone = clone $oProperty_Value;
						$clone->entity_id = $this->id;

						$clone->save();

						// Копируем файлы
						$clone->setDir($this->getDirPath());

						if (Core_File::isFile($oProperty_Value->getLargeFilePath()))
						{
							try
							{
								Core_File::copy($oProperty_Value->getLargeFilePath(), $clone->getLargeFilePath());
							} catch (Exception $e) {}
						}

						if (Core_File::isFile($oProperty_Value->getSmallFilePath()))
						{
							try
							{
								Core_File::copy($oProperty_Value->getSmallFilePath(), $clone->getSmallFilePath());
							} catch (Exception $e) {}
						}
					}
				}
				elseif (!isset($aMainValues[$oProperty->id]))
				{
					$clone = clone $oProperty_Value;
					$clone->entity_id = $this->id;

					$clone->save();

					// Копируем файлы
					$clone->setDir($this->getDirPath());

					if (is_file($oProperty_Value->getLargeFilePath()))
					{
						try
						{
							Core_File::copy($oProperty_Value->getLargeFilePath(), $clone->getLargeFilePath());
						} catch (Exception $e) {}
					}

					if (is_file($oProperty_Value->getSmallFilePath()))
					{
						try
						{
							Core_File::copy($oProperty_Value->getSmallFilePath(), $clone->getSmallFilePath());
						} catch (Exception $e) {}
					}
				}
			}
		}

		$this->save();

		Core_QueryBuilder::update('shop_orders')
			->set('siteuser_id', $this->id)
			->where('siteuser_id', '=', $oObject->id)
			->execute();

		$oObject->markDeleted();

		return $this;
	}

	/**
	 * Get siteuser bonuses
	 * @param Shop_Model $oShop
	 * @return array
	 */
	public function getBonuses(Shop_Model $oShop)
	{
		$aReturn = array(
			'total' => 0,
			'bonuses' => array()
		);

		$aShop_Discountcards = $oShop->Shop_Discountcards->getAllBySiteuser_id($this->id);

		if (isset($aShop_Discountcards[0]) && $aShop_Discountcards[0]->active)
		{
			$oShop_Discountcard = $aShop_Discountcards[0];

			$aShop_Discountcard_Bonuses = $oShop_Discountcard->getBonuses(FALSE);
			foreach ($aShop_Discountcard_Bonuses as $oShop_Discountcard_Bonus)
			{
				$aReturn['total'] += $oShop_Discountcard_Bonus->amount - $oShop_Discountcard_Bonus->written_off;
				$aReturn['bonuses'][] = $oShop_Discountcard_Bonus;
			}
		}

		$aReturn['total'] = Shop_Controller::instance()->round($aReturn['total']);

		return $aReturn;
	}

	static protected $_oldFields = array('name', 'surname', 'patronymic', 'company', 'phone', 'website', 'country', 'city', 'address');

	public function __call($name, $arguments)
	{
		if (in_array($name, self::$_oldFields))
		{
			$this->$name = $arguments[0];
			return $this;
		}

		return parent::__call($name, $arguments);
	}

	public function __isset($property)
	{
		if (in_array($property, self::$_oldFields))
		{
			return TRUE;
		}

		return parent::__isset($property);
	}

	/**
	 * Run when writing data to inaccessible properties
	 * @param string $property property name
	 * @param string $value property value
	 * @return self
	 */
	public function __set($property, $value)
	{
		// Siteuser_Source -> Crm_Source
		if ($property == 'siteuser_source_id')
		{
			$this->crm_source_id = $value;
			return $this;
		}

		return parent::__set($property, $value);
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event siteuser.onBeforeGetRelatedSite
	 * @hostcms-event siteuser.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}