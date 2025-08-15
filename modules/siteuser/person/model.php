<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Person_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Siteuser_Person_Model extends Core_Entity
{
	/**
	 * Model name
	 * @var mixed
	 */
	protected $_modelName = 'siteuser_person';

	protected $_tableName = 'siteuser_people';

	/**
	 * Column consist item's name
	 * @var string
	 */
	protected $_nameColumn = 'surname';

	/**
	 * Backend property
	 * @var mixed
	 */
	public $namePersonCompany = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $phone = NULL;

	 /**
	 * Backend property
	 * @var mixed
	 */
	public $email = NULL;

	 /**
	 * Backend property
	 * @var mixed
	 */
	public $postcode = NULL;

	 /**
	 * Backend property
	 * @var mixed
	 */
	public $country = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $city = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $address = NULL;

	/**
	 * List of skipped columns from table
	 * @var array
	 */
	protected $_skipColumns = array(
		'~postcode',
		'~country',
		'~city',
		'~address'
	);

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'siteuser_person_directory_address' => array('foreign_key' => 'siteuser_person_id'),
		'directory_address' => array('through' => 'siteuser_person_directory_address', 'through_table_name' => 'siteuser_people_directory_addresses', 'foreign_key' => 'siteuser_person_id'),

		'siteuser_person_directory_email' => array('foreign_key' => 'siteuser_person_id'),
		'directory_email' => array('through' => 'siteuser_person_directory_email', 'through_table_name' => 'siteuser_people_directory_emails', 'foreign_key' => 'siteuser_person_id'),

		'siteuser_person_directory_phone' => array('foreign_key' => 'siteuser_person_id'),
		'directory_phone' => array('through' => 'siteuser_person_directory_phone', 'through_table_name' => 'siteuser_people_directory_phones', 'foreign_key' =>
		'siteuser_person_id'),

		'siteuser_person_directory_messenger' => array('foreign_key' => 'siteuser_person_id'),
		'directory_messenger' => array('through' => 'siteuser_person_directory_messenger', 'through_table_name' => 'siteuser_people_directory_messengers', 'foreign_key' => 'siteuser_person_id'),

		'siteuser_person_directory_social' => array('foreign_key' => 'siteuser_person_id'),
		'directory_social' => array('through' => 'siteuser_person_directory_social', 'through_table_name' => 'siteuser_people_directory_socials', 'foreign_key' => 'siteuser_person_id'),

		'siteuser_person_directory_website' => array('foreign_key' => 'siteuser_person_id'),
		'directory_website' => array('through' => 'siteuser_person_directory_website', 'through_table_name' => 'siteuser_people_directory_websites', 'foreign_key' => 'siteuser_person_id'),

		'event_siteuser' => array(),
		'deal' => array()
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'siteuser' => array(),
		'user' => array(),
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
			$this->_preloadValues['sex'] = 2;
		}
	}

	/**
	 * Save object. Use self::update() or self::create()
	 * @return self
	 */
	public function save()
	{
		parent::save();

		if (!is_null($this->postcode) || !is_null($this->country) || !is_null($this->city) || !is_null($this->address))
		{
			if (!isset(self::$_columnCache[$this->_modelName]['postcode']))
			{
				$oDirectory_Address_Type = Core_Entity::factory('Directory_Address_Type')->getByName('Почтовый');
				if (is_null($oDirectory_Address_Type))
				{
					$oDirectory_Address_Type = Core_Entity::factory('Directory_Address_Type');
					$oDirectory_Address_Type->name = Core::_('Directory_Address_Type.default_name');
					$oDirectory_Address_Type->save();
				}

				$aDirectory_Addresses = $this->Directory_Addresses->findAll(FALSE);
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
				$oDirectory_Address->postcode = strval($this->postcode);
				$oDirectory_Address->save();

				if (!isset($aDirectory_Addresses[0]))
				{
					//$this->add($oDirectory_Address);
					$oSiteuser_Person_Directory_Address = Core_Entity::factory('Siteuser_Person_Directory_Address');
					$oSiteuser_Person_Directory_Address->siteuser_person_id = $this->id;
					$oSiteuser_Person_Directory_Address->directory_address_id = $oDirectory_Address->id;
					$oSiteuser_Person_Directory_Address->save();
				}
			}
		}

		return $this;
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function typeBackend()
	{
		return 	'<span class="representative-type bg-azure"><i class="fa fa-user white"></i></span>';
	}

	/**
	 * Get siteuser-person href
	 * @return string
	 */
	public function getHref()
	{
		return $this->Siteuser->getDirHref() . "person_" . intval($this->id) . '/';
	}

	/**
	 * Get siteuser-person path include CMS_FOLDER
	 * @return string
	 */
	public function getPath()
	{
		return CMS_FOLDER . $this->getHref();
	}

	/**
	 * Get image file path
	 * @return string|NULL
	 */
	public function getImageFilePath()
	{
		return $this->image != ''
			? $this->getPath() . $this->image
			: NULL;
	}

	/**
	 * Get image href or default user icon
	 * @return string
	 */
	public function getImageHref()
	{
		return $this->image
			? $this->getImageFileHref()
			: '/modules/skin/bootstrap/img/default_user.png';
	}

	/**
	 * Get person avatar
	 * @return string
	 */
	public function getAvatar()
	{
		return strlen($this->image)
			? $this->getImageHref()
			: Admin_Form_Controller::correctBackendPath("/{admin}/siteuser/index.php?loadPersonAvatar={$this->id}");
	}

	/**
	 * Get image href
	 * @return string
	 */
	public function getImageFileHref()
	{
		return '/' . $this->getHref() . $this->image;
	}

	/**
	 * Create files directory
	 * @return self
	 */
	public function createDir()
	{
		clearstatcache();

		if (!Core_File::isDir($this->getPath()))
		{
			try
			{
				Core_File::mkdir($this->getPath(), CHMOD, TRUE);
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Delete image file
	 * @return self
	 * @hostcms-event siteuser_person.onAfterDeleteImageFile
	 */
	public function deleteImageFile()
	{
		try
		{
			$this->image != ''
				&& Core_File::isFile($this->getImageFilePath())
				&& Core_File::delete($this->getImageFilePath());

		} catch (Exception $e) {}

		Core_Event::notify($this->_modelName . '.onAfterDeleteImageFile', $this);

		$this->image = '';
		$this->save();

		return $this;
	}

	/**
	 * Delete person directory
	 * @return self
	 */
	public function deleteDir()
	{
		$this->deleteImageFile();

		if (Core_File::isDir($this->getPath()))
		{
			try
			{
				Core_File::deleteDir($this->getPath());
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Get full name of user
	 *
	 * @return string
	 */
	public function getFullName()
	{
		$aPartsFullName = array();

		!empty($this->surname) && $aPartsFullName[] = $this->surname;
		!empty($this->name) && $aPartsFullName[] = $this->name;
		!empty($this->patronymic) && $aPartsFullName[] = $this->patronymic;

		return implode(' ', $aPartsFullName);
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function phoneBackend()
	{
		$aDirectoryPhones = $this->Directory_Phones->findAll();

		$sResult = '';

		if (count($aDirectoryPhones))
		{
			$sResult = '<div class="row">';

			foreach ($aDirectoryPhones as $oDirectoryPhone)
			{
				$sResult .= ' <div class="col-xs-12">
				<span class="semi-bold">' . htmlspecialchars($oDirectoryPhone->value) . '</span>
				<br />
				<small class="gray">' . htmlspecialchars($oDirectoryPhone->Directory_Phone_Type->name) . '</small>
				</div>';
			}

			$sResult .= '</div>';
		}

		return $sResult;
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function emailBackend()
	{
		$aDirectoryEmails = $this->Directory_Emails->findAll();

		$sResult = '';

		if (count($aDirectoryEmails))
		{
			foreach ($aDirectoryEmails as $oDirectoryEmail)
			{
				$sResult .= '<div class="row">
					<div class="col-xs-12">
						<a href="mailto:' . htmlspecialchars($oDirectoryEmail->value) . '">' . htmlspecialchars($oDirectoryEmail->value) . '</a>
					</div>
					<div class="col-xs-12">
						<small class="gray">' . htmlspecialchars($oDirectoryEmail->Directory_Email_Type->name) . '</small>
					</div>
				</div>';
			}
		}

		return $sResult;
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function imgBackend()
	{
		return '<img src="' . $this->getAvatar() . '"/>';
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function contractBackend()
	{
		return '';
	}

	/**
	 * Copy object
	 * @return Core_Entity
	 * @hostcms-event siteuser_person.onAfterRedeclaredCopy
	 */
	public function copy()
	{
		$newObject = parent::copy();

		if ($this->image != '' && Core_File::isFile($this->getImageFilePath()))
		{
			try
			{
				$newObject->createDir();
				Core_File::copy($this->getImageFilePath(), $newObject->getImageFilePath());
			}
			catch (Exception $e) {}
		}

		$newObject->name = $this->name;
		// $newObject->surname = $this->surname;
		$newObject->patronymic = $this->patronymic;
		$newObject->post = $this->post;
		$newObject->birthday = $this->birthday;
		$newObject->sex = $this->sex;

		/*$newObject->postcode = $this->postcode;
		$newObject->country = $this->country;
		$newObject->city = $this->city;
		$newObject->address = $this->address;*/

		$aDirectory_Addresses = $this->Directory_Addresses->findAll();
		foreach ($aDirectory_Addresses as $oDirectory_Address)
		{
			$newObject->add(clone $oDirectory_Address);
		}

		$aDirectory_Phones = $this->Directory_Phones->findAll();
		foreach ($aDirectory_Phones as $oDirectory_Phone)
		{
			$newObject->add(clone $oDirectory_Phone);
		}

		$aDirectory_Emails = $this->Directory_Emails->findAll();
		foreach ($aDirectory_Emails as $oDirectory_Email)
		{
			$newObject->add(clone $oDirectory_Email);
		}

		$aDirectory_Socials = $this->Directory_Socials->findAll();
		foreach ($aDirectory_Socials as $oDirectory_Social)
		{
			$newObject->add(clone $oDirectory_Social);
		}

		$aDirectory_Messengers = $this->Directory_Messengers->findAll();
		foreach ($aDirectory_Messengers as $oDirectory_Messenger)
		{
			$newObject->add(clone $oDirectory_Messenger);
		}

		$aDirectory_Websites = $this->Directory_Websites->findAll();
		foreach ($aDirectory_Websites as $oDirectory_Website)
		{
			$newObject->add(clone $oDirectory_Website);
		}

		Core_Event::notify($this->_modelName . '.onAfterRedeclaredCopy', $newObject, array($this));

		return $newObject;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return Core_Entity
	 * @hostcms-event siteuser_person.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Directory_Addresses->deleteAll(FALSE);
		$this->Directory_Emails->deleteAll(FALSE);
		$this->Directory_Messengers->deleteAll(FALSE);
		$this->Directory_Phones->deleteAll(FALSE);
		$this->Directory_Socials->deleteAll(FALSE);
		$this->Directory_Websites->deleteAll(FALSE);

		if (Core::moduleIsActive('event'))
		{
			$this->Event_Siteusers->deleteAll(FALSE);
		}

		// Удаляем директорию
		$this->deleteDir();

		return parent::delete($primaryKey);
	}

	/**
	 * Get user age
	 * @return string
	 */
	public function getAge()
	{
		return floor((time() - strtotime($this->birthday)) / 31556926);
	}

	/**
	 * Get user sex
	 * @return string
	 */
	public function getSex()
	{
		return $this->sex
			? '<i class="fa fa-venus pink"></i>'
			: '<i class="fa fa-mars sky"></i>';
	}

	/**
	 * Get XML for entity and children entities
	 * @return string
	 * @hostcms-event siteuser_person.onBeforeRedeclaredGetXml
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
	 * @hostcms-event siteuser_person.onBeforeRedeclaredGetStdObject
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
			->addXmlTag('dir', '/' . $this->getHref());

		return $this;
	}

	public function getByNameAndSurname($name, $surname, $bCache = TRUE)
	{
		$this->queryBuilder()
			->where('siteuser_people.name', '=', $name)
			->where('siteuser_people.surname', '=', $surname)
			->limit(1);

		$aSiteuser_People = $this->findAll($bCache);

		return isset($aSiteuser_People[0])
			? $aSiteuser_People[0]
			: NULL;
	}

	/**
	 * Merge person with another one
	 * @param Siteuser_Person_Model $oObject siteuser person
	 * @return self
	 */
	public function merge(Siteuser_Person_Model $oObject)
	{
		$this->name == ''
			&& $this->name = $oObject->name;

		$this->surname == ''
			&& $this->surname = $oObject->surname;

		$this->patronymic == ''
			&& $this->patronymic = $oObject->patronymic;

		$this->birthday == '0000-00-00'
			&& $this->birthday = $oObject->birthday;

		$this->post == ''
			&& $this->post = $oObject->post;

		// Image
		if ($this->image == '' && $oObject->image != '')
		{
			try
			{
				Core_File::copy($oObject->getImageFilePath(), $this->getPath() . $oObject->image);
				$this->image = $oObject->image;
			} catch (Exception $e) {
				Core_Message::show($e->getMessage(), 'error');
			}
		}

		// Directory_Addresses
		$aTmpAddresses = array();

		$aTmp_Directory_Addresses = $this->Directory_Addresses->findAll(FALSE);
		foreach ($aTmp_Directory_Addresses as $oDirectory_Address)
		{
			$aTmpAddresses[] = trim($oDirectory_Address->value);
		}

		$aDirectory_Addresses = $oObject->Directory_Addresses->findAll(FALSE);
		foreach ($aDirectory_Addresses as $oDirectory_Address)
		{
			strlen(trim($oDirectory_Address->value)) && !in_array($oDirectory_Address->value, $aTmpAddresses)
				&& $this->add(clone $oDirectory_Address);
		}

		// Directory_Phones
		$aTmpPhones = array();

		$aTmp_Directory_Phones = $this->Directory_Phones->findAll(FALSE);
		foreach ($aTmp_Directory_Phones as $oDirectory_Phone)
		{
			$aTmpPhones[] = Core_Str::sanitizePhoneNumber($oDirectory_Phone->value);
		}

		$aDirectory_Phones = $oObject->Directory_Phones->findAll(FALSE);
		foreach ($aDirectory_Phones as $oDirectory_Phone)
		{
			!in_array(Core_Str::sanitizePhoneNumber($oDirectory_Phone->value), $aTmpPhones)
				&& $this->add(clone $oDirectory_Phone);
		}

		// Directory_Emails
		$aTmpEmails = array();

		$aTmp_Directory_Emails = $this->Directory_Emails->findAll(FALSE);
		foreach ($aTmp_Directory_Emails as $oDirectory_Email)
		{
			$aTmpEmails[] = trim($oDirectory_Email->value);
		}

		$aDirectory_Emails = $oObject->Directory_Emails->findAll(FALSE);
		foreach ($aDirectory_Emails as $oDirectory_Email)
		{
			strlen(trim($oDirectory_Email->value)) && !in_array($oDirectory_Email->value, $aTmpEmails)
				&& $this->add(clone $oDirectory_Email);
		}

		// Directory_Socials
		$aTmpSocials = array();

		$aTmp_Directory_Socials = $this->Directory_Socials->findAll(FALSE);
		foreach ($aTmp_Directory_Socials as $oDirectory_Social)
		{
			$aTmpSocials[] = trim($oDirectory_Social->value);
		}

		$aDirectory_Socials = $oObject->Directory_Socials->findAll(FALSE);
		foreach ($aDirectory_Socials as $oDirectory_Social)
		{
			strlen(trim($oDirectory_Social->value)) && !in_array($oDirectory_Social->value, $aTmpSocials)
				&& $this->add(clone $oDirectory_Social);
		}

		// Directory_Messengers
		$aTmpMessengers = array();

		$aTmp_Directory_Messengers = $this->Directory_Messengers->findAll(FALSE);
		foreach ($aTmp_Directory_Messengers as $oDirectory_Messenger)
		{
			$aTmpMessengers[] = trim($oDirectory_Messenger->value);
		}

		$aDirectory_Messengers = $oObject->Directory_Messengers->findAll(FALSE);
		foreach ($aDirectory_Messengers as $oDirectory_Messenger)
		{
			strlen(trim($oDirectory_Messenger->value)) && !in_array($oDirectory_Messenger->value, $aTmpMessengers)
				&& $this->add(clone $oDirectory_Messenger);
		}

		// Directory_Websites
		$aTmpWebsites = array();

		$aTmp_Directory_Websites = $this->Directory_Websites->findAll(FALSE);
		foreach ($aTmp_Directory_Websites as $oDirectory_Website)
		{
			$aTmpWebsites[] = trim($oDirectory_Website->value);
		}

		$aDirectory_Websites = $oObject->Directory_Websites->findAll(FALSE);
		foreach ($aDirectory_Websites as $oDirectory_Website)
		{
			strlen(trim($oDirectory_Website->value)) && !in_array($oDirectory_Website->value, $aTmpWebsites)
				&& $this->add(clone $oDirectory_Website);
		}

		$this->save();

		$oObject->markDeleted();

		return $this;
	}

	/**
	 * Return html profile block for popup
	 */
	public function getProfilePopupBlock()
	{
		ob_start();
		?>
		<div class="siteuser-popup-wrapper">
			<img class="avatar" src="<?php echo $this->getAvatar()?>"/>
			<div class="semi-bold"><?php echo htmlspecialchars($this->getFullName())?></div>
		</div>
		<?php
		$aDirectory_Phones = $this->Directory_Phones->findAll(FALSE);

		if (count($aDirectory_Phones))
		{
			?><div><?php
			foreach ($aDirectory_Phones as $oDirectory_Phone)
			{
				if (strlen(Core_Str::sanitizePhoneNumber(trim($oDirectory_Phone->value))))
				{
					$oDirectory_Phone_Type = Core_Entity::factory('Directory_Phone_Type')->find($oDirectory_Phone->directory_phone_type_id);

					$sPhoneType = !is_null($oDirectory_Phone_Type->id)
						? htmlspecialchars($oDirectory_Phone_Type->name) . ": "
						: '';

					?><div><span class="popup-type"><i class="fa fa-phone fa-fw palegreen"></i> <?php echo $sPhoneType?></span><span><?php echo htmlspecialchars($oDirectory_Phone->value)?></span></div><?php
				}
			}
			?></div><?php
		}

		$aDirectory_Emails = $this->Directory_Emails->findAll(FALSE);

		if (count($aDirectory_Emails))
		{
			?><div class="margin-top-5"><?php
			foreach ($aDirectory_Emails as $oDirectory_Email)
			{
				if (strlen(trim($oDirectory_Email->value)))
				{
					$oDirectory_Email_Type = Core_Entity::factory('Directory_Email_Type')->find($oDirectory_Email->directory_email_type_id);

					$sEmailType = !is_null($oDirectory_Email_Type->id)
						? htmlspecialchars($oDirectory_Email_Type->name) . ": "
						: '';

						?><div><span class="popup-type"><i class="fa fa-envelope-o fa-fw warning"></i> <?php echo $sEmailType?></span><span><a href="mailto:<?php echo htmlspecialchars($oDirectory_Email->value)?>"><?php echo htmlspecialchars($oDirectory_Email->value)?></a></span></div><?php
				}
			}
		}

		return ob_get_clean();
	}

	/**
	 * Return html profile block
	 * @param string $class class for block
	 * @return string
	 */
	public function getProfileBlock($class = '')
	{
		$oUser = Core_Auth::getCurrentUser();
		$sFullName = $this->getFullName();

		$oAdmin_Form = Core_Entity::factory('Admin_Form', 230);

		$imgLink = $oAdmin_Form->Admin_Form_Actions->checkAllowedActionForUser($oUser, 'view')
			? '<a href="' . Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/representative/index.php') . '?hostcms[action]=view&hostcms[checked][1][' . $this->id . ']=1&siteuser_id=' . $this->siteuser_id . '" onclick="$.modalLoad({path: hostcmsBackend + \'/siteuser/representative/index.php\', action: \'view\', operation: \'modal\', additionalParams: \'hostcms[checked][1][' . $this->id . ']=1&siteuser_id=' . $this->siteuser_id . '\', windowId: \'id_content\', width: \'90%\'}); return false">' . htmlspecialchars($sFullName) . '</a>'
			: htmlspecialchars($sFullName);

		$nameLink = $oAdmin_Form->Admin_Form_Actions->checkAllowedActionForUser($oUser, 'edit')
			? '<a href="' . Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/representative/index.php') . '?hostcms[action]=edit&hostcms[checked][1][' . $this->id . ']=1&siteuser_id=' . $this->siteuser_id . '" onclick="$.modalLoad({path: hostcmsBackend + \'/siteuser/representative/index.php\', action: \'edit\', operation: \'modal\', additionalParams: \'hostcms[checked][1][' . $this->id . ']=1&siteuser_id=' . $this->siteuser_id . '\', view: \'list\', windowId: \'\', width: \'90%\'}); return false">
				<i class="fa fa-user fa-fw"></i>
				<i class="fa fa-pencil"></i>
			</a>'
			: '<i class="fa fa-user fa-fw"></i>';

		return '<li class="ticket-item ' . $class . '" data-popover="hover" data-person-id="' . $this->id . '">
			<div class="row">
				<div class="ticket-user ticket-siteuser col-xs-12"><img class="siteuser-avatar lazy" data-src="' . $this->getAvatar() .'" />' .
					'<div class="user-name"><div>' . $imgLink . '</div></div>' . '
				</div>
				<div class="ticket-state bg-azure">' . $nameLink . '</div>
			</div>
		</li>';
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event siteuser_person.onBeforeGetRelatedSite
	 * @hostcms-event siteuser_person.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Siteuser->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}