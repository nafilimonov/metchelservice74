<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form_Field_Dir_Model
 *
 * @package HostCMS
 * @subpackage Form
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Form_Field_Dir_Model extends Core_Entity
{
	/**
	 * Backend property
	 * @var int
	 */
	public $img = 0;

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'form_field_dir' => array('foreign_key' => 'parent_id'),
		'form_field' => array(),
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'form_field_dir' => array('foreign_key' => 'parent_id'),
		'form' => array(),
		'user' => array()
	);

	/**
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'form_field_dirs.sorting' => 'ASC',
		'form_field_dirs.name' => 'ASC'
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
	 * Get parent
	 * @return mixed
	 */
	public function getParent()
	{
		return $this->parent_id
			? Core_Entity::factory('Form_Field_Dir', $this->parent_id)
			: NULL;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event form_field_dir.onBeforeRedeclaredDelete
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

		return parent::delete($primaryKey);
	}

	/**
	 * Copy object
	 * @return Core_Entity
	 * @hostcms-event form_field_dir.onAfterRedeclaredCopy
	 */
	public function copy()
	{
		$newObject = parent::copy();

		$aForm_Field_Dirs = $this->Form_Field_Dirs->findAll(FALSE);
		foreach ($aForm_Field_Dirs as $oForm_Field_Dir)
		{
			$newObject->add(
				$oForm_Field_Dir->copy()->form_id($newObject->form_id)
			);
		}

		$aForm_Fields = $this->Form_Fields->findAll(FALSE);
		foreach ($aForm_Fields as $oForm_Field)
		{
			$newObject->add(
				$oForm_Field->copy()->form_id($newObject->form_id)
			);
		}

		Core_Event::notify($this->_modelName . '.onAfterRedeclaredCopy', $newObject, array($this));

		return $newObject;
	}

	/**
	 * Copy object
	 * @param int $iNewFormId new from ID
	 * @return Core_Entity
	 */
	public function copyDir($iNewFormId)
	{
		$newObject = clone $this;
		$newObject->form_id = $iNewFormId;
		$newObject->save();

		$aForm_Field_Dirs = $this->Form_Field_Dirs->findAll(FALSE);
		foreach ($aForm_Field_Dirs as $oForm_Field_Dir)
		{
			$newDir = $oForm_Field_Dir->copyDir($iNewFormId);
			$newDir->parent_id = $newObject->id;
			$newDir->save();
		}

		$aForm_Fields = $this->Form_Fields->findAll(FALSE);
		foreach ($aForm_Fields as $oForm_Field)
		{
			$newObject->add(
				$oForm_Field->copy()->form_id($iNewFormId)
			);
		}

		return $newObject;
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event form_field_dir.onBeforeGetRelatedSite
	 * @hostcms-event form_field_dir.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Form->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}