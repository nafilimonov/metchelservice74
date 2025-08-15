<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form_Model
 *
 * @package HostCMS
 * @subpackage Form
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Form_Model extends Core_Entity
{
	/**
	 * Backend property
	 * @var mixed
	 */
	public $adminFields = 0;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $rollback = 0;

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'form_field' => array(),
		'form_field_dir' => array(),
		'form_fill' => array(),
		'form_lead_conformity' => array(),
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array(),
		'user' => array(),
		'crm_source' => array()
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'use_captcha' => 1,
		'use_antispam' => 1,
		'button_name' => 'Submit'
	);

	/**
	 * List of Shortcodes tags
	 * @var array
	 */
	protected $_shortcodeTags = array(
		'description'
	);

	/**
	 * Has revisions
	 *
	 * @param boolean
	 */
	protected $_hasRevisions = TRUE;

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
			$this->_preloadValues['guid'] = Core_Guid::get();
			$this->_preloadValues['user_id'] = is_null($oUser) ? 0 : $oUser->id;
			$this->_preloadValues['site_id'] = defined('CURRENT_SITE') ? CURRENT_SITE : 0;
			$this->_preloadValues['button_value'] = Core::_('Form.button_value_default');
		}
	}

	/**
	 * Get file href
	 * @return string
	 */
	public function getHref()
	{
		return $this->Site->uploaddir . 'private/forms/form_' . $this->id;
	}

	/**
	 * Get file path
	 * @return string
	 */
	public function getPath()
	{
		return CMS_FOLDER . $this->getHref();
	}

	/**
	 * Create message files directory
	 * @return self
	 */
	public function createDir()
	{
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
	 * Delete message files directory
	 * @return self
	 */
	public function deleteDir()
	{
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
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event form.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Form_Field_Dirs->deleteAll(FALSE);
		$this->Form_Fields->deleteAll(FALSE);
		$this->Form_Fills->deleteAll(FALSE);
		$this->Form_Lead_Conformities->deleteAll(FALSE);

		$this->deleteDir();

		return parent::delete($primaryKey);
	}

	/**
	 * Copy object
	 * @return Core_Entity
	 * @hostcms-event form.onAfterRedeclaredCopy
	 */
	public function copy()
	{
		$newObject = parent::copy();

		$aForm_Field_Dirs = $this->Form_Field_Dirs->getAllByParent_id(0);
		foreach ($aForm_Field_Dirs as $oForm_Field_Dir)
		{
			$oForm_Field_Dir->copyDir($newObject->id);
		}

		$aForm_Fields = $this->Form_Fields->getAllByForm_field_dir_id(0);
		foreach ($aForm_Fields as $oForm_Field)
		{
			$newObject->add(
				$oForm_Field->copy()
			);
		}

		Core_Event::notify($this->_modelName . '.onAfterRedeclaredCopy', $newObject, array($this));

		return $newObject;
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function nameBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$count = $this->Form_Fills->getCount();
		$count && Core_Html_Entity::factory('Span')
			->class('badge badge-hostcms badge-square')
			->value($count)
			->execute();

		if ($this->use_captcha)
		{
			$captchaColor = '#a0d468';

			?><span class="badge badge-round badge-max-width margin-left-5" title="CAPTCHA" style="border-color: <?php echo $captchaColor?>; color: <?php echo Core_Str::hex2darker($captchaColor, 0.2)?>; background-color:<?php echo Core_Str::hex2lighter($captchaColor, 0.88)?>">CAPTCHA</span><?php
		}

		if ($this->use_antispam)
		{
			$antispamColor = '#edc051';

			?><span class="badge badge-round badge-max-width margin-left-5" title="ANTISPAM" style="border-color: <?php echo $antispamColor?>; color: <?php echo Core_Str::hex2darker($antispamColor, 0.2)?>; background-color:<?php echo Core_Str::hex2lighter($antispamColor, 0.88)?>">ANTISPAM</span><?php
		}

		if ($this->csrf)
		{
			$csrfColor = '#57b5e3';

			?><span class="badge badge-round badge-max-width margin-left-5" title="CSRF" style="border-color: <?php echo $csrfColor?>; color: <?php echo Core_Str::hex2darker($csrfColor, 0.2)?>; background-color:<?php echo Core_Str::hex2lighter($csrfColor, 0.88)?>">CSRF</span><?php
		}
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function adminFieldsBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$count = $this->Form_Fields->getCount();
		$count && Core_Html_Entity::factory('Span')
			->class('badge badge-ico badge-azure white')
			->value($count < 100 ? $count : '∞')
			->title($count)
			->execute();
	}

	/**
	 * Get XML for entity and children entities
	 * @return string
	 * @hostcms-event form.onBeforeRedeclaredGetXml
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
	 * @hostcms-event form.onBeforeRedeclaredGetStdObject
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
		$this->addXmlTag('captcha_id', $this->use_captcha ? Core_Captcha::getCaptchaId() : 0);

		return $this;
	}

	/**
	 * Backup revision
	 * @return self
	 */
	public function backupRevision()
	{
		if (Core::moduleIsActive('revision'))
		{
			$aBackup = array(
				'name' => $this->name,
				'email' => $this->email,
				'description' => $this->description,
				'user_id' => $this->user_id
			);

			Revision_Controller::backup($this, $aBackup);
		}

		return $this;
	}

	/**
	 * Rollback Revision
	 * @param int $revision_id Revision ID
	 * @return self
	 */
	public function rollbackRevision($revision_id)
	{
		if (Core::moduleIsActive('revision'))
		{
			$oRevision = Core_Entity::factory('Revision', $revision_id);

			$aBackup = json_decode($oRevision->value, TRUE);

			if (is_array($aBackup))
			{
				$this->name = Core_Array::get($aBackup, 'name');
				$this->email = Core_Array::get($aBackup, 'email');
				$this->description = Core_Array::get($aBackup, 'description');
				$this->user_id = Core_Array::get($aBackup, 'user_id');
				$this->save();
			}
		}

		return $this;
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event form.onBeforeGetRelatedSite
	 * @hostcms-event form.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}