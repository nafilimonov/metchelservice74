<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form_Field_Model
 *
 * @package HostCMS
 * @subpackage Form
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Form_Field_Model extends Core_Entity
{
	/**
	 * Backend property
	 * @var int
	 */
	public $img = 1;

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'form_fill_field' => array(),
		'form_lead_conformity' => array()
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'form' => array(),
		'list' => array(),
		'user' => array(),
		'form_field_dir' => array()
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'description' => '',
		'default_value' => '',
		'type' => 0,
		'size' => 0,
		'rows' => 0,
		'cols' => 0,
		'checked' => 0,
		'sorting' => 0,
		'obligatory' => 0,
		'active' => 1
	);

	/**
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'form_fields.sorting' => 'ASC',
		'form_fields.caption' => 'ASC'
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
		}
	}

	/**
	 * Get field by name
	 * @param string $name name
	 * @return Form_Field_Model|NULL
	 */
	public function getByName($name)
	{
		$this->queryBuilder()
			->where('name', '=', $name)
			->limit(1);

		$aObjects = $this->findAll();

		return isset($aObjects[0])
			? $aObjects[0]
			: NULL;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event form_field.onBeforeRedeclaredDelete
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
		$this->Form_Lead_Conformities->deleteAll(FALSE);

		return parent::delete($primaryKey);
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function nameBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		if ($this->obligatory)
		{
			Core_Html_Entity::factory('Span')
				->class('fa fa-asterisk darkorange fa-small')
				->execute();
		}
	}

	/**
	 * Change form field status
	 * @return self
	 * @hostcms-event form_field.onBeforeChangeActive
	 * @hostcms-event form_field.onAfterChangeActive
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
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event form_field.onBeforeGetRelatedSite
	 * @hostcms-event form_field.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Form->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function typeBackend()
	{
		$color = Core_Str::createColor($this->type);

		return '<span class="badge badge-round badge-max-width" style="border-color: ' . $color . '; color: ' . Core_Str::hex2darker($color, 0.2) . '; background-color: ' . Core_Str::hex2lighter($color, 0.88) . '">'
			. Core::_('Form_Field.type' . $this->type)
			. '</span>';
	}
}