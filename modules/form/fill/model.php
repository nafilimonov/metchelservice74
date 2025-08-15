<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form_Fill_Model
 *
 * @package HostCMS
 * @subpackage Form
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Form_Fill_Model extends Core_Entity
{
	/**
	 * Column consist item's name
	 * @var string
	 */
	protected $_nameColumn = 'datetime';

	/**
	 * Backend property
	 * @var mixed
	 */
	public $img_fill_fields = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $img_print = NULL;

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'form_fill_field' => array()
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'form' => array(),
		'source' => array(),
		'form_status' => array(),
		'user' => array()
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'read' => 0
	);

	/**
	 * Forbidden tags. If list of tags is empty, all tags will show.
	 * @var array
	 */
	protected $_forbiddenTags = array(
		'datetime'
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
			$this->_preloadValues['ip'] = Core::getClientIp();
			$this->_preloadValues['datetime'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function datetimeBackend()
	{
		ob_start();

		$datetime = htmlspecialchars(
			Core_Date::sql2datetime($this->datetime)
		);

		if ($this->read)
		{
			echo $datetime;
		}
		else
		{
			Core_Html_Entity::factory('Strong')
				->value($datetime)
				->execute();
		}

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function form_status_idBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		ob_start();

		$path = $oAdmin_Form_Controller->getPath();

		// Список статусов дел
		$aForm_Statuses = Core_Entity::factory('Form_Status')->findAll();

		$aMasFormStatuses = array(array('value' => Core::_('Admin.none'), 'color' => '#aebec4'));

		foreach ($aForm_Statuses as $oForm_Status)
		{
			$aMasFormStatuses[$oForm_Status->id] = array('value' => $oForm_Status->name, 'color' => $oForm_Status->color);
		}

		$oCore_Html_Entity_Dropdownlist = new Core_Html_Entity_Dropdownlist();

		Core_Html_Entity::factory('Span')
			->class('padding-left-10')
			->add(
				$oCore_Html_Entity_Dropdownlist
					->value($this->form_status_id)
					->options($aMasFormStatuses)
					->data('change-context', 'true')
					->onchange("$.adminLoad({path: '{$path}', additionalParams: 'hostcms[checked][0][{$this->id}]=0&formStatusId=' + $(this).find('li[selected]').prop('id'), action: 'changeStatus', windowId: '{$oAdmin_Form_Controller->getWindowId()}'});")
				)
			->execute();

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function textBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		ob_start();

		$oForm_Fill_Fields = $this->Form_Fill_Fields;
		$oForm_Fill_Fields->queryBuilder()
			->where('form_fill_fields.value', '!=', '')
			->limit(10);

		$aForm_Fill_Fields = $oForm_Fill_Fields->findAll(FALSE);

		?><div class="fill-form-text"><?php
		foreach ($aForm_Fill_Fields as $oForm_Fill_Field)
		{
			?><span class="field-name"><?php echo htmlspecialchars($oForm_Fill_Field->Form_Field->caption)?>:</span> <span><?php echo htmlspecialchars($oForm_Fill_Field->value)?></span> <?php
		}
		?></div><?php

		return ob_get_clean();
	}

	/**
	 * Get attached file href
	 * @return string
	 */
	public function getHref()
	{
		return $this->Form->getHref() . '/' . Core_File::getNestingDirPath($this->id, 3) . '/' . $this->id;
	}

	/**
	 * Get attached file path
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
	 * @hostcms-event form_fill.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Form_Fill_Fields->deleteAll(FALSE);

		$this->source_id && $this->Source->delete();

		$this->deleteDir();

		return parent::delete($primaryKey);
	}

	/**
	 * Get XML for entity and children entities
	 * @return string
	 * @hostcms-event form_fill.onBeforeRedeclaredGetXml
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
	 * @hostcms-event form_fill.onBeforeRedeclaredGetStdObject
	 */
	public function getStdObject($attributePrefix = '_')
	{
		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredGetStdObject', $this);

		// List of Form_Fields
		$this->addEntity(
			$oXmlFormFields = Core::factory('Core_Xml_Entity')->name('form_fields')
		);

		$oXmlFormFields->addEntities(
			$this->Form->Form_Fields->findAll()
		);

		$this->_prepareData();

		return parent::getStdObject($attributePrefix);
	}

	/**
	 * Prepare entity and children entities
	 * @return self
	 */
	protected function _prepareData()
	{
		$this->source_id && $this->addEntity(
			$this->Source->clearEntities()
		);

		$this
			->clearXmlTags()
			->addXmlTag('datetime', Core_Date::sql2datetime($this->datetime));

		$aForm_Fill_Fields = $this->Form_Fill_Fields->findAll(FALSE);
		foreach ($aForm_Fill_Fields as $oForm_Fill_Field)
		{
			$this->addEntity(
				$oForm_Fill_Field->clearEntitiesAfterGetXml(FALSE)
			);
		}

		return $this;
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event form_fill.onBeforeGetRelatedSite
	 * @hostcms-event form_fill.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Form->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}